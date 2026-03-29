<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoSecurity
{
    /**
     * Routes that are completely blocked in demo mode.
     *
     * @var array<int, string>
     */
    protected array $blockedRoutes = [
        'user-password.update',
        'profile.destroy',
        'admin-users.store',
        'admin-users.destroy',
        'helpers.reset-password',
        'helpers.destroy',
        'two-factor.enable',
        'two-factor.confirm',
        'two-factor.disable',
        'two-factor.qr-code',
        'two-factor.secret-key',
        'two-factor.recovery-codes',
    ];

    /**
     * Routes where file uploads are blocked in demo mode.
     *
     * @var array<int, string>
     */
    protected array $noUploadRoutes = [
        'documents.store',
        'salary-payments.upload-screenshot',
        'claims.store',
    ];

    /**
     * Apply security restrictions in demo mode.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('demo.enabled')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && in_array($routeName, $this->blockedRoutes, true)) {
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return back()->withErrors(['demo' => 'This action is disabled in demo mode.']);
            }

            abort(403, 'This action is disabled in demo mode.');
        }

        if ($routeName && in_array($routeName, $this->noUploadRoutes, true) && $request->allFiles()) {
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return back()->withErrors(['demo' => 'File uploads are disabled in demo mode.']);
            }

            abort(403, 'File uploads are disabled in demo mode.');
        }

        return $next($request);
    }
}
