<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'responsible_id'
    ];

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function improvementSheets(): HasMany
    {
        return $this->hasMany(ImprovementSheet::class);
    }

    public function improvementActions(): HasMany
    {
        return $this->hasMany(ImprovementAction::class);
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }

    public function improvementSheetResponsibles(): HasMany
    {
        return $this->hasMany(ImprovementSheetResponsible::class);
    }
}
