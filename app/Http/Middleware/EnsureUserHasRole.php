<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Allow the request only when the user has one of the given roles.
     *
     * Usage: ->middleware('role:seller') or ->middleware('role:seller,admin')
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowedRoles = array_map(fn (string $role): UserRole => UserRole::from($role), $roles);

        abort_unless(in_array($request->user()?->role, $allowedRoles, true), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
