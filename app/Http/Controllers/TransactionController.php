<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Http;
use App\Models\Operational\Transaction;
use App\Models\User\UserTailor;
use Carbon\Carbon;

class TransactionController extends Controller
{
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
            $transaction = Transaction::create([
                'user_tailor_id' => $tailor->uuid,
                'transaction_code' => "SUPERTAILOR-" . Str::random(4) . now()->format('YmdHis'),
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



            $transaction['midtrans'] = $status;


            return ResponseFormatter::success($transaction, "Transaksi berhasil dilakukan");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), message: "Internal Server Error", code: 500);
        }
    }
}
