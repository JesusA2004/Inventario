<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     *
     * La autenticación de dos factores de Fortify está desactivada en esta
     * app (ver config/fortify.php: 'features' no incluye
     * twoFactorAuthentication()), y App\Models\User no usa el trait
     * TwoFactorAuthenticatable que Fortify requiere para el reto de 2FA. Este
     * estado de factory queda como no-op (antes ni siquiera tenía un
     * `return`, lo cual PHPStan reportó correctamente) hasta que 2FA se
     * habilite de verdad en la app; activarlo aquí sin esa base no haría
     * nada funcional y sería agregar alcance fuera de esta tarea.
     */
    public function withTwoFactor(): static
    {
        return $this;
    }
}
