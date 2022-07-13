<?php

namespace Database\Factories\ManagementAccess;

use App\Models\User\UserTailor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ManagementAccess\UserTailorDetail>
 */
class UserTailorDetailFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition()
  {
    return [
      'first_name' => $this->faker->firstName,
      'last_name' => $this->faker->lastName,
      'profile_picture' => 'https://source.unsplash.com/240x240?people',
      'address' => $this->faker->address,
      'phone_number' => $this->faker->phoneNumber,
      'speciality' => $this->faker->randomElement(['Upper', 'Lower']),
    ];
  }
}
