<?php

namespace Dashed\DashedCore\Database\Factories;

use Illuminate\Support\Str;
use Dashed\DashedCore\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('secret'),
            'remember_token' => Str::random(10),
        ];
    }
}
