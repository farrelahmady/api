<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\UserCustomer;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\fileExists;

use App\Models\ManagementAccess\UserCustomerDetail;
use Illuminate\Validation\Rules\Password as RulesPassword;

class UserCustomerController extends Controller
{
    public function login(Request $request)
    {
        try {
            if (auth('sanctum')->check()) {
                return $request->user('userCustomer')->currentAccessToken()->delete();
                return ResponseFormatter::success(
                    'Anda telah login.',
                    [
                        'access_token' => auth('sanctum')->user()->token,
                        'token_type' => 'Bearer',
                        'user' => auth('sanctum')->user(),
                    ]
                );
            } else {
                $validator = Validator::make($request->all(), [
                    'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'exists:user_customers,email'],
                    'password' => ['required', 'string', RulesPassword::min(8)->numbers()->letters()],
                ]);

                if ($validator->fails()) {
                    return ResponseFormatter::error($validator->errors(), 'Masukan Tidak Valid', 422);
                }

                if (Auth::guard('userCustomer')->attempt(['email' => $request->email, 'password' => $request->password])) {
                    $user = Auth::guard('userCustomer')->user()->id;
                    $user = UserCustomer::with('profile')->find($user)->makeHidden(['created_at', 'updated_at']);
                    $token = $user->createToken('authCustomer')->plainTextToken;
                    return ResponseFormatter::success(
                        [
                            'access_token' => $token,
                            'token_type' => 'Bearer',
                            'user' => $user,
                        ],
                        'Login berhasil'
                    );
                } else {
                    return ResponseFormatter::error([], 'Kredensial tidak valid', 401);
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
                return ResponseFormatter::success(['token' => $token], 'Logout berhasil');
            } else {
                return ResponseFormatter::error('Anda belum login.', 'Logout gagal', 401);
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
            // return ResponseFormatter::success($request->file('profile_picture')->isValid(), 'Store Successful');
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:user_customers'],
                'password' => ['required', 'string', RulesPassword::min(8)->numbers()->letters()],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                // 'address' => ['required', 'string', 'max:255'],
                // 'district' => ['required', 'string', 'max:255'],
                // 'city' => ['required', 'string', 'max:255'],
                // 'province' => ['required', 'string', 'max:255'],
                // 'zip_code' => ['required', 'string', 'max:255'],
            ]);


            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), 'Masukan Tidak Valid', 422);
            }


            $profilePicture = $request->hasFile('profile_picture') && $request->file('profile_picture')->isValid() ?  asset('storage/' . $request->file('profile_picture')->store('images/customer/profile', 'public')) : null;

            $validatedData = $validator->validated();
            $validatedData['password'] = Hash::make($validatedData['password']);
            $validatedData['profile_picture'] = $profilePicture;
            $userCustomer = UserCustomer::create($validatedData)->id;

            $validatedData['user_customer_id'] = $userCustomer;
            UserCustomerDetail::create($validatedData);

            $userCustomer = UserCustomer::with('profile')->find($userCustomer)->makeHidden(['created_at', 'updated_at']);

            $tokenResult = $userCustomer->createToken('authCustomer')->plainTextToken;


            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $userCustomer
            ], 'user customer berhasil dibuat');
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UserCustomer  $userCustomer
     * @return \Illuminate\Http\Response
     */
    public function show($uuid)
    {
        try {
            $userCustomer = UserCustomer::with('profile')->where('uuid', $uuid)->first();
            if (!$userCustomer) {
                return ResponseFormatter::error(null, 'User Customer tidak ditemukan', 404);
            }
            return ResponseFormatter::success($userCustomer, 'User Customer berhasil ditemukan');
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'terjadi kesalahan', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserCustomer  $userCustomer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        try {
            $validator = Validator::make($request->all(), [
                'old_password' => [RulesPassword::min(8)->numbers()->letters()],
                'password' => ["string", "confirmed",  RulesPassword::min(8)->numbers()->letters()],
                'password_confirmation'  => ['required_with:password', "string", 'same:password',],
                'first_name' => ['string', 'max:255'],
                'last_name' => ['string', 'max:255'],
                'address' => ['string', 'max:255'],
                'district' => ['string', 'max:255'],
                'city' => ['string', 'max:255'],
                'province' => ['string', 'max:255'],
                'zip_code' => ['string', 'max:255'],
                'profile_picture' => ["image", 'max:2048', 'mimes:jpeg,png,jpg,gif,svg'],
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), 'Masukan Tidak Valid', 422);
            }
            $validatedData = $validator->validated();
            $userCustomer = UserCustomer::where('uuid', $uuid)->first();

            if (!$userCustomer) {
                return ResponseFormatter::error([], 'User Customer tidak ditemukan', 404);
            }


            if (isset($validatedData['password'])) {
                if (!Hash::check($request['old_password'], $userCustomer->password)) return ResponseFormatter::error(["password" => 'Password lama tidak sesuai'], 'Password lama tidak sesuai', 422);
                $validatedData['password'] = Hash::make($validatedData['password']);
            }

            if ($request->hasFile('profile_picture') && $request->file('profile_picture')->isValid()) {
                if ($userCustomer->profile->profile_picture) {

                    $path = substr($userCustomer->profile->profile_picture, strpos($userCustomer->profile->profile_picture, 'images'));
                    Storage::disk('public')->exists($path) ? Storage::disk('public')->delete($path) : "";
                }
                # code...
                $fileName = "cust-" . Str::random(16) . "-" . Carbon::now()->toDateString()  . "." . $request->file('profile_picture')->getClientOriginalExtension();
                $profilePicture = asset('storage/' . $request->file('profile_picture')->storeAs('images/customer/profile', $fileName, "public"));
                if ($profilePicture) {
                    $validatedData['profile_picture'] = $profilePicture;
                }
            }




            $userCustomer->update($validatedData);
            $userCustomer->profile->update($validatedData);
            $userCustomer = UserCustomer::with('profile')->find($userCustomer->id)->makeHidden(['created_at', 'updated_at']);
            return ResponseFormatter::success($userCustomer, 'user customer berhasil diupdate');
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "terjadi kesalahan", 500);
        }
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
