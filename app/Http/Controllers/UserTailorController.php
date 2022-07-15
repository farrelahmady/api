<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User\UserTailor;
use App\Helpers\ResponseFormatter;
use App\Models\Operational\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use App\Models\ManagementAccess\UserTailorDetail;
use Illuminate\Validation\Rules\Password as RulesPassword;

class UserTailorController extends Controller
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
        $validator = Validator::make($request->all(), [
          'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'exists:user_tailors,email'],
          'password' => ['required', 'string', RulesPassword::min(8)->numbers()->letters()],
        ]);

        if ($validator->fails()) {
          return ResponseFormatter::error($validator->errors(), 'Invalid Input', 422);
        }

        if (Auth::guard('userTailor')->attempt(['email' => $request->email, 'password' => $request->password])) {
          $user = Auth::guard('userTailor')->user()->id;
          $user = UserTailor::with('profile')->find($user)->makeHidden(['created_at', 'updated_at']);
          $token = $user->createToken('authTailor')->plainTextToken;
          return ResponseFormatter::success(
            [
              'access_token' => $token,
              'token_type' => 'Bearer',
              'user' => $user,
            ],
            'Login Successful'
          );
        } else {
          return ResponseFormatter::error([], 'Invalid Credentials', 401);
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
        $token = auth('sanctum')->user()->currentAccessToken()->delete();
        return ResponseFormatter::success(['token' => $token], 'Logout Successful');
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
  public function index(Request $req)
  {

    try {
      $paginate = $req->paginate;
      $limit = $req->limit;
      $premium = $req->input('premium');
      $speciality = $req->input('speciality');
      $name = $req->input('name');
      $address = $req->input('address');
      $star = $req->input('rating');
      $recommended = $req->has('recommended');
      $sort = $req->input('sort');
      $order = $req->input('order', 'asc');

      $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'))
        ->groupBy('user_tailor_id');

      $query = UserTailor::joinSub($rating, 'rating', function ($join) {
        $join->on('user_tailors.id', '=', 'rating.user_tailor_id');
      })->join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'rating.rating', 'user_tailor_details.id as profile_id');
      if ($recommended) {
        $query = $query->orderByDesc('is_premium')->orderByDesc('rating');
      }

      if ($req->has('premium')) {
        $premium = $premium == null || $premium >= 1 ? 1 : $premium;
        $query = $query->where('is_premium', +$premium);
      }
      if ($speciality) {
        $query->whereHas('profile', function ($q) use ($speciality) {
          $q->where(DB::raw('lower(speciality)'), $speciality);
        });
      }
      if ($name) {
        $query->whereHas('profile', function ($q) use ($name) {
          $q->where(DB::raw('lower(first_name)'), 'like', '%' . strtolower($name) . '%')->orWhere(DB::raw('lower(last_name)'), 'like', '%' . strtolower($name) . '%');
        });
      }
      if ($address) {
        $query->whereHas('profile', function ($q) use ($address) {
          $q->where(DB::raw('lower(address)'), 'like', '%' . strtolower($address) . '%');
        });
      }
      if ($star) {
        $query->where('rating', '>=', $star);
      }
      if ($limit) {
        $query = $query->take($limit);
      }
      if ($sort) {
        // return $order;
        $sort = explode(',', $sort);
        foreach ($sort as $s) {
          if (in_array($s, Schema::getColumnListing('user_tailor_details')) || in_array($s, Schema::getColumnListing('user_tailors')) || $s == 'rating') {
            $query = $query->orderBy($s, $order);
          }
        }
      }
      $query = $paginate ? $query->paginate($paginate) : $query->get()->makeHidden(['created_at', 'updated_at']);


      if ($query->count() <= 0) {
        return ResponseFormatter::error(null, 'No Tailor found', 404);
      }

      return ResponseFormatter::success($query, 'Tailors retrieved successfully');
    } catch (\Exception $e) {
      return ResponseFormatter::error($e->getMessage(), 'Something Went Wrong', 500);
    }
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
      $validator = Validator::make($request->all(), [
        'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:user_tailors'],
        'password' => ['required', 'string', RulesPassword::min(8)->numbers()->letters()],
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'profile_picture' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        'address' => ['nullable', 'string', 'max:255'],
        'phone_number' => ['nullable', 'string', 'max:15'],
        'speciality' => ['nullable', 'string', 'max:255'],
      ]);


      if ($validator->fails()) {
        return ResponseFormatter::error($validator->errors(), 'Invalid Input', 422);
      }

      $image = $request->hasFile('profile_picture') ?  asset('storage/' . $request->file('profile_picture')->store('images/tailor/profile', 'public')) : null;

      $validatedData = $validator->validated();
      $validatedData['password'] = Hash::make($validatedData['password']);
      $validatedData['profile_picture'] = $image;
      $userTailor = UserTailor::create($validatedData)->id;

      $validatedData['user_tailor_id'] = $userTailor;
      UserTailorDetail::create($validatedData);

      $userTailor = UserTailor::with('profile')->find($userTailor)->makeHidden(['created_at', 'updated_at']);

      $tokenResult = $userTailor->createToken('authTailor')->plainTextToken;


      return ResponseFormatter::success([
        'access_token' => $tokenResult,
        'token_type' => 'Bearer',
        'user' => $userTailor
      ], 'User Tailor Created Successfully');
    } catch (\Exception $e) {
      return ResponseFormatter::error(500, $e->getMessage(), 500);
    }
  }

  /**
   * Display the specified resource.
   *
   * @param  \App\Models\UserTailor  $userTailor
   * @return \Illuminate\Http\Response
   */
  public function show(UserTailor $userTailor)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \App\Models\UserTailor  $userTailor
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, UserTailor $userTailor)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  \App\Models\UserTailor  $userTailor
   * @return \Illuminate\Http\Response
   */
  public function destroy(UserTailor $userTailor)
  {
    //
  }
}
