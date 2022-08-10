<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\ManagementAccess\Availability;
use App\Models\Operational\Appointment;
use App\Models\User\Admin as UserAdmin;
use GuzzleHttp\Middleware;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{

    public function index(Request $req)
    {
        try {

            $tailor = $req->input('tailor');
            $data = collect();
            if (count($req->all()) <= 0) {
                // if (!auth('sanctum')->check()) {
                //     return ResponseFormatter::error(message: 'Unauthorized', code: 401);
                // }
                // if (!in_array($req->user('sanctum')->uuid, UserAdmin::pluck('uuid')->toArray())) {
                //     return ResponseFormatter::error(["message" => "Kamu tidak memiliki akses"], 'Forbidden', 403);
                // }
                $availability = Availability::all();
            } else {
                $availability = Availability::where('user_tailor_id', $tailor)->get();
            }

            if ($availability->count() <= 0) {
                return ResponseFormatter::error(["message" => "Data tidak ditemukan"], 'Not Found', 404);
            }


            // return ResponseFormatter::success($data, 'Data berhasil didapatkan');
            // $availability->keys()->each(function ($item) use ($data, $availability) {
            //   $time = collect();
            //   $availability[$item]->each(function ($item) use ($time) {
            //     $time->push($item->time);
            //   });
            //   $data->push([
            //     'date' => $item,
            //     'time' => $time
            //   ]);
            // });

            $availability = $availability->groupBy('user_tailor_id');

            $availability->keys()->each(function ($key) use ($data, $availability) {
                // return $availability;
                $schedule = collect();
                $date = $availability[$key]->groupBy("date");
                $date->keys()->each(function ($item) use ($key, $date, $schedule) {
                    $time = collect();
                    $date[$item]->each(function ($item) use ($time, $key) {
                        $time->push(["time" => $item->time, "booked" => Appointment::where('date', $item->date)->where('time', $item->time)->where('user_tailor_id', $key)->get()->count() > 0 ? true : false]);
                    });
                    $schedule->push(
                        [
                            "date" => $item,
                            "time" => $time,
                        ]
                    );
                });

                $data->push([
                    'user_tailor_id' => $key,
                    'schedule' => $schedule,
                ]);
            });

            if ($tailor) {
                $data = $data->where('user_tailor_id', $tailor)->first()["schedule"];
            }


            return ResponseFormatter::success($data, $data->count() . ' Data berhasil didapatkan');
        } catch (\Exception $e) {
            return ResponseFormatter::error(message: $e->getMessage(), code: 500);
        }
    }
}
