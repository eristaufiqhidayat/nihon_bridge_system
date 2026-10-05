<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Program kursus, mis. "Pelatihan Bahasa Jepang N5–N4". Berisi beberapa batch (angkatan). */
class Program extends Model
{
    protected $fillable = ['kode', 'nama', 'deskripsi', 'biaya', 'durasi_bulan', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'biaya' => 'integer'];
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
}
