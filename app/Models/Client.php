<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'company_name',
        'phone',
        'email',
        'address',
    ];

    protected $dateFormat = 'Y-m-d\TH:i:s.v';

    /**
     * Reclamations linked via matching client code
     * (reclamations.client_code -> clients.code).
     */
    public function reclamations(): HasMany
    {
        return $this->hasMany(Reclamation::class, 'client_code', 'code');
    }
}
