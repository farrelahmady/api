<?php

namespace App\Models\User;

use App\Models\Traits\HasUuid;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, HasUuid, Notifiable;

    protected $guard = 'admin';
    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'deleted_at',
    ];
}
