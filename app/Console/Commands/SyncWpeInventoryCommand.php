<?php

namespace App\Console\Commands;

use App\Services\Wpe\WpeInventorySyncService;
use Illuminate\Console\Command;

class SyncWpeInventoryCommand extends Command
{
    protected $signature = 'wpe:sync-inventory';

    protected $description = 'Sync WP Engine inventory (servers, sites, environments) from the API.';

    public function handle(): int
    {
        if (! config('wpengine.user') || ! config('wpengine.password')) {
            $this->error('Set WPE_API_USER and WPE_API_PASSWORD in .env');
            return self::FAILURE;
        }

        $this->info('Syncing WP Engine inventory...');

        try {
            $service = $this->laravel->make(WpeInventorySyncService::class);
            $result = $service->run(function (string $message): void {
                $this->line($message);
            });

            $this->info(sprintf(
                'Sync complete. Synced %d servers, %d sites, %d environments in %.1fs.',
                $result->accounts_synced_count,
                $result->sites_synced_count,
                $result->environments_synced_count,
                $result->duration_seconds
            ));

            if (! empty($result->warnings)) {
                foreach ($result->warnings as $warning) {
                    $this->warn('  ' . $warning);
                }
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
