<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ec_customers')) {
            Schema::create('ec_customers', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('avatar')->nullable();
                $table->date('dob')->nullable();
                $table->string('phone', 20)->nullable()->unique();
                $table->string('status', 60)->default('activated');
                $table->timestamp('confirmed_at')->nullable();
                $table->text('private_notes')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ec_customer_password_resets')) {
            Schema::create('ec_customer_password_resets', function (Blueprint $table): void {
                $table->string('email')->index();
                $table->string('token')->index();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('ec_customer_addresses')) {
            Schema::create('ec_customer_addresses', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email', 60)->nullable();
                $table->string('phone', 20);
                $table->string('country', 120)->nullable();
                $table->string('state', 120)->nullable();
                $table->string('city', 120)->nullable();
                $table->string('address');
                $table->foreignId('customer_id');
                $table->tinyInteger('is_default')->default(0)->unsigned();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (! function_exists('is_plugin_active') || ! is_plugin_active('ecommerce')) {
            Schema::dropIfExists('ec_customer_addresses');
            Schema::dropIfExists('ec_customer_password_resets');
            Schema::dropIfExists('ec_customers');
        }
    }
};
