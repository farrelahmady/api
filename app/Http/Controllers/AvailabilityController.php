<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use GuzzleHttp\Middleware;
use Illuminate\Http\Request;
use App\Models\User\UserTailor;
use App\Helpers\ResponseFormatter;
use App\Models\Operational\Appointment;
use App\Models\User\Admin as UserAdmin;
use Illuminate\Support\Facades\Validator;
use App\Models\ManagementAccess\Availability;
use App\Models\User\UserCustomer;

class AvailabilityController extends Controller
{

    public function index(Request $req)
    {
        try {

            $tailor = $req->input('tailor');

            $user = auth('sanctum')->user();
            $availability = Availability::orderBy("date");
            switch ($user->currentAccessToken()->tokenable_type) {
                case UserCustomer::class:
                    $validation = Validator::make($req->all(), [
                        'tailor' => 'required|uuid|exists:user_tailors,uuid'
                    ]);

                    if ($validation->fails()) {
                        return ResponseFormatter::error($validation->errors(), message: "Kesalahan input", code: 400);
                    }
                    $availability = $availability->where('user_tailor_id', $tailor)->get();

                    $data = collect();
                    $date = $availability->groupBy("date");
                    $date->keys()->each(function ($item) use ($user, $date, $data) {
                        $time = collect();
                        $date[$item]->each(function ($item) use ($time, $user) {
                            $time->push(["time" => $item->time, "booked" => Appointment::where('date', $item->date)->where('time', $item->time)->where('user_tailor_id', $user->uuid)->where('status', '<', 5)->get()->count() > 0 ? true : false]);
                        });
                        $data->push(
                            [
                                "date" => $item,
                                "time" => $time,
                            ]
                        );
                    });
                    break;

                case UserTailor::class:
                    $availability = $availability->where('user_tailor_id', $user->uuid)->get();
                    $data = collect();
                    $date = $availability->groupBy("date");
                    $date->keys()->each(function ($item) use ($user, $date, $data) {
                        $time = collect();
                        $date[$item]->each(function ($item) use ($time, $user) {
                            $time->push(["time" => $item->time, "booked" => Appointment::where('date', $item->date)->where('time', $item->time)->where('user_tailor_id', $user->uuid)->where('status', '<', 5)->get()->count() > 0 ? true : false]);
                        });
                        $data->push(
                            [
                                "date" => $item,
                                "time" => $time,
                            ]
                        );
                    });
                    break;

                default:
                    $availability = $availability->get();
                    $availability = $availability->groupBy('user_tailor_id');

                    $data = collect();
                    $availability->keys()->each(function ($key) use ($data, $availability, $user) {
                        // return $availability;
                        $schedule = collect();
                        $date = $availability[$key]->groupBy("date");
                        $date->keys()->each(function ($item) use ($key, $date, $schedule) {
                            $time = collect();
                            $date[$item]->each(function ($item) use ($time, $key) {
                                $time->push(["time" => $item->time, "booked" => Appointment::where('date', $item->date)->where('time', $item->time)->where('user_tailor_id', $key)->where('status', '<', 5)->get()->count() > 0 ? true : false]);
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
                    break;
            }



            if ($availability->count() <= 0) {
                return ResponseFormatter::error(["message" => "Data tidak ditemukan"], 'Not Found', 404);
            }





            return ResponseFormatter::success($data, $data->count() . ' Data berhasil didapatkan');
        } catch (\Exception $e) {
            return ResponseFormatter::error(message: $e->getMessage(), code: 500);
        }
    }

    public function store(Request $req)
    {
        try {
            if (auth()->user()->currentAccessToken()->tokenable_type !== UserTailor::class) {
                return ResponseFormatter::error(message: 'Anda tidak memiliki akses ini', code: 403);
            }

            $tailor = auth()->user();

            $start = now()->startOfDay();
            $end = now()->addDay(13)->endOfDay();
            $rule = is_array($req->time) ? [
                'date' => "required|date|after_or_equal:$start|before_or_equal:$end",
                'time' => 'required|array|max:' . $tailor->max_schedule_slot,
                'time.*' => 'required|date_format:H:i|distinct',
            ] : [
                'date' => "required|date|after_or_equal:$start|before_or_equal:$end",
                'time' => 'required|date_format:H:i',
            ];

            $validator = Validator::make($req->all(), $rule);

            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), message: "Kesalahan input", code: 400);
            }


            $times = is_array($req->time) ? collect($req->time) : collect([$req->time]);

            if ($req->has('all')) {
                //$dates = collect(Carbon::parse($req->date)->startOfWeek()->addDay());
                $dateStart = now();
                $dates = collect([
                    $start
                ]);
                while (!Carbon::parse($dates->last())->isSameDay(Carbon::parse($end))) {
                    $dates->push(Carbon::parse($dateStart->addDay()));
                }
            } else {
                $dates = collect([
                    $req->date
                ]);
            }
            $dates->each(function ($date) use ($tailor, $times) {
                $times->each(function ($time) use ($tailor, $date) {
                    $availability = Availability::where('user_tailor_id', $tailor->uuid)->where('date', Carbon::parse($date)->format('Y-m-d'))->first();

                    if ($availability) {
                        $availability->update([
                            'user_tailor_id' => $tailor->uuid,
                            'date' => Carbon::parse($date)->format('Y-m-d'),
                            'time' => $time,
                        ]);
                    } else {
                        Availability::create([
                            'user_tailor_id' => $tailor->uuid,
                            'date' => Carbon::parse($date)->format('Y-m-d'),
                            'time' => $time,
                        ]);
                    }
                });
            });


            $availability = Availability::where('user_tailor_id', $tailor->uuid)->orderBy("date")->get();



            $schedule = collect();
            $date = $availability->groupBy("date");
            $date->keys()->each(function ($item) use ($tailor, $date, $schedule) {
                $time = collect();
                $date[$item]->each(function ($item) use ($time, $tailor) {
                    $time->push(["time" => $item->time, "booked" => Appointment::where('date', $item->date)->where('time', $item->time)->where('user_tailor_id', $tailor->uuid)->where('status', '<', 5)->get()->count() > 0 ? true : false]);
                });
                $schedule->push(
                    [
                        "date" => $item,
                        "time" => $time,
                    ]
                );
            });



            return ResponseFormatter::success($schedule, $availability->count() . " Jadwal berhasil diubah");
        } catch (\Exception $e) {
            return ResponseFormatter::error(message: $e->getMessage(), code: 500);
        }
    }
}
