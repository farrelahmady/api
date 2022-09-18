<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User\Admin;
use Illuminate\Http\Request;
use App\Models\User\UserTailor;
use App\Models\User\UserCustomer;
use App\Helpers\ResponseFormatter;
use App\Models\Operational\Review;
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



            if ($req->has('status')) {
                $data = $data->where('status', $req->status);
            }

            if ($req->has('date')) {
                $data = $data->where('date', $req->date);
            }

            if ($req->has('before')) {
                $data = $data->where('date', '<', Carbon::parse($req->before));
            }

            if ($req->has('after')) {
                $data = $data->where('date', '>', Carbon::parse($req->after));
            }

            if ($req->has('time')) {
                $data = $data->where('time', $req->time);
            }

            if ($req->has('search')) {
                $data = $data->has('tailor.profile')->whereHas('tailor.profile', function ($query) use ($req) {
                    $query->where(DB::raw('lower(first_name)'), 'like', '%' . $req->search . '%')
                        ->orWhere(DB::raw('lower(last_name)'), 'like', '%' . $req->search . '%')
                        ->orWhere(DB::raw('lower(address)'), 'like', '%' . strtolower($req->search) . '%')
                        ->orWhere(DB::raw('lower(district)'), 'like', '%' . strtolower($req->search) . '%')
                        ->orWhere(DB::raw('lower(city)'), 'like', '%' . strtolower($req->search) . '%')
                        ->orWhere(DB::raw('lower(province)'), 'like', '%' . strtolower($req->search) . '%');
                })->orWhereHas('customer.profile', function ($query) use ($req) {
                    $query->where(DB::raw('lower(first_name)'), 'like', '%' . $req->search . '%')->orWhere(DB::raw('lower(last_name)'), 'like', '%' . $req->search . '%');
                })->orWhere('date', 'like', '%' . $req->search . '%');
            }


            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->get();

            $data = $data->get()->each(function ($item) use ($rating) {

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

    public function show($uuid)
    {
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
