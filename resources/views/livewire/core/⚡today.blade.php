<?php

use Livewire\Component;

new class extends Component {};
?>

<x-ui.page>
    <x-slot:header>
        <header class="flex items-center justify-between px-5 pt-[max(1.5rem,env(safe-area-inset-top))]">
            <x-ui.logo size="sm" />
            <span class="text-sm text-ink-muted">{{ App\Support\Dates::long(now()) }}</span>
        </header>
    </x-slot>

    <h1 class="font-display text-[34px] leading-tight font-semibold uppercase">{{ __('Welcome, :name', ['name' => Str::before(auth()->user()->name, ' ')]) }}</h1>

    <livewire:habits.today />
    <livewire:training.today />
</x-ui.page>
