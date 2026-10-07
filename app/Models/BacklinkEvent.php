<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacklinkEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'backlink_id',
        'event_type', // 'added', 'verified', 'changed', 'lost', 'restored', 'index_status_changed'
        'old_data',
        'new_data',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function backlink(): BelongsTo
    {
        return $this->belongsTo(Backlink::class);
    }
}
