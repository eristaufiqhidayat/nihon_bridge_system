<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['icon', 'color', 'message', 'created_at'];

    public static function log(string $icon, string $color, string $message): self
    {
        return static::create(compact('icon', 'color', 'message'));
    }
}
