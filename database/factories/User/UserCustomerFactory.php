<?php

namespace Database\Factories\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User\UserCustomer>
 */
class UserCustomerFactory extends Factory
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
      'password' => Hash::make("customer123"),
    ];
  }
}
