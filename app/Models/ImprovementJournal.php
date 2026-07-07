<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ImprovementJournal extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'date',
        'finding_source',
        'initial_finding_description',
        'root_cause_analysis',
        'action',
        'action_type',
        'process',
        'responsible',
        'planned_deadline',
        'actual_date',
        'effectiveness_criteria',
        'effectiveness',
        'closure_date',
        'observations',
    ];

    protected $casts = [
        'date'          => 'date',
        'actual_date'   => 'date',
        'closure_date'  => 'date',
        'effectiveness' => 'boolean',
    ];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}