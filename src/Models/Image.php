<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'image_type',
        'parentable_id',
        'parentable_type',
    ];

    public function parentable()
    {
        return $this->morphTo();
    }
}
