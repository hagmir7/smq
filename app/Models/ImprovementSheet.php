<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImprovementSheet extends Model
{
    use HasFactory;

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

    protected $casts = [
        'closed' => 'boolean',
        'effectiveness' => 'boolean',
        'observation_date' => 'date',
        'closing_date' => 'date',
    ];

    public function correctiveAction(): BelongsTo
    {
        return $this->belongsTo(CorrectiveAction::class);
    }

    /**
     * User responsible for the improvement sheet.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function improvementActions(): HasMany
    {
        return $this->hasMany(ImprovementAction::class);
    }
}
