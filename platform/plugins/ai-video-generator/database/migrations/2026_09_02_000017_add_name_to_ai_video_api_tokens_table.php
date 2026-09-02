<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_video_api_tokens', 'name')) {
            Schema::table('ai_video_api_tokens', function (Blueprint $table): void {
                $table->string('name')->nullable()->after('id');
            });
        }

        DB::table('ai_video_api_tokens')
            ->whereNull('name')
            ->orderBy('id')
            ->each(function (object $token): void {
                DB::table('ai_video_api_tokens')
                    ->where('id', $token->id)
                    ->update(['name' => 'API token #'.$token->id]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_video_api_tokens', 'name')) {
            Schema::table('ai_video_api_tokens', function (Blueprint $table): void {
                $table->dropColumn('name');
            });
        }
    }
};
