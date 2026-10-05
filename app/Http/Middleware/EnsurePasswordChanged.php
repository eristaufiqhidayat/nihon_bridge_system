<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengguna dengan password awal (mis. hasil impor data siswa) harus membuat password baru
 * sebelum bisa membuka halaman lain.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->must_change_password && ! $request->routeIs('password.first', 'password.first.update', 'logout')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Buat password baru terlebih dahulu.'], 403)
                : redirect()->route('password.first');
        }

        return $next($request);
    }
}
