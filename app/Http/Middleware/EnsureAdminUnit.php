<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses hanya untuk Admin Unit sekolah (MI / MTS / SMK).
 *
 * Dipakai pada menu operasional unit (master data, pendataan barang,
 * mutasi barang) yang secara sengaja dihilangkan dari sisi Yayasan.
 */
class EnsureAdminUnit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isSuperAdmin()) {
            if ($request->expectsJson()) {
                abort(403, 'Menu ini hanya tersedia untuk Admin Unit Sekolah.');
            }

            return redirect()->route('dashboard.yayasan')
                ->with('error', 'Menu tersebut dikelola oleh Admin Unit Sekolah, bukan Admin Yayasan.');
        }

        if (! $user->unit_id) {
            return redirect()->route('dashboard')
                ->with('error', 'Akun Anda belum terhubung ke unit sekolah mana pun. Hubungi Admin Yayasan.');
        }

        return $next($request);
    }
}
