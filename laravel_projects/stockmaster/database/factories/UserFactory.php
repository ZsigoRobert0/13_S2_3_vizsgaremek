<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'Username' => $this->faker->unique()->userName(),
            'Email' => $this->faker->unique()->safeEmail(),
            'PasswordHash' => Hash::make('password'),
            'RegistrationDate' => now(),
            'IsLoggedIn' => 0,
            'PreferredTheme' => 'dark',
            'NotificationsEnabled' => 1,
            'DemoBalance' => 10000.00,
            'RealBalance' => 0.00,
            'PreferredCurrency' => 'USD',
        ];
    }

    public function loggedIn(): static
    {
        return $this->state(fn () => [
            'IsLoggedIn' => 1,
        ]);
    }

    public function withTheme(string $theme): static
    {
        return $this->state(fn () => [
            'PreferredTheme' => $theme,
        ]);
    }

    public function notificationsDisabled(): static
    {
        return $this->state(fn () => [
            'NotificationsEnabled' => 0,
        ]);
    }
}