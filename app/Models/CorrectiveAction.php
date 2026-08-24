<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CorrectiveAction extends Model
{
    use HasFactory;

    protected $dateFormat = 'Y-m-d\TH:i:s.v';

    protected $fillable = [
        'code',
        'description',
        'type',
        // 'effectiveness_criteria',
        'due_date',
        'completion_date',
        'effectiveness',
        'reclamation_id',
        'service_id',
        'responsable_id',
        'user_id',
        'parent_id',
        'status',
        'closing_date',
        'person_id'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completion_date' => 'date',
        'closing_date' => 'date',
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


    public function person(){
        return $this->belongsTo(Person::class, 'person_id');
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
    public function improvementSheet(): HasOne
    {
        return $this->hasOne(ImprovementSheet::class);
    }


    public function scopeFiltered($query, \Illuminate\Http\Request $request)
    {
        if ($request->filled('reclamation_id')) {
            $query->where('reclamation_id', $request->integer('reclamation_id'));
        }

        if ($request->filled('reclamation_code')) {
            $query->whereHas('reclamation', fn($r) =>
            $r->where('code', 'like', '%' . $request->input('reclamation_code') . '%'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('reclamation', fn($r) =>
                    $r->where('code', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        if ($request->filled('effectiveness')) {
            $query->where('effectiveness', $request->string('effectiveness'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        return $query;
    }

}
