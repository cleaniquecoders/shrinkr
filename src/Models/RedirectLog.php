<?php

namespace CleaniqueCoders\Shrinkr\Models;

use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class RedirectLog
 *
 * Represents a log entry for each URL redirection.
 *
 * @property int $id
 * @property array $headers
 * @property array $query_params
 * @property-read Url $url
 */
class RedirectLog extends Model
{
    use HasFactory, InteractsWithUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'headers' => 'array',
        'query_params' => 'array',
    ];

    /**
     * Get the URL associated with the redirect log.
     *
     * @return BelongsTo<Url>
     */
    public function url(): BelongsTo
    {
        /** @var class-string<Url> $urlModel */
        $urlModel = config('shrinkr.models.url', Url::class);

        /** @phpstan-return BelongsTo<Url> */
        return $this->belongsTo($urlModel);
    }
}
