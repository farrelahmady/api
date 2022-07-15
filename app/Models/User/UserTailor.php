<?php

namespace App\Models\User;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

use App\Models\ManagementAccess\UserTailorDetail;
use App\Models\Operational\Review;

class UserTailor extends Authenticatable
{
  use HasFactory, SoftDeletes, HasApiTokens, Notifiable;

  protected $guard = 'userTailor';

  protected $guarded = ['id'];

  // protected $with = ['profile'];

  protected $casts = [
    'is_premium' => 'boolean',
    'is_admin' => 'boolean',
  ];

  protected $hidden = [
    'password',
    'is_admin',
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function profile()
  {
    return $this->hasOne(UserTailorDetail::class, 'user_tailor_id', 'id');
  }

  public function review()
  {
    return $this->hasMany(Review::class, 'user_tailor_id', 'id');
  }

  // public function sendPasswordResetNotification($token)
  // {
  //   $url = route('password.reset') . '?token=' . $token;
  //   $this->notify(new ResetPasswordNotification($url));
  // }
}
