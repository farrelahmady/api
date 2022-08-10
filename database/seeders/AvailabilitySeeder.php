<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User\UserTailor;
use Illuminate\Database\Seeder;
use App\Models\ManagementAccess\Availability;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserTailor::all()->each(function ($user) {
            $data = collect();
            $date = now()->startOfWeek()->locale('id');
            // $date->settings(['formatFunction' => 'translatedFormat']);
            for ($i = 0; $i < 14; $i++) {
                $data->put(
                    $date->format('Y-m-d'),
                    collect()
                );

                $temp = collect();
                for ($j = 0; $j < 3; $j++) {
                    do {
                        $time = Carbon::createFromTime(rand(6, 17), rand(0, 3) * 15);
                        // $time->settings(['formatFunction' => 'translatedFormat']);
                        // $time = $time->addMinute(random_int(0, 1) * 30)->addHour(random_int(1, 8));
                        # code...
                    } while ($temp->contains($time->format('H:i:s A')));
                    $temp->push(
                        (string)$time->format('H:i:s'),
                    );
                }
                // $data = $data->jsonserialize();
                $temp->sort()->values()->each(
                    fn ($item) =>
                    $data[$date->format('Y-m-d')]->push($item)
                );
                $date->addDay();
            }
            $dateList = collect();
            $data->keys()->each(function ($item) use ($dateList, $data, $user) {
                $data[$item]->each(function ($waktu) use ($dateList, $item, $user) {
                    $dateList->push(["date" => $item, "time" =>  $waktu, "user_tailor_id" => $user->uuid]);
                });
            });
            $dateList->each(function ($item) {
                Availability::create($item);
            });
            // Availability::insert($dateList->toArray());

            // Availability::create($dateList->toArray());
        });
    }
}
