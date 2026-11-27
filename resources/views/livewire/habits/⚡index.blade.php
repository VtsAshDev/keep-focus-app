<?php

use App\Actions\Habits\ArchiveHabit;
use App\Actions\Habits\CreateHabit;
use App\Actions\Habits\ListActiveHabits;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public function create(): void
    {
        $this->reset('name');
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'habit-form');
    }

    public function save(CreateHabit $create): void
    {
        $create->handle($this->user(), $this->name);

        $this->reset('name');
        $this->resetValidation();
        $this->dispatch('close-modal', name: 'habit-form');
    }

    public function archive(int $habitId, ArchiveHabit $archive): void
    {
        $archive->handle($this->user(), $habitId);
    }

    public function with(): array
    {
        return ['habits' => app(ListActiveHabits::class)->handle($this->user())];
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
};
?>

<x-ui.page>
    <x-slot:header>
        <x-ui.page-header :title="__('habits.title')" />
    </x-slot>

    @forelse ($habits as $habit)
        <x-ui.list-row wire:key="habit-{{ $habit->id }}" :title="$habit->name">
            <x-slot:trailing>
                <x-ui.button
                    size="sm"
                    variant="ghost"
                    wire:click="archive({{ $habit->id }})"
                    wire:confirm="{{ __('habits.archive_confirm') }}"
                    wire:loading.attr="disabled"
                    :aria-label="__('habits.archive_named', ['name' => $habit->name])"
                >{{ __('habits.archive') }}</x-ui.button>
            </x-slot:trailing>
        </x-ui.list-row>
    @empty
        <x-ui.empty-state icon="check" :title="__('habits.empty_title')" :text="__('habits.empty_description')" />
    @endforelse

    <x-ui.button variant="add" wire:click="create">{{ __('habits.new') }}</x-ui.button>

    <x-ui.modal name="habit-form" :title="__('habits.new')">
        <form wire:submit="save" class="flex flex-col gap-3">
            <x-ui.input :label="__('Name')" wire:model="name" maxlength="255" />
            <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
        </form>
    </x-ui.modal>
</x-ui.page>
