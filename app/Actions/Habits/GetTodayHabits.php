<?php

namespace App\Actions\Habits;

use App\Models\Habits\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GetTodayHabits
{
    /** @return Collection<int, Habit> */
    public function handle(User $user): Collection
    {
        $today = now(config()->string('app.timezone'))->toDateString();

        return $user->habits()
            ->active()
            ->withExists(['logs as completed_today' => fn (Builder $query) => $query
                ->where('user_id', $user->getKey())
                ->where('date', $today)])
            ->orderBy('id')
            ->get();
    }
}
