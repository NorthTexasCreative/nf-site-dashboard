<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $fillable = [
        'wpe_site_id',
        'server_id',
        'name',
        'group_name',
        'tags',
        'created_at_wpe',
        'sandbox',
        'transferable',
        'lifecycle_status',
        'notes',
        'last_synced_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'created_at_wpe' => 'datetime',
        'sandbox' => 'boolean',
        'transferable' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    public function needsAttention(): bool
    {
        return $this->lifecycle_status === 'unknown'
            || $this->environments()->whereNull('wp_version')->exists();
    }
}
