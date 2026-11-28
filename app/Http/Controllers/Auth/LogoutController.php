<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse;
use Symfony\Component\HttpFoundation\Response;

class LogoutController
{
    public function __invoke(Request $request): Response
    {
        auth('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return app(LogoutResponse::class)->toResponse($request);
    }
}
