<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kode akun (bagan akun gaya SAK EMKM). Kelompok ditentukan dari awalan kode:
 * 1-1xxx Kas & Bank (sumber dana pengeluaran), 5- Beban Pokok Pendapatan, 6- Beban Operasional, 8- Beban Lain-lain.
 */
class Account extends Model
{
    /** Awalan kode → [kunci kelompok, label]. Urutan ini dipakai untuk mengelompokkan tampilan. */
    public const GROUPS = [
        '1' => ['kas_bank', 'Kas & Bank'],
        '5' => ['beban_pokok', 'Beban Pokok Pendapatan'],
        '6' => ['beban_operasional', 'Beban Operasional'],
        '8' => ['beban_lain', 'Beban Lain-lain'],
    ];

    /** Format kode: 1-11xx/1-12xx untuk kas & bank, 5-/6-/8- diikuti 4 digit untuk beban. */
    public const KODE_REGEX = '/^(1-1[12]\d{2}|[568]-\d{4})$/';

    protected $fillable = ['kode', 'nama', 'keterangan', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function cashExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'cash_account_id');
    }

    public function scopeCash(Builder $q): Builder
    {
        return $q->where('kode', 'like', '1-%');
    }

    public function scopeExpense(Builder $q): Builder
    {
        return $q->where('kode', 'not like', '1-%');
    }

    public function getKelompokAttribute(): string
    {
        return self::GROUPS[substr((string) $this->kode, 0, 1)][0] ?? 'lain';
    }

    public function getKelompokLabelAttribute(): string
    {
        return self::GROUPS[substr((string) $this->kode, 0, 1)][1] ?? 'Lainnya';
    }

    public function getIsCashAttribute(): bool
    {
        return $this->kelompok === 'kas_bank';
    }

    /** "6-1301 · Beban Listrik" */
    public function getLabelAttribute(): string
    {
        return "{$this->kode} · {$this->nama}";
    }

    /** Jumlah transaksi yang memakai akun ini, per peran (beban / sumber dana). */
    public function deleteBlockers(): array
    {
        return array_filter([
            'transaksi pengeluaran (akun beban)' => $this->expenses()->count(),
            'transaksi pengeluaran (sumber dana)' => $this->cashExpenses()->count(),
        ]);
    }
}
