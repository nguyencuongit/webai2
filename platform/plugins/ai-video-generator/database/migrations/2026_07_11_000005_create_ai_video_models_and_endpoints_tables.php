<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ai_video_models')) {
            Schema::create('ai_video_models', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code', 120)->unique();
                $table->boolean('status')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_video_model_endpoints')) {
            Schema::create('ai_video_model_endpoints', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('model_id')
                    ->constrained('ai_video_models')
                    ->cascadeOnDelete();
                $table->string('name');
                $table->string('endpoint', 255);

                // Các key API sẽ nhận, ví dụ: prompt, duration, image, webhook_url.
                $table->json('fields')->nullable();
                $table->json('required_fields')->nullable();
                $table->json('defaults')->nullable();

                // Danh sách giá trị để UI/API chọn; endpoint không dùng thì để NULL.
                $table->json('durations')->nullable();
                $table->json('qualities')->nullable();
                $table->json('aspect_ratios')->nullable();
                $table->json('character_orientations')->nullable();
                $table->json('shot_types')->nullable();
                $table->json('options')->nullable();

                // Giá bán theo credit cho một giây video.
                $table->decimal('credits', 12, 4)->default(0);
                $table->boolean('status')->default(true)->index();
                $table->timestamps();

                $table->unique(['model_id', 'endpoint']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_video_model_endpoints');
        Schema::dropIfExists('ai_video_models');
    }
};
