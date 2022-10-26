<?php


namespace App\Models\ManagementAccess;

use App\Models\Operational\Review;
use Illuminate\Database\Eloquent\Model;
use App\Models\ManagementAccess\ReviewOption;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReviewPivot extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function review()
    {
        return $this->belongsToMany(Review::class, "review_options", "review_id", "review_option_id");
    }

    public function reviewOption()
    {
        return $this->belongsToMany(ReviewOption::class, "review_options", "review_option_id", "review_id");
    }
}
