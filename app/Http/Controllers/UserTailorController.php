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
                    $user = Auth::guard('userTailor')->user();
                    $user->tokens()->delete();
                    $token = $user->createToken('authTailor')->plainTextToken;

                    $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'))
                        ->groupBy('user_tailor_id');

                    $userTailor = UserTailor::joinSub($rating, 'rating', function ($join) {
                        $join->on('user_tailors.id', '=', 'rating.user_tailor_id');
                    })->join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'rating.rating', 'user_tailor_details.id as profile_id')->where('user_tailors.uuid', $user['uuid'])->first();

                    return ResponseFormatter::success(["user Tailor" => $userTailor, "rating" => $rating, "user" => $user], 'Login Successful');


                    return ResponseFormatter::success(
                        [
                            'access_token' => $token,
                            'token_type' => 'Bearer',
                            'user' => $userTailor,
                        ],
                        'Login Successful'
                    );
                } else {
                    return ResponseFormatter::error([], 'Invalid Credentials', 401);
                }
            }
        } catch (\Exception $err) {
            return ResponseFormatter::error($err->getMessage(), 'terjadi kesalahan', $err->getCode());
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
            return ResponseFormatter::error($err->getMessage(), 'terjadi kesalahan', $err->getCode());
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
            $search = $req->input('search');
            $name = $req->input('name');
            $address = $req->input('address');
            $star = $req->input('rating');
            $recommended = $req->has('recommended');
            $sort = $req->input('sort');
            $order = $req->input('order', 'asc');

            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))
                ->groupBy('user_tailor_id');

            // $rating = DB::table('reviews')->selectRaw("COUNT(rating) as total, user_tailor_id, CAST(AVG(rating) AS DECIMAL(5,0)) as rating")->groupBy("user_tailor_id")->get();
            // return ResponseFormatter::success($rating, "Rating berhasil dit");

            $query = UserTailor::joinSub($rating, 'rating', function ($join) {
                $join->on('user_tailors.id', '=', 'rating.user_tailor_id');
            })->join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'rating.rating', "rating.total_review", 'user_tailor_details.id as profile_id');

            if (!$req->has('all')) {
                $query = $query->where('is_ready', 1);
            }

            if ($recommended) {
                $query = $query->where('is_premium', 1)->orderByDesc('rating');
            }

            if ($req->has('premium')) {
                $premium = $premium == null || $premium >= 1 ? 1 : $premium;
                $query = $query->where('is_premium', +$premium);
            }
            // if ($speciality) {
            //     $query->whereHas('profile', function ($q) use ($speciality) {
            //         $q->where(DB::raw('lower(speciality)'), $speciality);
            //     });
            // }
            if ($search) {
                $query->whereHas('profile', function ($q) use ($search) {
                    $q->where(DB::raw('lower(first_name)'), 'like', '%' . strtolower($search) . '%')
                        ->orWhere(DB::raw('lower(last_name)'), 'like', '%' . strtolower($search) . '%')
                        ->orWhere(DB::raw('lower(address)'), 'like', '%' . strtolower($search) . '%')
                        ->orWhere(DB::raw('lower(district)'), 'like', '%' . strtolower($search) . '%')
                        ->orWhere(DB::raw('lower(province)'), 'like', '%' . strtolower($search) . '%')
                        ->orWhere(DB::raw('lower(city)'), 'like', '%' . strtolower($search) . '%');
                });
            }

            if ($name) {
                $query->whereHas('profile', function ($q) use ($name) {
                    $q->where(DB::raw('lower(first_name)'), 'like', '%' . strtolower($name) . '%')
                        ->orWhere(DB::raw('lower(last_name)'), 'like', '%' . strtolower($name) . '%');
                });
            }

            if ($address) {
                $query->whereHas('profile', function ($q) use ($address) {
                    $q->where(DB::raw('lower(address)'), 'like', '%' . strtolower($address) . '%')
                        ->orWhere(DB::raw('lower(district)'), 'like', '%' . strtolower($address) . '%')
                        ->orWhere(DB::raw('lower(city)'), 'like', '%' . strtolower($address) . '%')
                        ->orWhere(DB::raw('lower(province)'), 'like', '%' . strtolower($address) . '%');
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

            return ResponseFormatter::success($query, $query->count() . ' Tailors berhasil ditemukan');
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'terjadi kesalahan', 500);
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
                'first_name' => ['required', 'string', 'min:3', 'max:255'],
                'last_name' => ['required', 'string', 'min:3', 'max:255'],
                'address' => ['required', 'string', 'max:255'],
                'district' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'province' => ['required', 'string', 'max:255'],
                'zip_code' => ['required', 'string', 'max:255'],
                // 'profile_picture' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
                // 'phone_number' => ['nullable', 'string', 'max:15'],
                // 'speciality' => ['nullable', 'string', 'max:255'],
            ]);


            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), 'Invalid Input', 422);
            }

            $profilePicture = $request->hasFile('profile_picture') ?  asset('storage/' . $request->file('profile_picture')->store('images/tailor/profile', 'public')) : null;
            $placePicture = $request->hasFile('place_picture') ?  asset('storage/' . $request->file('place_picture')->store('images/tailor/place', 'public')) : null;

            $validatedData = $validator->validated();
            $validatedData['password'] = Hash::make($validatedData['password']);
            $validatedData['profile_picture'] = $profilePicture;
            $validatedData['place_picture'] = $placePicture;
            $userTailor = UserTailor::create($validatedData)->id;

            $validatedData['user_tailor_id'] = $userTailor;
            UserTailorDetail::create($validatedData);

            $userTailor = UserTailor::with('profile')->find($userTailor)->makeHidden(['created_at', 'updated_at']);

            $userTailor->tokens()->delete();
            $tokenResult = $userTailor->createToken('authTailor')->plainTextToken;


            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $userTailor
            ], 'User Tailor berhasil dibuat');
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
    public function show($uuid)
    {
        try {
            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'))
                ->groupBy('user_tailor_id');

            $userTailor = UserTailor::joinSub($rating, 'rating', function ($join) {
                $join->on('user_tailors.id', '=', 'rating.user_tailor_id');
            })->join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'rating.rating', 'user_tailor_details.id as profile_id')->where('user_tailors.uuid', $uuid)->first();
            // $userTailor = UserTailor::with('profile')->find($uuid);
            if (!$userTailor) {
                return ResponseFormatter::error(null, 'User Tailor tidak ditemukan', 404);
            }

            $userTailor["availability"] = collect();
            $userTailor->availability()->get(["date", "time",])->groupBy('date')->values()->each(function ($item) use (&$availability, $userTailor) {
                $userTailor["availability"]->push([
                    "date" => $item->first()->date,
                    "time" => $item->pluck('time')->toArray()
                ]);
            });
            $userTailor["availability"] = $userTailor["availability"]->count() > 0 ? $userTailor["availability"] : null;
            // $availability = $availability->map(function ($item) {

            //     return $item = collect([
            //         'date' => $item->date,
            //         'time' => $item->time,
            //     ]);
            // });

            // return $availability;
            // $userTailor["availability"] = $userTailor->availability()->get();
            return ResponseFormatter::success($userTailor, 'User Tailor berhasil ditemukan');
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'terjadi kesalahan', 500);
        }
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
