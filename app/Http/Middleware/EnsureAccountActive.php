<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->account_status === 'suspended'
            && $user->suspended_until !== null
            && $user->suspended_until->isPast()) {
            $user->update([
                'account_status' => 'active',
                'suspended_until' => null,
                'suspension_reason' => null,
            ]);
        }

        if ($user && in_array($user->account_status, ['suspended', 'banned'], true)) {
            $noticeType = $user->account_status === 'suspended' ? 'account_suspended' : 'account_banned';

            return response()->json([
                'message' => 'A sua conta não tem acesso a esta funcionalidade.',
                'account_status' => $user->account_status,
                'suspended_until' => $user->suspended_until,
                'suspension_reason' => $user->suspension_reason,
                'notice' => $user->notifications()->where('type', $noticeType)->latest()->value('message'),
            ], 403);
        }

        return $next($request);
    }
}
