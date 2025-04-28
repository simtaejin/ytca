<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoWeeklyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'start_date',
        'end_date',
        'view_count',
        'like_count',
        'comment_count',
        'view_increase',
        'like_increase',
        'comment_increase',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
