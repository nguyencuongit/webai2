<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\AiVideoGenerator\Repositories\Eloquent\AiVideoApiTokenRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Models/AiVideoApiToken.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Interfaces/AiVideoApiTokenInterface.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Eloquent/AiVideoApiTokenRepository.php';

class AiVideoApiTokenRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('ai_video_api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('token_api');
            $table->string('webhook_secret')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function test_it_returns_every_active_token_and_excludes_inactive_tokens(): void
    {
        AiVideoApiToken::query()->insert([
            ['id' => 9, 'name' => 'expired', 'token_api' => 'token-nine', 'status' => false],
            ['id' => 10, 'name' => 'active-a', 'token_api' => 'token-ten', 'status' => true],
            ['id' => 11, 'name' => 'active-b', 'token_api' => 'token-eleven', 'status' => true],
        ]);

        $repository = new AiVideoApiTokenRepository(new AiVideoApiToken);

        $this->assertSame([
            ['id' => 10, 'token_api' => 'token-ten'],
            ['id' => 11, 'token_api' => 'token-eleven'],
        ], $repository->getActiveTokens());
    }
}
