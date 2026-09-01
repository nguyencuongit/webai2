<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ai_video_credit_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('credits');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_video_credit_packages');
    }
};
