<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoMotionApi;
use Botble\AiVideoGenerator\Jobs\SubmitCustomerRoboNeoTask;
use Botble\AiVideoGenerator\Models\AiGenerationTask;
use Botble\AiVideoGenerator\Models\AiVideoModelEndpoint;
use Botble\AiVideoGenerator\Models\Customer;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiGenerationTaskInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoModelEndpointInterface;
use Botble\AiVideoGenerator\Services\AiGenerationService;
use Botble\AiVideoGenerator\Services\CustomerCreditService;
use Botble\AiVideoGenerator\Services\RoboNeo\MotionVideoTrimmer;
use Botble\AiVideoGenerator\Services\RoboNeo\Sources\CustomerRoboNeoTaskSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoProtocolException.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoMotionApi.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Models/AiGenerationTask.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Models/AiVideoModelEndpoint.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Models/Customer.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Interfaces/AiGenerationTaskInterface.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Interfaces/AiVideoApiTokenInterface.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Interfaces/AiVideoModelEndpointInterface.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/MotionVideoTrimmer.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/CustomerCreditService.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/Contracts/RoboNeoTaskSource.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Jobs/PollRoboNeoTask.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Jobs/SubmitCustomerRoboNeoTask.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/Sources/CustomerRoboNeoTaskSource.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/AiGenerationService.php';

class MimixRoboNeoPipelineTest extends TestCase
{
    public function test_video_lab_debits_mimix_credits_and_queues_without_calling_the_provider_inline(): void
    {
        Queue::fake();
        Storage::fake('public');
        Storage::disk('public')->put('roboneo/input.jpg', 'image');
        Storage::disk('public')->put('roboneo/input.mp4', 'video');
        config()->set('auth.guards.customer', [
            'driver' => 'session',
            'provider' => 'customers',
        ]);
        config()->set('auth.providers.customers', [
            'driver' => 'eloquent',
            'model' => Customer::class,
        ]);

        $customer = new Customer;
        $customer->forceFill(['id' => 51, 'credits_balance' => 100]);
        auth('customer')->setUser($customer);

        $endpoint = new AiVideoModelEndpoint;
        $endpoint->forceFill(['code' => 'roboneo', 'price' => 25, 'is_active' => true]);
        $endpoints = $this->createMock(AiVideoModelEndpointInterface::class);
        $endpoints->method('getActiveByCode')->with('roboneo')->willReturn($endpoint);

        $storedTask = new MimixInMemoryGenerationTask;
        $tasks = $this->createMock(AiGenerationTaskInterface::class);
        $tasks->method('storeFromResponse')->willReturnCallback(
            function (array $response, array|string $payload, ?int $customerId) use ($storedTask): Model {
                $data = $response['data'];
                $storedTask->forceFill([
                    'customer_id' => $customerId,
                    'task_id' => $data['task_id'],
                    'status' => $data['status'],
                    'is_completed' => false,
                    'generated' => $data['generated'],
                    'payload' => $payload,
                ]);

                return $storedTask;
            },
        );

        $provider = $this->createMock(RoboNeoMotionApi::class);
        $provider->expects($this->never())->method('generate');
        $tokens = $this->createMock(AiVideoApiTokenInterface::class);
        $credits = new MimixInMemoryCreditService([51 => 100]);
        $trimmer = $this->createMock(MotionVideoTrimmer::class);
        $service = new AiGenerationService($provider, $tasks, $tokens, $endpoints, $trimmer, $credits);

        $response = $service->create('roboneo', [
            'image_url' => Storage::disk('public')->url('roboneo/input.jpg'),
            'video_url' => Storage::disk('public')->url('roboneo/input.mp4'),
            'duration' => 10,
        ]);

        $this->assertSame('PROCESSING', data_get($response, 'data.status'));
        $this->assertNotSame('', (string) data_get($response, 'data.task_id'));
        $this->assertSame('customer', data_get($storedTask->payload, 'roboneo.source'));
        $this->assertSame('queued', data_get($storedTask->payload, 'roboneo.submission.state'));
        $this->assertSame(25, data_get($storedTask->payload, 'billing.credits_debited'));
        $this->assertSame(75, $credits->balance(51));
        Queue::assertPushed(SubmitCustomerRoboNeoTask::class, 1);
    }

    public function test_customer_completion_keeps_the_existing_generated_media_shape(): void
    {
        $task = $this->customerTask();
        $source = $this->customerSource(new MimixInMemoryCreditService([51 => 75]));

        $source->complete($task, [
            'key' => 'roboneo/customer-task/result.mp4',
            'url' => 'https://r2.example.com/roboneo/customer-task/result.mp4',
        ]);

        $this->assertSame('COMPLETED', $task->status);
        $this->assertTrue($task->is_completed);
        $this->assertSame(
            'https://r2.example.com/roboneo/customer-task/result.mp4',
            data_get($task->generated, '0.url'),
        );
        $this->assertSame('roboneo/customer-task/result.mp4', data_get($task->generated, '0.r2_key'));
        $this->assertNotNull($task->completed_at);
    }

    public function test_customer_failure_refunds_the_mimix_wallet_exactly_once(): void
    {
        $task = $this->customerTask();
        $credits = new MimixInMemoryCreditService([51 => 75]);
        $source = $this->customerSource($credits);

        $source->fail($task, 'POLLING_TIMEOUT', 'RoboNeo timed out.');
        $source->fail($task, 'POLLING_TIMEOUT', 'RoboNeo timed out.');

        $this->assertSame('FAILED', $task->status);
        $this->assertSame(100, $credits->balance(51));
        $this->assertNotNull(data_get($task->payload, 'billing.refunded_at'));
        $this->assertSame('POLLING_TIMEOUT', data_get($task->generated, '0.code'));
    }

    private function customerTask(): MimixInMemoryGenerationTask
    {
        $task = new MimixInMemoryGenerationTask;
        $task->forceFill([
            'customer_id' => 51,
            'task_id' => 'customer-task',
            'status' => 'PROCESSING',
            'is_completed' => false,
            'generated' => [],
            'payload' => [
                'roboneo' => ['source' => 'customer'],
                'billing' => [
                    'customer_id' => 51,
                    'credits_debited' => 25,
                    'refunded_at' => null,
                ],
            ],
        ]);

        return $task;
    }

    private function customerSource(MimixInMemoryCreditService $credits): CustomerRoboNeoTaskSource
    {
        return new CustomerRoboNeoTaskSource(
            $this->createMock(AiGenerationTaskInterface::class),
            $this->createMock(MotionVideoTrimmer::class),
            $credits,
        );
    }
}

class MimixInMemoryGenerationTask extends AiGenerationTask
{
    public function update(array $attributes = [], array $options = []): bool
    {
        $this->forceFill($attributes);

        return true;
    }

    public function fresh($with = []): static
    {
        return $this;
    }
}

class MimixInMemoryCreditService extends CustomerCreditService
{
    public function __construct(private array $balances) {}

    public function credit(int $customerId, int $amount, int $actorId): Customer
    {
        $this->balances[$customerId] = $this->balance($customerId) + $amount;

        return $this->customer($customerId);
    }

    public function debit(int $customerId, int $amount, int $actorId): Customer
    {
        $this->balances[$customerId] = $this->balance($customerId) - $amount;

        return $this->customer($customerId);
    }

    public function balance(int $customerId): int
    {
        return $this->balances[$customerId] ?? 0;
    }

    private function customer(int $customerId): Customer
    {
        $customer = new Customer;
        $customer->forceFill(['id' => $customerId, 'credits_balance' => $this->balance($customerId)]);

        return $customer;
    }
}
