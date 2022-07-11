<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\ManagementAccess\UserCustomerDetail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class UserCustomer extends Authenticatable
{
  use HasFactory, SoftDeletes, HasApiTokens;

  protected $guarded = ['id'];

  protected $hidden = [
    'id',
    'password',
    'deleted_at',
  ];

  public function userCustomerDetail()
  {
    return $this->hasOne(UserCustomerDetail::class);
  }
}
