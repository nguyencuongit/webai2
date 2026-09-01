<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_video_api_tokens', function (Blueprint $table): void {
            $table->string('webhook_secret')->nullable()->after('token_api');
        });
    }

    public function down(): void
    {
        Schema::table('ai_video_api_tokens', function (Blueprint $table): void {
            $table->dropColumn('webhook_secret');
        });
    }
};
