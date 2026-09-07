<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_video_kiot_proxy_keys')) {
            Schema::create('ai_video_kiot_proxy_keys', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->text('proxy_key');
                $table->string('region', 16)->default('random');
                $table->boolean('is_active')->default(true)->index();
                $table->string('health_status', 32)->default('healthy')->index();
                $table->string('leased_by')->nullable()->index();
                $table->timestamp('lease_until')->nullable()->index();
                $table->text('current_http_proxy')->nullable();
                $table->text('current_socks5_proxy')->nullable();
                $table->string('proxy_fingerprint', 32)->nullable()->index();
                $table->string('location')->nullable();
                $table->timestamp('expiration_at')->nullable();
                $table->timestamp('next_request_at')->nullable();
                $table->boolean('rotate_required')->default(false);
                $table->unsignedSmallInteger('failure_count')->default(0);
                $table->string('last_failure_code')->nullable();
                $table->timestamp('last_failed_at')->nullable();
                $table->timestamp('last_success_at')->nullable();
                $table->timestamp('blocked_until')->nullable()->index();
                $table->timestamp('last_used_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_video_roboneo_network_incidents')) {
            Schema::create('ai_video_roboneo_network_incidents', function (Blueprint $table): void {
                $table->id();
                $table->string('source', 32);
                $table->string('task_reference');
                $table->unsignedBigInteger('api_token_id')->nullable()->index();
                $table->unsignedBigInteger('kiot_proxy_key_id')->nullable()->index();
                $table->string('proxy_fingerprint', 32)->nullable()->index();
                $table->string('code', 64)->index();
                $table->timestamp('occurred_at')->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('ai_video_api_tokens')) {
            Schema::table('ai_video_api_tokens', function (Blueprint $table): void {
                if (! Schema::hasColumn('ai_video_api_tokens', 'health_status')) {
                    $table->string('health_status', 32)->default('healthy')->index();
                }
                if (! Schema::hasColumn('ai_video_api_tokens', 'blocked_until')) {
                    $table->timestamp('blocked_until')->nullable()->index();
                }
                if (! Schema::hasColumn('ai_video_api_tokens', 'busy_strikes')) {
                    $table->unsignedSmallInteger('busy_strikes')->default(0);
                }
                if (! Schema::hasColumn('ai_video_api_tokens', 'last_failure_code')) {
                    $table->string('last_failure_code', 64)->nullable();
                }
                if (! Schema::hasColumn('ai_video_api_tokens', 'last_failed_at')) {
                    $table->timestamp('last_failed_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_video_roboneo_network_incidents');
        Schema::dropIfExists('ai_video_kiot_proxy_keys');

        if (Schema::hasTable('ai_video_api_tokens')) {
            Schema::table('ai_video_api_tokens', function (Blueprint $table): void {
                foreach (['health_status', 'blocked_until', 'busy_strikes', 'last_failure_code', 'last_failed_at'] as $column) {
                    if (Schema::hasColumn('ai_video_api_tokens', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
