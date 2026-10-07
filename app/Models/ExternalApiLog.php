<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalApiLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'provider',
        'endpoint_category',
        'url',
        'http_method',
        'response_code',
        'latency_ms',
        'sanitized_request',
        'sanitized_response',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'response_code' => 'integer',
        'latency_ms' => 'integer',
        'sanitized_request' => 'array',
        'sanitized_response' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
