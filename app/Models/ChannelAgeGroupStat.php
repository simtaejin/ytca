<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelAgeGroupStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'age_group',
        'viewer_percentage',
        'collected_at',
    ];

    protected $casts = [
        'viewer_percentage' => 'float',
        'collected_at' => 'datetime',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
