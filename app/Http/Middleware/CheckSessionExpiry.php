<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionExpiry
{
    private const HOURS = 7;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $loginAt = $request->session()->get('login_at');

            if ($loginAt && now()->diffInHours($loginAt) >= self::HOURS) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('status', 'Sua sessão expirou após 7 horas. Por favor, faça login novamente.');
            }
        }

        return $next($request);
    }
}
