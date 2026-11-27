<?php

namespace App\Actions\Habits;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SetTodayHabitCompletion
{
    public function handle(User $user, int $habitId, bool $completed): void
    {
        $today = now(config()->string('app.timezone'))->toDateString();

        DB::transaction(function () use ($user, $habitId, $completed, $today) {
            $habit = $user->habits()->active()->lockForUpdate()->findOrFail($habitId);
            $attributes = ['habit_id' => $habit->id, 'date' => $today];

            if ($completed) {
                $user->habitLogs()->firstOrCreate($attributes);

                return;
            }

            $user->habitLogs()->where($attributes)->delete();
        }, attempts: 3);
    }
}
