<?php

namespace Database\Factories\ManagementAccess;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ManagementAccess\Catalog>
 */
class CatalogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            "name" => $this->faker->randomElement(["celana pendek", "batik", "jeans", "hoodie",]),
            "description" => $this->faker->text,
            "category" => $this->faker->randomElement(["LOWER", "UPPER"]),
            "fabric" => $this->faker->word,
            "price" => $this->faker->randomFloat(0, 5, 30) / 2 * 10000,
        ];
    }
}
