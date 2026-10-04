<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\User;
use App\Support\Catalog;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function __construct(private NotificationService $notifier)
    {
    }

    /** Isi / kosongkan satu sel jadwal; tolak bila instruktur bentrok dengan kelas lain. */
    public function saveSlot(Classroom $class, int $slot, int $day, ?string $subject, ?int $instructorId): ?Schedule
    {
        if (! $subject) {
            Schedule::where(['classroom_id' => $class->id, 'slot' => $slot, 'day' => $day])->delete();

            return null;
        }

        $clash = Schedule::with('classroom')->where('instructor_id', $instructorId)->where('slot', $slot)->where('day', $day)
            ->where('classroom_id', '!=', $class->id)->first();
        if ($clash) {
            $name = User::find($instructorId)?->name;
            throw ValidationException::withMessages([
                'slot' => "$name sudah mengajar {$clash->classroom->kode} (" . Catalog::MAPEL[$clash->subject] . ') pada '
                    . Catalog::DAYS[$day] . ' ' . Catalog::SLOTS[$slot] . '. Pilih instruktur atau jam lain.',
            ]);
        }

        $row = Schedule::updateOrCreate(
            ['classroom_id' => $class->id, 'slot' => $slot, 'day' => $day],
            ['subject' => $subject, 'instructor_id' => $instructorId]
        );

        $users = $class->students()->with('user')->get()->pluck('user')->push(User::find($instructorId));
        $this->notifier->notify($users, '📅', "Jadwal {$class->kode} diperbarui: " . Catalog::DAYS[$day] . ' ' . Catalog::SLOTS[$slot] . ' · ' . Catalog::MAPEL[$subject], route('kelas.index'));

        return $row;
    }
}
