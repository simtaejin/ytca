<?php

namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;

class VideoStat extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'video_stats';

    protected $fillable = [
        'youtube_channel_id',
        'youtube_video_id',
        'view_count',
        'like_count',
        'comment_count',
        'collected_at',
        'created_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;
}
