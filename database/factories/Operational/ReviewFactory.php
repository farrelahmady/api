<?php

namespace Database\Factories\Operational;

use App\Models\User\UserCustomer;
use App\Models\User\UserTailor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Operational\Review>
 */
class ReviewFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition()
  {
    return [
      'user_customer_id' => $this->faker->numberBetween(1, UserCustomer::count()),
      'user_tailor_id' => $this->faker->numberBetween(1, UserTailor::count()),
      'rating' => $this->faker->numberBetween(1, 5),
      'comment' => $this->faker->text,
    ];
  }
}
