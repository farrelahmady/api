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
        $files = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/profile/avatar-') !== false)->values();

        echo $files->count() . " files found. ";
        echo "Seeding UserTailor...\n";

        UserTailor::create([
            'email' => 'tailor@gmail.com',
            'password' => Hash::make("tailor123"),
        ])->profile()->create([
            'first_name' => 'Tailorine',
            'last_name' => 'Tailor',
            'phone_number' => '+639123456789',
            'profile_picture' => asset("storage/" . $files->shift()),
            'place_picture' => 'https://source.unsplash.com/720x480?tailor',
            'description' => 'Pelopor tailor pertama dan terbaik di Pekalongan, Jawa Tengah. Menyediakan jasa tailor jas, kebaya, kemeja, seragam, baju pesta, tunik, dress dan lain-lain.',
            'address' => 'Jalan TB Simatupang, kel. Simatupang',
            'district' => 'Simatupang',
            'city' => 'Tangerang',
            'province' => 'Banten',
            'zip_code' => '15158',
        ]);
        UserTailorFactory::new()->count($files->count())->create()->each(function ($userTailor) use ($files) {

            if ($userTailor->is_premium) {
                $userTailor->update([
                    'max_schedule_slot' => 5,
                ]);
            }
            UserTailorDetail::factory()->create([
                'profile_picture' => asset("storage/" . $files->shift()),
                'user_tailor_id' => $userTailor->id,
            ]);
        });
    }
}
