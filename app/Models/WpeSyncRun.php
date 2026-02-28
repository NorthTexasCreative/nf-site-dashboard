<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WpeSyncRun extends Model
{
    protected $table = 'wpe_sync_runs';

    protected $fillable = [
        'sync_type',
        'triggered_by_user_id',
        'started_at',
        'finished_at',
        'status',
        'duration_seconds',
        'accounts_count',
        'sites_count',
        'environments_count',
        'message',
        'output',
        'error',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
