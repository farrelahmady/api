<?php

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Middleware;
use App\Models\UserCustomer;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Models\UserCustomerDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserCustomerController extends Controller
{
  public function register(Request $request)
  {

    try {
      $validator = Validator::make($request->all(), [
        'email' => 'required|string|email:rfc,dns|max:255|unique:user_customers',
        'password' => ['required', 'string', Password::min(8)->numbers()],
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'address' => 'nullable|string|max:255',
        'phone_number' => 'nullable|string|max:255|min:10',
        'profile_picture' => ['image', 'max:2048'],
      ]);

      if ($validator->fails()) {
        return ResponseFormatter::error(['error' => $validator->errors()], 'Authentication Failed', 500);
      }
      $image = $request->hasFile('profile_picture') ?  asset('storage/' . $request->file('profile_picture')->store('images/customer/profile', 'public')) : null;

      $userCustomer = UserCustomer::create([
        'email' => $request->email,
        'password' => Hash::make($request->password)
      ])->id;
      UserCustomerDetail::create([
        'user_customer_id' => $userCustomer,
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'address' => $request->address,
        'phone_number' => $request->phone_number,
        'profile_picture' => $image
      ]);

      $userCustomer = UserCustomer::with('userCustomerDetail')->find($userCustomer);

      $tokenResult = $userCustomer->createToken('authToken')->plainTextToken;

      return ResponseFormatter::success(
        [
          'access_token' => $tokenResult,
          'token_type' => 'Bearer',
          'user' => $userCustomer
        ],
        'User Registered Successfully'
      );
    } catch (Exception $err) {
      return ResponseFormatter::error(
        [
          'message' => 'Something went wrong',
          'error' => $err
        ],
        'Authentication Failed',
        500
      );
    }
  }

  public function login(Request $request)
  {

    try {
      $validator = Validator::make(['email' => $request->email, 'password' => $request->password], [
        'email' => 'email:rfc,dns|required',
        'password' => 'required'
      ]);

      if ($validator->fails()) {
        return ResponseFormatter::error(['error' => $validator->errors()], 'Authentication Failed', 401);
      }


      $credetials = request(['email', 'password']);
      if (!Auth::guard('userCustomers')->attempt($credetials)) {
        return ResponseFormatter::error([
          'message' => 'Unauthorized'
        ], 'Authentication Failed', 401);
      }

      $user = UserCustomer::with('userCustomerDetail')->find(Auth::guard('userCustomers')->user()->id);

      $tokenResult = $user->createToken('authToken')->plainTextToken;

      return ResponseFormatter::success(
        [
          'access_token' => $tokenResult,
          'token_type' => 'Bearer',
          'user' => $user
        ],
        'Authenticated'
      );

      if (!Hash::check($request->password, $user->password)) {
        throw new \Exception("Invalid Credentials");
      }
    } catch (Exception $err) {
      return ResponseFormatter::error(
        [
          'message' => 'Something went wrong',
          'error' => $err
        ],
        'Authentication Failed',
        500
      );
    }
  }

  public function logout(Request $request)
  {
    try {
      $token = $request->user()->currentAccessToken()->delete();

      return ResponseFormatter::success(
        $token,
        'Logout Successfully'
      );
    } catch (Exception $err) {
      return ResponseFormatter::error(
        [
          'message' => 'Something went wrong',
          'error' => $err
        ],
        'Logout Failed',
        500
      );
    }
  }

  public function fetch(Request $request)
  {
    return ResponseFormatter::success(
      Auth()->user(),
      'User Fetched Successfully'
    );
  }
}
