<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchEngineProperty extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'domain_id',
        'provider', // 'bing_webmaster', 'google_search_console', 'indexnow'
        'property_url',
        'encrypted_credentials',
        'is_authorized',
        'authorization_status',
        'authorized_at',
        'quota_daily',
        'quota_used_today',
        'last_quota_reset_at',
        'metadata',
    ];

    protected $hidden = [
        'encrypted_credentials',
    ];

    protected $casts = [
        'encrypted_credentials' => 'encrypted:array',
        'is_authorized' => 'boolean',
        'authorized_at' => 'datetime',
        'last_quota_reset_at' => 'datetime',
        'quota_daily' => 'integer',
        'quota_used_today' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function hasQuotaAvailable(): bool
    {
        return $this->quota_used_today < $this->quota_daily;
    }

    public function incrementQuotaUsage(int $count = 1): void
    {
        $this->increment('quota_used_today', $count);
    }
}
