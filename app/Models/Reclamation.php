<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reclamation extends Model
{
    use HasFactory;

    protected $fillable = [
        'claimant_date',
        'claimant_name',
        'client_code',
        'client_phone',
        'client_email',
        'client_company_name',
        'reception_method',
        'object',
        'description',
        'post_analysis',
        'is_recevable',
        'corrective_action',
        'processing_analysis',
        'is_justifiee',
        'cause_analysis',
        'priority',
        'statut',
        'workflow_step',
        'responsable_id',
        'planned_closing_date',
        'closing_date',
        'received_at',
        'user_id',
    ];

    protected $casts = [
        'claimant_date' => 'date',
        'planned_closing_date' => 'date',
        'closing_date' => 'date',
        'received_at' => 'date',
        'is_recevable' => 'boolean',
        'is_justifiee' => 'boolean',
        'workflow_step' => 'integer',
    ];

    /**
     * User responsible for handling the reclamation.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * User who created/registered the reclamation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Client linked via matching code (reclamations.client_code -> clients.code).
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_code', 'code');
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }
}
