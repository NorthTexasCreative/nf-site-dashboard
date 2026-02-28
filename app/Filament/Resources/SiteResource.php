<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Filament\Resources\SiteResource\RelationManagers;
use App\Models\Site;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Inventory';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Textarea::make('notes')->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('server.nickname')->label('Server')->sortable(),
                Tables\Columns\TextColumn::make('group_name')->toggleable(),
                Tables\Columns\TextColumn::make('lifecycle_status')->badge()->color(fn (string $state): string => $state === 'unknown' ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('environments_count')->counts('environments')->label('Envs'),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('lifecycle_status')->options(['active' => 'Active', 'unknown' => 'Unknown']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->visible(fn (Site $record): bool => $record->lifecycle_status === 'active'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'view' => Pages\ViewSite::route('/{record}'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('name'),
                TextEntry::make('server.nickname')->label('Server'),
                TextEntry::make('wpe_site_id')->label('WPE Site ID')->copyable(),
                TextEntry::make('group_name'),
                TextEntry::make('lifecycle_status')->badge()->color(fn (string $state): string => $state === 'unknown' ? 'danger' : 'success'),
                TextEntry::make('notes')->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EnvironmentsRelationManager::class,
        ];
    }
}
