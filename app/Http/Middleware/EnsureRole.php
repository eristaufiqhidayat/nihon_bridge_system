<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi rute untuk peran tertentu. Pemakaian: ->middleware('role:admin,direktur')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, $roles, true), 403, 'Halaman ini tidak tersedia untuk peran Anda.');

        return $next($request);
    }
}
