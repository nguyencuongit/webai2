<?php

namespace Botble\AiVideoGenerator\Api\RoboNeo;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Low-level client for the RoboNeo internal HTTP protocol.
 *
 * This class deliberately performs no task persistence, uploads, polling loop,
 * or credit-confirmation workflow. Those concerns belong to the service layer.
 */
class RoboNeoApiClient
{
    private CookieJar $cookieJar;

    public function __construct(
        private readonly RoboNeoContext $context,
        private readonly string $accessToken,
        private readonly array $settings = [],
    ) {
        $cookies = array_map(
            static fn (array $cookie): SetCookie => new SetCookie($cookie),
            $context->cookies,
        );

        $this->cookieJar = new CookieJar(false, $cookies);
    }

    public function assertCredentials(): void
    {
        if ($this->accessToken === '') {
            throw new RoboNeoProtocolException('RoboNeo access token is required.', 'missing_access_token');
        }

        if ($this->setting('credentials.app_token') === '') {
            throw new RoboNeoProtocolException('RoboNeo application token is required.', 'missing_app_token');
        }
    }

    public function initialize(): void
    {
        $this->assertCredentials();

        if ($this->context->uid === '') {
            $this->context->uid = $this->resolveUid();
        }

        $this->ai('initconfig', '/roboneo/sync/request/initconfig', [], '/');
    }

