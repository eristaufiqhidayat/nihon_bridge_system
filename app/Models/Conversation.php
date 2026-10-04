<?php

namespace App\Models;

use App\Support\Fmt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['title', 'is_group', 'classroom_id'];

    protected function casts(): array
    {
        return ['is_group' => 'boolean'];
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')->withPivot('last_read_at');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at')->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /** Lawan bicara pada percakapan pribadi. */
    public function other(User $me): ?User
    {
        return $this->participants->firstWhere('id', '!=', $me->id);
    }

    public function displayName(User $me): string
    {
        if ($this->is_group || $this->title) {
            return $this->title ?? 'Grup';
        }
        $o = $this->other($me);
        if (! $o) {
            return '–';
        }

        return match ($o->role) {
            'instruktur' => $o->sensei_name,
            'admin' => $me->role === 'direktur' ? $o->name . ' · Admin' : 'Admin LPK · ' . explode(' ', $o->name)[0],
            'direktur' => 'Direktur',
            default => $o->name,
        };
    }

    public function displayInitials(User $me): string
    {
        if ($this->is_group) {
            return $this->classroom ? substr($this->classroom->kode, 1, 1) . substr($this->classroom->kode, -1) : 'GR';
        }
        $o = $this->other($me);

        return $o ? ($o->role === 'instruktur' ? Fmt::initials($o->sensei_name) : ($o->role === 'direktur' ? 'DR' : $o->initials)) : '?';
    }

    public function unreadFor(User $me): int
    {
        $read = $this->participants->firstWhere('id', $me->id)?->pivot?->last_read_at;

        return $this->messages()->where('user_id', '!=', $me->id)
            ->when($read, fn ($q) => $q->where('created_at', '>', $read))->count();
    }
}
