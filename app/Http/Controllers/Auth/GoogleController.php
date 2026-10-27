<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleController extends Controller
{
    /**
     * Send the user to the Google consent screen.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Đăng nhập bằng Google chưa được cấu hình (thiếu GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET).']);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Sign the Google user in: reuse the account with the same Google id or email, otherwise create a student.
     */
    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login');
        }

        if ($request->filled('error')) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Bạn đã huỷ đăng nhập bằng Google.']);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Google sign-in failed: '.$e->getMessage());

            return redirect()->route('login')
                ->withErrors(['email' => 'Không thể đăng nhập bằng Google, vui lòng thử lại.']);
        }

        $email = strtolower((string) $googleUser->getEmail());

        if ($email === '') {
            return redirect()->route('login')
                ->withErrors(['email' => 'Tài khoản Google không cung cấp email.']);
        }

        if (($googleUser->user['email_verified'] ?? true) === false) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Email của tài khoản Google này chưa được xác minh.']);
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            // Never hand an admin account to whoever controls a Google account with the same email.
            if ($user?->isAdmin()) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Tài khoản quản trị phải đăng nhập bằng email và mật khẩu.']);
            }
        }

        if ($user) {
            // Link an existing account (e.g. registered with email + password) to Google; keep its role.
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
                'avatar' => $user->avatar ?: $googleUser->getAvatar(),
            ])->save();
        } else {
            $user = User::forceCreate([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: $email,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => null,
                'role' => UserRole::Student,
                'email_verified_at' => now(),
            ]);

            event(new Registered($user));
        }

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        $homeRoute = $user->managementRoute() ?? 'dashboard';

        return redirect()->intended(route($homeRoute, absolute: false));
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
