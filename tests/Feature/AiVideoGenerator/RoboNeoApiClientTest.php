<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoApiClient;
use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoContext;
use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoIdentity.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoContext.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoProtocolException.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoApiClient.php';

class RoboNeoApiClientTest extends TestCase
{
    public function test_it_retries_a_connect_fail_response_and_keeps_the_same_trace_id(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*initconfig' => Http::sequence()
                ->push('<html><title>ERROR: CONNECT FAIL</title></html>', 503)
                ->push('<html><title>ERROR: CONNECT FAIL</title></html>', 503)
                ->push(['error_code' => 0, 'parameter' => []]),
        ]);

        $client = $this->client();
        $client->initialize();

        $requests = Http::recorded();

        $this->assertCount(3, $requests);
        $this->assertSame(
            $requests[0][0]->data()['parameter']['trace_id'],
            $requests[1][0]->data()['parameter']['trace_id'],
        );
        $this->assertSame(
            $requests[0][0]->data()['parameter']['trace_id'],
            $requests[2][0]->data()['parameter']['trace_id'],
        );
    }

    public function test_it_reports_the_failed_roboneo_stage_after_connect_fail_retries_are_exhausted(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*initconfig' => Http::sequence()
                ->push('<html><title>ERROR: CONNECT FAIL</title></html>', 503)
                ->push('<html><title>ERROR: CONNECT FAIL</title></html>', 503)
                ->push('<html><title>ERROR: CONNECT FAIL</title></html>', 503),
        ]);

        try {
            $this->client()->initialize();
            $this->fail('Expected the exhausted RoboNeo request to fail.');
        } catch (RoboNeoProtocolException $exception) {
            $this->assertSame('http_503_initconfig', $exception->protocolCode);
            $this->assertSame('initconfig', $exception->responseData['stage']);
            $this->assertSame(503, $exception->responseData['status']);
            $this->assertSame('ai-engine-gateway-roboneo.meitu.com', $exception->responseData['host']);
            $this->assertStringContainsString('initconfig', $exception->getMessage());
        }

        $this->assertCount(3, Http::recorded());
    }

    public function test_execute_motion_reuses_the_supplied_submission_identity(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*nodeexecute' => Http::response([
                'error_code' => 0,
                'parameter' => ['task_id' => 'provider-task-1'],
            ]),
        ]);

        try {
            $taskId = $this->client()->executeMotion(
                'room-1',
                'node-1',
                'motion prompt',
                'https://example.com/image.jpg',
                'https://example.com/video.mp4',
                'fixed-trace-id',
                '1788420000000-24681357',
            );
        } catch (\TypeError $error) {
            $this->fail('The original RoboNeo string seed must survive retries: '.$error->getMessage());
        }

        $parameter = Http::recorded()[0][0]->data()['parameter'];

        $this->assertSame('provider-task-1', $taskId);
        $this->assertSame('fixed-trace-id', $parameter['trace_id']);
        $this->assertSame('1788420000000-24681357', $parameter['node_list_array'][0][0]['parameters']['random']);
    }

    private function client(): RoboNeoApiClient
    {
        return new RoboNeoApiClient(
            new RoboNeoContext('gid-1', 'mt-g-1', 'sid-1', 'uid-1'),
            'access-token-1',
            [
                'credentials' => ['app_token' => 'app-token-1'],
                'http' => ['retry_delays_ms' => [0, 0]],
            ],
        );
    }
}
