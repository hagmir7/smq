<?php

namespace App\Observers;

use App\Models\ImprovementAction;
use App\Models\ImprovementJournal;

class ImprovementActionObserver
{
    public function created(ImprovementAction $action): void
    {
        $this->sync($action);
    }

    public function updated(ImprovementAction $action): void
    {
        $this->sync($action);
    }

    public function deleted(ImprovementAction $action): void
    {
        ImprovementJournal::where('source_type', ImprovementAction::class)
            ->where('source_id', $action->id)
            ->delete();
    }

    private function sync(ImprovementAction $action): void
    {
        ImprovementJournal::updateOrCreate(
            [
                'source_type' => ImprovementAction::class,
                'source_id'   => $action->id,
            ],
            [
                'date'                     => $action->created_at?->toDateString(),
                'finding_source'           => $action->improvementSheet?->finding_source,
                'initial_finding_description' => $action->improvementSheet?->description,
                'root_cause_analysis'      => $action->improvementSheet?->cause_analysis,
                'action'                   => $action->description,
                'action_type'              => 'Action d\'amélioration',
                'process'                  => $action->service?->name,
                'responsible'              => $action->responsable?->name,
                'planned_deadline'         => optional($action->due_date)->toDateString(),
                'actual_date'              => $action->completion_date,
                'effectiveness_criteria'   => $action->effectiveness_criteria,
                'effectiveness'            => $action->effectiveness === 'Efficace',
                'closure_date'             => $action->completion_date,
                'observations'             => null,
            ]
        );
    }
}