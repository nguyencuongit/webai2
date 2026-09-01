<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMotionRequest;
use App\Jobs\SubmitMotionJob;
use App\Models\MotionJob;
use App\Models\RoboNeoAccount;
use App\Services\RoboNeo\Contracts\RoboNeoGateway;
use App\Services\RoboNeo\MotionVideoTrimmer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class MotionController extends Controller
{
    public function index(): View
    {
        $jobs = MotionJob::query()->with('roboneoAccount')->latest()->limit(12)->get();
        $stats = [
            'total' => MotionJob::query()->count(),
            'active' => MotionJob::query()->whereIn('status', [
                MotionJob::STATUS_UPLOADING,
                MotionJob::STATUS_SUBMITTED,
                MotionJob::STATUS_PROCESSING,
            ])->count(),
            'completed' => MotionJob::query()->where('status', MotionJob::STATUS_COMPLETED)->count(),
        ];
        $accounts = RoboNeoAccount::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();

        return view('motion.index', compact('jobs', 'stats', 'accounts'));
    }

    public function store(
        StoreMotionRequest $request,
        RoboNeoGateway $gateway,
        MotionVideoTrimmer $videoTrimmer,
    ): RedirectResponse {
        $id = (string) Str::ulid();
        $image = $request->file('image');
        $video = $request->file('video');
        $directory = "motion/{$id}";
        $imagePath = $image->storeAs($directory, 'character.'.$image->extension(), 'local');
        $videoPath = $video->storeAs($directory, 'motion.'.$video->extension(), 'local');

        $job = MotionJob::query()->create([
            'id' => $id,
            'roboneo_account_id' => $request->input('roboneo_account_id'),
            'status' => MotionJob::STATUS_UPLOADING,
            'prompt' => $request->string('prompt')->trim()->toString(),
            'quality' => 'std',
            'duration_seconds' => $request->integer('duration_seconds'),
            'image_path' => $imagePath,
            'image_original_name' => $image->getClientOriginalName(),
            'video_path' => $videoPath,
            'video_original_name' => $video->getClientOriginalName(),
            'dry_run' => ! config('roboneo.live_enabled'),
        ]);

        try {
            $preparedVideoPath = $videoTrimmer->trim($job->video_path);

            if ($preparedVideoPath !== $job->video_path) {
                $job->update([
                    'video_path' => $preparedVideoPath,
                    'video_original_name' => pathinfo($job->video_original_name, PATHINFO_FILENAME).'.mp4',
                ]);
            }

            $quote = $gateway->quote($job);
            $job->update([
                ...$quote,
                'status' => MotionJob::STATUS_AWAITING_CONFIRMATION,
                'quoted_at' => now(),
                'error_code' => null,
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $job->update([
                'status' => MotionJob::STATUS_FAILED,
                'error_code' => method_exists($exception, 'getCode') ? (string) $exception->getCode() : null,
                'error_message' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('motion.show', $job);
    }

    public function show(MotionJob $motionJob): View
    {
        $motionJob->load('roboneoAccount');

        return view('motion.show', ['job' => $motionJob]);
    }

    public function confirm(MotionJob $motionJob): RedirectResponse
    {
        $confirmed = DB::transaction(function () use ($motionJob): bool {
            $job = MotionJob::query()->lockForUpdate()->findOrFail($motionJob->id);

            if (! $job->canConfirm()) {
                return false;
            }

            if ($job->quoted_cost > config('roboneo.motion.max_quote_cost')) {
                return false;
            }

            $job->update([
                'status' => MotionJob::STATUS_SUBMITTED,
                'confirmed_at' => now(),
            ]);

            return true;
        });

        if (! $confirmed) {
            return back()->withErrors([
                'confirm' => 'Job không thể gửi hoặc chi phí đã vượt giới hạn cấu hình.',
            ]);
        }

        SubmitMotionJob::dispatch($motionJob->id);

        return redirect()->route('motion.show', $motionJob);
    }

    public function cancel(MotionJob $motionJob): RedirectResponse
    {
        if ($motionJob->canConfirm()) {
            $motionJob->update(['status' => MotionJob::STATUS_CANCELLED]);
        }

        return redirect()->route('motion.show', $motionJob);
    }

    public function status(MotionJob $motionJob): JsonResponse
    {
        return response()->json([
            'id' => $motionJob->id,
            'status' => $motionJob->status,
            'task_id' => $motionJob->task_id,
            'poll_attempts' => $motionJob->poll_attempts,
            'result_url' => $motionJob->result_url,
            'error_message' => $motionJob->error_message,
            'terminal' => $motionJob->isTerminal(),
            'updated_at' => $motionJob->updated_at?->toIso8601String(),
        ]);
    }

    public function manifest(MotionJob $motionJob): JsonResponse
    {
        return response()->json([
            'id' => $motionJob->id,
            'mode' => $motionJob->dry_run ? 'dry-run' : 'live',
            'status' => $motionJob->status,
            'model' => 'kling-2.6-motion-control',
            'quality' => $motionJob->quality,
            'duration_seconds' => $motionJob->duration_seconds,
            'quoted_cost' => $motionJob->quoted_cost,
            'room_id' => $motionJob->room_id,
            'task_id' => $motionJob->task_id,
            'result_url' => $motionJob->result_url,
            'created_at' => $motionJob->created_at?->toIso8601String(),
            'completed_at' => $motionJob->completed_at?->toIso8601String(),
        ])->withHeaders([
            'Content-Disposition' => "attachment; filename=motion-{$motionJob->id}.json",
        ]);
    }
}
