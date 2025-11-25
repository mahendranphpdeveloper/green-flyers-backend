<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class UserData extends Model
{

    use HasApiTokens;
    use HasFactory;

    protected $table = 'UserData';

    protected $primaryKey = 'userId';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $guarded = [];

    public function itineraries()
    {
        return $this->hasMany(ItineraryData::class, 'userId', 'userId');
    }

    public function offsets()
    {
        return $this->hasMany(OffsetData::class, 'userId', 'userId');
    }
}
