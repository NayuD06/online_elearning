<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Only let users with one of the given roles through.
     *
     * Usage: ->middleware('role:admin,teacher')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowedRoles = array_map(fn (string $role): UserRole => UserRole::from($role), $roles);

        abort_unless($user && $user->hasRole(...$allowedRoles), Response::HTTP_FORBIDDEN, 'Bạn không có quyền truy cập trang này.');

        return $next($request);
    }
}
