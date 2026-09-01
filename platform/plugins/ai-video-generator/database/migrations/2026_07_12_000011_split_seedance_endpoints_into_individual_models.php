<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::transaction(function (): void {
            $source = DB::table('ai_video_model_endpoints')
                ->where('code', 'seedance-2-0')
                ->first();

            if (! $source) {
                return;
            }

            $defaults = json_decode($source->defaults ?: '{}', true) ?: [];
            unset($defaults['quality']);

            $variants = [
                ['quality' => '480p', 'endpoint' => 'ai/video/seedance-2-pro-480p'],
                ['quality' => '720p', 'endpoint' => 'ai/video/seedance-2-pro-720p'],
                ['quality' => '1080p', 'endpoint' => 'ai/video/seedance-2-pro-1080p'],
            ];

            DB::table('ai_video_model_endpoints')->where('id', $source->id)->delete();

            foreach ($variants as $variant) {
                DB::table('ai_video_model_endpoints')->insert([
                    'model_id' => $source->model_id,
                    'name' => 'Seedance 2.0 - ' . $variant['quality'],
                    'code' => 'seedance-2-0-' . $variant['quality'],
                    'endpoint' => $variant['endpoint'],
                    'endpoint_field' => null,
                    'endpoints' => null,
                    'fields' => $source->fields,
                    'required_fields' => $source->required_fields,
                    'defaults' => json_encode($defaults),
                    'durations' => $source->durations,
                    'qualities' => json_encode([]),
                    'aspect_ratios' => $source->aspect_ratios,
                    'character_orientations' => $source->character_orientations,
                    'shot_types' => $source->shot_types,
                    'options' => $source->options,
                    'price' => $source->price,
                    'status' => $source->status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $seedanceEndpoints = DB::table('ai_video_model_endpoints')
                ->whereIn('code', ['seedance-2-0-480p', 'seedance-2-0-720p', 'seedance-2-0-1080p'])
                ->orderBy('id')
                ->get();

            $source = $seedanceEndpoints->first();

            if (! $source) {
                return;
            }

            $defaults = json_decode($source->defaults ?: '{}', true) ?: [];
            $defaults['quality'] = '720p';

            DB::table('ai_video_model_endpoints')
                ->whereIn('id', $seedanceEndpoints->pluck('id'))
                ->delete();

            DB::table('ai_video_model_endpoints')->insert([
                'model_id' => $source->model_id,
                'name' => 'Seedance 2.0',
                'code' => 'seedance-2-0',
                'endpoint' => 'ai/video/seedance-2-pro-720p',
                'endpoint_field' => 'quality',
                'endpoints' => json_encode([
                    '480p' => 'ai/video/seedance-2-pro-480p',
                    '720p' => 'ai/video/seedance-2-pro-720p',
                    '1080p' => 'ai/video/seedance-2-pro-1080p',
                ]),
                'fields' => $source->fields,
                'required_fields' => $source->required_fields,
                'defaults' => json_encode($defaults),
                'durations' => $source->durations,
                'qualities' => json_encode([
                    ['value' => '480p', 'label' => '480p'],
                    ['value' => '720p', 'label' => '720p'],
                    ['value' => '1080p', 'label' => '1080p'],
                ]),
                'aspect_ratios' => $source->aspect_ratios,
                'character_orientations' => $source->character_orientations,
                'shot_types' => $source->shot_types,
                'options' => $source->options,
                'price' => $source->price,
                'status' => $source->status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
};
