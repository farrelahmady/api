<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
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
            $user->tokens()->delete();
            $token = $user->createToken('admin')->plainTextToken;
            return ResponseFormatter::success(
                [
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
}
