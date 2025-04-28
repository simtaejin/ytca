<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// 기본 Laravel 테스트 명령어
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// (옵션) 유튜브 리프레시 토큰 갱신 - 매시간
Schedule::command('youtube:refresh-tokens')->hourly();

// 유튜브 영상 목록 동기화 (매일 2회: 2시, 14시)
Schedule::command('youtube:sync-videos')
    ->twiceDaily(2, 14)
    ->withoutOverlapping();

// 유튜브 일일 통계 저장 (매일 1회: 3시)
Schedule::command('youtube:sync-daily-stats')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// 유튜브 주간 통계 저장 (매주 월요일 4시)
Schedule::command('youtube:sync-weekly-stats')
    ->weeklyOn(1, '04:00')
    ->withoutOverlapping();

// 유튜브 월간 통계 저장 (매달 1일 4시 15분)
Schedule::command('youtube:sync-monthly-stats')
    ->monthlyOn(1, '04:15')
    ->withoutOverlapping();
