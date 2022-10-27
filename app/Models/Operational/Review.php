<?php

namespace App\Models\Operational;

use App\Models\Traits\HasUuid;
use App\Models\User\UserTailor;
use App\Models\User\UserCustomer;
use Illuminate\Database\Eloquent\Model;
use App\Models\ManagementAccess\ReviewOption;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = ['id'];

    //protected $with = [
    //    'tailor.profile',
    //    'customer.profile',
    //];

    protected $hidden = [
        'deleted_at',
    ];

    protected $appends = ['review'];


    public function tailor()
    {
        return $this->belongsTo(UserTailor::class, 'user_tailor_id', 'uuid');
    }

    public function customer()
    {
        return $this->belongsTo(UserCustomer::class, 'user_customer_id', 'uuid');
    }

    public function reviewOptions()
    {
        return $this->belongsToMany(ReviewOption::class, "review_pivots", "review_id", "review_option_id");
    }

    public function getReviewAttribute()
    {
        $review = $this->reviewOptions()->pluck('review');
        return $review->toArray();
    }
}
