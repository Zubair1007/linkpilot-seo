<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscoveryJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'backlink_id',
        'provider',
        'method',
        'status',
        'attempt',
        'scheduled_at',
        'started_at',
        'completed_at',
        'response_code',
        'response_message',
        'payload_metadata',
    ];

    protected $casts = [
        'attempt' => 'integer',
        'response_code' => 'integer',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'payload_metadata' => 'array',
    ];

    public function backlink(): BelongsTo
    {
        return $this->belongsTo(Backlink::class);
    }
}
