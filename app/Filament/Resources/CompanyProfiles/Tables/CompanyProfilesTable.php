<?php

namespace App\Filament\Resources\CompanyProfiles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class CompanyProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label('কোম্পানির নাম')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('about_company')
                    ->label('বিবরণ')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('greeting_behavior')
                    ->label('গ্রিটিংস নিয়ন্ত্রণ')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ai_first' => 'info',
                        'human_first' => 'warning',
                        default => 'secondary',
                    }),
                BadgeColumn::make('is_active')
                    ->label('সক্রিয়')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'সক্রিয়' : 'নিষ্ক্রিয়'),
                TextColumn::make('created_at')
                    ->label('তৈরির সময়')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

