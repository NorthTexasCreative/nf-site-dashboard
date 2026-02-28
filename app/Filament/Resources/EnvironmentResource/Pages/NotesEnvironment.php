<?php

namespace App\Filament\Resources\EnvironmentResource\Pages;

use App\Filament\Resources\EnvironmentResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class NotesEnvironment extends EditRecord
{
    protected static string $resource = EnvironmentResource::class;

    protected static ?string $title = 'Notes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(10),
                    ]),
                Forms\Components\Section::make('Tasks')
                    ->schema([
                        Forms\Components\Placeholder::make('tasks_placeholder')
                            ->content('Tasks coming soon.'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make('back')
                ->label('Back to Environments')
                ->url(EnvironmentResource::getUrl('index')),
        ];
    }
}

