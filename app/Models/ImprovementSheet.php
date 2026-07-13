<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImprovementSheet extends Model
{
    protected $fillable = [
        'code',
        'corrective_action_id',
        'finding_source',
        'description',
        'cause_analysis',
        'title',
        'responsable_id',
        'service_id',
        'impact',
        'statut',
        'closed',
        'effectiveness',
        'observation_description',
        'observation_date',
        'closing_date',
    ];

    protected $dateFormat = 'Y-m-d\TH:i:s.v';

    protected $casts = [
        'closed'           => 'boolean',
        'effectiveness'    => 'boolean',
        'observation_date' => 'date',
        'closing_date'     => 'date',
    ];

    public function correctiveAction(): BelongsTo
    {
        return $this->belongsTo(CorrectiveAction::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function improvementActions()
    {
        return $this->hasMany(ImprovementAction::class);
    }

    public function responsibles()
    {
        return $this->hasMany(ImprovementSheetResponsible::class);
    }
}
