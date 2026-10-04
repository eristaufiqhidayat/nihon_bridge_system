<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['nama', 'kota', 'bidang', 'sejak'];

    public function jobOrders(): HasMany
    {
        return $this->hasMany(JobOrder::class);
    }
}
