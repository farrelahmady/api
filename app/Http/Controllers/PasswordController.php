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
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Notifications\ResetPassword;
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
                return ResponseFormatter::error($validator->errors(), 'masukan tidak valid', 422);
            }

            // Password::broker('admins');
            // We will send the password reset link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            ResetPassword::createUrlUsing(function ($notifiable, $token) use ($user_type) {
                return config('app.frontend_url') . "/reset-password/$user_type?token=$token&email={$notifiable->getEmailForPasswordReset()}";
            });
            $status = Password::broker($user_type)->sendResetLink(
                $req->only('email')
            );

            return $status == Password::RESET_LINK_SENT
                ? ResponseFormatter::success(null, __($status))
                : ResponseFormatter::error(['error' => __($status)], 'terjadi kesalahan', 500);

            //     $email = $validator->validated()['email'];
            // $token = Str::random(64);

            // $user = $user_type == 'tailor' ? UserTailor::with('profile') : UserCustomer::with('profile');
            // $user = $user->where('email', $email)->first();


            // if (!$user) {
            //     return ResponseFormatter::error([], 'User not found', 404);
            // }

            // $url = 'https://secret-forest-17845.herokuapp.com/reset-password?email=' . $email . '&token=' . $token;
            // Mail::send('email.resetPasswordMail', ['url' => $url, 'user' => $user], function ($message) use ($user) {
            //     $message->to($user->email, $user->profile->first_name . " " . $user->profile->last_name)->subject('Reset Password');
            // });

            // DB::table('password_resets')->insert([
            //     'email' => $email,
            //     'token' => $token,
            //     'created_at' => \Carbon\Carbon::now(),
            // ]);
            // return ResponseFormatter::success(
            //     [
            //         'email' => $email,
            //         'token' => $token,
            //     ],
            //     'Password reset link has been sent to your email.'
            // );
        } catch (\Exception $err) {
            // return $err->getCode();
            return ResponseFormatter::error($err->getMessage(), 'terjadi kesalahan', 500);
        }
    }

    public function resetPassword(Request $request, $user_type)
    {

        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'exists:user_'  . $user_type . 's,email'],
                'token' => ['required'],
                'password' => ['required', 'confirmed', 'string', RulesPassword::min(8)->numbers()->letters()],
                'password_confirmation' => ['required', 'string', RulesPassword::min(8)->numbers()->letters()]
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), 'masukan tidak valid', 422);
            }

            // Here we will attempt to reset the user's password. If it is successful we
            // will update the password on an actual user model and persist it to the
            // database. Otherwise we will parse the error and return the response.
            $status = Password::broker($user_type)->reset(
                $request->only('email', 'password', 'token'),
                function ($user) use ($request) {
                    // dd($user);
                    $user->update([
                        'password' => Hash::make($request->password)
                    ]);

                    event(new PasswordReset($user));
                }
            );

            // If the password was successfully reset, we will redirect the user back to
            // the application's home authenticated view. If there is an error we can
            // redirect them back to where they came from with their error message.
            if ($status == Password::PASSWORD_RESET) {

                return ResponseFormatter::success(null, __($status));
            } else {
                # code...
                return ResponseFormatter::error(['error' => __($status)], 'terjadi kesalahan', 500);
            }


            $validData = $validator->validated();

            $check_token = DB::table('password_resets')->where([
                'email' => $validData['email'],
                'token' => $validData['token'],
            ])->first();

            if (!$check_token) {
                return ResponseFormatter::error([], 'token tidak balid', 404);
            }

            $user = $user_type == 'tailor' ? new UserTailor() : new UserCustomer();

            $user = $user->where('email', $validData['email'])->update([
                'password' => Hash::make($validData['password']),
            ]);



            DB::table('password_resets')->where([
                'email' => $validData['email'],
                'token' => $validData['token'],
            ])->delete();

            return ResponseFormatter::success([], 'Password telah berhasil diatur ulang');
        } catch (\Exception $err) {
            return ResponseFormatter::error($err->getMessage(), 'terjadi kesalahan', 500);
        }
    }
}
