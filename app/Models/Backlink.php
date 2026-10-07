<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Backlink extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'campaign_id',
        'domain_id',
        'source_url',
        'target_url',
        'anchor_text',
        'link_type',
        'rel_attributes',
        'http_status',
        'final_url',
        'canonical_url',
        'robots_status',
        'domain_rating',
        'domain_authority',
        'page_authority',
        'authority_score',
        'metrics_updated_at',
        'indexability',
        'is_live',
        'is_indexed',
        'crawl_status',
        'index_status',
        'discovery_status',
        'first_seen_at',
        'last_seen_at',
        'last_verified_at',
        'last_crawled_at',
        'last_indexed_check_at',
        'retry_count',
        'max_retries',
        'next_retry_at',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'rel_attributes' => 'array',
        'is_live' => 'boolean',
        'is_indexed' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'last_crawled_at' => 'datetime',
        'last_indexed_check_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'metadata' => 'array',
        'http_status' => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(BacklinkEvent::class)->orderBy('created_at', 'desc');
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(HealthCheck::class)->orderBy('created_at', 'desc');
    }

    public function discoveryJobs(): HasMany
    {
        return $this->hasMany(DiscoveryJob::class)->orderBy('created_at', 'desc');
    }

    public function recordEvent(string $type, ?array $oldData = null, ?array $newData = null, ?string $notes = null): BacklinkEvent
    {
        return $this->events()->create([
            'event_type' => $type,
            'old_data' => $oldData,
            'new_data' => $newData,
            'notes' => $notes,
        ]);
    }
}
