<?php

namespace App\Filament\Resources\ServiceRequests;

use App\Filament\Resources\ServiceRequests\Pages\CreateServiceRequest;
use App\Filament\Resources\ServiceRequests\Pages\EditServiceRequest;
use App\Filament\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Models\ServiceRequest;
use App\Models\IvrService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = "Service Requests";

    public static function form(Schema $schema): Schema
    {
        $components = [
            \Filament\Forms\Components\TextInput::make("customer_name")->label("Customer Name"),
            \Filament\Forms\Components\TextInput::make("mobile_number")->label("Mobile Number")->required(),
            \Filament\Forms\Components\TextInput::make("alt_mobile_number")->label("Alternate Mobile Number"),
            \Filament\Forms\Components\TextInput::make("address")->label("Address"),
            \Filament\Forms\Components\TextInput::make("district")->label("District"),
            \Filament\Forms\Components\TextInput::make("product_name")->label("Product Name"),
            \Filament\Forms\Components\TextInput::make("barcode")->label("Barcode/Serial Number"),
            \Filament\Forms\Components\Textarea::make("problem_description")->label("Problem Description")->rows(3),
            \Filament\Forms\Components\TextInput::make("service_center")->label("Service Center"),
            \Filament\Forms\Components\TextInput::make("brand")->label("Brand")->default("WALTON"),
            \Filament\Forms\Components\Textarea::make("comments")->label("Comments")->rows(2),
            \Filament\Forms\Components\Select::make("status")
                ->label("Status")
                ->options([
                    "Incoming"  => "Incoming",
                    "Pending"   => "Pending",
                    "Escalation Requested" => "Escalation Requested",
                    "Resolved"  => "Resolved",
                    "Rejected"  => "Rejected",
                    "Drop Call" => "Drop Call",
                ])
                ->required(),
            \Filament\Forms\Components\Textarea::make("call_transcript")
                ->label("Call Transcript")
                ->rows(5),
        ];

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("id")->label("#")->sortable()->width("50px"),
                TextColumn::make("customer_name")->label("Name")->searchable()
                    ->default("N/A")
                    ->description(fn ($record) => $record->mobile_number ? "\ud83d\udcf1 {$record->mobile_number}" : null)
                    ->sortable(),
                TextColumn::make("ivrService.service_name")->label("Category")->badge()->color("info")->default("N/A"),
                TextColumn::make("problem_description")->label("Problem")->limit(50)
                    ->default("-")
                    ->description(fn ($record) => $record->product_name ? "\ud83d\udce6 {$record->product_name}" : null)
                    ->tooltip(fn ($record) => $record->problem_description),
                TextColumn::make("status")->label("Status")->badge()
                    ->color(fn (string $state): string => match($state) {
                        "Incoming"  => "info",
                        "Pending"   => "warning",
                        "Escalation Requested" => "purple",
                        "Drop Call" => "danger",
                        "Resolved"  => "success",
                        "Rejected"  => "danger",
                        default     => "gray",
                    })->sortable(),
                TextColumn::make("created_at")->label("Time")->since()->sortable()
                    ->description(fn ($record) => $record->call_transcript ? "\ud83c\udfac Transcript" : null)
                    ->tooltip(fn ($record) => $record->created_at?->format("d M Y, h:i A")),
            ])
            ->defaultSort("created_at", "desc")
            ->filters([
                SelectFilter::make("status")->label("Status")->options([
                    "Incoming"  => "Incoming",
                    "Pending"   => "Pending",
                    "Escalation Requested" => "Escalation Requested",
                    "Drop Call" => "Drop Call",
                    "Resolved"  => "Resolved",
                    "Rejected"  => "Rejected",
                ]),
                SelectFilter::make("ivr_service_id")->label("Category")
                    ->relationship("ivrService", "service_name"),
                Filter::make("created_at")->label("Date Range")
                    ->form([
                        DatePicker::make("from")->label("From"),
                        DatePicker::make("until")->label("Until"),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data["from"],  fn ($q) => $q->whereDate("created_at", ">=", $data["from"]))
                        ->when($data["until"], fn ($q) => $q->whereDate("created_at", "<=", $data["until"]))
                    ),
            ])
            ->actions([
                EditAction::make()->label("")->tooltip("Edit"),
                DeleteAction::make()->label("")->tooltip("Delete"),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn ($record) => static::getUrl("edit", ["record" => $record]));
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            "index"  => ListServiceRequests::route("/"),
            "create" => CreateServiceRequest::route("/create"),
            "edit"   => EditServiceRequest::route("/{record}/edit"),
        ];
    }
}
