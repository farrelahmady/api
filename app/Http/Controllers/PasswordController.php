<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\UserTailor;
use App\Models\User\UserCustomer;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as RulesPassword;

class PasswordController extends Controller
{
  public function forgotPassword(Request $req, $user_type)
  {
    try {
      $validator = Validator::make($req->all(), [
        'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'exists:user_'  . $user_type . 's,email'],
      ]);

      if ($validator->fails()) {
        return ResponseFormatter::error($validator->errors(), 'Invalid Input', 422);
      }

      $email = $validator->validated()['email'];
      $token = Str::random(64);

      $user = $user_type == 'tailor' ? UserTailor::with('profile') : UserCustomer::with('profile');
      $user = $user->where('email', $email)->first();
      // $user = $user_type == 'tailor' ? UserTailor::with('profile')->where('email', $email)->first() : UserCustomer::with('profile')->where('email', $email)->first();

      if (!$user) {
        return ResponseFormatter::error([], 'User not found', 404);
      }

      $url = 'http://secret-forest-17845.herokuapp.com/reset-password?email=' . $email . '&token=' . $token;
      Mail::send('email.resetPasswordMail', ['url' => $url, 'user' => $user], function ($message) use ($user) {
        $message->to($user->email, $user->profile->first_name . " " . $user->profile->last_name)->subject('Reset Password');
      });

      DB::table('password_resets')->insert([
        'email' => $email,
        'token' => $token,
        'created_at' => \Carbon\Carbon::now(),
      ]);
      return ResponseFormatter::success(
        [
          'email' => $email,
          'token' => $token,
        ],
        'Password reset link has been sent to your email.'
      );
    } catch (\Exception $err) {
      // return $err->getCode();
      return ResponseFormatter::error($err->getMessage(), 'Something went wrong', 500);
    }
  }

  public function resetPassword(Request $request, $user_type)
  {

    try {
      $validator = Validator::make($request->all(), [
        'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'exists:user_'  . $user_type . 's,email'],
        'token' => ['required', 'string', 'exists:password_resets,token'],
        'password' => ['required', 'string', RulesPassword::min(8)->numbers()->letters()],
      ]);

      if ($validator->fails()) {
        return ResponseFormatter::error($validator->errors(), 'Invalid Input', 422);
      }

      $validData = $validator->validated();

      $check_token = DB::table('password_resets')->where([
        'email' => $validData['email'],
        'token' => $validData['token'],
      ])->first();

      if (!$check_token) {
        return ResponseFormatter::error([], 'Invalid token', 404);
      }

      $user = $user_type == 'tailor' ? new UserTailor() : new UserCustomer();

      $user = $user->where('email', $validData['email'])->update([
        'password' => Hash::make($validData['new_password']),
      ]);



      DB::table('password_resets')->where([
        'email' => $validData['email'],
        'token' => $validData['token'],
      ])->delete();

      return ResponseFormatter::success([], 'Password has been reset successfully');
    } catch (\Exception $err) {
      return ResponseFormatter::error($err->getMessage(), 'Something went wrong', 500);
    }
  }
}
