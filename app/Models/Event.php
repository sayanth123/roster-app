<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'date', 'type', 'flight_number', 'departure', 'arrival', 
        'std_utc', 'sta_utc', 'check_in_utc', 'check_out_utc'
    ];
}
