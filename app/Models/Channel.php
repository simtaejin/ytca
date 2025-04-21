<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'youtube_channel_id',
        'name',
        'profile_image_url',
        'description',
        'subscriber_count',
        'video_count',
        'view_count',
        'synced_at',
        'is_active',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function youtubeToken()
    {
        return $this->belongsTo(\App\Models\YoutubeToken::class);
    }
}
