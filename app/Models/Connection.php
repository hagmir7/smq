<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    use HasFactory;

    protected $fillable = [
        'server',
        'username',
        'password',
        'auth_win',
        'status',
    ];

    protected $casts = [
        'auth_win' => 'boolean',
        'status' => 'boolean',
        'password' => 'encrypted',
    ];

    protected $hidden = [
        'password',
    ];
}
