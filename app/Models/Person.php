<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{


    protected $fillable = ['full_name', 'code', 'user_id'];



    protected $dateFormat = 'Y-m-d\TH:i:s.v';
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
