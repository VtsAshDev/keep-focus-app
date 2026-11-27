<?php

use App\Actions\Habits\ArchiveHabit;
use App\Actions\Habits\CreateHabit;
use App\Actions\Habits\GetTodayHabits;
use App\Actions\Habits\ListActiveHabits;
use App\Actions\Habits\SetTodayHabitCompletion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

test('creates an active habit for the user with a trimmed name', function () {
    $user = User::factory()->create();

    $habit = app(CreateHabit::class)->handle($user, '  Caminhar  ');

    expect($habit->user_id)->toBe($user->id)
        ->and($habit->name)->toBe('Caminhar')
        ->and($habit->archived_at)->toBeNull();
    $this->assertDatabaseHas('habits', ['id' => $habit->id, 'user_id' => $user->id, 'name' => 'Caminhar']);
});

test('validates the habit name after trimming', function (string $name) {
    $user = User::factory()->create();

    expect(fn () => app(CreateHabit::class)->handle($user, $name))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('habits', 0);
})->with(['blank' => '  ', 'too long' => str_repeat('a', 256)]);

test('lists the active habits of the user in creation order', function () {
    $user = User::factory()->create();
    $create = app(CreateHabit::class);
    $first = $create->handle($user, 'Pedalar');
    $archived = $create->handle($user, 'Ler');
    $last = $create->handle($user, 'Caminhar');
    $create->handle(User::factory()->create(), 'Estudar');
    app(ArchiveHabit::class)->handle($user, $archived->id);

    expect(app(ListActiveHabits::class)->handle($user)->modelKeys())->toBe([$first->id, $last->id]);
});

test('shows only active habits of the user and computes completion for the current local day', function () {
    config(['app.timezone' => 'America/Sao_Paulo']);
    $this->travelTo(Carbon::parse('2026-11-28 01:00:00', 'UTC'));
    $user = User::factory()->create();
    $create = app(CreateHabit::class);
    $completed = $create->handle($user, 'Pedalar');
    $pending = $create->handle($user, 'Caminhar');
    $archived = $create->handle($user, 'Ler');
    $create->handle(User::factory()->create(), 'Estudar');
    $user->habitLogs()->create(['habit_id' => $pending->id, 'date' => '2026-11-26']);
    app(SetTodayHabitCompletion::class)->handle($user, $completed->id, true);
    app(ArchiveHabit::class)->handle($user, $archived->id);

    $this->assertDatabaseHas('habit_logs', ['habit_id' => $completed->id, 'date' => '2026-11-27']);
    $habits = app(GetTodayHabits::class)->handle($user);
    expect($habits->modelKeys())->toBe([$completed->id, $pending->id])
        ->and($habits->pluck('completed_today')->all())->toBe([true, false]);

    $this->travelTo(Carbon::parse('2026-11-28 03:00:00', 'UTC'));
    expect(app(GetTodayHabits::class)->handle($user)->pluck('completed_today')->all())->toBe([false, false]);
});

test('sets and clears the current day completion idempotently without changing older days', function () {
    $this->freezeTime();
    $user = User::factory()->create();
    $habit = app(CreateHabit::class)->handle($user, 'Ler');
    $older = $user->habitLogs()->create(['habit_id' => $habit->id, 'date' => now()->subDay()->toDateString()]);
    $setCompletion = app(SetTodayHabitCompletion::class);

    $setCompletion->handle($user, $habit->id, true);
    $setCompletion->handle($user, $habit->id, true);

    $this->assertDatabaseCount('habit_logs', 2);
    $this->assertDatabaseHas('habit_logs', ['habit_id' => $habit->id, 'date' => now()->toDateString()]);

    $setCompletion->handle($user, $habit->id, false);
    $setCompletion->handle($user, $habit->id, false);

    $this->assertDatabaseCount('habit_logs', 1);
    $this->assertDatabaseHas('habit_logs', ['id' => $older->id]);
});

test('archiving keeps completion history and blocks changes to an archived habit', function () {
    $this->freezeTime();
    $user = User::factory()->create();
    $habit = app(CreateHabit::class)->handle($user, 'Ler');
    $setCompletion = app(SetTodayHabitCompletion::class);
    $archive = app(ArchiveHabit::class);
    $setCompletion->handle($user, $habit->id, true);

    $archivedAt = $archive->handle($user, $habit->id)->archived_at;
    expect($archivedAt)->not->toBeNull();
    expect(fn () => $setCompletion->handle($user, $habit->id, false))->toThrow(ModelNotFoundException::class);

    $this->travel(1)->day();
    expect($archive->handle($user, $habit->id)->archived_at->equalTo($archivedAt))->toBeTrue();
    expect(fn () => $setCompletion->handle($user, $habit->id, true))->toThrow(ModelNotFoundException::class);
    $this->assertDatabaseCount('habit_logs', 1);
});

test('refuses to archive or change the completion of another user habit', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $habit = app(CreateHabit::class)->handle($owner, 'Ler');

    expect(fn () => app(ArchiveHabit::class)->handle($otherUser, $habit->id))->toThrow(ModelNotFoundException::class);
    expect(fn () => app(SetTodayHabitCompletion::class)->handle($otherUser, $habit->id, true))
        ->toThrow(ModelNotFoundException::class);
    $this->assertDatabaseHas('habits', ['id' => $habit->id, 'archived_at' => null]);
    $this->assertDatabaseCount('habit_logs', 0);
});

test('stores at most one completion per habit and date and enforces its owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $habit = app(CreateHabit::class)->handle($user, 'Ler');
    $attributes = ['habit_id' => $habit->id, 'date' => '2026-11-27'];

    expect(fn () => $otherUser->habitLogs()->create($attributes))->toThrow(QueryException::class);
    $user->habitLogs()->create($attributes);
    expect(fn () => $user->habitLogs()->create($attributes))->toThrow(QueryException::class);
});
