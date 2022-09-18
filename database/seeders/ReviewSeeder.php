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
        $badRating = ["Tidak ramah", "Tidak sesuai dengan foto", "Tidak sesuai deskripsi", "Tailor terlambat", "Lokasi tempat tidak sesuai alamat", "Tempat tidak ditemukan", "Tailor kasar", "Tailor melakukan kekerasa / pelecehan", "Tempat tidak nyaman"];
        $goodRating = ["Tailor ramah", "Tempat nyaman", "Tailor sesuai dengan deskripsi", "Tempat bersih", "Tailor sangat membantu", "Tailor tepat waktu"];

        $reviewOptions = collect();
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= 3) {
                $options = $badRating;
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
                    'user_tailor_id' => $tailor->uuid,
                    'user_customer_id' => UserCustomer::all()->random()->uuid,
                    'rating' => $rating,
                    'review' => ReviewOption::where('rating', $rating)->get()->random()->review,
                    'message' => $faker->text
                ]);
            }
        });
    }
}
