<?php

namespace App\Models\ManagementAccess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCustomerDetail extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = ['id'];

  protected $hidden = [
    'id',
    'user_customer_id',
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
