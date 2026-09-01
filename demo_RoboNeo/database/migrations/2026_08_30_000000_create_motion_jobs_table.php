<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motion_jobs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('status', 40)->index();
            $table->text('prompt');
            $table->string('quality', 12)->default('std');
            $table->unsignedTinyInteger('duration_seconds');
            $table->unsignedInteger('quoted_cost')->nullable();
            $table->string('image_path');
            $table->string('image_original_name');
            $table->string('video_path');
            $table->string('video_original_name');
            $table->string('room_id')->nullable()->index();
            $table->string('task_id')->nullable()->unique();
            $table->string('motion_node_id')->nullable();
            $table->json('image_asset')->nullable();
            $table->json('video_asset')->nullable();
            $table->longText('session_data')->nullable();
            $table->text('result_url')->nullable();
            $table->text('result_cover_url')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('raw_status')->nullable();
            $table->unsignedSmallInteger('poll_attempts')->default(0);
            $table->boolean('dry_run')->default(true);
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motion_jobs');
    }
};
