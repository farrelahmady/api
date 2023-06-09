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
use App\Models\Operational\Transaction;
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


                    //$rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->where('user_tailor_id', $user->uuid)->first();


                    $userTailor = UserTailor::with(['review.customer.profile'])->join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id')->where('user_tailors.uuid', $user['uuid'])->first();
                    //if ($rating === null) {
                    //    $userTailor->rating = 0;
                    //    $userTailor->total_review = 0;
                    //} else {
                    //    collect($rating)->keys()->map(function ($key) use ($rating, $userTailor) {
                    //        if ($key != 'user_tailor_id') {
                    //            $userTailor->{$key} = $rating->{$key};
                    //        }
                    //    });
                    //}
                    if ($userTailor->is_premium) {
                        $userTailor['transaction'] = Transaction::where('user_tailor_id', $userTailor->uuid)->where('status', 2)->get();
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


            $query = UserTailor::with(['review.customer.profile'])->withTrashed()->join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id');
            // return $query;

            if (!$req->has('all')) {
                $query = $query->where('is_ready', 1);
            }

            if ($req->has('status')) {
                if ($req->status) {
                    //return $req->status;
                    $query = $query->where('user_tailors.deleted_at', null);
                } else {
                    $query = $query->where('user_tailors.deleted_at', '!=', null);
                }
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
                // 'phone_number' => ['nullable', 'phone_number', 'min:10', 'max:15'],
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


            $userTailor = UserTailor::with(['review.customer.profile'])->join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id')->where('user_tailors.uuid', $uuid)->first();

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
            $id = auth('sanctum')->user()->id;
            $userTailor = UserTailor::find($id);

            //if ($userTailor == null) {
            //    return ResponseFormatter::error(null, 'User Tailor tidak ditemukan', 404);
            //}

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
                $profilePicture = asset('storage/' . $req->file('profile_picture')->storePubliclyAs('images/tailor/profile', $fileName, "public"));
                // return ;
                //if (!Storage::disk('public')->exists(substr($profilePicture, strpos($profilePicture, 'images')))) {
                //    return ResponseFormatter::error(null, 'Gagal mengupload gambar', 500);
                //}
                $userTailor->profile->profile_picture = $profilePicture;
                $message .= " Foto Profil ";
            }

            if ($req->hasFile('place_picture') && $req->file('place_picture')->isValid()) {
                if ($userTailor->profile->place_picture) {

                    $path = substr($userTailor->profile->place_picture, strpos($userTailor->profile->place_picture, 'images'));
                    Storage::disk('public')->exists($path) ? Storage::disk('public')->delete($path) : "";
                }

                $fileName = "plc-" . Str::random(16) . "-" . Carbon::now()->toDateString()  . "." . $req->file('place_picture')->getClientOriginalExtension();
                $place_picture = asset('storage/' . $req->file('place_picture')->storePubliclyAs('images/tailor/tailorplace', $fileName, "public"));
                // return ;
                //if (!Storage::disk('public')->exists(substr($place_picture, strpos($place_picture, 'images')))) {
                //    return ResponseFormatter::error(null, 'Gagal mengupload gambar', 500);
                //}
                $userTailor->profile->place_picture = $place_picture;
                $message .= " Foto Lokasi ";
            }

            $userTailor->profile->save();

            $userTailor = UserTailor::with(['review.customer.profile'])->join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id')->where('user_tailors.id', $id)->first();
            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->where('user_tailor_id', $userTailor->uuid)->first();

            if ($rating === null) {
                $userTailor->rating = 0;
                $userTailor->total_review = 0;
            } else {
                collect($rating)->keys()->map(function ($key) use ($rating, $userTailor) {
                    if ($key != 'user_tailor_id') {
                        $userTailor->{$key} = (int) $rating->{$key};
                    }
                });
            }
            return ResponseFormatter::success($userTailor, "$message" . "berhasil diubah");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'terjadi kesalahan', 500);
        }
    }
    public function update(Request $request, $uuid)
    {
        try {

            $validator = Validator::make($request->all(), [
                'old_password' => [RulesPassword::min(8)->numbers()->letters()],
                'password' => ["string", "confirmed",  RulesPassword::min(8)->numbers()->letters()],
                'password_confirmation'  => ['required_with:password', "string", 'same:password',],
                'first_name' => ['nullable', 'string', 'min:3', 'max:255'],
                'last_name' => ['nullable', 'string', 'min:3', 'max:255'],
                'description' => ['nullable', 'string', 'min:3', 'max:350'],
                'phone_number' => ['nullable', 'phone_number', 'min:10', 'max:15'],
                'address' => ['nullable', 'string', 'max:255'],
                'district' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:255'],
                'province' => ['nullable', 'string', 'max:255'],
                'zip_code' => ['nullable', 'numeric', 'digits:5'],
                'premium' => ['nullable', 'boolean'],
                'is_ready' => ['nullable', 'boolean'],
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error(
                    $validator->errors(),
                    'Terjadi kesalahan saat validasi data',
                    422
                );
            }

            $userTailor = UserTailor::where('uuid', $uuid)->first();

            if ($userTailor == null) {
                return ResponseFormatter::error(null, 'User Tailor tidak ditemukan', 404);
            }

            if ($request->old_password) {
                if (!Hash::check($request->old_password, $userTailor->password)) {
                    return ResponseFormatter::error(null, 'Password lama tidak sesuai', 422);
                }
            }

            if ($request->password) {
                $userTailor->password = Hash::make($request->password);
            }

            if ($request->has('premium')) {
                $userTailor->is_premium = $request->premium;
            }

            if ($request->has('is_ready')) {
                $userTailor->is_ready = $request->is_ready;
            }

            if ($request->first_name) {
                $userTailor->profile->first_name = $request->first_name;
            }

            if ($request->last_name) {
                $userTailor->profile->last_name = $request->last_name;
            }

            if ($request->description) {
                $userTailor->profile->description = $request->description;
            }

            if ($request->phone_number) {
                $userTailor->profile->phone_number = $request->phone_number;
            }

            if ($request->address) {
                $userTailor->profile->address = $request->address;
            }

            if ($request->district) {
                $userTailor->profile->district = $request->district;
            }

            if ($request->city) {
                $userTailor->profile->city = $request->city;
            }

            if ($request->province) {
                $userTailor->profile->province = $request->province;
            }

            if ($request->zip_code) {
                $userTailor->profile->zip_code = $request->zip_code;
            }




            $userTailor->save();
            $userTailor->profile->save();

            $userTailor = UserTailor::with(['review.customer.profile'])->join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id')->where('user_tailors.uuid', $uuid)->first();

            return ResponseFormatter::success($userTailor, 'User Tailor berhasil diperbarui');
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
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserTailor  $userTailor
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, $uuid)
    {
        // return UserTailor::class;
        try {
            $userTailor = UserTailor::where('uuid', $uuid)->first();
            if (!$userTailor) {
                return ResponseFormatter::error(null, 'User Tailor tidak ditemukan', 404);
            }
            $userTailor->delete();
            $userTailor->profile->delete();
            $userTailor->tokens()->delete();

            return ResponseFormatter::success(null, 'User Tailor berhasil di non aktifkan');
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "terjadi kesalahan", 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserTailor  $userTailor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $uuid)
    {
        try {
            $userTailor = UserTailor::onlyTrashed()->where('uuid', $uuid)->first();
            if (!$userTailor) {
                return ResponseFormatter::error(null, 'User Tailor tidak ditemukan', 404);
            }
            // return $userTailor->profile;
            if ($userTailor->profile->profile_picture) {
                $path = substr($userTailor->profile->profile_picture, strpos($userTailor->profile->profile_picture, 'images'));
                Storage::disk('public')->exists($path) ? Storage::disk('public')->delete($path) : "";
            }
            $userTailor->forceDelete();
            return ResponseFormatter::success(null, 'User Tailor berhasil dihapus');
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "terjadi kesalahan", 500);
        }
    }

    public function restore($uuid)
    {
        try {
            $userTailor = UserTailor::onlyTrashed()->where('uuid', $uuid)->first();
            if (!$userTailor) {
                return ResponseFormatter::error(null, 'User Tailor tidak ditemukan', 404);
            }
            $profile = UserTailorDetail::onlyTrashed()->where('user_tailor_id', $userTailor->id)->first();
            if ($profile) {
                $profile->restore();
            }
            $userTailor->restore();
            $userTailor = UserTailor::find($userTailor->id)->first()->makeHidden(['created_at', 'updated_at']);
            $profile = UserTailorDetail::find($profile->id)->first()->makeHidden(['created_at', 'updated_at']);
            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->where('user_tailor_id', $userTailor->uuid)->first();

            $userTailor = collect($userTailor)->merge($profile)->merge($rating);

            return ResponseFormatter::success($userTailor, 'User Tailor berhasil di aktifkan');
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "terjadi kesalahan", 500);
        }
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

            if ($userTailor->profile->$field == null) {
                return ResponseFormatter::error(null, 'Tailor tidak memiliki foto', 404);
            }

            $path = substr($userTailor->profile->$field, strpos($userTailor->profile->$field, 'images'));
            if (!Storage::disk('public')->exists($path)) {
                $userTailor->profile->$field = null;
                $userTailor->profile->save();
                return ResponseFormatter::error(null, 'Foto tidak ditemukan', 404);
            }
            Storage::disk('public')->delete($path);
            $userTailor->profile->$field = null;
            $userTailor->profile->save();

            $userTailor = UserTailor::with(['review.customer.profile'])->join('user_tailor_details', 'user_tailors.id', '=', 'user_tailor_details.user_tailor_id')->select('user_tailors.*', 'user_tailor_details.*', 'user_tailor_details.id as profile_id')->where('user_tailors.id', $id)->first();
            $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'), DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->where('user_tailor_id', $userTailor->uuid)->first();

            if ($rating === null) {
                $userTailor->rating = 0;
                $userTailor->total_review = 0;
            } else {
                collect($rating)->keys()->map(function ($key) use ($rating, $userTailor) {
                    if ($key != 'user_tailor_id') {
                        $userTailor->{$key} = (int) $rating->{$key};
                    }
                });
            }
            return ResponseFormatter::success($userTailor, "Foto berhasil dihapus");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'terjadi kesalahan', 500);
        }
    }
}
