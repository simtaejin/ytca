<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoDailyReport extends Model
{
    protected $fillable = [
        'date',
        'prompt',
        'gpt_answer',
    ];
}