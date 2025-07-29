<?php
// app/Models/GlobalAlert.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalAlert extends Model
{
    protected $fillable = [
        'type', 'title', 'body', 'buttons', 'status', 'metadata'
    ];

    protected $casts = [
        'buttons' => 'array',
        'metadata' => 'array',
    ];

    // Scope to get active alert
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
