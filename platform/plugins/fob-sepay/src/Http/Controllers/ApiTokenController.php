<?php

namespace FriendsOfBotble\SePay\Http\Controllers;

use FriendsOfBotble\SePay\SePayClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiTokenController
{
    public function connect(Request $request, SePayClient $client): RedirectResponse
    {
       
        $validated = $request->validate([
            'api_token' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $client->connectWithApiToken($validated['api_token']);
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->withErrors(['api_token' => $exception->getMessage()]);
        }

        return back()->with('success', 'SePay API token connected successfully.');
    }
}
