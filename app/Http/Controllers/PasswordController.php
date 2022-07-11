<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User\UserTailor;
use App\Helpers\ResponseFormatter;
use App\Models\User\UserCustomer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class PasswordController extends Controller
{
  public function forgotPassword(Request $req, $user_type)
  {
    try {
      if (!$req->has('action_link')) {
        throw new \Exception('Action Link is required', 406);
      }

      $validator = \Validator::make($req->all(), [
        'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'exists:user_'  . $user_type . 's,email'],
      ]);

      if ($validator->fails()) {
        return ResponseFormatter::error($validator->errors(), 'Invalid Input', 422);
      }

      $email = $validator->validated()['email'];
      $token = \Str::random(64);

      $user = $user_type == 'tailor' ? UserTailor::with('profile') : UserCustomer::with('profile');
      $user = $user->where('email', $email)->first();
      // $user = $user_type == 'tailor' ? UserTailor::with('profile')->where('email', $email)->first() : UserCustomer::with('profile')->where('email', $email)->first();

      if (!$user) {
        return ResponseFormatter::error([], 'User not found', 404);
      }

      $action_link = $req->action_link . '?token=' . $token;
      Mail::send('email.resetPasswordMail', ['action_link' => $action_link, 'user' => $user], function ($message) use ($user) {
        $message->to($user->email, $user->profile->first_name . " " . $user->profile->last_name)->subject('Reset Password');
      });

      \DB::table('password_resets')->insert([
        'email' => $email,
        'token' => $token,
        'created_at' => \Carbon\Carbon::now(),
      ]);
      return ResponseFormatter::success(
        [
          'email' => $email,
          'token' => $token,
          'action_link' => $action_link,
        ],
        'Password reset link has been sent to your email.'
      );
    } catch (\Exception $err) {
      // return $err->getCode();
      return ResponseFormatter::error($err->getMessage(), 'Something went wrong', 500);
    }
  }

  public function resetPassword(Request $request)
  {
    return $request->token;
  }
}
