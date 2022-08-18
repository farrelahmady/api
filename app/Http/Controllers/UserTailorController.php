<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\UserTailor;
use App\Helpers\ResponseFormatter;
use App\Models\Operational\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\ManagementAccess\UserTailorDetail;
use Illuminate\Validation\Rules\Password as RulesPassword;

class UserTailorController extends Controller
{
    public function authCheck(Request $req)
    {
        try {
            $user = Auth::guard("sanctum")->user();
            if ($user && in_array($user->uuid, UserTailor::all()->pluck("uuid")->toArray())) {
                return ResponseFormatter::success([
                    "access_token" => $req->bearerToken(),
                    "token_type" => "Bearer",
                    "user" => $user
                ]);
            } else {
                return ResponseFormatter::error('Anda Belum Login', 401);
            }
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "terjadi kesalahan", 500);
        }
    }
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
                    // $user->tokens()->delete();
                    $token = $user->createToken('authTailor')->plainTextToken;


                    $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->where('user_tailor_id', $user->id)->first();

                    $userTailor = UserTailor::join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id')->where('user_tailors.uuid', $user['uuid'])->first();
                    if ($rating === null) {
                        $userTailor->rating = 0;
                        $userTailor->total_review = 0;
                    } else {
                        collect($rating)->keys()->map(function ($key) use ($rating, $userTailor) {
                            if ($key != 'user_tailor_id') {
                                $userTailor->{$key} = $rating->{$key};
                            }
                        });
                    }


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
            return ResponseFormatter::error($err->getMessage(), 'terjadi kesalahan', 500);
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

            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->get();

            $query = UserTailor::join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id');
            // return $query;

            if (!$req->has('all')) {
                $query = $query->where('is_ready', 1);
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

            if ($limit) {
                $query = $query->take($limit);
            }

            $query =  $query->get()->makeHidden(['created_at', 'updated_at']);

            $query->each(function ($query) use ($rating) {
                $tailorRating = $rating->where('user_tailor_id', $query->id)->first();
                if ($tailorRating === null) {
                    $query["rating"] = 0;
                    $query["total_review"] = 0;
                } else {
                    collect($tailorRating)->keys()->map(function ($key) use ($tailorRating, $query) {
                        if ($key != 'user_tailor_id') {
                            $query[$key] = $tailorRating[$key];
                        }
                    });
                }
            });

            if ($star) {
                $query->where('rating', '>=', $star);
            }

            if ($recommended) {
                $query = $query->where('is_premium', 1)->sortByDesc('rating')->values();
            }
            if ($sort) {
                // return $order;
                $sort = explode(',', $sort);
                foreach ($sort as $s) {
                    if (in_array($s, Schema::getColumnListing('user_tailor_details')) || in_array($s, Schema::getColumnListing('user_tailors')) || $s == 'rating') {
                        $query = $query->sortBy($s, $order)->values();
                    }
                }
            }

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

            // $userTailor->tokens()->delete();
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
            // $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'))
            //     ->groupBy('user_tailor_id');

            // $userTailor = UserTailor::joinSub($rating, 'rating', function ($join) {
            //     $join->on('user_tailors.id', '=', 'rating.user_tailor_id');
            // })->join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'rating.rating', 'user_tailor_details.id as profile_id')->where('user_tailors.uuid', $uuid)->first();


            $userTailor = UserTailor::join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id')->where('user_tailors.uuid', $uuid)->first();

            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->where('user_tailor_id', $userTailor->id)->first();
            // $userTailor = UserTailor::with('profile')->find($uuid);
            if (!$userTailor) {
                return ResponseFormatter::error(null, 'User Tailor tidak ditemukan', 404);
            }

            if ($rating === null) {
                $userTailor->rating = 0;
                $userTailor->total_review = 0;
            } else {
                collect($rating)->keys()->map(function ($key) use ($rating, $userTailor) {
                    if ($key != 'user_tailor_id') {
                        $userTailor->{$key} = $rating->{$key};
                    }
                });
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

    public function updatePicture(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'profile_picture' => 'image|mimes:jpeg,png,jpg|max:5120',
                'place_picture' => 'image|mimes:jpeg,png,jpg|max:5120',
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error([
                    'message' => 'Data tidak valid',
                    'error' => $validator->errors()
                ], 'Data tidak valid', 422);
            }
            $id = $req->user()->id;
            $userTailor = UserTailor::find($id);

            if ($userTailor == null) {
                return ResponseFormatter::error(null, 'User Tailor tidak ditemukan', 404);
            }

            if (!$req->hasFile('profile_picture') && !$req->hasFile('place_picture')) {
                return ResponseFormatter::error(null, 'File tidak ditemukan', 404);
            }

            $message = "";
            if ($req->hasFile('profile_picture') && $req->file('profile_picture')->isValid()) {
                if ($userTailor->profile->profile_picture) {

                    $path = substr($userTailor->profile->profile_picture, strpos($userTailor->profile->profile_picture, 'images'));
                    Storage::disk('public')->exists($path) ? Storage::disk('public')->delete($path) : "";
                }

                $fileName = "tlr-" . Str::random(16) . "-" . Carbon::now()->toDateString()  . "." . $req->file('profile_picture')->getClientOriginalExtension();
                $profilePicture = asset('storage/' . $req->file('profile_picture')->storeAs('images/tailor/profile', $fileName, "public"));
                // return ;
                if (!Storage::disk('public')->exists(substr($profilePicture, strpos($profilePicture, 'images')))) {
                    return ResponseFormatter::error(null, 'Gagal mengupload gambar', 500);
                }
                $userTailor->profile->profile_picture = $profilePicture;
                $message .= " Foto Profil ";
            }

            if ($req->hasFile('place_picture') && $req->file('place_picture')->isValid()) {
                if ($userTailor->profile->place_picture) {

                    $path = substr($userTailor->profile->place_picture, strpos($userTailor->profile->place_picture, 'images'));
                    Storage::disk('public')->exists($path) ? Storage::disk('public')->delete($path) : "";
                }

                $fileName = "plc-" . Str::random(16) . "-" . Carbon::now()->toDateString()  . "." . $req->file('place_picture')->getClientOriginalExtension();
                $placePicture = asset('storage/' . $req->file('place_picture')->storeAs('images/tailor/place', $fileName, "public"));
                // return ;
                if (!Storage::disk('public')->exists(substr($placePicture, strpos($placePicture, 'images')))) {
                    return ResponseFormatter::error(null, 'Gagal mengupload gambar', 500);
                }
                $userTailor->profile->place_picture = $placePicture;
                $message .= " Foto Lokasi ";
            }

            $userTailor->profile->save();

            $userTailor = UserTailor::join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id')->where('user_tailors.id', $id)->first();
            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->where('user_tailor_id', $userTailor->id)->first();

            if ($rating === null) {
                $userTailor->rating = 0;
                $userTailor->total_review = 0;
            } else {
                collect($rating)->keys()->map(function ($key) use ($rating, $userTailor) {
                    if ($key != 'user_tailor_id') {
                        $userTailor->{$key} = $rating->{$key};
                    }
                });
            }
            return ResponseFormatter::success($userTailor, "$message" . "berhasil diubah");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'terjadi kesalahan', 500);
        }
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

    public function deletePicture($field)
    {
        try {
            if ($field != 'profile_picture' && $field != 'place_picture') {
                return ResponseFormatter::error(null, 'Field tidak ditemukan', 404);
            }
            $id = Auth::guard('sanctum')->user()->id;
            $userTailor = UserTailor::find($id);

            if ($userTailor == null) {
                return ResponseFormatter::error(null, 'User Tailor tidak ditemukan', 404);
            }

            if ($userTailor->profile->$field) {
                $path = substr($userTailor->profile->$field, strpos($userTailor->profile->$field, 'images'));
                Storage::disk('public')->exists($path) ? Storage::disk('public')->delete($path) : "";
                $userTailor->profile->$field = null;
                $userTailor->profile->save();
                // $userTailor->profile = UserTailorDetail::where('user_tailor_id', $userTailor->id)->update([$field => null]);
            }

            $userTailor = UserTailor::join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id')->where('user_tailors.id', $id)->first();
            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->where('user_tailor_id', $userTailor->id)->first();

            if ($rating === null) {
                $userTailor->rating = 0;
                $userTailor->total_review = 0;
            } else {
                collect($rating)->keys()->map(function ($key) use ($rating, $userTailor) {
                    if ($key != 'user_tailor_id') {
                        $userTailor->{$key} = $rating->{$key};
                    }
                });
            }
            return ResponseFormatter::success($userTailor, "Foto berhasil dihapus");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'terjadi kesalahan', 500);
        }
    }
}
