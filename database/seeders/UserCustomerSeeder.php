<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User\UserCustomer;
use Illuminate\Support\Facades\Hash;
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
    UserCustomer::create([
      'email' => 'customer@gmail.com',
      'password' => Hash::make("customer123"),
    ])->profile()->create([
      'first_name' => 'Tailorine',
      'last_name' => 'Customer',
      'phone_number' => '+639123456789',
      'address' => 'Jalan TB Simatupang, kel. Simatupang, Kec. Simatupang, Kota Tangerang, Banten 15158',
      'profile_picture' => 'https://source.unsplash.com/240x240?people',

    ]);
    UserCustomerFactory::new()->count(20)->create()->each(function ($userCustomer) {
      UserCustomerDetail::factory()->create([
        'user_customer_id' => $userCustomer->id,
      ]);
    });
  }
}
