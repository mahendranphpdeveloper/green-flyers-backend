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
        'originCity',
        'destination',
        'destinationCity',
        'travel_date',
        'cabin_class',
        'co2_per_passenger',
        'source',
        'reuse_history',
        'user_id', // Include the foreign key in mass assignable attributes
    ];

    /**
     * The user that this ApiCall belongs to.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\UserData::class, 'user_id', 'userId');
    }
}
