<?php

namespace CleaniqueCoders\Shrinkr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Url extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_url',
        'shortened_url',
        'custom_slug',
        'is_expired',
    ];

    protected $casts = [
        'is_expired' => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return 'shortened_url';
    }
}
