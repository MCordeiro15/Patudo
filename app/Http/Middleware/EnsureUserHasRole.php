<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        $hasRole = match ($role) {
            'admin' => $user instanceof User && $user->isAdmin(),
            'petsitter' => $user instanceof User && $user->isPetsitter(),
            'owner' => $user instanceof User && ! $user->isAdmin() && ! $user->isPetsitter(),
            default => false,
        };

        if (! $hasRole) {
            abort_if($request->expectsJson(), 403);

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
