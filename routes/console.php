<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('youtube:refresh-tokens')->hourly();

// 유튜브 영상 목록 동기화 (하루 2번: 오전 2시, 오후 2시)
Schedule::command('youtube:sync-videos')
    ->twiceDaily(2, 14)  // 2시, 14시
    ->withoutOverlapping();

// 유튜브 일일 통계 저장 (하루 1번: 새벽 3시)
Schedule::command('youtube:sync-daily-stats')
    ->dailyAt('03:00')
    ->withoutOverlapping();
