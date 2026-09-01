<?php

use Botble\AiVideoGenerator\Api\MagnificApiCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('ai_video_model_endpoints', function (Blueprint $table): void {
            $table->string('code', 120)->nullable()->after('name');
            $table->string('endpoint_field', 120)->nullable()->after('endpoint');
            $table->json('endpoints')->nullable()->after('endpoint_field');
        });

        $catalog = app(MagnificApiCatalog::class);

        foreach ($catalog->all() as $code => $config) {
            $modelId = DB::table('ai_video_models')->where('code', $config['provider'])->value('id');

            if (! $modelId) {
                continue;
            }

            if ($code === 'seedance-2-0') {
                DB::table('ai_video_model_endpoints')
                    ->where('model_id', $modelId)
                    ->whereIn('endpoint', array_values($config['endpoints']))
                    ->delete();

                DB::table('ai_video_model_endpoints')->insert([
                    'model_id' => $modelId,
                    'name' => $config['label'],
                    'code' => $code,
                    'endpoint' => $config['endpoint'],
                    'endpoint_field' => $config['endpoint_field'],
                    'endpoints' => json_encode($config['endpoints']),
                    'fields' => json_encode($config['fields']),
                    'required_fields' => json_encode($config['required_fields'] ?? []),
                    'defaults' => json_encode($config['defaults'] ?? []),
                    'durations' => json_encode($config['durations'] ?? []),
                    'qualities' => json_encode($config['qualities'] ?? []),
                    'aspect_ratios' => json_encode($config['aspect_ratios'] ?? []),
                    'character_orientations' => null,
                    'shot_types' => null,
                    'options' => null,
                    'price' => 0,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('ai_video_model_endpoints')
                ->where('model_id', $modelId)
                ->where('endpoint', $config['endpoint'])
                ->update(['code' => $code, 'updated_at' => now()]);
        }

        Schema::table('ai_video_model_endpoints', function (Blueprint $table): void {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('ai_video_model_endpoints', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'endpoint_field', 'endpoints']);
        });
    }
};
