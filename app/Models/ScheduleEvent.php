<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleEvent extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recurrence_days' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Check if this event occurs on a given day of week (0=Sun, 1=Mon, ..., 6=Sat).
     */
    public function occursOnDay(int $dayOfWeek): bool
    {
        if ($this->recurrence_type === 'daily') {
            return true;
        }

        return in_array($dayOfWeek, $this->recurrence_days ?? []);
    }
}
