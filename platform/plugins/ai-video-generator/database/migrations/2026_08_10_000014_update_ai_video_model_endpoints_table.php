<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ai_video_model_endpoints')) {
            return;
        }

        Schema::table('ai_video_model_endpoints', function (Blueprint $table): void {
            $table->dropForeign(['model_id']);
            $table->unsignedBigInteger('model_id')->nullable()->change();
            $table->string('endpoint', 255)->nullable()->change();
            $table->string('image')->nullable()->after('endpoint');
            $table->text('description')->nullable()->after('image');
            $table->string('tag', 50)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_video_model_endpoints')) {
            return;
        }

        Schema::table('ai_video_model_endpoints', function (Blueprint $table): void {
            $table->dropColumn(['image', 'description', 'tag']);
            $table->foreign('model_id')
                ->references('id')
                ->on('ai_video_models')
                ->nullOnDelete();
        });
    }
};
