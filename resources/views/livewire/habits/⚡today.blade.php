<?php

use App\Actions\Habits\GetTodayHabits;
use App\Actions\Habits\SetTodayHabitCompletion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $date;

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function setCompletion(int $habitId, bool $completed, SetTodayHabitCompletion $setCompletion): void
    {
        if ($this->date !== now()->toDateString()) {
            $this->redirectRoute('today');

            return;
        }

        $setCompletion->handle($this->user(), $habitId, $completed);
    }

    public function with(): array
    {
        $habits = app(GetTodayHabits::class)->handle($this->user());

        return [
            'habits' => $habits,
            'completedCount' => $habits->where('completed_today', true)->count(),
        ];
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
};
?>

<x-ui.card :title="__('habits.today')">
    @if ($habits->isEmpty())
        <p class="mt-1.5 text-[15px]">{{ __('habits.today_empty') }}</p>
        <x-ui.button :href="route('habits.index')" variant="secondary" size="sm" wire:navigate class="mt-3">
            {{ __('habits.create_first') }}
        </x-ui.button>
    @else
        <x-slot:aside>
            <span class="text-sm tabular-nums text-ink-muted" aria-live="polite">{{ __(':done of :total', ['done' => $completedCount, 'total' => $habits->count()]) }}</span>
        </x-slot:aside>

        <ul class="mt-1 divide-y divide-line">
            @foreach ($habits as $habit)
                <li wire:key="habit-{{ $habit->id }}">
                    <x-ui.checkbox
                        :checked="$habit->completed_today"
                        wire:change="setCompletion({{ $habit->id }}, $event.target.checked)"
                        wire:loading.attr="disabled"
                    >
                        <span class="min-w-0 wrap-anywhere">{{ $habit->name }}</span>
                    </x-ui.checkbox>
                </li>
            @endforeach
        </ul>
    @endif
</x-ui.card>
