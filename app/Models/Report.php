<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'campaign_id',
        'title',
        'type', // 'campaign_summary', 'backlink_audit', 'lost_backlinks', 'index_status', 'api_activity'
        'format', // 'json', 'csv', 'xlsx', 'pdf'
        'summary_metrics',
        'file_path',
    ];

    protected $casts = [
        'summary_metrics' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
