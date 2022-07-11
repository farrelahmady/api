<?php

namespace Database\Seeders;

use Database\Factories\ManagementAccess\UserCustomerDetailFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserCustomerDetailSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    UserCustomerDetailFactory::new()->count(10)->create();
  }
}
