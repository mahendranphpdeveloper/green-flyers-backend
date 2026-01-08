<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $guarded = [];

    // Relations

    public function singleitinerary()
    {
        return $this->belongsTo(SingleItineraryData::class, 'singleitinerary_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(UserData::class, 'user_id', 'userId');
    }
}
