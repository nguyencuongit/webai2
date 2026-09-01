<?php

namespace Tests\Feature;

use App\Models\MotionJob;
use App\Models\RoboNeoAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoboNeoAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_personal_token_creates_encrypted_default_account(): void
    {
        config()->set('roboneo.credentials.app_token', 'authorized-app-token');
        Http::preventStrayRequests();
        Http::fake([
            '*users/show_current*' => Http::response([
                'meta' => ['code' => 0],
                'response' => ['user' => ['id' => 'uid-123']],
            ]),
            '*initconfig' => Http::response(['error_code' => 0, 'parameter' => []]),
        ]);

        $response = $this->post(route('roboneo-accounts.store'), [
            'label' => 'Tài khoản chính',
            'access_token' => 'personal-access-token-123456789',
        ]);

        $account = RoboNeoAccount::query()->sole();
        $response->assertRedirect(route('roboneo-accounts.index'));
        $this->assertSame('Tài khoản chính', $account->label);
        $this->assertSame('uid-123', $account->uid);
        $this->assertSame('personal-access-token-123456789', $account->access_token);
        $this->assertTrue($account->is_default);
        $this->assertTrue($account->is_active);
        $this->assertNotNull($account->last_verified_at);
        $this->assertStringNotContainsString(
            'personal-access-token-123456789',
            DB::table($account->getTable())->value('access_token'),
        );
        $this->assertArrayNotHasKey('access_token', $account->toArray());
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/users/show_current')
            && $request->header('access-token')[0] === 'personal-access-token-123456789');
    }

    public function test_invalid_personal_token_is_not_stored(): void
    {
        config()->set('roboneo.credentials.app_token', 'authorized-app-token');
        Http::preventStrayRequests();
        Http::fake([
            '*users/show_current*' => Http::response([
                'meta' => ['code' => 10109, 'msg' => 'Authentication expired, please login again.'],
                'response' => new \stdClass,
            ]),
        ]);

        $this->post(route('roboneo-accounts.store'), [
            'label' => 'Token lỗi',
            'access_token' => 'expired-personal-access-token',
        ])->assertSessionHasErrors([
            'access_token' => 'Personal Access Token không hợp lệ hoặc đã hết hạn.',
        ]);

        $this->assertSame(0, RoboNeoAccount::query()->count());
    }

    public function test_account_can_be_set_as_default(): void
    {
        $oldDefault = RoboNeoAccount::factory()->default()->create();
        $newDefault = RoboNeoAccount::factory()->create();

        $this->post(route('roboneo-accounts.default', $newDefault))
            ->assertRedirect(route('roboneo-accounts.index'));

        $this->assertFalse($oldDefault->fresh()->is_default);
        $this->assertTrue($newDefault->fresh()->is_default);
    }

    public function test_account_list_escapes_labels_and_never_renders_tokens(): void
    {
        $account = RoboNeoAccount::factory()->create([
            'label' => '<script>alert("account")</script>',
            'access_token' => 'secret-personal-access-token',
        ]);

        $response = $this->get(route('roboneo-accounts.index'));

        $response->assertOk();
        $response->assertSee($account->label);
        $response->assertDontSee($account->label, false);
        $response->assertDontSee('secret-personal-access-token');
    }

    public function test_replacing_a_token_verifies_it_before_updating_the_account(): void
    {
        config()->set('roboneo.credentials.app_token', 'authorized-app-token');
        $account = RoboNeoAccount::factory()->default()->create([
            'access_token' => 'old-personal-access-token',
            'uid' => 'uid-old',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            '*users/show_current*' => Http::response([
                'meta' => ['code' => 0],
                'response' => ['user' => ['id' => 'uid-new']],
            ]),
            '*initconfig' => Http::response(['error_code' => 0, 'parameter' => []]),
        ]);

        $this->put(route('roboneo-accounts.update', $account), [
            'label' => 'Tài khoản đã đổi token',
            'access_token' => 'new-personal-access-token-123456',
            'is_active' => '1',
        ])->assertRedirect(route('roboneo-accounts.index'));

        $account->refresh();
        $this->assertSame('Tài khoản đã đổi token', $account->label);
        $this->assertSame('new-personal-access-token-123456', $account->access_token);
        $this->assertSame('uid-new', $account->uid);
        $this->assertTrue($account->is_default);
    }

    public function test_account_used_by_a_job_cannot_be_deleted(): void
    {
        $account = RoboNeoAccount::factory()->default()->create();
        MotionJob::query()->create([
            'roboneo_account_id' => $account->id,
            'status' => MotionJob::STATUS_FAILED,
            'prompt' => 'Test',
            'quality' => 'std',
            'duration_seconds' => 10,
            'image_path' => 'motion/image.jpg',
            'image_original_name' => 'image.jpg',
            'video_path' => 'motion/video.mp4',
            'video_original_name' => 'video.mp4',
            'dry_run' => false,
        ]);

        $this->delete(route('roboneo-accounts.destroy', $account))
            ->assertSessionHasErrors([
                'account' => 'Không thể xóa tài khoản đã được sử dụng bởi job.',
            ]);

        $this->assertModelExists($account);
    }

    public function test_saved_personal_token_can_be_verified_again(): void
    {
        config()->set('roboneo.credentials.app_token', 'authorized-app-token');
        $account = RoboNeoAccount::factory()->create([
            'access_token' => 'saved-personal-access-token',
            'uid' => null,
            'last_verified_at' => null,
            'last_error' => 'Previous error',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            '*users/show_current*' => Http::response([
                'meta' => ['code' => 0],
                'response' => ['user' => ['id' => 'uid-verified']],
            ]),
            '*initconfig' => Http::response(['error_code' => 0, 'parameter' => []]),
        ]);

        $this->post(route('roboneo-accounts.verify', $account))
            ->assertRedirect(route('roboneo-accounts.index'));

        $account->refresh();
        $this->assertSame('uid-verified', $account->uid);
        $this->assertNotNull($account->last_verified_at);
        $this->assertNull($account->last_error);
    }

    public function test_unused_default_account_can_be_deleted_and_replaced(): void
    {
        $default = RoboNeoAccount::factory()->default()->create();
        $replacement = RoboNeoAccount::factory()->create(['label' => 'Replacement']);

        $this->delete(route('roboneo-accounts.destroy', $default))
            ->assertRedirect(route('roboneo-accounts.index'));

        $this->assertModelMissing($default);
        $this->assertTrue($replacement->fresh()->is_default);
    }
}
