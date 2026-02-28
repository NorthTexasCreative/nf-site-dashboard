<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Environment extends Model
{
    public const UPDATE_METHODS = ['wpe_managed', 'script', 'manual', 'none'];

    public const UPDATE_SCHEDULES = [
        'Tuesday AM (A-F)',
        'Wednesday AM (G-L)',
        'Thursday AM (M-R)',
        'Friday AM (S-Z)',
        'Daily',
        'Manual',
    ];

    protected $fillable = [
        'wpe_install_id',
        'site_id',
        'name',
        'environment',
        'cname',
        'php_version',
        'is_multisite',
        'status',
        'primary_domain',
        'wp_version',
        'stable_ips',
        'lifecycle_status',
        'notes',
        'update_method',
        'update_schedule',
        'updates_schedule_set',
        'last_synced_at',
        'last_detail_sync_at',
    ];

    protected $casts = [
        'is_multisite' => 'boolean',
        'updates_schedule_set' => 'boolean',
        'stable_ips' => 'array',
        'last_synced_at' => 'datetime',
        'last_detail_sync_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function needsAttention(): bool
    {
        return empty($this->wp_version) || ($this->status && $this->status !== 'active');
    }

    /**
     * Derived Updates Integrity state (not stored). Returns [label, color] for badge.
     * @return array{0: string, 1: string}
     */
    public function getUpdatesIntegrity(): array
    {
        if ($this->lifecycle_status === 'deleted') {
            return ['Archived', 'gray'];
        }
        $scheduleBlank = $this->update_schedule === null || trim((string) $this->update_schedule) === '';
        $set = (bool) $this->updates_schedule_set;
        if ($scheduleBlank && ! $set) {
            return ['Not Set', 'danger'];
        }
        if ($scheduleBlank && $set) {
            return ['No Schedule', 'danger'];
        }
        if (! $scheduleBlank && ! $set) {
            return ['Not Confirmed', 'danger'];
        }
        return ['OK', 'success'];
    }
}
