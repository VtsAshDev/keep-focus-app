<?php

namespace App\Models\Habits;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Carbon|null $archived_at
 * @property-read bool $completed_today
 */
#[Fillable(['name', 'archived_at'])]
class Habit extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<HabitLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }
}
