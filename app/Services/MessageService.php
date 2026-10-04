<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;

class MessageService
{
    public function __construct(private NotificationService $notifier)
    {
    }

    public function conversationsFor(User $user): Collection
    {
        return $user->conversations()->with(['participants', 'latestMessage', 'classroom'])->get()
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at)->values();
    }

    public function unreadTotal(User $user): int
    {
        return $this->conversationsFor($user)->sum(fn ($c) => $c->unreadFor($user));
    }

    public function markRead(Conversation $c, User $user): void
    {
        $c->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);
    }

    public function send(Conversation $c, User $from, string $body): Message
    {
        $m = $c->messages()->create(['user_id' => $from->id, 'body' => $body]);
        $this->markRead($c, $from);
        $c->touch();
        $others = $c->participants->where('id', '!=', $from->id)->filter(fn ($u) => $u->pref('pesan'));
        if (! $c->is_group) {
            $this->notifier->notify($others, '✉️', 'Pesan baru dari ' . ($from->role === 'instruktur' ? $from->sensei_name : $from->name), route('pesan.index', $c));
        }

        return $m;
    }
}
