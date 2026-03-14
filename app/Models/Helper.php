<?php

namespace App\Models;

use Database\Factories\HelperFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Helper extends Model
{
    /** @use HasFactory<HelperFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_of_application' => 'date',
            'monthly_salary' => 'decimal:2',
            'monthly_levy_rate' => 'decimal:2',
            'fees_payable_to_ea' => 'decimal:2',
            'paynow_enabled' => 'boolean',
            'round_up_rest_day_rate' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<SalaryPayment, $this>
     */
    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class);
    }

    /**
     * @return Attribute<float, never>
     */
    protected function restDayRate(): Attribute
    {
        return Attribute::get(function (): float {
            $rate = (float) $this->monthly_salary / 26;

            return $this->round_up_rest_day_rate ? ceil($rate) : round($rate, 2);
        });
    }
}
