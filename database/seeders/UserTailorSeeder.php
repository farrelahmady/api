<?php

namespace Database\Seeders;

use App\Models\User\UserTailor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        $profiles = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/profile/avatar-') !== false)->values();
        $places = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/place/') !== false)->values();

        echo $profiles->count() . " files found. ";
        echo $places->count() . " files found. ";
        echo "Seeding UserTailor...\n";

        UserTailor::create([
            'email' => 'tailor@gmail.com',
            'password' => Hash::make("tailor123"),
        ])->profile()->create([
            'first_name' => 'Tailorine',
            'last_name' => 'Tailor',
            'phone_number' => '+639123456789',
            'profile_picture' => asset("storage/" . $profiles->shift()),
            'place_picture' => asset("storage/" . $places->random()),
            'description' => 'Pelopor tailor pertama dan terbaik di Pekalongan, Jawa Tengah. Menyediakan jasa tailor jas, kebaya, kemeja, seragam, baju pesta, tunik, dress dan lain-lain.',
            'address' => 'Jalan TB Simatupang, kel. Simatupang',
            'district' => 'Simatupang',
            'city' => 'Tangerang',
            'province' => 'Banten',
            'zip_code' => '15158',
        ]);
        UserTailorFactory::new()->count($profiles->count())->create()->each(function ($userTailor) use ($profiles, $places) {

            if ($userTailor->is_premium) {
                $userTailor->update([
                    'max_schedule_slot' => 5,
                ]);
            }
            UserTailorDetail::factory()->create([
                'profile_picture' => asset("storage/" . $profiles->shift()),
                'place_picture' => asset("storage/" . $places->random()),
                'user_tailor_id' => $userTailor->id,
            ]);
        });
    }
}
