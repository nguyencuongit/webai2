<?php

namespace Botble\AiVideoGenerator\Api;

use Illuminate\Http\Request;

class MagnificWebhook
{
    public function handle(Request $request, ?string $provider = null): array
    {
        $rawBody = $request->getContent();
        $payload = $request->all();

        return [
            'provider' => $provider,
            'payload' => $payload,
            'task' => $this->taskData($payload),
            'query' => $request->query(),
            'raw' => $rawBody,
            'headers' => $this->headers($request),
            'signature_verified' => $this->verify($request, $rawBody),
        ];
    }

    public function verify(Request $request, ?string $rawBody = null): ?bool
    {
        $secret = $this->secret();

        if (! $secret) {
            return null;
        }

        $webhookId = $request->header('webhook-id');
        $webhookTimestamp = $request->header('webhook-timestamp');
        $webhookSignature = $request->header('webhook-signature');

        if (! $webhookId || ! $webhookTimestamp || ! $webhookSignature) {
            return false;
        }

        $signature = $this->generateSignature(
            $secret,
            $this->contentToSign($webhookId, $webhookTimestamp, $rawBody ?? $request->getContent())
        );

        return $this->verifySignature($signature, $webhookSignature);
    }

    public function contentToSign(string $webhookId, string $webhookTimestamp, string $body): string
    {
        return "{$webhookId}.{$webhookTimestamp}.{$body}";
    }

    public function generateSignature(string $secret, string $contentToSign): string
    {
        $decodedSecret = base64_decode($secret, true);

        $hmac = hash_hmac(
            'sha256',
            $contentToSign,
            $decodedSecret !== false ? $decodedSecret : $secret,
            true
        );

        return base64_encode($hmac);
    }

    public function verifySignature(string $generatedSignature, string $headerSignatures): bool
    {
        foreach (explode(' ', trim($headerSignatures)) as $signature) {
            $parts = explode(',', $signature, 2);

            if (count($parts) !== 2) {
                continue;
            }

            if (hash_equals($parts[1], $generatedSignature)) {
                return true;
            }
        }

        return false;
    }

    protected function taskData(array $payload): array
    {
        return [
            'task_id' => $payload['task_id'] ?? null,
            'status' => $payload['status'] ?? null,
            'generated' => $payload['generated'] ?? [],
            'has_nsfw' => $payload['has_nsfw'] ?? [],
        ];
    }

    protected function headers(Request $request): array
    {
        return [
            'webhook-id' => $request->header('webhook-id'),
            'webhook-timestamp' => $request->header('webhook-timestamp'),
            'webhook-signature' => $request->header('webhook-signature'),
        ];
    }

    protected function secret(): ?string
    {
        return config('services.magnific.webhook_secret')
            ?: config('plugins.ai-video-generator.general.webhook_secret');
    }
}
