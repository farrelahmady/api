<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Models\Operational\Appointment;
use Illuminate\Support\Facades\Validator;
use App\Models\ManagementAccess\Availability;

class AppointmentController extends Controller
{
    public function store(Request $req)
    {
        // return ResponseFormatter::success($req->all(), 'Data berhasil ditambahkan');
        try {
            Carbon::setLocale('id');

            $user = auth('sanctum')->user();
            $validator = Validator::make($req->all(), [
                'user_tailor_id' => 'required|uuid|exists:user_tailors,uuid',
                'date' => 'required|date',
                'time' => 'required|date_format:H:i',
                'additional_message' => 'nullable|min:10',
            ]);



            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), 'Masukan Tidak Valid', 422);
            }
            $dateTime = Carbon::createFromFormat('Y-m-d H:i', $req->date . " " . $req->time)->format("Y-m-d H:i:s");
            if ($dateTime < Carbon::now()->format("Y-m-d H:i:s")) {
                return ResponseFormatter::error(["message" => "Tanggal dan Waktu yang dipilih telah dilewati dari tanggal dan waktu sekarang"], 'Bad Request', 400);
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
            $appointment = Appointment::where('user_tailor_id', $req->user_tailor_id)->where('date', $data['date'])->where('time', $data['time'])->get();

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
}
