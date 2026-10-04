<?php

namespace App\Models;

use App\Support\Catalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'section', 'level', 'question', 'options', 'answer_key', 'explanation', 'passage', 'audio_script', 'created_by'];

    protected function casts(): array
    {
        return ['options' => 'array', 'answer_key' => 'integer'];
    }

    public function getSectionNameAttribute(): string
    {
        return Catalog::sectionName($this->section);
    }

    public function getSectionBadgeAttribute(): string
    {
        return Catalog::SECTIONS[$this->section]['badge'] ?? 'b-grey';
    }

    /** Salinan untuk disimpan di paket ujian. */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'section' => $this->section,
            'level' => $this->level,
            'question' => $this->question,
            'options' => $this->options,
            'key' => $this->answer_key,
            'explanation' => $this->explanation,
            'passage' => $this->passage,
            'audio' => $this->audio_script,
        ];
    }

    public static function nextCode(): string
    {
        $max = static::withTrashed()->where('code', 'like', 'S-3%')->max('code');
        $n = $max ? ((int) substr($max, 2)) + 1 : 3001;

        return 'S-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}
