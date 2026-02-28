<?php

namespace App\Filament\Widgets;

use App\Models\WpeSyncRun;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LastWpeInventorySyncWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 1;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $run = WpeSyncRun::query()
            ->where('sync_type', 'inventory')
            ->orderByDesc('started_at')
            ->first();

        if (! $run) {
            return [
                Stat::make('Last Inventory Sync', 'Never run')
                    ->description('Use the Sync WP Engine Inventory button above to run.')
                    ->color('gray')
                    ->icon('heroicon-o-arrow-path'),
            ];
        }

        $statusLabel = match ($run->status) {
            'running' => 'Running',
            'success' => 'Success',
            'failed' => 'Failed',
            default => ucfirst($run->status),
        };

        $statusColor = match ($run->status) {
            'running' => 'gray',
            'success' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };

        $description = $run->finished_at
            ? $run->finished_at->diffForHumans()
            : 'Started ' . $run->started_at->diffForHumans();

        if ($run->status === 'success' && $run->accounts_count !== null) {
            $description .= ' — ' . $run->accounts_count . ' servers, '
                . $run->sites_count . ' sites, '
                . $run->environments_count . ' environments';
            if ($run->duration_seconds !== null) {
                $description .= ' in ' . $run->duration_seconds . 's';
            }
        }

        return [
            Stat::make('Last Inventory Sync', $statusLabel)
                ->description($description)
                ->color($statusColor)
                ->icon(match ($run->status) {
                    'running' => 'heroicon-o-arrow-path',
                    'success' => 'heroicon-o-check-circle',
                    'failed' => 'heroicon-o-x-circle',
                    default => 'heroicon-o-arrow-path',
                }),
        ];
    }
}
