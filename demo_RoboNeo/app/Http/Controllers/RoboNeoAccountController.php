<?php

namespace App\Http\Controllers;

use App\Exceptions\RoboNeoProtocolException;
use App\Http\Requests\StoreRoboNeoAccountRequest;
use App\Http\Requests\UpdateRoboNeoAccountRequest;
use App\Models\RoboNeoAccount;
use App\Services\RoboNeo\RoboNeoAccountVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoboNeoAccountController extends Controller
{
    public function index(): View
    {
        return view('roboneo-accounts.index', [
            'accounts' => RoboNeoAccount::query()
                ->withCount('motionJobs')
                ->orderByDesc('is_default')
                ->orderBy('label')
                ->get(),
        ]);
    }

    public function store(
        StoreRoboNeoAccountRequest $request,
        RoboNeoAccountVerifier $verifier,
    ): RedirectResponse {
        try {
            $uid = $verifier->verify($request->string('access_token')->toString());
        } catch (RoboNeoProtocolException) {
            throw ValidationException::withMessages([
                'access_token' => 'Personal Access Token không hợp lệ hoặc đã hết hạn.',
            ]);
        }

        $this->ensureUidIsAvailable($uid);

        $account = DB::transaction(function () use ($request, $uid): RoboNeoAccount {
            $account = RoboNeoAccount::query()->create([
                'label' => $request->string('label')->trim()->toString(),
                'access_token' => $request->string('access_token')->toString(),
                'uid' => $uid,
                'is_active' => true,
                'last_verified_at' => now(),
                'last_error' => null,
            ]);

            if ($request->boolean('is_default') || ! RoboNeoAccount::query()->where('is_default', true)->exists()) {
                $account->makeDefault();
            }

            return $account;
        });

        return redirect()->route('roboneo-accounts.index')
            ->with('status', "Đã thêm tài khoản {$account->label}.");
    }

    public function setDefault(RoboNeoAccount $roboNeoAccount): RedirectResponse
    {
        $roboNeoAccount->makeDefault();

        return redirect()->route('roboneo-accounts.index')
            ->with('status', "Đã đặt {$roboNeoAccount->label} làm tài khoản mặc định.");
    }

    public function edit(RoboNeoAccount $roboNeoAccount): View
    {
        return view('roboneo-accounts.edit', ['account' => $roboNeoAccount]);
    }

    public function update(
        UpdateRoboNeoAccountRequest $request,
        RoboNeoAccount $roboNeoAccount,
        RoboNeoAccountVerifier $verifier,
    ): RedirectResponse {
        $token = $request->string('access_token')->trim()->toString();
        $uid = $roboNeoAccount->uid;

        if ($token !== '') {
            try {
                $uid = $verifier->verify($token);
            } catch (RoboNeoProtocolException) {
                throw ValidationException::withMessages([
                    'access_token' => 'Personal Access Token không hợp lệ hoặc đã hết hạn.',
                ]);
            }

            $this->ensureUidIsAvailable($uid, $roboNeoAccount);
        }

        DB::transaction(function () use ($request, $roboNeoAccount, $token, $uid): void {
            $wasDefault = $roboNeoAccount->is_default;
            $attributes = [
                'label' => $request->string('label')->trim()->toString(),
                'is_active' => $request->boolean('is_active'),
            ];

            if ($token !== '') {
                $attributes += [
                    'access_token' => $token,
                    'uid' => $uid,
                    'last_verified_at' => now(),
                    'last_error' => null,
                ];
            }

            $roboNeoAccount->update($attributes);

            if ($request->boolean('is_default')) {
                $roboNeoAccount->makeDefault();

                return;
            }

            if ($wasDefault && ! $roboNeoAccount->is_active) {
                $roboNeoAccount->update(['is_default' => false]);
                $this->promoteFirstActiveAccount();
            }
        });

        return redirect()->route('roboneo-accounts.index')
            ->with('status', "Đã cập nhật {$roboNeoAccount->label}.");
    }

    public function verify(
        RoboNeoAccount $roboNeoAccount,
        RoboNeoAccountVerifier $verifier,
    ): RedirectResponse {
        try {
            $uid = $verifier->verify($roboNeoAccount->access_token);
            $this->ensureUidIsAvailable($uid, $roboNeoAccount);
        } catch (RoboNeoProtocolException) {
            $roboNeoAccount->update(['last_error' => 'Personal Access Token không hợp lệ hoặc đã hết hạn.']);

            return back()->withErrors([
                'account' => 'Personal Access Token không hợp lệ hoặc đã hết hạn.',
            ]);
        }

        $roboNeoAccount->update([
            'uid' => $uid,
            'last_verified_at' => now(),
            'last_error' => null,
        ]);

        return redirect()->route('roboneo-accounts.index')
            ->with('status', "Token của {$roboNeoAccount->label} đang hoạt động.");
    }

    public function destroy(RoboNeoAccount $roboNeoAccount): RedirectResponse
    {
        if ($roboNeoAccount->motionJobs()->exists()) {
            return back()->withErrors([
                'account' => 'Không thể xóa tài khoản đã được sử dụng bởi job.',
            ]);
        }

        DB::transaction(function () use ($roboNeoAccount): void {
            $wasDefault = $roboNeoAccount->is_default;
            $roboNeoAccount->delete();

            if ($wasDefault) {
                $this->promoteFirstActiveAccount();
            }
        });

        return redirect()->route('roboneo-accounts.index')
            ->with('status', 'Đã xóa tài khoản RoboNeo.');
    }

    private function ensureUidIsAvailable(string $uid, ?RoboNeoAccount $except = null): void
    {
        $query = RoboNeoAccount::query()->where('uid', $uid);

        if ($except !== null) {
            $query->whereKeyNot($except->getKey());
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'access_token' => 'Tài khoản RoboNeo này đã được thêm trước đó.',
            ]);
        }
    }

    private function promoteFirstActiveAccount(): void
    {
        RoboNeoAccount::query()
            ->where('is_active', true)
            ->orderBy('label')
            ->first()
            ?->makeDefault();
    }
}
