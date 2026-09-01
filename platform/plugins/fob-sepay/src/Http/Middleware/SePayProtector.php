<?php

namespace FriendsOfBotble\SePay\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SePayProtector
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $this->apiToken($request);
        $storedApiKey = setting('sepay_api_key');

        if (
            ! $apiKey
            || ! $storedApiKey
            || ! hash_equals($storedApiKey, $apiKey)
        ) {
            Log::warning('SePay webhook rejected by authentication.', [
                'path' => $request->path(),
                'has_authorization_header' => $request->hasHeader('Authorization'),
                'content_type' => $request->header('Content-Type'),
            ]);

            return response()->json(['message' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }

    public function apiToken(Request $request): string
    {
        $header = $request->header('Authorization', '');

        if (! str_contains($header, 'Apikey ')) {
            return false;
        }

        $apiKey = str_replace('Apikey ', '', $header);

        return trim($apiKey);
    }
}
