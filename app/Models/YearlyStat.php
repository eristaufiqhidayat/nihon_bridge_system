<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YearlyStat extends Model
{
    protected $fillable = ['year', 'peserta', 'kelas', 'lulus', 'berangkat', 'levels'];

    protected function casts(): array
    {
        return ['levels' => 'array'];
    }
}
