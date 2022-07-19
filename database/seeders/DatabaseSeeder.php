<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserCustomerSeeder::class);
        $this->call(UserTailorSeeder::class);
        $this->call(ReviewSeeder::class);
        $this->call(CatalogSeeder::class);
    }
}
