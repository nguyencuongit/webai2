<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ai_video_tasks')) {
            return;
        }

        Schema::create('ai_video_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->index();
            $table->string('task_id')->unique();
            $table->string('status', 60)->default('CREATED')->index();
            $table->boolean('is_completed')->default(false)->index();
            $table->json('generated')->nullable();
            $table->json('has_nsfw')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_video_tasks');
    }
};
