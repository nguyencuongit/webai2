<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::table('ai_video_model_endpoints')
            ->whereNull('endpoint_field')
            ->orderBy('id')
            ->each(function (object $endpoint): void {
                $defaults = json_decode($endpoint->defaults ?: '{}', true) ?: [];

                if (($defaults['quality'] ?? null) !== '') {
                    return;
                }

                unset($defaults['quality']);

                DB::table('ai_video_model_endpoints')
                    ->where('id', $endpoint->id)
                    ->update(['defaults' => json_encode($defaults), 'updated_at' => now()]);
            });
    }

    public function down(): void
    {
    }
};
