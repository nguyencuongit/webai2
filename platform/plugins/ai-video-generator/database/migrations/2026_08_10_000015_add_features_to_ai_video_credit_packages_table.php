<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ai_video_credit_packages')) {
            return;
        }

        Schema::table('ai_video_credit_packages', function (Blueprint $table): void {
            $table->text('features')->nullable()->after('credits');
            $table->boolean('is_popular')->default(false)->after('features');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_video_credit_packages')) {
            return;
        }

        Schema::table('ai_video_credit_packages', function (Blueprint $table): void {
            $table->dropColumn(['features', 'is_popular']);
        });
    }
};
