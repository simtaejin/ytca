<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Video;
use App\Models\VideoDailyStat;
use App\Models\VideoWeeklyStat;
use Carbon\Carbon;

class SyncWeeklyStats extends Command
{
    protected $signature = 'youtube:sync-weekly-stats';
    protected $description = '7일간 video_daily_stats를 기반으로 주간 통계를 저장합니다.';

    public function handle()
    {
        $this->info('🔄 Video Weekly Stats 동기화 시작...');

        // 주간 기준: 오늘 포함 지난 6일
        $endDate = Carbon::yesterday()->toDateString(); // 어제까지
        $startDate = Carbon::parse($endDate)->subDays(6)->toDateString(); // 어제 기준 6일 전

        $this->info("📅 기간: {$startDate} ~ {$endDate}");

        $videos = Video::all();
        $saved = 0;

        foreach ($videos as $video) {
            // 지난 7일간 일일 통계 가져오기
            $dailyStats = VideoDailyStat::where('video_id', $video->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date')
                ->get();

            if ($dailyStats->count() < 7) {
                $this->warn("⚠️ 영상 [{$video->id}] {$video->title} → 데이터 부족 (7일 미만)");
                continue;
            }

            // 주간 증가량 합산
            $viewIncrease = $dailyStats->sum('view_increase');
            $likeIncrease = $dailyStats->sum('like_increase');
            $commentIncrease = $dailyStats->sum('comment_increase');

            // 주간 마지막 날 누적값 (7일 중 가장 마지막)
            $lastDayStat = $dailyStats->last();

            VideoWeeklyStat::updateOrCreate(
                [
                    'video_id' => $video->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                [
                    'view_count' => $lastDayStat->view_count,
                    'like_count' => $lastDayStat->like_count,
                    'comment_count' => $lastDayStat->comment_count,
                    'view_increase' => $viewIncrease,
                    'like_increase' => $likeIncrease,
                    'comment_increase' => $commentIncrease,
                ]
            );

            $saved++;
        }

        $this->info("✅ 주간 통계 저장 완료: {$saved}개 영상");
    }
}
