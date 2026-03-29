<?php

namespace App\Models;

use Database\Factories\SalaryPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SalaryPayment extends Model
{
    /** @use HasFactory<SalaryPaymentFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'pro_rated_amount' => 'decimal:2',
            'rest_day_rate' => 'decimal:2',
            'extra_rest_day_pay' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'ad_hoc_payments' => 'array',
            'sundays_worked_dates' => 'array',
            'working_days_start' => 'date',
            'working_days_end' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the total of all ad-hoc payment amounts.
     */
    public function adHocPaymentsTotal(): float
    {
        return round(collect($this->ad_hoc_payments ?? [])->sum('amount'), 2);
    }

    /**
     * @return BelongsTo<Helper, $this>
     */
    public function helper(): BelongsTo
    {
        return $this->belongsTo(Helper::class);
    }

    /**
     * @return BelongsToMany<Claim, $this>
     */
    public function claims(): BelongsToMany
    {
        return $this->belongsToMany(Claim::class)->withPivot('paid_separately', 'payment_method')->withTimestamps();
    }
}
