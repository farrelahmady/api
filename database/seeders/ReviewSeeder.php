<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use App\Models\User\UserTailor;
use Illuminate\Database\Seeder;
use App\Models\User\UserCustomer;
use App\Models\Operational\Review;
use App\Models\ManagementAccess\ReviewOption;
use Database\Factories\Operational\ReviewFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $badRating = ["Tidak ramah", "Tempat tidak sesuai dengan foto", "Tempat tidak ditemukan", "Tailor tidak dapat ditemui"];
        $mediumRating = ["Tidak ramah", "Tempat tidak sesuai dengan foto", "Tempat tidak ditemukan", "Tailor tidak dapat ditemui"];
        $goodRating = ["Ramah", "Tempat sesuai dengan foto", "Tempat ditemukan", "Tailor dapat ditemui"];

        $reviewOptions = collect();
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= 2) {
                $options = $badRating;
            } else if ($i <= 4) {
                $options = $mediumRating;
            } else {
                $options = $goodRating;
            }

            foreach ($options as $option) {
                $reviewOptions->push([
                    'rating' => $i,
                    'review' => $option
                ]);
            }
        }

        $reviewOptions->each(function ($option) {
            ReviewOption::create($option);
        });

        UserTailor::all()->each(function ($tailor) {
            $faker = Faker::create('id_ID');
            for ($i = 0; $i < 10; $i++) {
                $rating = $faker->numberBetween(1, 5);
                ReviewFactory::new()->create([
                    'user_tailor_id' => $tailor->id,
                    'user_customer_id' => $faker->numberBetween(1, UserCustomer::count()),
                    'rating' => $rating,
                    'review' => ReviewOption::where('rating', $rating)->get()->random()->review,
                    'message' => $faker->text
                ]);
            }
        });
    }
}
