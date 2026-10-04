<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Kirim notifikasi dalam aplikasi ke satu atau beberapa pengguna.
     *
     * @param  User|iterable<User>|int  $users
     */
    public function notify($users, string $icon, string $message, ?string $url = null): void
    {
        $ids = match (true) {
            $users instanceof User => [$users->id],
            is_int($users) => [$users],
            default => Collection::make($users)->map(fn ($u) => $u instanceof User ? $u->id : $u)->all(),
        };
        foreach (array_unique($ids) as $id) {
            AppNotification::create(['user_id' => $id, 'icon' => $icon, 'message' => $message, 'url' => $url]);
        }
    }

    public function notifyRole(string|array $roles, string $icon, string $message, ?string $url = null): void
    {
        $this->notify(User::whereIn('role', (array) $roles)->where('is_active', true)->get(), $icon, $message, $url);
    }

    public function unreadCount(User $user): int
    {
        return $user->appNotifications()->whereNull('read_at')->count();
    }

    public function markAllRead(User $user): void
    {
        $user->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);
    }
}
