<?php

namespace App\Models\ManagementAccess;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Availability extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        // 'user_tailor_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
