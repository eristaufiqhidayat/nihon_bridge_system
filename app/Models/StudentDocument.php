<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDocument extends Model
{
    protected $fillable = ['student_id', 'doc_key', 'status'];
}
