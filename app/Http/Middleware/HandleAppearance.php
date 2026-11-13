<?php


/**
 * Middleware que controla o tema de aparência (claro/escuro) do utilizador. Salva e restaura a preferência de tema.
 * Middleware that controls the user's appearance theme (light/dark). Saves and restores theme preference.
 */
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('appearance', $request->cookie('appearance') ?? 'system');

        return $next($request);
    }
}
