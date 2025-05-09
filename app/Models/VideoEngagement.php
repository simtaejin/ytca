<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoEngagement extends Model
{
    protected $fillable = [
        'video_id',
        'views',
        'likes',
        'comments',
        'shares',
        'subscribers_gained',
        'estimated_minutes_watched',
        'average_view_duration',
        'average_view_percentage',
    ];

    // 🔗 영상과의 관계 정의
    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
