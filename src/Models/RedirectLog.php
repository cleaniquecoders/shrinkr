<?php

namespace CleaniqueCoders\Shrinkr\Models;

use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedirectLog extends Model
{
    use HasFactory, InteractsWithUuid;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'headers' => 'array',
        'query_params' => 'array',
    ];

    public function url(): BelongsTo
    {
        return $this->belongsTo(
            config('shrinkr.models.url')
        );
    }
}
