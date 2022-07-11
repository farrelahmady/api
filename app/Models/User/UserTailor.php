<?php

namespace App\Models\User;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\ResetPasswordNotification;
use App\Models\ManagementAccess\UserTailorDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserTailor extends Authenticatable
{
  use HasFactory, SoftDeletes, HasApiTokens, Notifiable;

  protected $guard = 'userTailor';

  protected $guarded = ['id'];

  protected $casts = [
    'is_premium' => 'boolean',
    'is_admin' => 'boolean',
  ];

  protected $hidden = [
    'id',
    'password',
    'is_admin',
    'deleted_at',
  ];

  public function profile()
  {
    return $this->hasOne(UserTailorDetail::class, 'user_tailor_id', 'id');
  }

  // public function sendPasswordResetNotification($token)
  // {
  //   $url = route('password.reset') . '?token=' . $token;
  //   $this->notify(new ResetPasswordNotification($url));
  // }
}
