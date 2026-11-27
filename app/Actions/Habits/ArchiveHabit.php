<?php

namespace App\Actions\Habits;

use App\Models\Habits\Habit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ArchiveHabit
{
    public function handle(User $user, int $habitId): Habit
    {
        return DB::transaction(function () use ($user, $habitId) {
            $habit = $user->habits()->lockForUpdate()->findOrFail($habitId);

            if ($habit->archived_at === null) {
                $habit->update(['archived_at' => now()]);
            }

            return $habit;
        }, attempts: 3);
    }
}
