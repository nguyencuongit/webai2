<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_video_kiot_proxy_keys')
            && ! Schema::hasColumn('ai_video_kiot_proxy_keys', 'max_concurrent_tasks')) {
            Schema::table('ai_video_kiot_proxy_keys', function (Blueprint $table): void {
                $table->unsignedSmallInteger('max_concurrent_tasks')->default(5)->after('is_active');
            });
        }

        if (! Schema::hasTable('ai_video_kiot_proxy_leases')) {
            Schema::create('ai_video_kiot_proxy_leases', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('kiot_proxy_key_id')->index();
                $table->string('owner')->unique();
                $table->timestamp('lease_until')->index();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_video_kiot_proxy_leases');

        if (Schema::hasTable('ai_video_kiot_proxy_keys')
            && Schema::hasColumn('ai_video_kiot_proxy_keys', 'max_concurrent_tasks')) {
            Schema::table('ai_video_kiot_proxy_keys', function (Blueprint $table): void {
                $table->dropColumn('max_concurrent_tasks');
            });
        }
    }
};
