<?php

namespace App\Models\ManagementAccess;

use App\Models\User\UserTailor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserTailorDetail extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = ['id'];

  protected $hidden = [
    'id',
    'user_tailor_id',
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
