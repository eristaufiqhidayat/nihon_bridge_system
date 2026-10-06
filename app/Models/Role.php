<?php

namespace App\Models;

use App\Support\Catalog;
use App\Support\Navigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Peran pengguna. Peran bawaan memakai menu tetap dari Navigation::MENU;
 * peran tambahan hanya mendapat menu yang dipilih admin (plus Pesan dan Profil).
 */
class Role extends Model
{
    protected $fillable = ['key', 'name', 'description', 'menus', 'is_system'];

    protected function casts(): array
    {
        return ['menus' => 'array', 'is_system' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'key');
    }

    /** Item menu sidebar: [route, ikon, label, pola route aktif]. */
    public function menuItems(): array
    {
        if ($this->is_system) {
            return Navigation::MENU[$this->key] ?? [];
        }
        $picked = $this->menus ?? [];

        return [
            ...array_values(array_filter(Navigation::options(), fn ($m) => in_array($m[0], $picked, true))),
            ...Navigation::ALWAYS,
        ];
    }

    /** Route halaman awal setelah login. */
    public function homeRoute(): string
    {
        return Catalog::HOME[$this->key] ?? $this->menuItems()[0][0];
    }

    /** Apakah peran tambahan ini boleh membuka route bernama $routeName (lewat menu yang dipilih). */
    public function allowsRoute(?string $routeName): bool
    {
        if ($this->is_system || $routeName === null) {
            return false;
        }

        return collect($this->menuItems())->contains(fn ($m) => Str::is($m[3], $routeName));
    }

    public function getBadgeAttribute(): string
    {
        return Catalog::ROLE_BADGE[$this->key] ?? 'b-grey';
    }

    /** Kunci unik dari nama, mis. "Staf Keuangan" → "staf-keuangan". */
    public static function keyFor(string $name): string
    {
        $base = substr(Str::slug($name), 0, 34) ?: 'peran';
        $key = $base;
        for ($i = 2; static::where('key', $key)->exists(); $i++) {
            $key = "{$base}-{$i}";
        }

        return $key;
    }
}
