<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoMonthlyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'month',
        'view_count',
        'like_count',
        'comment_count',
        'view_increase',
        'like_increase',
        'comment_increase',
    ];

    protected $casts = [
        'month' => 'string',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
