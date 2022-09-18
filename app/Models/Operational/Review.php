<?php

namespace App\Models\Operational;

use App\Models\Traits\HasUuid;
use App\Models\User\UserCustomer;
use App\Models\User\UserTailor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = ['id'];

    protected $with = [
        'tailor',
        'customer',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function tailor()
    {
        return $this->belongsTo(UserTailor::class, 'user_tailor_id', 'uuid');
    }

    public function customer()
    {
        return $this->belongsTo(UserCustomer::class, 'user_customer_id', 'uuid');
    }
}
