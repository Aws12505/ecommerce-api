<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Slider extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'description', 'image',
        'action_type', 'action_value', 'extra',
        'is_active', 'sort_order'
    ];
    protected $casts = [
        'extra' => 'array',
        'is_active' => 'boolean',
    ];

    // Optional: for convenience, computed fields for the front-end
    public function getImageUrlAttribute()
    {
        return Storage::disk('public')->url($this->image);
    }
}
