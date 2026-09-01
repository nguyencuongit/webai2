<?php

use Botble\AiVideoGenerator\Api\MagnificApiCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        foreach (app(MagnificApiCatalog::class)->all() as $code => $config) {
            DB::table('ai_video_model_endpoints')
                ->where('code', $code)
                ->update(['name' => $config['label'], 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
    }
};
