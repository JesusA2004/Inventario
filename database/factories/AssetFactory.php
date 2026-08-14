<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'internal_code' => 'TEST-'.fake()->unique()->numerify('###-###'),
            'name' => fake()->randomElement(['Laptop', 'Monitor', 'Impresora', 'Tablet']),
            'status' => AssetStatus::Activo,
            'in_inventory' => true,
            'acquired_at' => now()->toDateString(),
        ];
    }
}
