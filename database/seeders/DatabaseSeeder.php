<?php

namespace Database\Seeders;

use App\Models\User\Admin;
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
        Admin::create([
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin@123'),
        ]);
        Admin::create([
            'email' => 'testadmin@gmail.com',
            'password' => bcrypt('admin@123'),
        ]);
        $this->call(UserCustomerSeeder::class);
        $this->call(UserTailorSeeder::class);
        $this->call(ReviewSeeder::class);
        $this->call(CatalogSeeder::class);
        $this->call(AvailabilitySeeder::class);
        $this->call(AppointmentSeeder::class);
    }
}
