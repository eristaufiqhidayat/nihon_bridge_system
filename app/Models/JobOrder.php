<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOrder extends Model
{
    protected $fillable = ['code', 'company_id', 'posisi', 'jalur', 'kuota', 'syarat', 'interview', 'status'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(JobCandidate::class);
    }

    public static function nextCode(): string
    {
        $year = now()->year;
        $max = static::where('code', 'like', "JO-$year-%")->max('code');
        $n = $max ? ((int) substr($max, -3)) + 1 : 1;

        return "JO-$year-" . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }
}
