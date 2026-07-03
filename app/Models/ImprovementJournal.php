<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImprovementJournal extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'date' => 'date',
        'actual_date' => 'date',
        'closure_date' => 'date',
        'effectiveness' => 'boolean',
    ];
}
