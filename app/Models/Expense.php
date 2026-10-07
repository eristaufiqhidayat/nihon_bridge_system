<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** Transaksi pengeluaran (bukti kas keluar): debit akun beban, kredit akun kas/bank. */
class Expense extends Model
{
    protected $fillable = ['nomor', 'tanggal', 'account_id', 'cash_account_id', 'penerima', 'uraian', 'jumlah', 'user_id'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'jumlah' => 'integer'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Nomor bukti berikutnya per bulan: BKK/2026/10/0001. */
    public static function nextNumber($tanggal): string
    {
        $d = Carbon::parse($tanggal);
        $prefix = sprintf('BKK/%04d/%02d/', $d->year, $d->month);
        $last = static::where('nomor', 'like', $prefix . '%')->orderByDesc('nomor')->value('nomor');

        return $prefix . sprintf('%04d', $last ? (int) substr($last, -4) + 1 : 1);
    }
}
