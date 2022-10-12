<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User\UserTailor;
use App\Helpers\ResponseFormatter;
use App\Models\Operational\Review;
use App\Http\Requests\StoreReviewRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\ManagementAccess\ReviewOption;
use App\Models\User\UserCustomer;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $req)
    {
        try {
            //params
            $rating = $req->input('rating');
            $review = $req->input('review');

            //query
            $user = auth('sanctum')->user();
            $reviews = Review::query();
            switch ($user->currentAccessToken()->tokenable_type) {
                case UserTailor::class:
                    $reviews = Review::whereHas('tailor', function ($query) {
                        $query->where('uuid', auth()->user()->uuid);
                    });
                    break;
                case UserCustomer::class:
                    $reviews = Review::whereHas('customer', function ($query) {
                        $query->where('uuid', auth()->user()->uuid);
                    });
                    break;
            }

            if ($rating) {
                $reviews = $reviews->where('rating', $rating);
            }
            if ($review) {
                $reviews = $reviews->where('review', 'like', '%' . $review . '%');
            }

            $reviews = $reviews->get();

            return ResponseFormatter::success($reviews, count($reviews) . " Data retrieved successfully");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi Kesalahan Sistem", 500);
        }
    }

    public function getReviewOption(Request $req)
    {
        try {
            $rating = $req->rating;
            $options = collect();


            $reviewOptions = ReviewOption::all();


            $keyRating = $reviewOptions->groupBy('rating')->keys();
            $keyRating->each(function ($key) use ($options, $reviewOptions) {
                $options->push([
                    'rating' => $key,
                    "review" => $reviewOptions->where('rating', $key)->pluck('review')
                ]);
            });

            if ($rating) {
                $options = $options->where('rating', $rating)[0];
            }

            return ResponseFormatter::success($options, 'Data review berhasil didapatkan');
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi Kesalahan Sistem", 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $req)
    {
        try {
            $cust = auth()->user();

            return $cust;
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi Kesalahan Sistem", 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreReviewRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $req)
    {
        try {
            $cust = auth()->user();

            if ($cust->currentAccessToken()->tokenable_type != UserCustomer::class) {
                return ResponseFormatter::error(null, "Anda tidak memiliki akses untuk melakukan review", 500);
            }

            $validator = Validator::make($req->all(), [
                'tailor' => 'required|uuid|exists:user_tailors,uuid',
                'review' => 'nullable|string|exists:review_options,review',
                'rating' => 'required|integer|between:1,5',
                'message' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), "Terjadi Kesalahan Sistem", 500);
            }

            $data = $validator->validate();

            $data['user_customer_id'] = $cust->uuid;
            $data['user_tailor_id'] = $data['tailor'];
            unset($data['tailor']);

            $review = Review::create($data);

            $review = Review::with(['customer.profile', 'tailor.profile'])->where('uuid', $review->uuid)->first();

            return ResponseFormatter::success($review, 'Data review berhasil dibuat');
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi Kesalahan Sistem", 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Operational\Review  $review
     * @return \Illuminate\Http\Response
     */
    public function show(Review $review)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Operational\Review  $review
     * @return \Illuminate\Http\Response
     */
    public function edit(Review $review)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateReviewRequest  $request
     * @param  \App\Models\Operational\Review  $review
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateReviewRequest $request, Review $review)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Operational\Review  $review
     * @return \Illuminate\Http\Response
     */
    public function destroy(Review $review)
    {
        //
    }
}
