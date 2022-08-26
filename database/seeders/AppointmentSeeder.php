<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User\UserCustomer;
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
        $userCust = UserCustomer::limit(5)->get();
        $userCust->each(function ($cust) {
            for ($i = 1; $i <= 5 * 5; $i++) {
                do {
                    $schedule = Availability::all()->random();

                    $appointment = Appointment::where('user_tailor_id', $schedule->user_tailor_id)
                        ->where('date', $schedule->date)
                        ->where('time', $schedule->time)
                        ->first();
                } while ($appointment != null);

                $appointment = Appointment::factory()->create([
                    'user_tailor_id' => $schedule->user_tailor_id,
                    'user_customer_id' => $cust->uuid,
                    'date' => $schedule->date,
                    'time' => $schedule->time,
                    'status' => $i % 5 == 0 ? 5 : $i % 5,
                ]);
            }
        });
        //for ($i = 0; $i < $availability->count() / 2; $i++) {
        //    $tailor_id = $availability->random()->user_tailor_id;
        //    $date = $availability->where('user_tailor_id', $tailor_id)->random()->date;
        //    $time = $availability->where('user_tailor_id', $tailor_id)->where('date', $date)->first()->time;
        //    $data = [
        //        'user_tailor_id' => $tailor_id,
        //        'date' => $date,
        //        'time' => $time,
        //    ];
        //    Appointment::factory()->create($data);
        //}
    }
}
