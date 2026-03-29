<?php

namespace App\Models;

use Database\Factories\ClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Claim extends Model
{
    /** @use HasFactory<ClaimFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Helper, $this>
     */
    public function helper(): BelongsTo
    {
        return $this->belongsTo(Helper::class);
    }

    /**
     * @return BelongsToMany<SalaryPayment, $this>
     */
    public function salaryPayments(): BelongsToMany
    {
        return $this->belongsToMany(SalaryPayment::class)->withPivot('paid_separately', 'payment_method')->withTimestamps();
    }
}
