<?php

namespace App\Actions\Habits;

use App\Models\Habits\Habit;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateHabit
{
    public function handle(User $user, string $name): Habit
    {
        $name = Str::trim($name);

        Validator::make(['name' => $name], ['name' => ['required', 'string', 'max:255']])->validate();

        return $user->habits()->create(['name' => $name]);
    }
}
