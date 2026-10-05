<?php

namespace App\Models;

use App\Support\Catalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $fillable = ['batch_id', 'kode', 'level', 'nama', 'wali_id', 'ruang', 'periode'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function wali(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function examSchedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class);
    }

    /**
     * Grid jadwal [slot][day] => Schedule|null.
     *
     * @return array<int, array<int, Schedule|null>>
     */
    public function grid(): array
    {
        $grid = [];
        foreach (array_keys(Catalog::SLOTS) as $s) {
            foreach (array_keys(Catalog::DAYS) as $d) {
                $grid[$s][$d] = null;
            }
        }
        foreach ($this->schedules()->with('instructor')->get() as $row) {
            $grid[$row->slot][$row->day] = $row;
        }

        return $grid;
    }
}
