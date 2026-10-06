<?php

namespace App\Models;

use App\Support\Catalog;
use App\Support\Fmt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'must_change_password', 'role', 'phone', 'avatar_path', 'is_active', 'notification_prefs'];

    protected $hidden = ['password', 'remember_token'];

    public const DEFAULT_PREFS = ['jadwal' => true, 'hasil' => true, 'pesan' => true, 'mingguan' => false];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'notification_prefs' => 'array',
        ];
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')->withPivot('last_read_at');
    }

    public function waliClasses(): HasMany
    {
        return $this->hasMany(Classroom::class, 'wali_id');
    }

    public function teachingSchedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'instructor_id');
    }

    /** Data peran (tabel roles) yang dipakai pengguna ini. */
    public function roleInfo(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'key');
    }

    /** Route halaman awal setelah login. */
    public function homeRoute(): string
    {
        return $this->roleInfo?->homeRoute() ?? Catalog::HOME[$this->role] ?? 'profil.show';
    }

    /** Menu sidebar sesuai peran. */
    public function menuItems(): array
    {
        return $this->roleInfo?->menuItems() ?? [];
    }

    /**
     * Data yang masih terhubung dengan akun non-peserta: [label => jumlah].
     * Kosong berarti akun aman dihapus. Data peserta dicek lewat Student::deleteBlockers().
     */
    public function deleteBlockers(): array
    {
        $counts = [
            'kelas sebagai wali' => $this->waliClasses()->count(),
            'jadwal mengajar' => $this->teachingSchedules()->count(),
            'sesi kehadiran' => AttendanceSession::where('instructor_id', $this->id)->count(),
            'soal bank soal' => Question::where('created_by', $this->id)->count(),
            'pembayaran diverifikasi' => Payment::where('verified_by', $this->id)->count(),
            'pesan terkirim' => Message::where('user_id', $this->id)->count(),
        ];

        return array_filter($counts);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function getRoleBadgeAttribute(): string
    {
        return Catalog::ROLE_BADGE[$this->role] ?? 'b-grey';
    }

    public function getInitialsAttribute(): string
    {
        return Fmt::initials($this->name);
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->roleInfo?->name ?? Catalog::ROLES[$this->role] ?? $this->role;
    }

    /** "Sato Sensei" untuk instruktur. */
    public function getSenseiNameAttribute(): string
    {
        return explode(' ', $this->name)[0] . ' Sensei';
    }

    /** Baris kecil di bawah nama (sidebar & chip pengguna). */
    public function getSubtitleAttribute(): string
    {
        return match ($this->role) {
            'peserta' => 'Peserta · ' . ($this->student?->classroom?->kode ?? '–'),
            'instruktur' => 'Instruktur · ' . ($this->waliClasses()->value('kode') ?? '–'),
            'admin' => 'Administrator',
            'direktur' => 'Direktur',
            default => $this->role_label,
        };
    }

    public function pref(string $key): bool
    {
        return (bool) (($this->notification_prefs ?? []) + self::DEFAULT_PREFS)[$key];
    }

    /** Kelas yang diajar (wali atau terjadwal mengajar). */
    public function taughtClassroomIds(): array
    {
        return $this->teachingSchedules()->pluck('classroom_id')
            ->merge($this->waliClasses()->pluck('id'))->unique()->values()->all();
    }
}
