<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi rute untuk peran tertentu. Pemakaian: ->middleware('role:admin,direktur')
 * Menu yang dicentang/dilepas di Data Role ikut membuka/menutup rute (Role::allowsRoute, Role::deniesRoute).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        // Menu tiap peran diatur di Data Role: menu tambahan membuka rutenya, menu bawaan yang dilepas menutupnya.
        $route = $request->route()?->getName();
        $role = $user?->roleInfo;
        $allowed = $user && ($role?->allowsRoute($route) || (in_array($user->role, $roles, true) && ! $role?->deniesRoute($route)));
        abort_unless($allowed, 403, 'Halaman ini tidak tersedia untuk peran Anda.');

        return $next($request);
    }
}
