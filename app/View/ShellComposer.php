<?php

namespace App\View;

use App\Services\MessageService;
use App\Support\Navigation;
use Illuminate\View\View;

/**
 * Data untuk kerangka aplikasi (sidebar, topbar, notifikasi).
 */
class ShellComposer
{
    public function __construct(private MessageService $messages)
    {
    }

    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }
        $notifs = $user->appNotifications()->latest()->limit(8)->get();

        $view->with([
            'me' => $user,
            'menu' => Navigation::MENU[$user->role] ?? [],
            'unreadMessages' => $this->messages->unreadTotal($user),
            'notifs' => $notifs,
            'notifUnread' => $user->appNotifications()->whereNull('read_at')->count(),
        ]);
    }
}
