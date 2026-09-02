<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_video_external_tasks')) {
            return;
        }

        Schema::create('ai_video_external_tasks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('task_id')->unique();
            $table->string('url_image', 2000);
            $table->string('url_video', 2000);
            $table->string('status', 30)->default('PROCESSING')->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_video_external_tasks');
    }
};
