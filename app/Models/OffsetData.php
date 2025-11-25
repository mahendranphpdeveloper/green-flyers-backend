<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OffsetData extends Model
{
    use HasFactory;

    protected $table = 'OffsetData';

    protected $primaryKey = 'SINo';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $guarded = [];

    public function itinerary()
    {
        return $this->belongsTo(ItineraryData::class, 'ItineraryId', 'ItineraryId');
    }

    public function user()
    {
        return $this->belongsTo(UserData::class, 'userId', 'userId');
    }
}
