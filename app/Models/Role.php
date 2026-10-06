<?php

namespace App\Models;

use App\Support\Catalog;
use App\Support\Navigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Peran pengguna beserta menu yang boleh dibuka (plus Pesan dan Profil yang selalu ada).
 * Peran bawaan memakai menu dari Navigation::MENU sampai admin mengubah centangnya.
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

    /** Menu yang selalu tercentang dan tidak bisa dilepas: Admin harus tetap bisa membuka Dashboard dan Data Role. */
    public const LOCKED = ['admin' => ['admin.dashboard', 'role-admin.index']];

    /** Menu bawaan peran (dari kode), tanpa Pesan dan Profil. */
    public function defaultItems(): array
    {
        $always = array_column(Navigation::ALWAYS, 0);

        return array_values(array_filter(Navigation::MENU[$this->key] ?? [], fn ($m) => ! in_array($m[0], $always, true)));
    }

    /** Menu yang bisa dicentang untuk peran ini. Peserta hanya menu peserta; peran lain menu bawaannya + menu admin. */
    public function options(): array
    {
        if (! $this->is_system) {
            return Navigation::options();
        }
        $items = $this->defaultItems();
        if ($this->key !== 'peserta') {
            $have = array_column($items, 0);
            foreach (Navigation::options() as $m) {
                if (! in_array($m[0], $have, true)) {
                    $items[] = $m;
                }
            }
        }

        return $items;
    }

    public function lockedKeys(): array
    {
        return self::LOCKED[$this->key] ?? [];
    }

    /** Route menu yang aktif untuk peran ini. Peran bawaan yang belum pernah diatur memakai menu bawaannya. */
    public function pickedKeys(): array
    {
        $picked = $this->menus ?? ($this->is_system ? array_column($this->defaultItems(), 0) : []);

        return array_values(array_unique([...$this->lockedKeys(), ...$picked]));
    }

    /** Item menu sidebar: [route, ikon, label, pola route aktif]. */
    public function menuItems(): array
    {
        $picked = $this->pickedKeys();

        return [
            ...array_values(array_filter($this->options(), fn ($m) => in_array($m[0], $picked, true))),
            ...Navigation::ALWAYS,
        ];
    }

    /** Route halaman awal setelah login: halaman awal bawaan bila menunya masih aktif, selain itu menu pertama. */
    public function homeRoute(): string
    {
        $home = Catalog::HOME[$this->key] ?? null;

        return $home && in_array($home, $this->pickedKeys(), true) ? $home : $this->menuItems()[0][0];
    }

    /** Rute dibuka lewat menu tambahan (menu yang dicentang tapi bukan menu bawaan peran ini). */
    public function allowsRoute(?string $routeName): bool
    {
        if ($routeName === null) {
            return false;
        }
        $default = array_column($this->defaultItems(), 0);

        return collect($this->options())
            ->filter(fn ($m) => in_array($m[0], $this->pickedKeys(), true) && ! in_array($m[0], $default, true))
            ->contains(fn ($m) => Str::is($m[3], $routeName));
    }

    /** Rute ditutup karena menu bawaannya dilepas centangnya oleh admin. */
    public function deniesRoute(?string $routeName): bool
    {
        if ($routeName === null || $this->menus === null) {
            return false;
        }
        $picked = $this->pickedKeys();

        return collect($this->defaultItems())
            ->reject(fn ($m) => in_array($m[0], $picked, true))
            ->contains(fn ($m) => Str::is($m[3], $routeName));
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
