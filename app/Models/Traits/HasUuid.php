<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

/**
 * Trait HasUuid.
 */
trait HasUuid
{
    protected static function bootHasUuid()
    {

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string)Str::uuid();
            }
        });
    }
}
