<?php

namespace App\Models\ManagementAccess;

use App\Models\Operational\Review;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReviewOption extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function reviews()
    {
        return $this->belongsToMany(Review::class, "review_pivots", "review_option_id", "review_id");
    }
}
