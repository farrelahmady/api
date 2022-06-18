<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class UserTailor extends Model
{
  use HasFactory, HasApiTokens;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $guarded = ['id'];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password'
  ];

  function userTailorDetail()
  {
    return $this->hasOne(UserTailorDetail::class);
  }
}
