<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
class Notification extends DatabaseNotification
{
    protected $dateFormat = 'Y-m-d\TH:i:s.v';
}
