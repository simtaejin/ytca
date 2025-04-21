<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YoutubeToken extends Model
{
    protected $fillable = [
        'google_id',
        'email',
        'display_name',
        'access_token',
        'refresh_token',
        'expires_at',
        'token_type',
        'scope',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function channels()
    {
        return $this->hasMany(\App\Models\Channel::class);
    }
}
