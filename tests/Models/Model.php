<?php

namespace MasterRO\Searchable\Tests\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Model extends Eloquent
{
    protected $guarded = ['id'];

    protected $appends = ['type'];

    /**
     * Get Type Attribute
     *
     * @return string
     */
    public function getTypeAttribute(): string
    {
        return class_basename(static::class);
    }

    /**
     * Get Description Attribute
     *
     * @param $description
     *
     * @return string
     */
    public function getDescriptionAttribute($description)
    {
        return Str::limit($description, 63);
    }
}
