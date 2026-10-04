<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['student_id', 'installment_no', 'amount', 'method', 'status', 'paid_at', 'proof_path', 'verified_by'];

    protected function casts(): array
    {
        return ['paid_at' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
