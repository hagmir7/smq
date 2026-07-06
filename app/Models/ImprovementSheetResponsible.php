<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImprovementSheetResponsible extends Model
{
    protected $fillable = [
        'improvement_sheet_id',
        'service_id',
        'responsable_id',
    ];

    public function improvementSheet(): BelongsTo
    {
        return $this->belongsTo(ImprovementSheet::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}