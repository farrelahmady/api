<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User\Admin;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\User\UserTailor;
use App\Models\User\UserCustomer;
use App\Helpers\ResponseFormatter;
use App\Models\Operational\Review;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Operational\Appointment;
use Illuminate\Support\Facades\Validator;
use App\Models\ManagementAccess\Availability;

class AppointmentController extends Controller
{

    public function index(Request $req)
    {
        try {
            $admin = Admin::where('uuid', auth('sanctum')->user()['uuid'])->where('email', auth('sanctum')->user()['email'])->first();
            $tailor = UserTailor::withTrashed()->where('uuid', auth('sanctum')->user()['uuid'])->where('email', auth('sanctum')->user()['email'])->first();
            $customer = UserCustomer::withTrashed()->where('uuid', auth('sanctum')->user()['uuid'])->where('email', auth('sanctum')->user()['email'])->first();
            if ($admin) {

                $data = Appointment::with(['tailor.profile', 'customer.profile']);

                if ($req->has('customer')) {
                    $data = $data->where('user_customer_id', $req->customer);
                }
            } else if ($tailor) {
                $data = Appointment::with(['tailor.profile', 'customer.profile'])->where('user_tailor_id', auth('sanctum')->user()['uuid']);
            } else if ($customer) {
                $data = Appointment::with(['tailor.profile', 'customer.profile'])->where('user_customer_id', auth('sanctum')->user()['uuid']);
            }

            if ($req->input('search')) {
                $data = $data->where(function ($data) use ($req) {
                    $data->has('tailor.profile')->whereHas('tailor.profile', function ($query) use ($req) {
                        $query->where(DB::raw('lower(first_name)'), 'like', '%' . $req->search . '%')
                            ->orWhere(DB::raw('lower(last_name)'), 'like', '%' . $req->search . '%')
                            ->orWhere(DB::raw('lower(address)'), 'like', '%' . strtolower($req->search) . '%')
                            ->orWhere(DB::raw('lower(district)'), 'like', '%' . strtolower($req->search) . '%')
                            ->orWhere(DB::raw('lower(city)'), 'like', '%' . strtolower($req->search) . '%')
                            ->orWhere(DB::raw('lower(province)'), 'like', '%' . strtolower($req->search) . '%');
                    })->orWhereHas('customer.profile', function ($query) use ($req) {
                        $query->where(DB::raw('lower(first_name)'), 'like', '%' . $req->search . '%')->orWhere(DB::raw('lower(last_name)'), 'like', '%' . $req->search . '%');
                    })->orWhere('date', 'like', '%' . $req->search . '%');
                });
            }


            if ($req->input('status')) {
                $data = $data->where('status', $req->status);
            }

            if ($req->input('date')) {
                $data = $data->where('date', $req->date);
            }

            if ($req->input('before')) {
                $data = $data->where('date', '<', Carbon::parse($req->before));
            }

            if ($req->input('after')) {
                $data = $data->where('date', '>', Carbon::parse($req->after));
            }

            if ($req->input('time')) {
                $data = $data->where('time', $req->time);
            }

            if ($req->has('chart')) {
                $data = $this->chart($data);
            }



            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->get();

            $data = $data->get()->each(function ($item) {

                $item->date = Carbon::parse($item->date)->settings(['formatFunction' => 'translatedFormat'])->format('l, d F Y');
                $item->status = (int)$item->status;
            });

            return ResponseFormatter::success(
                $data,
                $data->count() . ' Data retrieved successfully'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::error(data: $e->getMessage(), message: 'Something Went Wrong', code: 500);
        }
    }

    public function chart()
    {
        try {
            $status = ["requested", "approved", "active", "done", "cancel"];
            // * get all appointments in the current year
            $appointments = Appointment::select("*")->whereBetween('date', [
                now()->startOfYear(),
                now()->endOfYear()
            ])->get();
            // * get all appointments in the current year

            // * Structuring the data to be returned in the format required by the frontend
            $data = new Collection([
                "this_week" => new Collection(),
                "this_month" => new Collection(),
                "this_year" => new Collection(),
            ]);
            foreach ($data as $key => $value) {
                $data[$key]->put("total", 0);

                $data[$key]->put("data", new Collection([
                    "labels" => new Collection(),
                    "values" => new Collection()
                ]));

                foreach ($status as $value) {
                    $data[$key]['data']->put($value, new Collection());
                }
            }
            // * Structuring the data to be returned in the format required by the frontend

            // ! WEEKLY
            // * Get data for this week
            $thisWeekAppointment = $appointments->whereBetween(
                'date',
                [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]
            );

            // * Set total data for this week
            $data['this_week']['total'] = $thisWeekAppointment->count();

            // * Group data by date
            $thisWeekAppointment = $thisWeekAppointment->groupBy("date");

            for ($i = now()->startOfWeek(); $i < now()->endOfWeek(); $i->addDay()) {
                // * Set labels for this week
                $day = $i->format('l');
                $labels = $data["this_week"]['data']['labels'];
                $labels->push($day);

                // * Set values for this week
                $values = $data["this_week"]["data"]["values"];
                if ($thisWeekAppointment->has($i->format('Y-m-d'))) {
                    $values->push($thisWeekAppointment[$i->format('Y-m-d')]->count());
                    foreach ($status as $index => $value) {
                        $data["this_week"]['data'][$value]->push($thisWeekAppointment[$i->format('Y-m-d')]->where('status', $index + 1)->count());
                    }
                } else {
                    $values->push(0);
                    foreach ($status as $value) {
                        $data["this_week"]['data'][$value]->push(0);
                    }
                }
            }
            // ! WEEKLY

            // ! MONTHLY
            // * Get data for this month
            $thisMonthAppointment = $appointments->whereBetween(
                'date',
                [
                    now()->startOfMonth(),
                    now()->endOfMonth(),
                ]
            );

            // * Set total data for this month
            $data['this_month']['total'] = $thisMonthAppointment->count();

            // * Group data by date
            $thisMonthAppointment = $thisMonthAppointment->groupBy("date");

            for ($i = now()->startOfMonth(); $i < now()->endOfMonth(); $i->addDays()) {
                // * Set labels for this month
                $day = $i->format('Y-m-d');
                $labels = $data["this_month"]['data']['labels'];
                $labels->push($day);

                // * Set values for this month
                $values = $data["this_month"]["data"]["values"];
                if ($thisMonthAppointment->has($i->format('Y-m-d'))) {
                    $values->push($thisMonthAppointment[$i->format('Y-m-d')]->count());
                    foreach ($status as $index => $value) {
                        $data["this_month"]['data'][$value]->push($thisMonthAppointment[$i->format('Y-m-d')]->where('status', $index + 1)->count());
                    }
                } else {
                    $values->push(0);
                    foreach ($status as $value) {
                        $data["this_month"]['data'][$value]->push(0);
                    }
                }
            }
            // ! MONTHLY

            // ! YEARLY
            // * Set total data for this month
            $data['this_year']['total'] = $appointments->count();

            // * Group data by date (MONTH)
            $thisYearAppointment = $appointments->groupBy(function ($item) {
                $date = Carbon::parse($item->date);
                return $date->format('F');
            });

            for ($i = now()->startOfYear(); $i < now()->endOfYear(); $i->addMonth()) {
                // * Set labels for this Year
                $day = $i->format('F');
                $labels = $data["this_year"]['data']['labels'];
                $labels->push($day);

                // * Set values for this Year
                $values = $data["this_year"]["data"]["values"];
                if ($thisYearAppointment->has($i->format('F'))) {
                    $values->push($thisYearAppointment[$i->format('F')]->count());
                    foreach ($status as $index => $value) {
                        $data["this_year"]['data'][$value]->push($thisYearAppointment[$i->format('F')]->where('status', $index + 1)->count());
                    }
                } else {
                    $values->push(0);
                    foreach ($status as $value) {
                        $data["this_year"]['data'][$value]->push(0);
                    }
                }
            }
            // ! YEARLY

            return ResponseFormatter::success(
                $data,
                'Data retrieved successfully'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::error(data: $e->getMessage(), message: 'Something Went Wrong', code: 500);
        }
    }

    public function show($uuid)
    {
        if ($uuid === "chart") {
            return $this->chart();
        }
        try {
            $data = Appointment::with(['tailor.profile', 'customer.profile'])->where('uuid', $uuid);
            switch (auth()->user()->currentAccessToken()->tokenable_type) {
                case UserTailor::class:
                    $data = $data->whereHas('tailor', function ($query) {
                        $query->where('uuid', auth()->user()->uuid);
                    });
                    break;
                case UserCustomer::class:
                    $data = $data->whereHas('customer', function ($query) {
                        $query->where('uuid', auth()->user()->uuid);
                    });
                    break;
            }

            $data = $data->first();
            if ($data) {
                $data->date = Carbon::parse($data->date)->settings(['formatFunction' => 'translatedFormat'])->format('l, d F Y');
                $data->status = (int)$data->status;
                return ResponseFormatter::success(
                    $data,
                    'Data retrieved successfully'
                );
            } else {
                return ResponseFormatter::error(data: '', message: 'Data not found', code: 404);
            }
        } catch (\Exception $e) {
            return ResponseFormatter::error(data: $e->getMessage(), message: 'Something Went Wrong', code: 500);
        }
    }


    public function store(Request $req)
    {
        // return ResponseFormatter::success($req->all(), 'Data berhasil ditambahkan');
        try {
            Carbon::setLocale('id');

            $user = auth('sanctum')->user();
            $validator = Validator::make($req->all(), [
                'user_tailor_id' => 'required|uuid|exists:user_tailors,uuid',
                'date' => 'required|date',
                'time' => 'required|date_format:H:i:s',
                'additional_message' => 'nullable|min:10',
            ]);



            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), 'Masukan Tidak Valid', 422);
            }
            $dateTime = Carbon::createFromFormat('Y-m-d H:i:s', $req->date . " " . $req->time)->format("Y-m-d H:i:s");
            if ($dateTime < Carbon::now()->format("Y-m-d H:i:s")) {
                return ResponseFormatter::error(["message" => "Tanggal dan Waktu yang dipilih telah dilewati dari tanggal dan waktu sekarang"], 'Bad Request', 400);
            }

            $tailor = UserTailor::where('uuid', $req->user_tailor_id)->first();
            if (!$tailor) {
                return ResponseFormatter::error(["message" => "Tailor tidak ditemukan"], 'Not Found', 404);
            }

            $data = $validator->validate();
            $data['user_customer_id'] = $user->uuid;

            $dateTime = explode(
                " ",
                $dateTime
            );
            $data['date'] = $dateTime[0];
            $data['time'] = $dateTime[1];
            // return $data;

            $availability = Availability::where('user_tailor_id', $req->user_tailor_id)->where('date', $data['date'])->where('time', $data['time'])->get();
            $appointment = Appointment::where('user_tailor_id', $req->user_tailor_id)->where('date', $data['date'])->where('time', $data['time'])->where('status', '<', 5)->get();

            $custAppointment = Appointment::where('user_customer_id', $req->user_customer_id)->where('date', $data['date'])->where('time', $data['time'])->get();
            // return $custAppointment->count() >= 0;
            if ($availability->count() <= 0 || $appointment->count() > 0) {
                return ResponseFormatter::error(["message" => "Jadwal janji temu tidak tersedia"], 'Forbidden', 403);
            } else if ($custAppointment->count() > 0) {
                return ResponseFormatter::error(["message" => "Anda telah membuat janji temu di jam yang sama"], 'Forbidden', 403);
            }





            // return $data;

            $appointment = Appointment::create($data);
            return ResponseFormatter::success($appointment, 'Berhasil membuat appointment');
        } catch (\Exception $e) {
            return ResponseFormatter::error(message: $e->getMessage(), code: 500);
        }
    }

    public function update(Request $req, $uuid)
    {
        try {
            if (count($req->all()) <= 0) {
                return ResponseFormatter::error(message: 'Data tidak boleh kosong', code: 400);
            }
            $data = Appointment::with("tailor.profile", "customer.profile")->where('uuid', $uuid);
            switch (auth()->user()->currentAccessToken()->tokenable_type) {
                case UserTailor::class:
                    $data = $data->whereHas('tailor', function ($query) {
                        $query->where('uuid', auth()->user()->uuid);
                    });
                    break;
                case UserCustomer::class:
                    $data = $data->whereHas('customer', function ($query) {
                        $query->where('uuid', auth()->user()->uuid);
                    });
                    break;
            }
            $data = $data->first();
            if (!$data) {
                return ResponseFormatter::error(data: '', message: 'Data not found', code: 404);
            }

            $data->update($req->only(['status']));

            return ResponseFormatter::success($data, 'Berhasil mengubah status appointment');
        } catch (\Exception $e) {
            return ResponseFormatter::error(message: $e->getMessage(), code: 500);
        }
    }
}
