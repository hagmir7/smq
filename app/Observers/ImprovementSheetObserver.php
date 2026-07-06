<?php

namespace App\Observers;

use App\Models\ImprovementJournal;
use App\Models\ImprovementSheet;

class ImprovementSheetObserver
{
    public function created(ImprovementSheet $sheet): void
    {
        $this->sync($sheet);
    }

    public function updated(ImprovementSheet $sheet): void
    {
        $this->sync($sheet);
    }

    public function deleted(ImprovementSheet $sheet): void
    {
        ImprovementJournal::where('source_type', ImprovementSheet::class)
            ->where('source_id', $sheet->id)
            ->delete();
    }

    private function sync(ImprovementSheet $sheet): void
    {
        ImprovementJournal::updateOrCreate(
            [
                'source_type' => ImprovementSheet::class,
                'source_id'   => $sheet->id,
            ],
            [
                'date'                          => $sheet->created_at?->toDateString(),
                'finding_source'                => $sheet->finding_source,
                'initial_finding_description'   => $sheet->description,
                'root_cause_analysis'           => $sheet->cause_analysis,
                'process'                       => $sheet->service?->name,
                'responsible'                   => $sheet->responsable?->name,
                'actual_date'                   => $sheet->observation_date,
                'effectiveness'                 => $sheet->effectiveness,
                'closure_date'                  => $sheet->closing_date,
                'observations'                  => $sheet->observation_description,
            ]
        );
    }
}