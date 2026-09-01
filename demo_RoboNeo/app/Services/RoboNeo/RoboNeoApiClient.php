<?php

namespace App\Services\RoboNeo;

use App\Exceptions\RoboNeoProtocolException;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class RoboNeoApiClient
{
    private CookieJar $cookieJar;

    public function __construct(
        private readonly RoboNeoContext $context,
        private readonly string $accessToken,
    ) {
        $cookies = array_map(
            static fn (array $cookie): SetCookie => new SetCookie($cookie),
            $context->cookies,
        );
        $this->cookieJar = new CookieJar(false, $cookies);
    }

    public function assertLiveCredentials(): void
    {
        if ($this->accessToken === '') {
            throw new RoboNeoProtocolException('RoboNeo Personal Access Token is required for live mode.', 'missing_access_token');
        }

        if (! config('roboneo.credentials.app_token')) {
            throw new RoboNeoProtocolException('ROBONEO_APP_TOKEN is required for live mode.', 'missing_app_token');
        }
    }

    public function initialize(): void
    {
        $this->assertLiveCredentials();

        if ($this->context->uid === '') {
            $this->context->uid = $this->resolveUid();
        }

        $this->ai('initconfig', '/roboneo/sync/request/initconfig', [], '/');
    }

    public function createRoom(): string
    {
        $data = $this->ai(
            'createroom',
            '/roboneo/sync/request/createroom',
            ['room_type' => 2],
            '/home',
        );
        $roomId = (string) ($data['room_id'] ?? '');

        if ($roomId === '') {
            throw new RoboNeoProtocolException('RoboNeo create room response had no room_id.', 'missing_room_id', $data);
        }

        return $roomId;
    }

    public function initializeCanvas(string $roomId): void
    {
        $this->webPost('/workflow/canvas/init.json', ['room_id' => $roomId]);
    }

    public function uploadPolicy(string $suffix): array
    {
        return $this->ai('uploadpolicy', '/roboneo/sync/request/uploadpolicy', [
            'upload_version' => '2',
            'app' => 'RoboNeo',
            'type' => 'roboneo_private_web',
            'count' => 1,
            'suffix' => $suffix,
            'sig' => '',
            'sigTime' => (string) time(),
            'sigVersion' => '1.3',
            'version' => '2',
        ]);
    }

    public function strategyPolicy(array $policy, string $suffix): array
    {
        $url = rtrim(config('roboneo.hosts.strategy'), '/').'/upload/policy';
        $response = $this->request()->get($url, [
            'app' => 'RoboNeo',
            'count' => 1,
            'sig' => $policy['sig'] ?? '',
            'sigTime' => $policy['sigTime'] ?? $policy['sig_time'] ?? '',
            'sigVersion' => $policy['sigVersion'] ?? $policy['sig_version'] ?? '1.3',
            'suffix' => $suffix,
            'type' => 'roboneo_private_web',
            'version' => '2',
        ]);

        $response->throw();

        return $response->json() ?? [];
    }

    public function mediaCheck(string $type, string $url): void
    {
        $this->ai('mediacheck', '/roboneo/sync/request/mediacheck', [
            'image_urls' => $type === 'image' ? [$url] : [],
            'video_urls' => $type === 'video' ? [$url] : [],
        ]);
    }

    public function createAsset(string $roomId, string $type, array $asset): string
    {
        $payload = [
            'room_id' => $roomId,
            'task_type' => 'workflow',
            'material_type' => $type,
            'url' => $asset['url'],
            'watermark_url' => $asset['watermark_url'] ?? $asset['url'],
            'name' => $asset['name'],
        ];

        foreach (['thumbnail_url', 'ext', 'width', 'height'] as $key) {
            if (! empty($asset[$key])) {
                $payload[$key] = $asset[$key];
            }
        }

        $data = $this->webPost('/asset_library/asset/create.json', $payload);
        $assetId = (string) ($data['asset_id'] ?? '');

        if ($assetId === '') {
            throw new RoboNeoProtocolException('RoboNeo asset response had no asset_id.', 'missing_asset_id', $data);
        }

        return $assetId;
    }

    public function saveCanvas(string $roomId, array $graph): void
    {
        $this->webPost('/workflow/canvas/save.json', [
            'room_id' => $roomId,
            'data' => json_encode($graph, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function countMotionCost(
        string $roomId,
        string $nodeId,
        int $duration,
        string $prompt,
        string $imageUrl,
        string $videoUrl,
    ): int {
        $apiName = config('roboneo.motion.api_name');
        $quality = config('roboneo.motion.quality');
        $data = $this->ai('countcost', '/roboneo/sync/request/countcost', [
            'room_id' => $roomId,
            'items' => [[
                'id' => $nodeId,
                'tool_name' => $apiName,
                'video_duration' => $duration,
                'size' => '',
                'resolution' => '',
                'params' => [
                    'prompt' => $prompt,
                    'quality' => $quality,
                    'image_url' => $imageUrl,
                    'video_url' => $videoUrl,
                    'video_duration' => $duration,
                ],
            ]],
        ]);
        $item = $data['items'][0] ?? null;

        if (! is_array($item)) {
            throw new RoboNeoProtocolException('RoboNeo countcost response had no item.', 'missing_cost_item', $data);
        }

        return (int) ($item['cost'] ?? $item['carrots'] ?? 0);
    }

    public function executeMotion(
        string $roomId,
        string $nodeId,
        string $prompt,
        string $imageUrl,
        string $videoUrl,
    ): string {
        $apiName = config('roboneo.motion.api_name');
        $treeId = config('roboneo.motion.tree_id');
        $data = $this->ai('nodeexecute', '/roboneo/sync/request/nodeexecute', [
            'room_id' => $roomId,
            'node_id' => $nodeId,
            'node_list_array' => [[[
                'name' => $apiName,
                'tree_id' => $treeId,
                'tool_abstract_name' => ['en' => 'Motion Control', 'cn' => 'Motion Control'],
                'node_id' => $nodeId,
                'parameters' => [
                    'prompt' => $prompt,
                    'quality' => config('roboneo.motion.quality'),
                    'image_url' => $imageUrl,
                    'video_url' => $videoUrl,
                    'random' => RoboNeoIdentity::seed(),
                ],
            ]]],
        ]);
        $taskId = $this->findScalarByKeys($data, ['task_id', 'taskId']);

        if ($taskId === null && isset($data['task_ids'][0])) {
            $taskId = (string) $data['task_ids'][0];
        }

        if ($taskId === null) {
            throw new RoboNeoProtocolException('RoboNeo execute response had no task id.', 'missing_task_id', $data);
        }

        return $taskId;
    }

    public function queryTask(string $taskId, string $roomId): array
    {
        return $this->ai('nodeexecutequery', '/roboneo/sync/request/nodeexecutequery', [
            'task_ids' => [$taskId],
            'room_id' => $roomId,
        ]);
    }

    public function finalize(string $roomId, ?string $coverUrl): void
    {
        try {
            $this->ai('historymodify', '/roboneo/sync/request/historymodify', ['room_id' => $roomId]);
        } catch (RoboNeoProtocolException) {
        }

        if ($coverUrl) {
            try {
                $this->webPost('/workflow/canvas/save_cover.json', [
                    'room_id' => $roomId,
                    'cover' => $coverUrl,
                ]);
            } catch (RoboNeoProtocolException) {
            }
        }

        try {
            $this->ai('roomsubmit', '/roboneo/sync/request/roomsubmit', [
                'room_id' => $roomId,
                'room_type' => 2,
            ]);
        } catch (RoboNeoProtocolException) {
        }
    }

    public function contextSnapshot(): array
    {
        $this->context->cookies = $this->cookieJar->toArray();

        return $this->context->toArray();
    }

    private function resolveUid(): string
    {
        $query = [
            ...$this->accountParams(),
            'mt_g' => $this->context->mtG,
            'sid' => $this->context->sid,
            'access_token' => $this->accessToken,
        ];
        $url = rtrim(config('roboneo.hosts.account_api'), '/').'/users/show_current';
        $response = $this->request()->get($url, $query);
        $response->throw();
        $payload = $response->json() ?? [];
        $data = $payload['response']['user']
            ?? $payload['response']
            ?? $payload['data']['user']
            ?? $payload['data']
            ?? $payload;
        $uid = (string) ($data['id'] ?? $data['uid'] ?? '');

        if ($uid === '') {
            throw new RoboNeoProtocolException('Could not resolve RoboNeo uid from access token.', 'missing_uid', $payload);
        }

        return $uid;
    }

    private function ai(string $operation, string $path, array $fields, string $position = '/ai_flow'): array
    {
        $parameter = [
            'token' => config('roboneo.credentials.app_token'),
            'gid' => $this->context->gid,
            'uid' => $this->context->uid,
            'trace_id' => RoboNeoIdentity::traceId(),
            'client_id' => config('roboneo.client.id'),
            'app_scene' => config('roboneo.client.scene'),
            'area_code' => config('roboneo.client.area_code'),
            'lang' => config('roboneo.client.language'),
            'extra' => ['big_data_patch' => ['position_type' => $position]],
            'path_scene' => $operation,
            ...$fields,
        ];
        $url = rtrim(config('roboneo.hosts.ai_engine'), '/').$path;
        $response = $this->request()->post($url, ['parameter' => $parameter]);
        $response->throw();
        $payload = $response->json() ?? [];

        if ((int) ($payload['error_code'] ?? -1) !== 0) {
            throw new RoboNeoProtocolException(
                (string) ($payload['message'] ?? $payload['error_msg'] ?? $payload['msg'] ?? "RoboNeo {$operation} failed."),
                (string) ($payload['error_code'] ?? 'unknown'),
                $payload,
            );
        }

        foreach (['data', 'parameter', 'response', 'result'] as $key) {
            if (is_array($payload[$key] ?? null) && $payload[$key] !== []) {
                return $payload[$key];
            }
        }

        return [];
    }

    private function webPost(string $path, array $fields): array
    {
        $url = rtrim(config('roboneo.hosts.web_api'), '/').$path;
        $response = $this->request()->post($url, [...$this->webParams(), ...$fields]);
        $response->throw();
        $payload = $response->json() ?? [];

        if ((int) ($payload['code'] ?? -1) !== 0) {
            throw new RoboNeoProtocolException(
                (string) ($payload['message'] ?? "RoboNeo web API {$path} failed."),
                (string) ($payload['code'] ?? 'unknown'),
                $payload,
            );
        }

        return is_array($payload['data'] ?? null) ? $payload['data'] : [];
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'access-token' => $this->accessToken,
            'accept' => 'application/json, text/plain, */*',
            'origin' => 'https://www.roboneo.com',
            'referer' => 'https://www.roboneo.com/',
            'accept-language' => 'en-US,en;q=0.9',
            'user-agent' => config('roboneo.client.user_agent'),
        ])->withOptions(['cookies' => $this->cookieJar])
            ->connectTimeout(20)
            ->timeout(120);
    }

    private function webParams(): array
    {
        return [
            'gnum' => $this->context->gid,
            'client_id' => config('roboneo.client.id'),
            'client_language' => config('roboneo.client.language'),
            'country_code' => config('roboneo.client.area_code'),
        ];
    }

    private function accountParams(): array
    {
        return [
            'client_id' => config('roboneo.client.id'),
            'client_language' => config('roboneo.client.language'),
            'overseas' => '1',
            'client_type' => '2',
            'web_version' => config('roboneo.client.web_version'),
            'zip_version' => config('roboneo.client.zip_version'),
            'is_web' => '1',
            'client_accept_cookies' => '1',
            'country_code' => config('roboneo.client.area_code'),
        ];
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
