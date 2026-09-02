<?php

namespace Botble\AiVideoGenerator\Api\RoboNeo;

/**
 * Direct RoboNeo Motion Control API. It has no database, queue, route, webhook,
 * or result-storage dependency. Pass local image/video paths and an access token.
 */
class RoboNeoMotionApi
{
    public function __construct(
        private readonly RoboNeoUploader $uploader = new RoboNeoUploader,
        private readonly RoboNeoMotionGraphBuilder $graphBuilder = new RoboNeoMotionGraphBuilder,
    ) {}

    /**
     * Uploads both files, creates the canvas, and submits the generation task.
     * Returns the RoboNeo task ID plus all state required to poll it later.
     */
    public function generate(string $imagePath, string $videoPath, string $accessToken, int $duration = 10, array $settings = []): array
    {
        $settings = $this->settings($settings);
        $prompt = $this->prompt($settings);
        $context = RoboNeoContext::make($settings['credentials']['gid'] ?? null, $settings['credentials']['uid'] ?? null, $settings['cookies'] ?? []);
        $client = new RoboNeoApiClient($context, $accessToken, $settings);
        $client->initialize();
        $roomId = $client->createRoom();
        $client->initializeCanvas($roomId);
        $image = $this->uploader->upload($client, $roomId, $imagePath, 'image');
        $video = $this->uploader->upload($client, $roomId, $videoPath, 'video');
        $built = $this->graphBuilder->build($prompt, $image, $video, [
            'tree_id' => $settings['motion']['tree_id'] ?? null,
            'api_name' => $settings['motion']['api_name'] ?? null,
        ]);
        $client->saveCanvas($roomId, $built['graph']);
        $taskId = $client->executeMotion($roomId, $built['motion_node_id'], $prompt, $image['url'], $video['url']);

        return [
            'task_id' => $taskId,
            'room_id' => $roomId,
            'motion_node_id' => $built['motion_node_id'],
            'image_asset' => $image,
            'video_asset' => $video,
            'session_data' => $client->contextSnapshot(),
        ];
    }

    public function quote(string $imagePath, string $videoPath, string $accessToken, int $duration = 10, array $settings = []): array
    {
        $settings = $this->settings($settings);
        $prompt = $this->prompt($settings);
        $context = RoboNeoContext::make($settings['credentials']['gid'] ?? null, $settings['credentials']['uid'] ?? null, $settings['cookies'] ?? []);
        $client = new RoboNeoApiClient($context, $accessToken, $settings);
        $client->initialize();
        $roomId = $client->createRoom();
        $client->initializeCanvas($roomId);
        $image = $this->uploader->upload($client, $roomId, $imagePath, 'image');
        $video = $this->uploader->upload($client, $roomId, $videoPath, 'video');
        $built = $this->graphBuilder->build($prompt, $image, $video, [
            'tree_id' => $settings['motion']['tree_id'] ?? null,
            'api_name' => $settings['motion']['api_name'] ?? null,
        ]);
        $client->saveCanvas($roomId, $built['graph']);

        return [
            'room_id' => $roomId,
            'motion_node_id' => $built['motion_node_id'],
            'quoted_cost' => $client->countMotionCost($roomId, $built['motion_node_id'], $duration, $prompt, $image['url'], $video['url']),
            'image_asset' => $image,
            'video_asset' => $video,
            'session_data' => $client->contextSnapshot(),
        ];
    }

    public function submit(array $quotedTask, string $accessToken, array $settings = []): array
    {
        $settings = $this->settings($settings);
        $prompt = $this->prompt($settings);
        $context = RoboNeoContext::fromArray($quotedTask['session_data'] ?? []);
        $client = new RoboNeoApiClient($context, $accessToken, $settings);
        $taskId = $client->executeMotion($quotedTask['room_id'], $quotedTask['motion_node_id'], $prompt, $quotedTask['image_asset']['url'], $quotedTask['video_asset']['url']);

        return ['task_id' => $taskId, 'session_data' => $client->contextSnapshot()];
    }

    public function status(string $taskId, string $roomId, string $accessToken, array $sessionData = [], array $settings = []): array
    {
        $settings = $this->settings($settings);
        $client = new RoboNeoApiClient(RoboNeoContext::fromArray($sessionData), $accessToken, $settings);

        return ['data' => $client->queryTask($taskId, $roomId), 'session_data' => $client->contextSnapshot()];
    }

    /**
     * Poll a RoboNeo task and normalize its state without persisting output.
     */
    public function poll(string $taskId, string $roomId, string $accessToken, array $sessionData = [], array $settings = []): array
    {
        $settings = $this->settings($settings);
        $client = new RoboNeoApiClient(RoboNeoContext::fromArray($sessionData), $accessToken, $settings);
        $data = $client->queryTask($taskId, $roomId);
        $task = $this->selectTask($data, $taskId);
        $status = strtoupper((string) ($task['state'] ?? $task['status'] ?? $data['state'] ?? $data['status'] ?? 'PROCESSING'));
        $sessionData = $client->contextSnapshot();

        if (in_array($status, ['CANCEL', 'CANCELLED', 'FAIL', 'FAILED', 'ERROR'], true)) {
            return [
                'state' => 'FAILED',
                'failure_code' => $this->findScalarByKeys($task, ['fail_code', 'error_code', 'code']) ?? 'ROBONEO_FAILED',
                'message' => $this->findScalarByKeys($task, ['error_message', 'message', 'msg']) ?? "RoboNeo task ended with status {$status}.",
                'session_data' => $sessionData,
                'raw' => $data,
            ];
        }

        $resultUrl = $this->findMp4Url($this->outputCandidates($task, $data));

        if ($resultUrl !== null) {
            $coverUrl = $this->findScalarByKeys($this->outputCandidates($task, $data), ['cover_url', 'coverUrl', 'thumbnail_url']);
            $client->finalize($roomId, $coverUrl);

            return [
                'state' => 'COMPLETED',
                'result_url' => $resultUrl,
                'session_data' => $client->contextSnapshot(),
                'raw' => $data,
            ];
        }

        return ['state' => 'PROCESSING', 'session_data' => $sessionData, 'raw' => $data];
    }

    private function settings(array $settings): array
    {
        return array_replace_recursive(config('plugins.ai-video-generator.general.roboneo', []), $settings);
    }

    private function prompt(array $settings): string
    {
        $prompt = trim((string) data_get($settings, 'motion.prompt'));

        if ($prompt === '') {
            throw new RoboNeoProtocolException('ROBONEO_MOTION_PROMPT must be configured.', 'missing_motion_prompt');
        }

        return $prompt;
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
        $outputKeys = ['output', 'outputs', 'result', 'results', 'result_url', 'result_urls', 'video_url', 'video_urls'];

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
            if (filter_var($value, FILTER_VALIDATE_URL) && preg_match('/\\.mp4(?:\\?|$|&)/i', $value)) {
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
            if (is_array($child) && ($found = $this->findScalarByKeys($child, $keys, $depth + 1)) !== null) {
                return $found;
            }
        }

        return null;
    }
}
