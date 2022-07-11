<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User\UserTailor;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class PasswordController extends Controller
{
  public function forgotPassword(Request $req)
  {
    try {
      $validator = \Validator::make($req->all(), [
        'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'exists:user_tailors,email'],
      ]);

      if ($validator->fails()) {
        return ResponseFormatter::error($validator->errors(), 'Invalid Input', 422);
      }

      $validatedData = $validator->validated();

      $url = route('password.reset') . '?token=' . \Str::random(10);
      $email = Mail::send('email.resetPasswordMail', ['url' => $url], function ($message) {
        $message->to('john@johndoe.com', 'John Doe');
        $message->subject('Reset Password');
      });

      return ResponseFormatter::success(
        [
          'email' => $validatedData['email'],
          'resetUrl' => $url,
        ],
        'Password reset link has been sent to your email.'
      );
      // ? ResponseFormatter::success('Password reset link sent to your email.')
      // : ResponseFormatter::error('Something went wrong.', 'Password reset link not sent.', 500);
      // $token = UserTailor::where('email', $validatedData['email'])->first()->createToken('authToken')->plainTextToken;
      // return ResponseFormatter::success(
      //   [
      //     'access_token' => $token,
      //     'token_type' => 'Bearer',
      //   ],
      //   'Password Reset Token Sent'
      // );
    } catch (\Exception $err) {
      return ResponseFormatter::error($err->getMessage(), 'Something went wrong', $err->getCode());
    }
  }

  public function resetPassword(Request $request)
  {
    return $request->token;
  }
}
