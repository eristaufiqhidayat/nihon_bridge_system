<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $fillable = ['user_id', 'icon', 'message', 'url', 'read_at', 'created_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
