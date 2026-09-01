<?php

namespace Botble\AiVideoGenerator\Http\Controllers\API;

use Botble\AiVideoGenerator\Services\AiGenerationService;
use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends BaseController
{
    public function __construct(protected AiGenerationService $aiGenerationService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'AI video generation endpoint scaffold.',
            'data' => $request->only(['prompt', 'image_url', 'duration', 'ratio']),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'AI video status endpoint scaffold.',
            'data' => [
                'id' => $id,
            ],
        ]);
    }
}
