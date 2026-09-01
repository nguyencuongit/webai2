<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('motion_jobs', function (Blueprint $table): void {
            $table->foreignUlid('roboneo_account_id')
                ->nullable()
                ->after('id')
                ->constrained('robo_neo_accounts')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('motion_jobs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('roboneo_account_id');
        });
    }
};
