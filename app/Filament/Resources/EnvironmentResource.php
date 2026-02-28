<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnvironmentResource\Pages;
use App\Models\Environment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader as CsvReader;

class EnvironmentResource extends Resource
{
    protected static ?string $model = Environment::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1) Environment Name (sticky handled via theme CSS best-effort)
                Tables\Columns\TextColumn::make('name')
                    ->label('Environment Name')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Environment $record): string => static::getUrl('notes', ['record' => $record]))
                    ->extraAttributes(['class' => 'nf-sticky-first-col']),

                // 2) Lifecycle Status (inline editable)
                SelectColumn::make('lifecycle_status')
                    ->label('Lifecycle Status')
                    ->options([
                        'active' => 'Active',
                        'to_be_deleted' => 'To Be Deleted',
                        'deleted' => 'Deleted',
                    ])
                    ->rules(['required']),

                // 3) WPE Status (read-only)
                Tables\Columns\TextColumn::make('status')
                    ->label('WPE Status')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'active' ? 'success' : 'danger'),

                // 4) Updates Integrity (derived: Archived | Not Set | No Schedule | Not Confirmed | OK)
                Tables\Columns\TextColumn::make('updates_integrity')
                    ->label('Updates Integrity')
                    ->state(fn (Environment $record): string => $record->getUpdatesIntegrity()[0])
                    ->badge()
                    ->color(fn (Environment $record): string => $record->getUpdatesIntegrity()[1]),

                // 5) Update Schedule (inline editable, blank allowed)
                SelectColumn::make('update_schedule')
                    ->label('Update Schedule')
                    ->placeholder('')
                    ->selectablePlaceholder()
                    ->options(array_combine(Environment::UPDATE_SCHEDULES, Environment::UPDATE_SCHEDULES)),

                // 6) Updates Schedule Set (inline editable)
                SelectColumn::make('updates_schedule_set')
                    ->label('Updates Schedule Set')
                    ->options([
                        1 => 'Yes',
                        0 => 'No',
                    ])
                    ->rules(['required']),

                // 7) Update Method (inline editable, blank allowed)
                SelectColumn::make('update_method')
                    ->label('Update Method')
                    ->placeholder('')
                    ->selectablePlaceholder()
                    ->options([
                        'wpe_managed' => 'wpe_managed',
                        'script' => 'script',
                        'manual' => 'manual',
                        'none' => 'none',
                    ]),

                // 8) Env Type badge
                Tables\Columns\TextColumn::make('environment')
                    ->label('Env Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'production' => 'success',
                        'staging' => 'info',
                        default => 'gray',
                    }),

                // 9) Site name
                Tables\Columns\TextColumn::make('site.name')->label('Site'),

                // 10) Server nickname (Account nickname)
                Tables\Columns\TextColumn::make('site.server.nickname')->label('Server'),

                // 11) WP Version
                Tables\Columns\TextColumn::make('wp_version')->label('WP Version'),

                // 12) PHP Version
                Tables\Columns\TextColumn::make('php_version')->label('PHP Version'),
            ])
            ->defaultSort('name', 'asc')
            ->headerActions([
                TableAction::make('importUpdateScheduleCsv')
                    ->label('Import Update Schedule CSV')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('CSV file')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->storeFiles(false)
                            ->required(),
                        Forms\Components\Toggle::make('overwrite_existing')
                            ->label('Overwrite existing schedules')
                            ->default(false),
                    ])
                    ->action(function (array $data): void {
                        /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file */
                        $file = $data['file'];
                        $overwriteExisting = (bool) ($data['overwrite_existing'] ?? false);

                        $csv = CsvReader::createFromPath($file->getRealPath(), 'r');
                        $csv->setHeaderOffset(0);

                        $matched = 0;
                        $updated = 0;
                        $skipped = 0;
                        $unmatched = 0;
                        $ambiguous = 0;
                        $invalid = 0;

                        $reportRows = [];

                        $normalizeScheduleSet = function (string $raw): bool {
                            $v = strtolower(trim($raw));
                            return in_array($v, ['yes', 'true', '1'], true);
                        };

                        foreach ($csv->getRecords() as $row) {
                            $envName = trim((string) ($row['Environment Name'] ?? $row['Environment name'] ?? ''));
                            $schedule = trim((string) ($row['Update Schedule'] ?? $row['Update schedule'] ?? ''));
                            $scheduleSetRaw = trim((string) ($row['Updates Schedule Set'] ?? $row['Updates schedule set'] ?? ''));
                            $scheduleSet = $scheduleSetRaw !== '' ? $normalizeScheduleSet($scheduleSetRaw) : false;
                            $serverNickname = trim((string) ($row['Server'] ?? $row['Server nickname'] ?? $row['Server Nickname'] ?? ''));
                            $envType = trim((string) ($row['Env Type'] ?? $row['Env type'] ?? $row['Environment'] ?? ''));

                            if ($envName === '') {
                                $skipped++;
                                $reportRows[] = [$envName, $schedule, $scheduleSet ? 'Yes' : 'No', 'skipped', 'Missing Environment Name'];
                                continue;
                            }

                            $candidates = Environment::query()->where('name', $envName)->with('site.server')->get();
                            if ($candidates->isEmpty()) {
                                $unmatched++;
                                $reportRows[] = [$envName, $schedule, $scheduleSet ? 'Yes' : 'No', 'unmatched', 'No environment found'];
                                continue;
                            }

                            if ($candidates->count() > 1) {
                                $before = $candidates->count();
                                if ($serverNickname !== '') {
                                    $candidates = $candidates->filter(fn (Environment $e) => $e->site?->server?->nickname === $serverNickname);
                                }
                                if ($candidates->count() > 1 && $envType !== '') {
                                    $candidates = $candidates->filter(fn (Environment $e) => $e->environment === $envType);
                                }
                                if ($candidates->count() !== 1) {
                                    $ambiguous++;
                                    $reportRows[] = [$envName, $schedule, $scheduleSet ? 'Yes' : 'No', 'ambiguous', 'Multiple matches, could not disambiguate'];
                                    continue;
                                }
                            }

                            $environment = $candidates->first();
                            $matched++;

                            $skipBecauseNoOverwrite = ! $overwriteExisting && (
                                ($environment->update_schedule !== null && trim((string) $environment->update_schedule) !== '')
                                || $environment->updates_schedule_set === true
                            );
                            if ($skipBecauseNoOverwrite) {
                                $skipped++;
                                $reportRows[] = [$envName, $schedule, $scheduleSet ? 'Yes' : 'No', 'skipped', 'Existing values preserved'];
                                continue;
                            }

                            if ($schedule !== '' && ! in_array($schedule, Environment::UPDATE_SCHEDULES, true)) {
                                $invalid++;
                                $reportRows[] = [$envName, $schedule, $scheduleSet ? 'Yes' : 'No', 'invalid', 'Schedule not in allowed list'];
                                continue;
                            }

                            $updateSchedule = $schedule !== '' ? $schedule : null;
                            $environment->update([
                                'update_schedule' => $updateSchedule,
                                'updates_schedule_set' => $scheduleSet,
                            ]);
                            $updated++;
                            $reportRows[] = [$envName, $schedule ?: '(blank)', $scheduleSet ? 'Yes' : 'No', 'updated', ''];
                        }

                        $filename = 'update-schedule-import-' . now()->format('Ymd-His') . '.csv';
                        $storagePath = 'import-reports/' . $filename;

                        $handle = fopen('php://temp', 'w+');
                        fputcsv($handle, ['Environment Name', 'Update Schedule', 'Updates Schedule Set', 'Result', 'Message']);
                        foreach ($reportRows as $reportRow) {
                            fputcsv($handle, $reportRow);
                        }
                        rewind($handle);
                        $csvOutput = stream_get_contents($handle);
                        fclose($handle);

                        Storage::disk('local')->put($storagePath, $csvOutput);

                        Notification::make()
                            ->title('Import complete')
                            ->body("Matched: {$matched}\nUpdated: {$updated}\nSkipped: {$skipped}\nUnmatched: {$unmatched}\nAmbiguous: {$ambiguous}\nInvalid: {$invalid}")
                            ->success()
                            ->actions([
                                NotificationAction::make('downloadReport')
                                    ->label('Download report')
                                    ->url(route('import-reports.download', ['filename' => $filename]))
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lifecycle_status')
                    ->label('Lifecycle Status')
                    ->options([
                        'active' => 'Active',
                        'to_be_deleted' => 'To Be Deleted',
                        'deleted' => 'Deleted',
                    ]),
                Tables\Filters\Filter::make('update_schedule_blank')
                    ->label('Update Schedule is blank')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $q) {
                        $q->whereNull('update_schedule')->orWhere('update_schedule', '');
                    })),
                Tables\Filters\Filter::make('updates_schedule_set_no')
                    ->label('Updates Schedule Set = No')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $q) {
                        $q->where('updates_schedule_set', false)->orWhereNull('updates_schedule_set');
                    })),
                Tables\Filters\SelectFilter::make('updates_integrity')
                    ->label('Updates Integrity')
                    ->options([
                        'archived' => 'Archived',
                        'not_set' => 'Not Set',
                        'no_schedule' => 'No Schedule',
                        'not_confirmed' => 'Not Confirmed',
                        'ok' => 'OK',
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $value = $data['value'] ?? null;
                        if ($value === null || $value === '') {
                            return;
                        }
                        if ($value === 'archived') {
                            $query->where('lifecycle_status', 'deleted');
                            return;
                        }
                        if ($value === 'not_set') {
                            $query->where(function (Builder $q) {
                                $q->where(function (Builder $q2) {
                                    $q2->whereNull('update_schedule')->orWhere('update_schedule', '');
                                })->where(function (Builder $q2) {
                                    $q2->where('updates_schedule_set', false)->orWhereNull('updates_schedule_set');
                                });
                            });
                            return;
                        }
                        if ($value === 'no_schedule') {
                            $query->where(function (Builder $q) {
                                $q->where(function (Builder $q2) {
                                    $q2->whereNull('update_schedule')->orWhere('update_schedule', '');
                                })->where('updates_schedule_set', true);
                            });
                            return;
                        }
                        if ($value === 'not_confirmed') {
                            $query->where(function (Builder $q) {
                                $q->whereNotNull('update_schedule')->where('update_schedule', '!=', '')
                                    ->where(function (Builder $q2) {
                                        $q2->where('updates_schedule_set', false)->orWhereNull('updates_schedule_set');
                                    });
                            });
                            return;
                        }
                        if ($value === 'ok') {
                            $query->whereNotNull('update_schedule')->where('update_schedule', '!=', '')
                                ->where('updates_schedule_set', true);
                        }
                    }),
                Tables\Filters\SelectFilter::make('update_schedule')
                    ->label('Update Schedule')
                    ->options(array_combine(Environment::UPDATE_SCHEDULES, Environment::UPDATE_SCHEDULES)),
                Tables\Filters\SelectFilter::make('update_method')
                    ->label('Update Method')
                    ->options([
                        'wpe_managed' => 'wpe_managed',
                        'script' => 'script',
                        'manual' => 'manual',
                        'none' => 'none',
                    ]),
                Tables\Filters\SelectFilter::make('environment')
                    ->label('Env type')
                    ->options([
                        'production' => 'Production',
                        'staging' => 'Staging',
                        'development' => 'Development',
                    ]),
                Tables\Filters\SelectFilter::make('server')
                    ->label('Server')
                    ->relationship('site.server', 'nickname')
                    ->searchable()
                    ->preload(),
            ])
            ->recordClasses(fn (Environment $record): ?string => $record->lifecycle_status === 'deleted' ? 'opacity-50' : null)
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnvironments::route('/'),
            'notes' => Pages\NotesEnvironment::route('/{record}/notes'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
