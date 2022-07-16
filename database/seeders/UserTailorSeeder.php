<?php

namespace Database\Seeders;

use App\Models\User\UserTailor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Database\Factories\User\UserTailorFactory;
use App\Models\ManagementAccess\UserTailorDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserTailorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserTailor::create([
            'email' => 'tailor@gmail.com',
            'password' => Hash::make("tailor123"),
        ])->profile()->create([
            'first_name' => 'Tailorine',
            'last_name' => 'Tailor',
            'phone_number' => '+639123456789',
            'profile_picture' => 'https://source.unsplash.com/240x240?people',
            'place_picture' => 'https://source.unsplash.com/720x480?tailor',
            'description' => 'Pelopor tailor pertama dan terbaik di Pekalongan, Jawa Tengah. Menyediakan jasa tailor jas, kebaya, kemeja, seragam, baju pesta, tunik, dress dan lain-lain.',
            'address' => 'Jalan TB Simatupang, kel. Simatupang',
            'district' => 'Simatupang',
            'city' => 'Tangerang',
            'province' => 'Banten',
            'zip_code' => '15158',
        ]);
        UserTailorFactory::new()->count(20)->create()->each(function ($userTailor) {
            if ($userTailor->is_premium) {
                $userTailor->update([
                    'max_schedule_slot' => 5,
                ]);
            }
            UserTailorDetail::factory()->create([
                'user_tailor_id' => $userTailor->id,
            ]);
        });
    }
}
