<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses hanya untuk Super Admin tingkat Yayasan.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isSuperAdmin()) {
            if ($request->expectsJson()) {
                abort(403, 'Halaman ini hanya dapat diakses oleh Admin Yayasan.');
            }

            return redirect()->route('dashboard')
                ->with('error', 'Halaman tersebut hanya dapat diakses oleh Admin Yayasan.');
        }

        return $next($request);
    }
}
