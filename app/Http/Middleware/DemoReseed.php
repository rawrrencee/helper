<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DemoReseed
{
    /**
     * Reseed the database when the demo has gone stale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('demo.enabled')) {
            return $next($request);
        }

        if (! $request->isMethod('POST') || ! $request->routeIs('login')) {
            return $next($request);
        }

        $lastLogin = Cache::get('demo:last_login_at');
        $staleMinutes = config('demo.stale_minutes');

        if ($lastLogin === null || $lastLogin->diffInMinutes(now()) >= $staleMinutes) {
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        }

        return $next($request);
    }
}
