<?php

namespace App\Models\Operational;

use App\Models\Traits\HasUuid;
use App\Models\User\UserTailor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = ['id'];

    public function tailor()
    {
        return $this->belongsTo(UserTailor::class);
    }
}
