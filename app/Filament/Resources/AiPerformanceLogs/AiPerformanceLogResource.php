<?php

namespace App\Filament\Resources\AiPerformanceLogs;

use App\Models\AiPerformanceLog;
use App\Filament\Resources\AiPerformanceLogs\Pages\ListAiPerformanceLogs;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class AiPerformanceLogResource extends Resource
{
    protected static ?string $model = AiPerformanceLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static ?string $navigationLabel = 'AI Performance';
    protected static string|\UnitEnum|null $navigationGroup = '📊 Analytics & Logs';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'AI Performance Log';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('serviceRequest.customer_name')
                    ->label('Customer')
                    ->default('Unknown')
                    ->description(fn ($record) => $record->serviceRequest?->mobile_number)
                    ->searchable(),

                TextColumn::make('ivrService.service_name')
                    ->label('Service')
                    ->badge()
                    ->color('info')
                    ->default('—'),

                TextColumn::make('score')
                    ->label('AI Score')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->color(fn ($state) => match(true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default      => 'danger',
                    })
                    ->badge()
                    ->sortable(),

                TextColumn::make('training_status')
                    ->label('Training Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'suggested' => '💡 Suggested',
                        'confirmed' => '✅ Confirmed',
                        'rejected'  => '❌ Rejected',
                        default     => '⏳ Pending',
                    })
                    ->color(fn ($state) => match($state) {
                        'suggested' => 'warning',
                        'confirmed' => 'success',
                        'rejected'  => 'danger',
                        default     => 'gray',
                    })
                    ->badge()
                    ->sortable(),

                TextColumn::make('ai_suggestion')
                    ->label('AI Suggestion')
                    ->limit(80)
                    ->wrap()
                    ->tooltip(fn ($record) => $record->ai_suggestion),

                TextColumn::make('filled_fields')
                    ->label('Data Collected')
                    ->formatStateUsing(fn ($state, $record) => "{$state}/{$record->total_fields}")
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('score_range')
                    ->label('Score Filter')
                    ->options([
                        'excellent' => '🌟 Excellent (90%+)',
                        'good'      => '✅ Good (70-89%)',
                        'average'   => '⚠️ Average (50-69%)',
                        'poor'      => '❌ Poor (<50%)',
                    ])
                    ->query(function ($query, array $data) {
                        if (!$data['value']) return $query;
                        return match($data['value']) {
                            'excellent' => $query->where('score', '>=', 90),
                            'good'      => $query->whereBetween('score', [70, 89]),
                            'average'   => $query->whereBetween('score', [50, 69]),
                            'poor'      => $query->where('score', '<', 50),
                        };
                    }),
                
                SelectFilter::make('training_status')
                    ->label('Training Status')
                    ->options([
                        'suggested' => '💡 AI Suggested',
                        'confirmed' => '✅ Confirmed for Training',
                        'rejected'  => '❌ Rejected',
                        'pending'   => '⏳ Pending Review',
                    ]),
            ])
            ->actions([
                Action::make('confirm_training')
                    ->label('✅ Confirm')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn ($record) => $record->training_status !== 'confirmed')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm as Training Data')
                    ->modalDescription(fn ($record) => 
                        "This conversation will be used for AI training.\n\n" .
                        "Score: {$record->score}%\n" .
                        "Data: {$record->filled_fields}/{$record->total_fields} fields\n\n" .
                        "AI suggests: {$record->ai_suggestion}"
                    )
                    ->form([
                        \Filament\Forms\Components\Textarea::make('training_notes')
                            ->label('Admin Notes (Optional)')
                            ->placeholder('Why this is good training data...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'training_status' => 'confirmed',
                            'training_notes' => $data['training_notes'] ?? null,
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('✅ Training Data Confirmed!')
                            ->body("Log #{$record->id} added to training.")
                            ->send();
                    }),

                Action::make('reject_training')
                    ->label('❌ Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn ($record) => $record->training_status !== 'rejected')
                    ->requiresConfirmation()
                    ->modalHeading('Reject from Training')
                    ->modalDescription('Why won\'t you use this conversation for training?')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('training_notes')
                            ->label('Reason for Rejection')
                            ->placeholder('AI hallucination / Data incomplete / Customer abusive...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'training_status' => 'rejected',
                            'training_notes' => $data['training_notes'],
                        ]);
                        
                        Notification::make()
                            ->warning()
                            ->title('❌ Rejected from Training')
                            ->body("Log #{$record->id} excluded from training.")
                            ->send();
                    }),

                Action::make('view_transcript')
                    ->label('📜 Transcript')
                    ->color('info')
                    ->icon('heroicon-o-document-text')
                    ->modalHeading('Full Conversation Transcript')
                    ->modalContent(fn ($record) => view('filament.modals.transcript-view', [
                        'transcript' => $record->serviceRequest?->call_transcript ?? 'No transcript available',
                        'score' => $record->score,
                        'suggestion' => $record->ai_suggestion,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('বন্ধ করুন'),

                Action::make('view_detail')
                    ->label('Details')
                    ->color('warning')
                    ->icon('heroicon-o-information-circle')
                    ->modalHeading('AI Performance Details')
                    ->modalDescription(fn ($record) =>
                        '📊 Score: ' . $record->score . '% | ' .
                        '✅ Collected: ' . $record->filled_fields . '/' . $record->total_fields . ' | ' .
                        '❌ Missing: ' . (is_array($record->missing_fields) && count($record->missing_fields) ? implode(', ', $record->missing_fields) : 'None')
                    )
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->headerActions([
                Action::make('export_training_data')
                    ->label('📥 Export Training Data')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url('/admin/export-training-data')
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiPerformanceLogs::route('/'),
        ];
    }
}
