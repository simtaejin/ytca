<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class VideoStat extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'video_stats';

    protected $fillable = [
        'video_id', 'channel_id', 'collected_at',
        'view_count', 'like_count', 'comment_count', 'created_at'
    ];

    public $timestamps = false;
}
