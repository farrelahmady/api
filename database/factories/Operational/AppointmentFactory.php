<?php

namespace Database\Factories\Operational;


use App\Models\User\UserCustomer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'status' => $this->faker->randomElement([1, 2, 3, 4, 5]),
            'additional_message' => $this->faker->sentence,
            'user_customer_id' => UserCustomer::all()->random()->uuid,
        ];
    }
}
