<?php

namespace App\Services\RoboNeo;

use App\Exceptions\RoboNeoProtocolException;
use App\Models\MotionJob;
use App\Services\RoboNeo\Contracts\RoboNeoGateway;
use Illuminate\Support\Facades\Storage;

class LiveRoboNeoGateway implements RoboNeoGateway
{
    public function __construct(
        private readonly RoboNeoUploader $uploader,
        private readonly MotionGraphBuilder $graphBuilder,
    ) {}

    public function quote(MotionJob $job): array
    {
        $api = $this->client($job);
        $api->initialize();
        $roomId = $api->createRoom();
        $api->initializeCanvas($roomId);

        $image = $this->uploader->upload(
            $api,
            $roomId,
            Storage::disk('local')->path($job->image_path),
            $job->image_original_name,
            'image',
        );
        $video = $this->uploader->upload(
            $api,
            $roomId,
            Storage::disk('local')->path($job->video_path),
            $job->video_original_name,
            'video',
        );
        $built = $this->graphBuilder->build($job->prompt, $image, $video);
        $api->saveCanvas($roomId, $built['graph']);
        $cost = $api->countMotionCost(
            $roomId,
            $built['motion_node_id'],
            $job->duration_seconds,
            $job->prompt,
            $image['url'],
            $video['url'],
        );

        return [
            'room_id' => $roomId,
            'motion_node_id' => $built['motion_node_id'],
            'quoted_cost' => $cost,
            'image_asset' => $image,
            'video_asset' => $video,
            'session_data' => $api->contextSnapshot(),
            'raw_status' => ['phase' => 'quoted'],
        ];
    }

    public function submit(MotionJob $job): array
    {
        if ($job->task_id) {
            return ['task_id' => $job->task_id, 'session_data' => $job->session_data];
        }

        $api = $this->client($job);
        $api->assertLiveCredentials();
        $taskId = $api->executeMotion(
            $job->room_id,
            $job->motion_node_id,
            $job->prompt,
            $job->image_asset['url'],
            $job->video_asset['url'],
        );

        return [
            'task_id' => $taskId,
            'session_data' => $api->contextSnapshot(),
            'raw_status' => ['phase' => 'submitted'],
        ];
    }

    public function poll(MotionJob $job): array
    {
        $api = $this->client($job);
        $data = $api->queryTask($job->task_id, $job->room_id);
        $task = $this->selectTask($data, $job->task_id);
        $status = strtoupper((string) ($task['state'] ?? $task['status'] ?? $data['state'] ?? $data['status'] ?? 'PROCESSING'));

        if (in_array($status, ['CANCEL', 'CANCELLED', 'FAIL', 'FAILED', 'ERROR'], true)) {
            return [
                'state' => 'failed',
                'message' => $this->findScalarByKeys($task, ['error_message', 'message', 'msg'])
                    ?? "RoboNeo task ended with status {$status}.",
                'session_data' => $api->contextSnapshot(),
                'raw' => $data,
            ];
        }

        $outputs = $this->outputCandidates($task, $data);
        $resultUrl = $this->findMp4Url($outputs);
        $coverUrl = $this->findScalarByKeys($outputs, ['cover_url', 'coverUrl', 'thumbnail_url']);

        if ($resultUrl !== null) {
            $api->finalize($job->room_id, $coverUrl);

            return [
                'state' => 'completed',
                'result_url' => $resultUrl,
                'cover_url' => $coverUrl,
                'session_data' => $api->contextSnapshot(),
                'raw' => $data,
            ];
        }

        return [
            'state' => 'processing',
            'session_data' => $api->contextSnapshot(),
            'raw' => $data,
        ];
    }

    private function client(MotionJob $job): RoboNeoApiClient
    {
        $account = $job->roboneoAccount;

        if (! $account) {
            throw new RoboNeoProtocolException(
                'Job chưa được gắn với tài khoản RoboNeo.',
                'missing_roboneo_account',
            );
        }

        if (! $account->is_active) {
            throw new RoboNeoProtocolException(
                'Tài khoản RoboNeo của job đã bị vô hiệu hóa.',
                'inactive_roboneo_account',
            );
        }

        return new RoboNeoApiClient(RoboNeoContext::fromJob($job), $account->access_token);
    }

    private function selectTask(array $data, string $taskId): array
    {
        $tasks = $data['tasks'] ?? [];

        if (isset($tasks[$taskId]) && is_array($tasks[$taskId])) {
            return ['task_id' => $taskId, ...$tasks[$taskId]];
        }

        if (array_is_list($tasks)) {
            foreach ($tasks as $task) {
                if (is_array($task) && (string) ($task['task_id'] ?? '') === $taskId) {
                    return $task;
                }
            }

            return is_array($tasks[0] ?? null) ? $tasks[0] : [];
        }

        return $data;
    }

    private function outputCandidates(array $task, array $data): array
    {
        $candidates = [];
        $outputKeys = [
            'output',
            'outputs',
            'result',
            'results',
            'result_url',
            'result_urls',
            'video_url',
            'video_urls',
        ];

        foreach ($task['steps'] ?? [] as $step) {
            if (! is_array($step)) {
                continue;
            }

            foreach ($outputKeys as $key) {
                if (array_key_exists($key, $step)) {
                    $candidates[] = $step[$key];
                }
            }
        }

        foreach ([$task, $data] as $container) {
            foreach ($outputKeys as $key) {
                if (array_key_exists($key, $container)) {
                    $candidates[] = $container[$key];
                }
            }
        }

        return $candidates;
    }

    private function findMp4Url(mixed $value, int $depth = 0): ?string
    {
        if ($depth > 10) {
            return null;
        }

        if (is_string($value)) {
            if (filter_var($value, FILTER_VALIDATE_URL) && preg_match('/\.mp4(?:\?|$|&)/i', $value)) {
                return $value;
            }

            if (str_starts_with(trim($value), '{') || str_starts_with(trim($value), '[')) {
                $decoded = json_decode($value, true);

                return is_array($decoded) ? $this->findMp4Url($decoded, $depth + 1) : null;
            }

            return null;
        }

        if (is_array($value)) {
            foreach ($value as $child) {
                $found = $this->findMp4Url($child, $depth + 1);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function findScalarByKeys(array $value, array $keys, int $depth = 0): ?string
    {
        if ($depth > 8) {
            return null;
        }

        foreach ($keys as $key) {
            if (isset($value[$key]) && is_scalar($value[$key]) && (string) $value[$key] !== '') {
                return (string) $value[$key];
            }
        }

        foreach ($value as $child) {
            if (is_array($child)) {
                $found = $this->findScalarByKeys($child, $keys, $depth + 1);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
