<?php

namespace App\Filament\Resources\ServerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SitesRelationManager extends RelationManager
{
    protected static string $relationship = 'sites';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('lifecycle_status')->badge()->color(fn (string $state): string => $state === 'unknown' ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('environments_count')->counts('environments')->label('Envs'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()->url(fn ($record) => \App\Filament\Resources\SiteResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([]);
    }
}
