<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/habitos', 'habits.index')->middleware('auth')->name('habits.index');
