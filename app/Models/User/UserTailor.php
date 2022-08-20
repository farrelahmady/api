<?php

namespace App\Models\User;

use App\Models\ManagementAccess\Availability;
use App\Models\Traits\HasUuid;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Operational\Review;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ManagementAccess\UserTailorDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserTailor extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable, HasUuid;

    protected $guard = 'userTailor';

    protected $guarded = ['id'];

    // protected $with = ['profile'];

    protected $casts = [
        'is_premium' => 'boolean',
        'is_admin' => 'boolean',
    ];

    protected $hidden = [
        'id',
        'password',
        'is_admin',
    ];

    public function profile()
    {
        return $this->hasOne(UserTailorDetail::class, 'user_tailor_id', 'id');
    }

    public function review()
    {
        return $this->hasMany(Review::class, 'user_tailor_id', 'id');
    }

    public function availability()
    {
        return $this->hasMany(Availability::class, 'user_tailor_id', 'uuid');
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
