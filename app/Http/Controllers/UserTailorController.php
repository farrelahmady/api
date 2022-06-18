<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\UserTailor;
use Illuminate\Http\Request;
use App\Models\UserTailorDetail;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserTailorController extends Controller
{
  public function register(Request $request)
  {

    try {
      $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6|',
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'address' => 'nullable|string|max:255',
        'phone_number' => 'nullable|string|max:255',
        'profile_picture' => ['image', 'size:2048'],
        'speciality' => 'nullable|string|max:255'
      ]);

      if ($validator->fails()) {
        return ResponseFormatter::error(['error' => $validator->errors()], 'Authentication Failed', 500);
      }

      $userTailor = UserTailor::create([
        'email' => $request->email,
        'password' => Hash::make($request->password)
      ])->id;

      $image = $request->hasFile('profile_picture') ?  asset('storage/' . $request->file('profile_picture')->store('images/tailor/profile', 'public')) : null;

      UserTailorDetail::create([
        'user_tailor_id' => $userTailor,
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'address' => $request->address,
        'phone_number' => $request->phone_number,
        'profile_picture' => $image,
        'speciality' => $request->speciality
      ]);

      $userTailor = UserTailor::with('userTailorDetail')->find($userTailor);

      $tokenResult = $userTailor->createToken('authToken')->plainTextToken;

      return ResponseFormatter::success(
        [
          'access_token' => $tokenResult,
          'token_type' => 'Bearer',
          'user' => $userTailor
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
        'email' => 'email|required',
        'password' => 'required'
      ]);

      if ($validator->fails()) {
        return ResponseFormatter::error(['error' => $validator->errors()], 'Authentication Failed', 500);
      }


      $credetials = request(['email', 'password']);
      if (!Auth::guard('userTailors')->attempt($credetials)) {
        return ResponseFormatter::error([
          'message' => 'Unauthorized'
        ], 'Authentication Failed', 500);
      }

      $user = UserTailor::with('userTailorDetail')->find(Auth::guard('userTailors')->user()->id);

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
