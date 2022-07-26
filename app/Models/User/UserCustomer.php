<?php

namespace App\Models\User;

use App\Models\Traits\HasUuid;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ManagementAccess\UserCustomerDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserCustomer extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, HasUuid, Notifiable;

    protected $guard = 'userCustomer';
    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'password',
        'deleted_at',
    ];

    public function profile()
    {
        return $this->hasOne(UserCustomerDetail::class, 'user_customer_id', 'id');
    }
}
