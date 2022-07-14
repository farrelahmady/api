<?php

namespace App\Models\Operational;

use App\Models\User\UserTailor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
  use HasFactory;

  protected $guarded = ['id'];

  protected $hidden = [
    'deleted_at',
  ];

  public function tailor()
  {
    return $this->belongsTo(UserTailor::class, 'user_tailor_id', 'id');
  }
}
