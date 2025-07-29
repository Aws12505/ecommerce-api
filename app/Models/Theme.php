<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = ['name', 'palette', 'is_active'];

    protected $casts = [
        'palette' => 'array',
        'is_active' => 'boolean',
    ];
}
