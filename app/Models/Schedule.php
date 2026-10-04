<?php

namespace App\Models;

use App\Support\Catalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    protected $fillable = ['classroom_id', 'day', 'slot', 'subject', 'instructor_id'];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function getSubjectNameAttribute(): string
    {
        return Catalog::MAPEL[$this->subject] ?? $this->subject;
    }

    public function getSubjectBgAttribute(): string
    {
        return Catalog::MAPEL_BG[$this->subject] ?? 'ic-bg-grey';
    }
}
