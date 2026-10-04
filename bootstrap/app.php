<?php

use App\Http\Middleware\EnsureRole;
use App\Support\Catalog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureRole::class]);
        $middleware->redirectUsersTo(fn (Request $request) => route(Catalog::HOME[$request->user()->role] ?? 'profil.show'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Token CSRF kedaluwarsa (419), biasanya karena sesi habis setelah halaman lama dibiarkan terbuka.
        // Daripada menampilkan "Page Expired", arahkan pengguna kembali dengan pesan yang jelas.
        // Laravel sudah mengubah TokenMismatchException menjadi HttpException 419 sebelum callback ini dipanggil.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi berakhir. Muat ulang halaman lalu masuk lagi.', 'expired_session' => true], 419);
            }
            if ($request->routeIs('logout') || ! $request->user()) {
                return redirect()->route('login')->with('toast', 'Sesi Anda sudah berakhir. Silakan masuk lagi.');
            }

            return redirect()->back()->withInput($request->except('password', 'password_confirmation', 'current_password', '_token'))
                ->with('toast', 'Halaman terlalu lama dibuka. Periksa isian lalu kirim ulang.');
        });
    })->create();
