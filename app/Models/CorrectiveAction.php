<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorrectiveAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'type',
        'effectiveness_criteria',
        'due_date',
        'completion_date',
        'effectiveness',
        'reclamation_id',
        'service_id',
        'responsable_id',
        'user_id',
        'parent_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completion_date' => 'date',
    ];

    public function reclamation(): BelongsTo
    {
        return $this->belongsTo(Reclamation::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * User responsible for carrying out the action.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * User who created the corrective action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parent corrective action (self-referencing).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CorrectiveAction::class, 'parent_id');
    }

    /**
     * Child corrective actions (self-referencing).
     */
    public function children(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class, 'parent_id');
    }

    /**
     * Improvement sheets raised from this corrective action.
     */
    public function improvementSheets(): HasMany
    {
        return $this->hasMany(ImprovementSheet::class);
    }

}
