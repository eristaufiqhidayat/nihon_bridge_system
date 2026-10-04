<?php

namespace App\Models;

use App\Support\Catalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Applicant extends Model
{
    protected $fillable = [
        'reg_no', 'nama', 'nik', 'ttl', 'asal', 'usia', 'pendidikan', 'program', 'level_bahasa', 'referensi', 'hp',
        'email', 'status', 'tes_jadwal', 'alasan', 'classroom_id', 'user_id', 'berkas', 'registered_at',
    ];

    protected function casts(): array
    {
        return ['berkas' => 'array', 'registered_at' => 'date'];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function hasFile(string $key): bool
    {
        return ! empty(($this->berkas ?? [])[$key]);
    }

    /** Label berkas wajib yang belum ada. */
    public function missingFiles(): array
    {
        $labels = ['ktp' => 'KTP', 'ijazah' => 'Ijazah', 'foto' => 'Pas foto', 'izin' => 'Surat izin orang tua'];

        return array_values(array_filter($labels, fn ($l, $k) => ! $this->hasFile($k), ARRAY_FILTER_USE_BOTH));
    }

    public function getStatusLabelAttribute(): string
    {
        return Catalog::APP_STATUS[$this->status][0];
    }

    public function getStatusBadgeAttribute(): string
    {
        return Catalog::APP_STATUS[$this->status][1];
    }

    public static function nextRegNo(): string
    {
        $year = now()->year;
        $max = static::where('reg_no', 'like', "REG-$year-%")->max('reg_no');
        $n = $max ? ((int) substr($max, -4)) + 1 : 1;

        return "REG-$year-" . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}
