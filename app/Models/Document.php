<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hidden_from_helper' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Helper, $this>
     */
    public function helper(): BelongsTo
    {
        return $this->belongsTo(Helper::class);
    }
}
