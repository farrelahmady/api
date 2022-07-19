<?php

namespace App\Models\ManagementAccess;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Catalog extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'user_tailor_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function item()
    {
        return $this->hasMany(CatalogItem::class, 'catalog_id', 'id');
    }
}
