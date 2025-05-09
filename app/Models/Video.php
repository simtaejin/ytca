<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'youtube_video_id',
        'title',
        'description',
        'thumbnail_url',
        'published_at',
        'duration',
        'view_count',
        'like_count',
        'comment_count',
        'video_type',
        'privacy_status',
        'synced_at',
        'is_active',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // 🔗 관계 설정: Video → Channel (N:1)
    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function scene()
    {
        return $this->hasOne(VideoScene::class);
    }

    public function engagement()
    {
        return $this->hasOne(VideoEngagement::class);
    }
}
