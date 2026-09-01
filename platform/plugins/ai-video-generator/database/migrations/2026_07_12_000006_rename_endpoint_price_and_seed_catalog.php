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
            $table->decimal('price', 12, 4)->default(0)->after('options');
        });

        DB::table('ai_video_model_endpoints')->update(['price' => DB::raw('credits_per_second')]);

        Schema::table('ai_video_model_endpoints', function (Blueprint $table): void {
            $table->dropColumn('credits_per_second');
        });

        $catalog = app(MagnificApiCatalog::class);

        foreach ($catalog->all() as $item) {
            $model = DB::table('ai_video_models')->updateOrInsert(
                ['code' => $item['provider']],
                ['name' => ucfirst($item['provider']), 'status' => true, 'updated_at' => now(), 'created_at' => now()]
            );

            $modelId = DB::table('ai_video_models')->where('code', $item['provider'])->value('id');
            $endpointMap = $item['endpoints'] ?? [null => $item['endpoint']];

            foreach ($endpointMap as $quality => $endpoint) {
                $config = $item;
                $defaults = $config['defaults'] ?? [];

                if ($quality !== null) {
                    $defaults['quality'] = $quality;
                    $config['qualities'] = array_values(array_filter(
                        $config['qualities'] ?? [],
                        fn (array $option) => $option['value'] === $quality
                    ));
                }

                DB::table('ai_video_model_endpoints')->updateOrInsert(
                    ['model_id' => $modelId, 'endpoint' => $endpoint],
                    [
                        'name' => $item['label'] . ($quality !== null ? ' - ' . $quality : ''),
                        'fields' => json_encode($config['fields'] ?? []),
                        'required_fields' => json_encode($config['required_fields'] ?? []),
                        'defaults' => json_encode($defaults),
                        'durations' => json_encode($config['durations'] ?? []),
                        'qualities' => json_encode($config['qualities'] ?? []),
                        'aspect_ratios' => json_encode($config['aspect_ratios'] ?? []),
                        'character_orientations' => json_encode($config['character_orientations'] ?? []),
                        'shot_types' => json_encode($config['shot_types'] ?? []),
                        'options' => null,
                        'price' => 0,
                        'status' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('ai_video_model_endpoints', function (Blueprint $table): void {
            $table->decimal('credits_per_second', 12, 4)->default(0)->after('options');
        });

        DB::table('ai_video_model_endpoints')->update(['credits_per_second' => DB::raw('price')]);

        Schema::table('ai_video_model_endpoints', function (Blueprint $table): void {
            $table->dropColumn('price');
        });
    }
};
