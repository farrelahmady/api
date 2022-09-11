<?php

namespace App\Models\Operational;

use App\Models\Traits\HasUuid;
use App\Models\User\UserTailor;
use Illuminate\Database\Eloquent\Model;
use App\Models\ManagementAccess\Midtrans;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = ['id'];

    protected $with = ['tailor', 'midtrans'];

    protected $casts = [
        'status' => 'integer',
        'gross_amount' => 'float',
    ];

    public function tailor()
    {
        return $this->belongsTo(UserTailor::class, 'user_tailor_id', 'uuid');
    }

    public function midtrans()
    {
        return $this->hasOne(Midtrans::class, 'order_id', 'transaction_code');
    }
}
