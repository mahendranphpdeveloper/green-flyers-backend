<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiCall extends Model
{
    use HasFactory;

    protected $table = 'api_calls';

    protected $fillable = [
        'origin',
        'destination',
        'travel_date',
        'cabin_class',
        'co2_per_passenger',
        'source',
    ];
}
