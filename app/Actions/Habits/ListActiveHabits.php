<?php

namespace App\Actions\Habits;

use App\Models\Habits\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListActiveHabits
{
    /** @return Collection<int, Habit> */
    public function handle(User $user): Collection
    {
        return $user->habits()->active()->orderBy('id')->get();
    }
}
