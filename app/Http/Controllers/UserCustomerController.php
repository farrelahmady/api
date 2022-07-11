<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User\UserCustomer;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ManagementAccess\UserCustomerDetail;
use Illuminate\Validation\Rules\Password as RulesPassword;


class UserCustomerController extends Controller
{
  public function login(Request $request)
  {
    try {
      if (auth('sanctum')->check()) {
        return ResponseFormatter::success(
          'You are already logged in.',
          [
            'access_token' => auth('sanctum')->user()->token,
            'token_type' => 'Bearer',
            'user' => auth('sanctum')->user(),
          ]
        );
      } else {

        $validator = \Validator::make($request->all(), [
          'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'exists:user_customers,email'],
          'password' => ['required', 'string', RulesPassword::min(8)->numbers()->letters()],
        ]);

        if ($validator->fails()) {
          return ResponseFormatter::error($validator->errors(), 'Invalid Input', 422);
        }

        if (Auth::guard('userCustomer')->attempt(['email' => $request->email, 'password' => $request->password])) {
          $user = Auth::guard('userCustomer')->user()->id;
          $user = UserCustomer::with('profile')->find($user)->makeHidden(['created_at', 'updated_at']);
          $token = $user->createToken('userCustomer')->plainTextToken;
          return ResponseFormatter::success(
            [
              'access_token' => $token,
              'token_type' => 'Bearer',
              'user' => $user,
            ],
            'Login Successful'
          );
        }
      }
    } catch (\Exception $err) {
      return ResponseFormatter::error($err->getMessage(), 'Something went wrong', $err->getCode());
    }
  }

  public function logout()
  {
    try {
      if (auth('sanctum')->check()) {
        auth('sanctum')->user()->currentAccessToken()->delete();
        return ResponseFormatter::success('Logout Successful');
      } else {
        return ResponseFormatter::error('You are not logged in.', 'Logout Failed', 401);
      }
    } catch (\Exception $err) {
      return ResponseFormatter::error($err->getMessage(), 'Something went wrong', $err->getCode());
    }
  }
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
    try {
      $validator = \Validator::make($request->all(), [
        'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:user_customers'],
        'password' => ['required', 'string', RulesPassword::min(8)->numbers()->letters()],
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'profile_picture' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        'address' => ['nullable', 'string', 'max:255'],
        'phone_number' => ['nullable', 'string', 'max:15'],
      ]);


      if ($validator->fails()) {
        return ResponseFormatter::error($validator->errors(), 'Invalid Input', 422);
      }

      $image = $request->hasFile('profile_picture') ?  asset('storage/' . $request->file('profile_picture')->store('images/customer/profile', 'public')) : null;

      $validatedData = $validator->validated();
      $validatedData['password'] = Hash::make($validatedData['password']);
      $validatedData['profile_picture'] = $image;
      $userCustomer = UserCustomer::create($validatedData)->id;

      $validatedData['user_customer_id'] = $userCustomer;
      UserCustomerDetail::create($validatedData);

      $userCustomer = UserCustomer::with('profile')->find($userCustomer)->makeHidden(['created_at', 'updated_at']);

      $tokenResult = $userCustomer->createToken('authToken')->plainTextToken;


      return ResponseFormatter::success([
        'access_token' => $tokenResult,
        'token_type' => 'Bearer',
        'user' => $userCustomer
      ], 'User Customer Created Successfully');
    } catch (\Exception $e) {
      return ResponseFormatter::error(500, $e->getMessage(), 500);
    }
  }

  /**
   * Display the specified resource.
   *
   * @param  \App\Models\UserCustomer  $userCustomer
   * @return \Illuminate\Http\Response
   */
  public function show(UserCustomer $userCustomer)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \App\Models\UserCustomer  $userCustomer
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, UserCustomer $userCustomer)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  \App\Models\UserCustomer  $userCustomer
   * @return \Illuminate\Http\Response
   */
  public function destroy(UserCustomer $userCustomer)
  {
    //
  }
}
