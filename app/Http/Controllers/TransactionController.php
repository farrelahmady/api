<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User\Admin;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\UserTailor;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Operational\Transaction;
use App\Models\ManagementAccess\Midtrans;

class TransactionController extends Controller
{
    public function index(Request $req)
    {
        try {
            $status = $req->status;
            $limit = $req->limit;
            $transactions = Transaction::with('tailor.profile');

            switch (auth()->user()->currentAccessToken()->tokenable_type) {
                case UserTailor::class:
                    $transactions = $transactions->whereHas('tailor', function ($query) {
                        $query->where('uuid', auth()->user()->uuid);
                    });
                    break;
            }

            if ($status) {
                $transactions = $transactions->where('status', $status);
            }

            $transactions = $transactions->latest();

            if ($limit) {
                $transactions = $transactions->limit($limit);
            }
            $transactions = $transactions->get();

            if ($transactions->count() == 0) {
                return ResponseFormatter::error(null, "Transaksi tidak ditemukan", 404);
            }

            return ResponseFormatter::success(
                $transactions,
                count($transactions) . " data transaksi berhasil didapatkan"
            );
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi Kesalahan Sistem", 500);
        }
    }
    public function store(Request $req)
    {
        try {
            $tailor = auth()->user();

            switch ($req->input('category')) {
                case 'monthly':
                    $category = 'monthly';
                    $amount = 55500;
                    break;
                case 'yearly':
                    $category = 'yearly';
                    $amount = 499500;
                    break;

                default:
                    return ResponseFormatter::error(message: 'Layanan tidak tersedia', code: 500);
                    break;
            }
            $transaction_code = "SUPERTAILOR-" . Str::random(4) . now()->format('YmdHis');
            $transaction = Transaction::create([
                'user_tailor_id' => $tailor->uuid,
                'transaction_code' => $transaction_code,
                'category' => $category,
                'gross_amount' => $amount,
            ]);




            //! Set Midtrans Snap Config
            $midtrans = [
                'transaction_details' => [
                    'order_id' => $transaction->transaction_code,
                    'gross_amount' => $transaction->gross_amount,
                ],
                'customer_details' => [
                    'first_name' => $tailor->profile->first_name . " " . $tailor->profile->last_name,
                    'email' => $tailor->email,
                ],
            ];

            $fetch = Http::withBasicAuth(config('services.midtrans.server_key'), '')->post("https://app.sandbox.midtrans.com/snap/v1/transactions", $midtrans);

            if ($fetch->failed()) {
                return ResponseFormatter::error(message: 'Gagal melakukan pembayaran', code: 500);
            }
            // Get Snap Payment Page URL
            $paymentUrl = $fetch->collect()['redirect_url'];

            Midtrans::create([
                'order_id' => $transaction_code,
                'gross_amount' => $amount,
            ]);




            return ResponseFormatter::success([
                "transaction" => $transaction,
                "redirect_url" => $paymentUrl
            ], "Transaksi berhasil dilakukan");
        } catch (\Exception $e) {
            return ResponseFormatter::error(data: $e->getMessage(), message: "Internal Server Error", code: 500);
        }
    }

    public function update(Request $req, $order_id)
    {
        try {
            $tailor = auth()->user();

            $status = Http::withBasicAuth(config('services.midtrans.server_key'), '')->get("https://api.sandbox.midtrans.com/v2/$order_id/status")->collect();

            if ($status['status_code'][0] != 2) {
                return ResponseFormatter::error($status, message: "Transaksi gagal", code: 500);
            }

            $transaction = Transaction::where('transaction_code', $order_id)->first();
            if ($status['transaction_status'] == 'pending') {
                $transaction->status = 1;
            } else if ($status['transaction_status'] == 'settlement') {
                $transaction->status = 2;
                $premiumExpiresOn = $tailor['premium_expires_on'] != null ? Carbon::parse($tailor['premium_expires_on']) : Carbon::now();
                UserTailor::where('uuid', $tailor->uuid)->update([
                    'is_premium' => true,
                    'premium_expires_on' => $transaction['category'] == 'monthly' ? $premiumExpiresOn->addMonth() : $premiumExpiresOn->addYear()
                ]);
            } else {
                $transaction->status = 3;
            }
            $transaction->save();

            $midtransColumn = DB::getSchemaBuilder()->getColumnListing('midtrans');
            $statusKeys = $status->keys();
            $statusKeys->each(function ($key) use ($midtransColumn, $status, $transaction) {
                if (!in_array($key, $midtransColumn)) {
                    $status->forget($key);
                }
            });

            //return $status;
            Midtrans::where('order_id', $order_id)->update($status->toArray());



            $transaction = Transaction::where('transaction_code', $order_id)->first();


            return ResponseFormatter::success($transaction, "Transaksi berhasil dilakukan");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), message: "Internal Server Error", code: 500);
        }
    }
}
