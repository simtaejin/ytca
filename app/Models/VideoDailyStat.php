<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoDailyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'youtube_channel_id',
        'youtube_video_id', // ← 추가
        'channel_id',        // 필요 시
        'view_count',
        'like_count',
        'comment_count',
        'collected_at',
    ];

    protected $casts = [
        'date' => 'date',
        'collected_at' => 'datetime',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
