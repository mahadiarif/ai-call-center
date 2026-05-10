<?php
namespace App\Filament\Resources\FeedbackSurveys\Pages;
use App\Filament\Resources\FeedbackSurveys\FeedbackSurveyResource;
use App\Models\FeedbackSurvey;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFeedbackSurveys extends ListRecords
{
    protected static string $resource = FeedbackSurveyResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('+ নতুন Entry')]; }
    public function getSubheading(): ?string
    {
        $total=$t=FeedbackSurvey::count();
        $completed=FeedbackSurvey::where('call_status','completed')->count();
        $satisfied=FeedbackSurvey::where('satisfied','yes')->count();
        $problems=FeedbackSurvey::where('has_problem','yes')->count();
        $pct=$completed>0?round(($satisfied/$completed)*100).'%':'—';
        return "Total: {$total} | Completed: {$completed} | Satisfied: {$satisfied} ({$pct}) | Problems: {$problems}";
    }
}