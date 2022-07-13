<?php

namespace Database\Seeders;

use Database\Factories\Operational\ReviewFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    ReviewFactory::new()->count(50)->create();
  }
}
