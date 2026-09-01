<?php

namespace Tests\Feature;

use App\Services\RoboNeo\RoboNeoApiClient;
use App\Services\RoboNeo\RoboNeoContext;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoboNeoApiClientTest extends TestCase
{
    public function test_initialize_resolves_uid_from_the_current_account_response(): void
    {
        config()->set('roboneo.credentials.app_token', 'authorized-app-token');
        Http::preventStrayRequests();
        Http::fake([
            '*users/show_current*' => Http::response([
                'meta' => ['code' => 0],
                'response' => ['user' => ['id' => 'uid-123']],
            ]),
            '*initconfig' => Http::response(['error_code' => 0, 'parameter' => []]),
            '*createroom' => Http::response([
                'error_code' => 0,
                'parameter' => ['room_id' => 'room-123', 'room_type' => 2],
            ]),
        ]);

        $client = new RoboNeoApiClient(new RoboNeoContext('gid-1', 'mt-g', 'sid'), 'user-access');
        $client->initialize();

        $this->assertSame('uid-123', $client->contextSnapshot()['uid']);
        $this->assertSame('room-123', $client->createRoom());
    }

    public function test_cost_and_execute_requests_match_the_observed_protocol(): void
    {
        config()->set('roboneo.credentials.app_token', 'authorized-app-token');
        Http::preventStrayRequests();
        Http::fake([
            '*countcost' => Http::response(['error_code' => 0, 'data' => ['items' => [['cost' => 72]]]]),
            '*nodeexecute' => Http::response(['error_code' => 0, 'data' => ['task_id' => 'task-123']]),
        ]);

        $client = new RoboNeoApiClient(new RoboNeoContext('gid-1', 'mt-g', 'sid', 'uid-1'), 'user-access');
        $cost = $client->countMotionCost('room-1', 'node-1', 10, 'Prompt', 'https://a/image.jpg', 'https://a/video.mp4');
        $taskId = $client->executeMotion('room-1', 'node-1', 'Prompt', 'https://a/image.jpg', 'https://a/video.mp4');

        $this->assertSame(72, $cost);
        $this->assertSame('task-123', $taskId);

        Http::assertSent(function (Request $request): bool {
            if (! str_ends_with($request->url(), '/roboneo/sync/request/countcost')) {
                return false;
            }

            $parameter = $request->data()['parameter'];

            return $parameter['path_scene'] === 'countcost'
                && $request->header('access-token')[0] === 'user-access'
                && $parameter['items'][0]['tool_name'] === 'video_bonbon_motioncontrol_v26'
                && $parameter['items'][0]['params']['quality'] === 'std'
                && $parameter['items'][0]['params']['video_duration'] === 10;
        });
        Http::assertSent(function (Request $request): bool {
            if (! str_ends_with($request->url(), '/roboneo/sync/request/nodeexecute')) {
                return false;
            }

            $parameter = $request->data()['parameter'];
            $node = $parameter['node_list_array'][0][0];

            return $node['name'] === 'video_bonbon_motioncontrol_v26'
                && $node['tree_id'] === '93'
                && $node['parameters']['quality'] === 'std'
                && $node['parameters']['image_url'] === 'https://a/image.jpg';
        });
    }
}
