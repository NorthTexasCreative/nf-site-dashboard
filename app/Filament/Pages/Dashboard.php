<?php

namespace App\Filament\Pages;

use App\Models\WpeSyncRun;
use App\Services\Wpe\WpeInventorySyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Cache;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncWpeInventory')
                ->label('Sync WP Engine Inventory')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Sync WP Engine Inventory')
                ->modalDescription('This will sync servers, sites, and environments from the WP Engine API. It may take a few minutes.')
                ->modalSubmitActionLabel('Run sync')
                ->action(function (): void {
                    $lock = Cache::lock('wpe-sync-inventory-lock', 1800);
                    $run = null;

                    if (! $lock->get()) {
                        Notification::make()
                            ->title('Sync already running.')
                            ->body('Another sync is in progress. Please wait for it to finish.')
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        $user = auth()->user();
                        $run = WpeSyncRun::create([
                            'sync_type' => 'inventory',
                            'triggered_by_user_id' => $user?->id,
                            'started_at' => now(),
                            'status' => 'running',
                        ]);

                        $outputLines = [];
                        $service = app(WpeInventorySyncService::class);
                        $result = $service->run(function (string $message) use (&$outputLines): void {
                            $outputLines[] = $message;
                        });

                        $run->update([
                            'finished_at' => now(),
                            'status' => 'success',
                            'duration_seconds' => (int) round($result->duration_seconds),
                            'accounts_count' => $result->accounts_synced_count,
                            'sites_count' => $result->sites_synced_count,
                            'environments_count' => $result->environments_synced_count,
                            'message' => sprintf(
                                'Synced %d servers, %d sites, %d environments in %.1fs.',
                                $result->accounts_synced_count,
                                $result->sites_synced_count,
                                $result->environments_synced_count,
                                $result->duration_seconds
                            ),
                            'output' => implode("\n", $outputLines) . (empty($result->warnings) ? '' : "\n\nWarnings:\n" . implode("\n", $result->warnings)),
                        ]);

                        Notification::make()
                            ->title('Sync complete')
                            ->body($run->message)
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        if ($run !== null) {
                            $run->update([
                                'finished_at' => now(),
                                'status' => 'failed',
                                'message' => 'Sync failed: ' . $e->getMessage(),
                                'error' => $e->getMessage() . "\n\n" . $e->getTraceAsString(),
                            ]);
                        }

                        Notification::make()
                            ->title('Sync failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        $lock->release();
                    }
                }),
        ];
    }
}
