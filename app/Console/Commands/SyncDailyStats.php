<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Video;
use App\Models\VideoDailyStat;
use Carbon\Carbon;

class SyncDailyStats extends Command
{
    protected $signature = 'youtube:sync-daily-stats';
    protected $description = '오늘 날짜 기준으로 videos 테이블의 데이터를 video_daily_stats에 저장합니다.';

    public function handle()
    {
        $this->info('🔄 Video Daily Stats 동기화 시작...');

        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        $videos = Video::all();
        $saved = 0;

        // 🔄 모든 영상 저장 → 0건도 포함
        foreach ($videos as $video) {
            // 어제 데이터 가져오기
            $yesterdayStat = VideoDailyStat::where('video_id', $video->id)
                ->where('date', $yesterday)
                ->first();

            $viewIncrease = $yesterdayStat ? $video->view_count - $yesterdayStat->view_count : 0;
            $likeIncrease = $yesterdayStat ? $video->like_count - $yesterdayStat->like_count : 0;
            $commentIncrease = $yesterdayStat ? $video->comment_count - $yesterdayStat->comment_count : 0;

            VideoDailyStat::updateOrCreate(
                [
                    'video_id' => $video->id,
                    'date' => $today,
                ],
                [
                    'view_count' => $video->view_count,
                    'like_count' => $video->like_count,
                    'comment_count' => $video->comment_count,
                    'view_increase' => $viewIncrease,
                    'like_increase' => $likeIncrease,
                    'comment_increase' => $commentIncrease,
                    'collected_at' => now(),
                ]
            );

            $saved++;
        }

        $this->info("✅ {$saved}개의 영상 통계가 저장되었습니다.");
    }
}
