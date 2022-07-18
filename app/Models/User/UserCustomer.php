<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\ManagementAccess\UserCustomerDetail;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class UserCustomer extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, HasUuid;

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
