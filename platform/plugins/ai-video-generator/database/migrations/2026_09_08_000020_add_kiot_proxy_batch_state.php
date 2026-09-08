<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_video_kiot_proxy_keys')
            && ! Schema::hasColumn('ai_video_kiot_proxy_keys', 'batch_sealed')) {
            Schema::table('ai_video_kiot_proxy_keys', function (Blueprint $table): void {
                $table->boolean('batch_sealed')->default(false)->after('max_concurrent_tasks')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_video_kiot_proxy_keys')
            && Schema::hasColumn('ai_video_kiot_proxy_keys', 'batch_sealed')) {
            Schema::table('ai_video_kiot_proxy_keys', function (Blueprint $table): void {
                $table->dropColumn('batch_sealed');
            });
        }
    }
};
