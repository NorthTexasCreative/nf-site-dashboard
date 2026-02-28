<?php

namespace App\Filament\Resources\SiteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EnvironmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'environments';

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
                Tables\Columns\TextColumn::make('environment')->badge(),
                Tables\Columns\TextColumn::make('primary_domain'),
                Tables\Columns\TextColumn::make('wp_version')->label('WP'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Notes')
                    ->url(fn ($record) => \App\Filament\Resources\EnvironmentResource::getUrl('notes', ['record' => $record])),
            ])
            ->bulkActions([]);
    }
}
