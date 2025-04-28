<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\ChannelAgeGroupStat;
use App\Services\YoutubeAnalyticsService;
use Carbon\Carbon;

class SyncAgeGroupStats extends Command
{
    protected $signature = 'youtube:sync-age-groups';
    protected $description = 'YouTube Analytics API를 통해 채널별 시청자 연령대 통계를 수집합니다.';

    public function handle()
    {
        $this->info('🔄 채널별 시청자 연령대 데이터 수집 시작...');

        $channels = Channel::where('is_active', true)
            ->whereNotNull('youtube_token_id')
            ->with('youtubeToken')
            ->get();

        $startDate = now()->subDays(7)->toDateString(); // 최근 7일 기준
        $endDate = now()->subDays(2)->toDateString();   // 2일 전까지만 (API 데이터 지연 고려)

        $totalSaved = 0;

        foreach ($channels as $channel) {
            $token = $channel->youtubeToken;

            if (!$token) {
                $this->warn("⚠️ 채널 {$channel->id} ({$channel->name}) → 연결된 토큰 없음");
                continue;
            }

            $analytics = new YoutubeAnalyticsService($token);

            $rows = $analytics->getChannelAgeGroupStats($startDate, $endDate);

            if (empty($rows)) {
                $this->warn("⚠️ 채널 {$channel->id} ({$channel->name}) → 연령대 데이터 없음");
                continue;
            }

            foreach ($rows as $row) {
                $ageGroup = $row[0];         // '18-24' 같은 문자열
                $viewerPercentage = $row[1]; // 퍼센트 값

                ChannelAgeGroupStat::updateOrCreate(
                    [
                        'channel_id' => $channel->id,
                        'age_group' => $ageGroup,
                        'collected_at' => Carbon::now()->startOfHour(),
                    ],
                    [
                        'viewer_percentage' => $viewerPercentage,
                    ]
                );

                $totalSaved++;
            }

            $this->info("✅ 채널 {$channel->name} 연령대 데이터 저장 완료!");
        }

        $this->info("🎯 전체 저장 완료: {$totalSaved}개 연령대 데이터");
    }
}
