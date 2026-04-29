<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserShare extends Model
{
    use HasFactory;

    protected $table = 'user_shares';

    protected $fillable = [
        'social_meta_tag_id',
        'image_path',
        'shared_url',
    ];

    /**
     * Get the meta tags associated with the share.
     */
    public function metaTag()
    {
        return $this->belongsTo(SocialMetaTag::class, 'social_meta_tag_id');
    }
}
