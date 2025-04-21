<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoScene extends Model
{
    protected $fillable = [
        'video_id',
        'narration_json',
        'image_prompt_json',
        'note',
    ];

    protected $casts = [
        'narration_json' => 'array',
        'image_prompt_json' => 'array',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
