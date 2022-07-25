<?php

namespace Database\Factories\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User\UserTailor>
 */
class UserTailorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'email' => preg_replace('/@example\..*/', '@gmail.com', $this->faker->unique()->safeEmail),
            'password' => Hash::make("tailor123"), // password
            'is_premium' => $this->faker->boolean,
            'is_ready' => true,
        ];
    }
}
