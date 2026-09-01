<?php

namespace FriendsOfBotble\SePay;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SePayClient
{
    public function isConnected(): bool
    {
        return filled(setting()->get('sepay_api_token'));
    }

    public function connectWithApiToken(string $apiToken): void
    {
        $apiToken = trim($apiToken);

        $response = Http::baseUrl('https://userapi.sepay.vn/v2')
        ->withToken($apiToken)
        ->acceptJson()
        ->timeout(15)
        ->get('bank-accounts');

        if (! $response->successful()) {
            throw new Exception($response->json('message') ?: 'SePay API token is invalid.', $response->status());
        }

        $data = $response->json();
        if (($data['status'] ?? 'success') !== 'success') {
            throw new Exception($data['message'] ?? 'SePay API token is invalid.');
        }

        setting()->set([
            'sepay_api_token' => $apiToken,
            'sepay_access_token' => null,
            'sepay_refresh_token' => null,
            'sepay_expired_at' => null,
            'sepay_connected_at' => now(),
        ])->save();

        Cache::forget('sepay.profile');
        Cache::forget('sepay.bank-accounts');
    }

    public function profile(): ?object
    {
        // API Token only grants access to the User API resources. The legacy
        // `me` endpoint rejects this token, so it must not decide whether the
        // account is connected in the payment settings screen.
        if ($this->isConnected()) {
            return null;
        }

        return Cache::remember('sepay.profile', 60 * 60, function () {
            return (object) $this->request('get', 'me');
        });
    }

    public function company(): ?array
    {
        return $this->request('get', 'company');
    }

    public function bankAccounts(): array
    {
        return $this->request('get', 'bank-accounts');
    }

    public function bankAccount($id): ?object
    {
        return Cache::remember("sepay.bank-account.$id", 60 * 60, function () use ($id) {
            return (object) $this->request('get', "bank-accounts/$id");
        });
    }

    public function bankSubAccounts(int $bankAccountId)
    {
        return $this->request('get', "bank-accounts/$bankAccountId/sub-accounts");
    }

    public function webhook(int $id): ?array
    {
        return $this->request('get', "webhooks/$id");
    }

    public function createWebhook(array $data): array
    {
        $apiKey = bin2hex(random_bytes(16));

        setting()->set('sepay_api_key', $apiKey)->save();

        return $this->request('post', 'webhooks', [
            'name' => sprintf('FOB SePay - %s', config('app.name')),
            'event_type' => 'In_only',
            'authen_type' => 'Api_Key',
            'api_key' => $apiKey,
            'webhook_url' => route('sepay.webhook'),
            'is_verify_payment' => 1,
            'skip_if_no_code' => 1,
            'request_content_type' => 'Json',
            'only_va' => 0,
            ...$data,
        ]);
    }

    public function updateWebhook(int $id, array $data): array
    {
        return $this->request('patch', "webhooks/$id", $data);
    }

    public function request(string $method, string $url, array $data = []): array
    {
        try {
            $response = Http::baseUrl('https://userapi.sepay.vn/v2')
                ->withToken($this->apiToken())
                ->acceptJson()
                ->$method($url, $data);

            if (! $response->successful()) {
                throw new Exception($response->json('message') ?: 'Unable to call SePay API.', $response->status());
            }

            $data = $response->json();

            if (isset($data['status']) && $data['status'] !== 'success') {
                throw new Exception($data['message'] ?? $data['messages']['error'], $response->status());
            }

            return $data['data'] ?? [];
        } catch (Exception $e) {
            Log::error('SePay API error: ' . $e->getMessage());

            throw $e;
        }
    }

    protected function apiToken(): string
    {
        $apiToken = setting()->get('sepay_api_token');

        if (! $apiToken) {
            throw new Exception('SePay API token not found. Please configure it in payment settings.');
        }

        return $apiToken;
    }
}
