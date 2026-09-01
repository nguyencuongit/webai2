<?php

namespace Botble\AiVideoGenerator\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExternalApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) setting('access_token');
        $token = (string) $request->header('token', '');

        if ($expectedToken === '' || $token === '' || ! hash_equals($expectedToken, $token)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
