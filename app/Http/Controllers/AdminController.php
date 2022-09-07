<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Session\Store;
use App\Models\User\UserTailor;
use App\Helpers\ResponseFormatter;
use App\Models\Operational\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\Null_;
use Illuminate\Validation\Rules\Password as RulesPassword;

class AdminController extends Controller
{
    public function login(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'email' => 'required|email:rfc,dns',
                'password' => ['required', 'string', RulesPassword::min(8)->numbers()->letters()->symbols()],
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error(null, "Invalid credentials", 401);
            }

            if (!Auth::guard('admin')->attempt(['email' => $req->email, 'password' => $req->password])) {
                return ResponseFormatter::error(null, "Invalid credentials", 401);
            }
            $user = Auth::guard('admin')->user();
            // $user->tokens()->delete();
            $token = $user->createToken('admin')->plainTextToken;
            // $loginKey = collect(session()->all())->keys()->filter(fn ($file) => strpos($file, 'login_admin') !== false)->values()->implode("");
            return ResponseFormatter::success(
                [
                    // "session_id" => session()->getId(),
                    // "login_id" => $loginKey,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user,
                ],
                'Login berhasil'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi Kesalaahan", 500);
        }
    }

    public function logout(Request $req)
    {
        try {
            // $sessionId = $req->session_id;
            // $s = new \Illuminate\Session\Store(NULL, Session::getHandler(), $sessionId);
            // $s->start();
            // $userID = $s->get($req->login_id);
            // Session::put($req->login_id, $userID);

            $user = Auth::guard('sanctum')->user();
            // return ResponseFormatter::success($user->tokens()->delete(), "Logout berhasil");
            if ($user) {
                $user->tokens()->delete();
                $token = $req->bearerToken();
                return ResponseFormatter::success(['token' => $token . " Berhasil dihapus"], 'Logout berhasil');
            } else {
                return ResponseFormatter::error('Anda belum login.', 'Logout gagal', 401);
            }
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi Kesalaahan", 500);
        }
    }

    public function changePassword(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'old_password' => ['required', 'string', RulesPassword::min(8)->numbers()->letters()->symbols()],
                'password' => ['required_with:old_password', 'confirmed', 'string', RulesPassword::min(8)->numbers()->letters()->symbols()],
                'password_confirmation' => ['required_with:password', 'string', RulesPassword::min(8)->numbers()->letters()->symbols()],
            ]);
            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), "Invalid credentials", 401);
            }
            $user = Auth::guard('sanctum')->user();

            // return $user;

            if (!Hash::check($req->old_password, $user->password)) {
                return ResponseFormatter::error(null, "Invalid credentials", 401);
            }
            $user->password = Hash::make($req->password);
            $user->save();
            return ResponseFormatter::success($user, "Password berhasil diubah");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi Kesalaahan", 500);
        }
    }

    public function loginAs(Request $req, $uuid)
    {
        try {
            $user = UserTailor::where('uuid', $uuid)->first();
            if (!$user) {
                return ResponseFormatter::error([], 'User not found', 404);
            }

            $token = $user->createToken('authTailor')->plainTextToken;

            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->where('user_tailor_id', $user->id)->first();

            $userTailor = UserTailor::join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id')->where('user_tailors.uuid', $user['uuid'])->first();
            if ($rating === null) {
                $userTailor->rating = 0;
                $userTailor->total_review = 0;
            } else {
                collect($rating)->keys()->map(function ($key) use ($rating, $userTailor) {
                    if ($key != 'user_tailor_id') {
                        $userTailor->{$key} = $rating->{$key};
                    }
                });
            }
            return ResponseFormatter::success(
                [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $userTailor,
                ],
                'Login Successful'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'terjadi kesalahan sistem', 500);
        }
    }
}
