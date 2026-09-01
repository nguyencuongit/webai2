<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('payment_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('payment_logs', 'payment_id')) {
                $table->unsignedBigInteger('payment_id')->nullable()->index()->after('id');
            }

            if (! Schema::hasColumn('payment_logs', 'charge_id')) {
                $table->string('charge_id', 120)->nullable()->index()->after('payment_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('payment_logs', 'charge_id')) {
                $table->dropIndex(['charge_id']);
                $table->dropColumn('charge_id');
            }

            if (Schema::hasColumn('payment_logs', 'payment_id')) {
                $table->dropIndex(['payment_id']);
                $table->dropColumn('payment_id');
            }
        });
    }
};

