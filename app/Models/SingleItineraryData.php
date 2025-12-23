<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SingleItineraryData extends Model
{
    use HasFactory;

    protected $table = 'singleitinerarydata';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $guarded = [];

    // Relations

    public function itinerary()
    {
        return $this->belongsTo(ItineraryData::class, 'ItineraryId', 'ItineraryId');
    }

    public function user()
    {
        return $this->belongsTo(UserData::class, 'userId', 'userId');
    }
}
