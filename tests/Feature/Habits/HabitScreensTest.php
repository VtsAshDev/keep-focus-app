<?php

use App\Models\User;
use Livewire\Livewire;

test('the habits page requires authentication', function () {
    $this->get('/habitos')->assertRedirect(route('login'));
});

test('a daily habit can be created, completed, cleared and archived', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/habitos')
        ->assertOk()
        ->assertSee('Nenhum hábito cadastrado');

    $manager = Livewire::test('habits.index')
        ->call('create')
        ->assertDispatched('open-modal', name: 'habit-form')
        ->call('save')
        ->assertHasErrors('name')
        ->set('name', 'Ler dez páginas')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal', name: 'habit-form')
        ->assertSee('Ler dez páginas');

    $habit = $user->habits()->sole();

    Livewire::test('habits.today')
        ->assertSee('0 de 1')
        ->call('setCompletion', $habit->id, true)
        ->assertSee('1 de 1');

    $this->get('/hoje')
        ->assertOk()
        ->assertSeeInOrder(['Hábitos de hoje', '1 de 1', 'Ler dez páginas']);

    Livewire::test('habits.today')
        ->call('setCompletion', $habit->id, false)
        ->assertSee('0 de 1');

    $manager->call('archive', $habit->id)
        ->assertDontSee('Ler dez páginas');

    $this->get('/hoje')
        ->assertSee('Criar primeiro hábito')
        ->assertDontSee('Ler dez páginas');
});

test('a stale today checklist refreshes before changing a new day', function () {
    $user = User::factory()->create();
    $habit = $user->habits()->create(['name' => 'Ler']);
    $this->travelTo(now()->endOfDay());

    $checklist = Livewire::actingAs($user)->test('habits.today');
    $this->travel(1)->second();

    $checklist->call('setCompletion', $habit->id, true)
        ->assertRedirect(route('today'));

    $this->assertDatabaseCount('habit_logs', 0);
});
