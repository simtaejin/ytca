<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaylistVideo extends Model
{
    protected $table = 'playlist_video'; // 꼭 지정

    public $timestamps = false;

    protected $fillable = [
        'playlist_id',
        'video_id',
    ];
}
