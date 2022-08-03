<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Session\Store;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Auth;
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
      $user->tokens()->delete();
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
}