    public function createRoom(): string
    {
        $data = $this->ai('createroom', '/roboneo/sync/request/createroom', ['room_type' => 2], '/home');
        $roomId = (string) ($data['room_id'] ?? '');

        if ($roomId === '') {
            throw new RoboNeoProtocolException('RoboNeo create-room response had no room_id.', 'missing_room_id', $data);
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
            'upload_version' => '2', 'app' => 'RoboNeo', 'type' => 'roboneo_private_web', 'count' => 1,
            'suffix' => $suffix, 'sig' => '', 'sigTime' => (string) time(), 'sigVersion' => '1.3', 'version' => '2',
        ]);
    }

    public function strategyPolicy(array $policy, string $suffix): array
    {
        $response = $this->request()->get(rtrim($this->setting('hosts.strategy'), '/').'/upload/policy', [
            'app' => 'RoboNeo', 'count' => 1, 'sig' => $policy['sig'] ?? '',
            'sigTime' => $policy['sigTime'] ?? $policy['sig_time'] ?? '',
            'sigVersion' => $policy['sigVersion'] ?? $policy['sig_version'] ?? '1.3',
            'suffix' => $suffix, 'type' => 'roboneo_private_web', 'version' => '2',
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
        $data = $this->webPost('/asset_library/asset/create.json', array_filter([
            'room_id' => $roomId, 'task_type' => 'workflow', 'material_type' => $type,
            'url' => $asset['url'] ?? null, 'watermark_url' => $asset['watermark_url'] ?? ($asset['url'] ?? null),
            'thumbnail_url' => $asset['thumbnail_url'] ?? null, 'ext' => $asset['ext'] ?? null,
            'width' => $asset['width'] ?? null, 'height' => $asset['height'] ?? null, 'name' => $asset['name'] ?? null,
        ], static fn ($value) => $value !== null && $value !== ''));
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

    public function countMotionCost(string $roomId, string $nodeId, int $duration, string $prompt, string $imageUrl, string $videoUrl): int
    {
        $data = $this->ai('countcost', '/roboneo/sync/request/countcost', ['room_id' => $roomId, 'items' => [[
            'id' => $nodeId, 'tool_name' => $this->setting('motion.api_name'), 'video_duration' => $duration,
            'size' => '', 'resolution' => '', 'params' => [
                'prompt' => $prompt, 'quality' => $this->setting('motion.quality'), 'image_url' => $imageUrl,
                'video_url' => $videoUrl, 'video_duration' => $duration,
            ],
        ]]]);
        $item = $data['items'][0] ?? null;

        if (! is_array($item)) {
            throw new RoboNeoProtocolException('RoboNeo cost response had no item.', 'missing_cost_item', $data);
        }

        return (int) ($item['cost'] ?? $item['carrots'] ?? 0);
    }

    public function executeMotion(string $roomId, string $nodeId, string $prompt, string $imageUrl, string $videoUrl): string
    {
        $apiName = $this->setting('motion.api_name');
        $data = $this->ai('nodeexecute', '/roboneo/sync/request/nodeexecute', ['room_id' => $roomId, 'node_id' => $nodeId, 'node_list_array' => [[[
            'name' => $apiName, 'tree_id' => $this->setting('motion.tree_id'),
            'tool_abstract_name' => ['en' => 'Motion Control', 'cn' => 'Motion Control'], 'node_id' => $nodeId,
            'parameters' => [
                'prompt' => $prompt, 'quality' => $this->setting('motion.quality'), 'image_url' => $imageUrl,
                'video_url' => $videoUrl, 'random' => RoboNeoIdentity::seed(),
            ],
        ]]]]);
        $taskId = $this->findScalarByKeys($data, ['task_id', 'taskId']) ?? ($data['task_ids'][0] ?? null);

        if (! is_scalar($taskId) || (string) $taskId === '') {
            throw new RoboNeoProtocolException('RoboNeo execute response had no task id.', 'missing_task_id', $data);
        }

        return (string) $taskId;
    }

    public function queryTask(string $taskId, string $roomId): array
    {
        return $this->ai('nodeexecutequery', '/roboneo/sync/request/nodeexecutequery', ['task_ids' => [$taskId], 'room_id' => $roomId]);
    }

    public function finalize(string $roomId, ?string $coverUrl = null): void
    {
        try {
            $this->ai('historymodify', '/roboneo/sync/request/historymodify', ['room_id' => $roomId]);
        } catch (RoboNeoProtocolException) {
        }

        if ($coverUrl) {
            try {
                $this->webPost('/workflow/canvas/save_cover.json', ['room_id' => $roomId, 'cover' => $coverUrl]);
            } catch (RoboNeoProtocolException) {
            }
        }

        try {
            $this->ai('roomsubmit', '/roboneo/sync/request/roomsubmit', ['room_id' => $roomId, 'room_type' => 2]);
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
        $url = rtrim($this->setting('hosts.account_api'), '/').'/users/show_current';
        $response = $this->request()->get($url, [
            ...$this->accountParams(), 'mt_g' => $this->context->mtG, 'sid' => $this->context->sid,
            'access_token' => $this->accessToken,
        ]);
        $response->throw();
        $payload = $response->json() ?? [];
        $data = $payload['response']['user'] ?? $payload['response'] ?? $payload['data']['user'] ?? $payload['data'] ?? $payload;
        $uid = (string) ($data['id'] ?? $data['uid'] ?? '');

        if ($uid === '') {
            throw new RoboNeoProtocolException('Could not resolve RoboNeo uid from access token.', 'missing_uid', $payload);
        }

        return $uid;
    }

    private function ai(string $operation, string $path, array $fields, string $position = '/ai_flow'): array
    {
        $parameter = [
            'token' => $this->setting('credentials.app_token'), 'gid' => $this->context->gid, 'uid' => $this->context->uid,
            'trace_id' => RoboNeoIdentity::traceId(), 'client_id' => $this->setting('client.id'),
            'app_scene' => $this->setting('client.scene'), 'area_code' => $this->setting('client.area_code'),
            'lang' => $this->setting('client.language'), 'extra' => ['big_data_patch' => ['position_type' => $position]],
            'path_scene' => $operation, ...$fields,
        ];
        $response = $this->request()->post(rtrim($this->setting('hosts.ai_engine'), '/').$path, ['parameter' => $parameter]);
        $response->throw();
        $payload = $response->json() ?? [];

        if ((int) ($payload['error_code'] ?? -1) !== 0) {
            throw new RoboNeoProtocolException((string) ($payload['message'] ?? $payload['error_msg'] ?? $payload['msg'] ?? "RoboNeo {$operation} failed."), (string) ($payload['error_code'] ?? 'unknown'), $payload);
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
        $response = $this->request()->post(rtrim($this->setting('hosts.web_api'), '/').$path, [...$this->webParams(), ...$fields]);
        $response->throw();
        $payload = $response->json() ?? [];

        if ((int) ($payload['code'] ?? -1) !== 0) {
            throw new RoboNeoProtocolException((string) ($payload['message'] ?? "RoboNeo web API {$path} failed."), (string) ($payload['code'] ?? 'unknown'), $payload);
        }

        return is_array($payload['data'] ?? null) ? $payload['data'] : [];
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'access-token' => $this->accessToken, 'accept' => 'application/json, text/plain, */*',
            'origin' => 'https://www.roboneo.com', 'referer' => 'https://www.roboneo.com/',
            'accept-language' => 'en-US,en;q=0.9', 'user-agent' => $this->setting('client.user_agent'),
            // RoboNeo must be contacted directly. The local development environment
            // may define a non-running HTTP(S)_PROXY, which would otherwise make the
            // queue worker unable to poll or download a completed task.
        ])->withOptions([
            'cookies' => $this->cookieJar,
            // CURLOPT_PROXY must be explicit here. An empty Guzzle proxy array
            // is merged with the HTTP(S)_PROXY defaults in CLI workers.
            'curl' => [
                CURLOPT_PROXY => '',
            ],
        ])->connectTimeout(20)->timeout(120);
    }

    private function webParams(): array
    {
        return ['gnum' => $this->context->gid, 'client_id' => $this->setting('client.id'), 'client_language' => $this->setting('client.language'), 'country_code' => $this->setting('client.area_code')];
    }

    private function accountParams(): array
    {
        return [
            'client_id' => $this->setting('client.id'), 'client_language' => $this->setting('client.language'),
            'overseas' => '1', 'client_type' => '2', 'web_version' => $this->setting('client.web_version'),
            'zip_version' => $this->setting('client.zip_version'), 'is_web' => '1', 'client_accept_cookies' => '1',
            'country_code' => $this->setting('client.area_code'),
        ];
    }

    private function setting(string $key): string
    {
        $defaults = [
            'hosts.ai_engine' => 'https://ai-engine-gateway-roboneo.meitu.com', 'hosts.web_api' => 'https://webapi.roboneo.com',
            'hosts.account_api' => 'https://api.account.meitu.com', 'hosts.strategy' => 'https://strategy.app.meitudata.com',
            'credentials.app_token' => '', 'client.id' => '1189857647', 'client.scene' => 'roboneo',
            'client.area_code' => 'VN', 'client.language' => 'en', 'client.web_version' => '4.9.0', 'client.zip_version' => '4.76000',
            'client.user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',
            'motion.api_name' => 'video_bonbon_motioncontrol_v26', 'motion.tree_id' => '93', 'motion.quality' => 'std',
        ];
        $value = data_get($this->settings, $key, config("plugins.ai-video-generator.general.roboneo.{$key}", $defaults[$key]));

        return (string) $value;
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
