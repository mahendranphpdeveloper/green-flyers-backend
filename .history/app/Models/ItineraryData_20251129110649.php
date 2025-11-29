<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryData extends Model
{
    use HasFactory;

    protected $table = 'Itinerarydata';

    protected $primaryKey = 'ItineraryId';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(UserData::class, 'userId', 'userId');
    }

    public function offsets()
    {
        return $this->hasMany(OffsetData::class, 'ItineraryId', 'ItineraryId');
    }
}
