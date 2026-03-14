<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Helper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'helper_id' => Helper::factory(),
            'name' => fake()->word().'.pdf',
            'file_path' => 'documents/1/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1024, 1048576),
            'hidden_from_helper' => false,
        ];
    }
}
