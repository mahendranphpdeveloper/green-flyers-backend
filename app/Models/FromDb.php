<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FromDb extends Model
{
    use HasFactory;

    // Specify the table if it doesn't follow Laravel's naming convention
    protected $table = 'from_db';

    // Primary key type
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;

    // Mass assignable attributes
    protected $fillable = [
        'api_call_id',
        'origin',
        'destination',
        'travel_date',
        'cabin_class',
        'co2_per_passenger',
        'used_at',
        'used_by_user',
        'passengers',
    ];

    // Dates / timestamps
    public $timestamps = false; // since you use 'used_at' manually

    protected $casts = [
        'travel_date' => 'string',
        'used_at' => 'string',
        'co2_per_passenger' => 'decimal:2',
        'passengers' => 'integer',
        'used_by_user' => 'integer',
        'api_call_id' => 'integer',
    ];

    /**
     * Relation to ApiCall
     */
    public function apiCall()
    {
        return $this->belongsTo(ApiCall::class, 'api_call_id', 'id');
    }
}
