<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImprovementAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'improvement_sheet_id',
        'code',
        'description',
        'responsable_id',
        'service_id',
        'effectiveness_criteria',
        'due_date',
        'completion_date',
        'effectiveness',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completion_date' => 'date',
    ];

    public function improvementSheet(): BelongsTo
    {
        return $this->belongsTo(ImprovementSheet::class);
    }

    /**
     * User responsible for the improvement action.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
