<?php

namespace App\Filament\Widgets;

use App\Models\Site;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class NeedsAttentionWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Site::query()
                    ->where('lifecycle_status', 'unknown')
                    ->orWhereHas('environments', fn ($q) => $q->whereNull('wp_version'))
            )
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('server.nickname')->label('Server'),
                TextColumn::make('lifecycle_status')->badge()->color(fn (string $state): string => $state === 'unknown' ? 'danger' : 'warning'),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('view')
                    ->label('View')
                    ->url(fn (Site $record): string => \App\Filament\Resources\SiteResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([10, 25, 50]);
    }

    protected function getTableHeading(): ?string
    {
        return 'Needs Attention';
    }
}