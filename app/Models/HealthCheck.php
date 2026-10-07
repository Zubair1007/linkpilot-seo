<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthCheck extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'backlink_id',
        'source_url',
        'http_status',
        'response_time_ms',
        'redirect_count',
        'final_url',
        'canonical_url',
        'meta_robots',
        'x_robots_tag',
        'robots_txt_status',
        'content_type',
        'is_https',
        'page_title',
        'backlink_found',
        'target_url_found',
        'anchor_text_found',
        'rel_attributes_found',
        'passed',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'is_https' => 'boolean',
        'backlink_found' => 'boolean',
        'passed' => 'boolean',
        'rel_attributes_found' => 'array',
        'created_at' => 'datetime',
        'http_status' => 'integer',
        'response_time_ms' => 'integer',
        'redirect_count' => 'integer',
    ];

    public function backlink(): BelongsTo
    {
        return $this->belongsTo(Backlink::class);
    }
}
