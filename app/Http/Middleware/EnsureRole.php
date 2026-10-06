<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi rute untuk peran tertentu. Pemakaian: ->middleware('role:admin,direktur')
 * Peran tambahan diizinkan bila rute termasuk menu yang dipilih untuknya (Role::allowsRoute).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        // Peran tambahan (dari menu Data Role) boleh membuka rute milik menu yang dipilih untuknya.
        $allowed = $user && (in_array($user->role, $roles, true) || $user->roleInfo?->allowsRoute($request->route()?->getName()));
        abort_unless($allowed, 403, 'Halaman ini tidak tersedia untuk peran Anda.');

        return $next($request);
    }
}
