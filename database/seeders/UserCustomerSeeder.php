<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User\UserCustomer;
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
    UserCustomerFactory::new()->count(10)->create()->each(function ($userCustomer) {
      UserCustomerDetail::factory()->create([
        'user_customer_id' => $userCustomer->id,
      ]);
    });
  }
}
