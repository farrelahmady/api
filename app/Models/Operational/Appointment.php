<?php

namespace App\Models\Operational;

use App\Models\Traits\HasUuid;
use App\Models\User\UserTailor;
use App\Models\User\UserCustomer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'appointments';


    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'integer',
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
