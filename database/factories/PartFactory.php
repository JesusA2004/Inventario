<?php

namespace Database\Factories;

use App\Enums\PartStatus;
use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Part>
 */
class PartFactory extends Factory
{
    protected $model = Part::class;

    public function definition(): array
    {
        return [
            'internal_code' => 'TESTPZ-'.fake()->unique()->numerify('###-###'),
            'name' => fake()->randomElement(['Memoria RAM', 'Disco duro', 'Fuente de poder', 'Teclado']),
            'status' => PartStatus::Funcional,
            'in_inventory' => true,
            'quantity' => 1,
        ];
    }
}
