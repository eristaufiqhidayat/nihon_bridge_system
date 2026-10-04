<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function open(Request $request, AppNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return redirect($notification->url ?: url()->previous());
    }

    public function readAll(Request $request, NotificationService $notifier): RedirectResponse
    {
        $notifier->markAllRead($request->user());

        return back()->with('toast', 'Semua notifikasi ditandai dibaca');
    }
}
