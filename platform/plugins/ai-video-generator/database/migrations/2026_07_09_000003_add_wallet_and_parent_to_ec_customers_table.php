<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ec_customers')) {
            return;
        }

        Schema::table('ec_customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('ec_customers', 'credits_balance')) {
                $table->unsignedInteger('credits_balance')->default(0)->after('private_notes');
            }

            if (! Schema::hasColumn('ec_customers', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('credits_balance')
                    ->constrained('ec_customers')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ec_customers')) {
            return;
        }

        Schema::table('ec_customers', function (Blueprint $table): void {
            if (Schema::hasColumn('ec_customers', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }

            if (Schema::hasColumn('ec_customers', 'credits_balance')) {
                $table->dropColumn('credits_balance');
            }
        });
    }
};
