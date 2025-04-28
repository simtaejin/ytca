<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Video;
use App\Models\VideoDailyStat;
use App\Models\VideoMonthlyStat;
use Carbon\Carbon;

class SyncMonthlyStats extends Command
{
    protected $signature = 'youtube:sync-monthly-stats';
    protected $description = '지난달 video_daily_stats를 기반으로 월간 통계를 저장합니다.';

    public function handle()
    {
        $this->info('🔄 Video Monthly Stats 동기화 시작...');

        $startDate = now()->subMonthNoOverflow()->startOfMonth()->toDateString(); // 지난달 1일
        $endDate = now()->subMonthNoOverflow()->endOfMonth()->toDateString();     // 지난달 말일
        $month = Carbon::parse($startDate)->format('Y-m'); // 저장할 month

        $this->info("📅 기간: {$startDate} ~ {$endDate} (월: {$month})");

        $videos = Video::all();
        $saved = 0;

        foreach ($videos as $video) {
            $dailyStats = VideoDailyStat::where('video_id', $video->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date')
                ->get();

            $totalDaysInMonth = Carbon::parse($startDate)->daysInMonth;

            if ($dailyStats->count() < $totalDaysInMonth) {
                $this->warn("⚠️ 영상 [{$video->id}] {$video->title} → 데이터 부족 ({$dailyStats->count()}일)");
                continue;
            }

            $viewIncrease = $dailyStats->sum('view_increase');
            $likeIncrease = $dailyStats->sum('like_increase');
            $commentIncrease = $dailyStats->sum('comment_increase');

            $lastDayStat = $dailyStats->last();

            VideoMonthlyStat::updateOrCreate(
                [
                    'video_id' => $video->id,
                    'month' => $month,
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

        $this->info("✅ 월간 통계 저장 완료: {$saved}개 영상");
    }
}
