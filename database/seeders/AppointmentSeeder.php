<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Operational\Appointment;
use App\Models\ManagementAccess\Availability;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $availability = Availability::all();

        for ($i = 0; $i < $availability->count() / 4; $i++) {
            $tailor_id = $availability->random()->user_tailor_id;
            $date = $availability->where('user_tailor_id', $tailor_id)->random()->date;
            $time = $availability->where('user_tailor_id', $tailor_id)->where('date', $date)->first()->time;
            $data = [
                'user_tailor_id' => $tailor_id,
                'date' => $date,
                'time' => $time,
            ];
            Appointment::factory()->create($data);
        }
    }
}
