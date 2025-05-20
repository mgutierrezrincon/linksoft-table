<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'file_type',
        'parentable_id',
        'parentable_type',
        'name',
    ];

    public function parentable()
    {
        return $this->morphTo();
    }
}
