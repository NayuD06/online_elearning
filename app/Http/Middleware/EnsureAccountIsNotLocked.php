<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsNotLocked
{
    /**
     * Sign out a user whose account an admin locked while they were still signed in.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isLocked()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => User::LOCKED_MESSAGE]);
        }

        return $next($request);
    }
}
