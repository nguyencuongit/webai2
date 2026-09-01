<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Fronts;

use Botble\AiVideoGenerator\Http\Requests\Fronts\GenerateVideoRequest;
use Botble\AiVideoGenerator\Models\AiGenerationTask;
use Botble\AiVideoGenerator\Services\AiGenerationService;
use Botble\AiVideoGenerator\Services\AiGenerationTaskStatusService;
use Botble\AiVideoGenerator\Services\GeneratedMediaCleanupService;
use Botble\AiVideoGenerator\Services\R2\R2VideoStorageService;
use Botble\AiVideoGenerator\Services\VideoLabDataService;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class VideoLabController extends BaseController
{
    public function index(VideoLabDataService $videoLabDataService)
    {

        return Theme::scope('video', [
            'aiModels' => $videoLabDataService->models(),
        ])->render();
    }

    public function history(Request $request): JsonResponse
    {
        $paginator = AiGenerationTask::query()
            ->where('is_completed', true)
            ->whereNotNull('generated')
            ->where('customer_id', auth('customer')->id())
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->cursorPaginate(12);

        $tasks = $paginator->getCollection()
            ->map(fn (AiGenerationTask $task) => [
                'task_id' => $task->task_id,
                'generated' => array_values(array_filter($task->generated ?? [])),
            ])
            ->filter(fn (array $task) => ! empty($task['generated']))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $tasks,
                'next_cursor' => $paginator->nextCursor()?->encode(),
            ],
        ]);
    }

    public function generate(GenerateVideoRequest $request, AiGenerationService $generationService): JsonResponse
    {
        $data = $request->validated();
        $model = (string) ($data['model'] ?? '');
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in before creating content.',
            ], 401);
        }

        $estimatedCredits = $generationService->estimateCredits($model, $data);

        if ($customer->credits_balance < $estimatedCredits) {
            return response()->json([
                'success' => false,
                'message' => 'Số dư Credit không đủ để tạo video này.',
            ], 422);
        }

        try {
            $response = $generationService->create($model, $data);
            $task = $response['data'] ?? $response;
        } catch (RequestException $exception) {
            Log::warning('Cannot create AI video task from Magnific API.', [
                'model' => $model,
                'status' => $exception->response?->status(),
                'response' => $exception->response?->json(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $exception->response?->json('message') ?: 'Không gửi được yêu cầu tạo video.',
            ], $exception->response?->status() ?: 502);
        } catch (Throwable $exception) {
            Log::error('Cannot create AI video task.', [
                'model' => $model,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không gửi được yêu cầu tạo video.',
            ], 500);
        }

        if (empty($task['task_id'])) {
            Log::warning('AI video API response is missing task_id.', [
                'model' => $model,
                'response' => $response,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'API chưa trả về mã task tạo video.',
            ], 502);
        }

        $customer->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Video request sent.',
            'data' => [
                'task_id' => $task['task_id'] ?? null,
                'status' => $task['status'] ?? null,
                'is_completed' => strtoupper((string) ($task['status'] ?? '')) === 'COMPLETED',
                'generated' => $task['generated'] ?? [],
                'credits_balance' => (int) $customer->credits_balance,
            ],
        ]);
    }

    public function status(string $taskId, AiGenerationTaskStatusService $taskStatusService): JsonResponse
    {
        $task = $taskStatusService->find($taskId);

        if (! $task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $task,
        ]);
    }

    public function download(string $taskId, Request $request, R2VideoStorageService $r2VideoStorage)
    {
        $mediaIndex = (int) $request->integer('media', 0);

        if ($mediaIndex < 0) {
            abort(404);
        }

        $task = AiGenerationTask::query()
            ->where('task_id', $taskId)
            ->firstOrFail();

        $media = data_get($task->generated ?? [], $mediaIndex);

        if (is_string($media) && $media !== '') {
            return redirect()->away($media);
        }

        if (! is_array($media)) {
            abort(404);
        }

        $r2Key = $media['r2_key'] ?? null;

        if (is_string($r2Key) && $r2Key !== '') {
            $extension = pathinfo($r2Key, PATHINFO_EXTENSION) ?: 'mp4';
            $filename = sprintf('video-%s.%s', $task->task_id, $extension);

            return redirect()->away($r2VideoStorage->temporaryDownloadUrl($r2Key, $filename));
        }

        if (is_string($media['url'] ?? null) && $media['url'] !== '') {
            return redirect()->away($media['url']);
        }

        abort(404);
    }

    public function destroy(string $taskId, GeneratedMediaCleanupService $mediaCleanupService): JsonResponse
    {
        $customerId = auth('customer')->id();

        if (! $customerId) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lÃ²ng Ä‘Äƒng nháº­p Ä‘á»ƒ xÃ³a video.',
            ], 401);
        }

        $task = AiGenerationTask::query()
            ->where('task_id', $taskId)
            ->where('customer_id', $customerId)
            ->first();

        if (! $task) {
            return response()->json([
                'success' => false,
                'message' => 'KhÃ´ng tÃ¬m tháº¥y video cá»§a báº¡n.',
            ], 404);
        }

        $generated = array_values(array_filter($task->generated ?? []));

        try {
            $mediaCleanupService->delete($generated);

            // Keep the task for administration/auditing, but remove its media reference
            // so it no longer appears in the customer's video library.
            $task->update(['generated' => null]);
        } catch (Throwable $exception) {
            Log::error('Cannot delete AI generated video.', [
                'task_id' => $taskId,
                'customer_id' => $customerId,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'KhÃ´ng thá»ƒ xÃ³a video. Vui lÃ²ng thá»­ láº¡i.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'ÄÃ£ xÃ³a video.',
        ]);
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file'],
            'field' => ['required', 'string', 'in:image,image_end,image_url,video_url,start_image_url,end_image_url'],
        ]);

        $imageFields = ['image', 'image_end', 'image_url', 'start_image_url', 'end_image_url'];

        if (in_array($request->string('field')->toString(), $imageFields, true)) {
            $request->validate([
                'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240', 'dimensions:min_width=300,min_height=300'],
            ], [
                'file.mimes' => 'Ảnh chỉ hỗ trợ định dạng JPG, JPEG, PNG, WEBP hoặc GIF.',
                'file.max' => 'Dung lượng ảnh không được vượt quá 10MB.',
                'file.dimensions' => 'Kích thước ảnh tối thiểu là 300 × 300px.',
            ]);

            $dimensions = getimagesize($request->file('file')->getRealPath());

            if (! $dimensions) {
                throw ValidationException::withMessages([
                    'file' => 'Không thể đọc kích thước ảnh. Vui lòng chọn ảnh hợp lệ.',
                ]);
            }

            $ratio = $dimensions[0] / $dimensions[1];

            if ($ratio < 0.4 || $ratio > 2.5) {
                throw ValidationException::withMessages([
                    'file' => 'Tỷ lệ ảnh phải nằm trong khoảng 1:2.5 đến 2.5:1.',
                ]);
            }
        } else {
            $request->validate([
                'file' => ['required', 'file', 'mimes:mp4,mov,webm,m4v', 'max:51200'],
            ], [
                'file.mimes' => 'Video chỉ hỗ trợ định dạng MP4, MOV, WEBM hoặc M4V.',
            ]);
        }

        $path = $request->file('file')->store('ai-video-generator/media', 'public');

        return response()->json([
            'success' => true,
            'data' => [
                'url' => Storage::disk('public')->url($path),
                'name' => $request->file('file')->getClientOriginalName(),
            ],
        ]);
    }
}
