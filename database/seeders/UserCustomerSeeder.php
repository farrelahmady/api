<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User\UserCustomer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Database\Factories\User\UserCustomerFactory;
use App\Models\ManagementAccess\UserCustomerDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $files = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/customer/profile/avatar-') !== false)->values();

        echo $files->count() . " files found. ";
        echo "Seeding UserCustomer...\n";

        UserCustomer::create([
            'email' => 'customer@gmail.com',
            'password' => Hash::make("customer123"),
        ])->profile()->create([
            'first_name' => 'Tailorine',
            'last_name' => 'Customer',
            'phone_number' => '+639123456789',
            'address' => 'Jalan TB Simatupang, kel. Simatupang',
            'district' => 'Simatupang',
            'city' => 'Tangerang',
            'province' => 'Banten',
            'zip_code' => '15158',
            'profile_picture' => asset("storage/" . $files->shift()),

        ]);
        UserCustomerFactory::new()->count($files->count())->create()->each(function ($userCustomer) use ($files) {
            UserCustomerDetail::factory()->create([
                'profile_picture' => asset("storage/" . $files->shift()),
                'user_customer_id' => $userCustomer->id,
            ]);
        });
    }
}
