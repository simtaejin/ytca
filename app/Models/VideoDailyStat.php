<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoDailyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'date',
        'view_count',
        'like_count',
        'comment_count',
        'view_increase',
        'like_increase',
        'comment_increase',
        'collected_at',
    ];

    protected $casts = [
        'date' => 'date',
        'collected_at' => 'datetime',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
