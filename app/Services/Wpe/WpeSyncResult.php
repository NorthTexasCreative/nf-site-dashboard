<?php

namespace App\Services\Wpe;

readonly class WpeSyncResult
{
    public function __construct(
        public int $accounts_synced_count,
        public int $sites_synced_count,
        public int $environments_synced_count,
        public float $duration_seconds,
        /** @var array<string> */
        public array $warnings = [],
    ) {}
}
