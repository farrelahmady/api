<?php

namespace App\Models\User;

use App\Models\Traits\HasUuid;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Operational\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;

use App\Models\ManagementAccess\Availability;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ManagementAccess\UserTailorDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserTailor extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable, HasUuid;

    protected $guard = 'userTailor';

    protected $guarded = ['id'];

    protected $appends = ['rating', 'total_review'];

    //protected $with = ['profile'];

    protected $casts = [
        'is_premium' => 'boolean',
        'is_admin' => 'boolean',
    ];

    protected $hidden = [
        'password',
        'is_admin',
    ];

    public function profile()
    {
        return $this->hasOne(UserTailorDetail::class, 'user_tailor_id', 'id');
    }

    public function review()
    {
        return $this->hasMany(Review::class, 'user_tailor_id', 'uuid');
    }

    public function availability()
    {
        return $this->hasMany(Availability::class, 'user_tailor_id', 'uuid');
    }

    public function getRatingAttribute()
    {
        $rating = Review::select('user_tailor_id', DB::raw('CAST(AVG(rating) AS DECIMAL(5,0)) as rating'))->groupBy('user_tailor_id')->where('user_tailor_id', $this->uuid)->first();
        return $rating ? (int)$rating->rating : 0;
    }
    public function getTotalReviewAttribute()
    {
        $rating = Review::select('user_tailor_id', DB::raw('COUNT(*) as total_review'))->groupBy('user_tailor_id')->where('user_tailor_id', $this->uuid)->first();
        return $rating ? (int)$rating->total_review : 0;
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }






    // public function sendPasswordResetNotification($token)
    // {
    //   $url = route('password.reset') . '?token=' . $token;
    //   $this->notify(new ResetPasswordNotification($url));
    // }
}
