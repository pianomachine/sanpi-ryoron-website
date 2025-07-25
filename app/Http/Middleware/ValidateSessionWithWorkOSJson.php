<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\WorkOS\WorkOS;
use Symfony\Component\HttpFoundation\Response;
use WorkOS\Exception\WorkOSException;

class ValidateSessionWithWorkOSJson
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        WorkOS::configure();

        if (! $request->session()->get('workos_access_token') ||
            ! $request->session()->get('workos_refresh_token')) {
            return $this->logout($request);
        }

        try {
            [$accessToken, $refreshToken] = WorkOS::ensureAccessTokenIsValid(
                $request->session()->get('workos_access_token'),
                $request->session()->get('workos_refresh_token'),
            );

            $request->session()->put('workos_access_token', $accessToken);
            $request->session()->put('workos_refresh_token', $refreshToken);
        } catch (WorkOSException $e) {
            report($e);

            return $this->logout($request);
        }

        return $next($request);
    }

    /**
     * Log the user out of the application.
     */
    protected function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // For JSON requests (AJAX), return JSON error instead of redirect
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => '認証が必要です。再度ログインしてください。'
            ], 401);
        }

        return redirect('/');
    }
}
