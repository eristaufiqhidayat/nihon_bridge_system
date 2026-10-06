<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu tahap pembayaran angkatan, dengan tanggal jatuh temponya. */
class BatchInstallment extends Model
{
    protected $fillable = ['batch_id', 'tahap', 'jatuh_tempo'];

    protected function casts(): array
    {
        return ['tahap' => 'integer', 'jatuh_tempo' => 'date'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
