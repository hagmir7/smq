<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReclamationRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_date',
        'registration_date',
        'client_name',
        'subject',
        'proposed_actions',
        'planned_date',
        'actual_completion_date',
        'improvement_sheet_number',
        'closing_date',
    ];

    protected $casts = [
        'complaint_date' => 'date',
        'registration_date' => 'date',
        'planned_date' => 'date',
        'actual_completion_date' => 'date',
        'closing_date' => 'date',
    ];

    protected $dateFormat = 'Y-m-d\TH:i:s.v';
}
