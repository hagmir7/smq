<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;


class Reclamation extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

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
        'code'
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

    protected $dateFormat = 'Y-m-d\TH:i:s.v';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('public') // or 's3', whatever your disk is
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->performOnCollections('attachments')
            ->nonQueued(); // keep it simple; drop this if you queue conversions
    }




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
