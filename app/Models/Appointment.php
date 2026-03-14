<?php

namespace App\Models;

use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
        ];
    }

    /**
     * Scope to upcoming appointments (not cancelled, date >= today).
     *
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('appointment_date', '>=', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time');
    }
}
