<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Video;
use App\Models\VideoEngagement;
use App\Services\YoutubeAnalyticsService;
use Illuminate\Console\Command;

class YoutubeSyncEngagement extends Command
{
    protected $signature = 'youtube:sync-engagement {--channel=}';
    protected $description = 'YouTube Analytics API를 사용해 영상별 참여도(좋아요, 댓글, 시청시간 등)를 동기화합니다.';

    public function handle()
    {
        $channelIdOption = $this->option('channel');

        $channels = $channelIdOption
            ? Channel::where('youtube_channel_id', $channelIdOption)->get()
            : Channel::where('is_active', true)->get();

        foreach ($channels as $channel) {
            $this->info("🔄 채널: {$channel->name} 참여도 동기화 중...");

            $accessToken = $channel->youtubeToken?->access_token;

            if (!$accessToken) {
                $this->warn("❌ access_token이 없습니다. 채널: {$channel->name}");
                continue;
            }

            $analytics = new YoutubeAnalyticsService($accessToken);
            $videos = Video::where('channel_id', $channel->id)
                ->where('privacy_status', 'public')
                ->get();

            $updated = 0;

            foreach ($videos as $video) {
                $metrics = $analytics->getVideoEngagementMetrics($video->youtube_video_id, $video->published_at);

                if (!$metrics) {
                    $this->warn("⚠️ 영상 [{$video->title}] 참여도 데이터 없음.");
                    continue;
                }

                // ✅ 평균 시청률 보정
                $videoDuration = $this->parseDurationToSeconds($video->duration);
                $metrics['average_view_percentage'] = $videoDuration > 0
                    ? round(min($metrics['average_view_duration'] / $videoDuration * 100, 100), 1)
                    : 0;

                // ✅ 추가 지표 계산
                $views = $metrics['views'] ?: 1; // 0 나눗셈 방지
                $engagementScore = ($metrics['likes'] + $metrics['comments'] + $metrics['shares']) / $views;
                $watchQuality = $metrics['estimated_minutes_watched'] / $views;
                $totalScore = $engagementScore + $watchQuality;

                // ✅ 등급 판별
                $grade = match (true) {
                    $totalScore >= 1.5 => 'A',
                    $totalScore >= 1.0 => 'B',
                    $totalScore >= 0.5 => 'C',
                    $totalScore >= 0.25 => 'D',
                    default => 'F',
                };

                $metrics['engagement_score'] = round($engagementScore, 4);
                $metrics['watch_quality'] = round($watchQuality, 4);
                $metrics['video_grade'] = $grade;

                VideoEngagement::updateOrCreate(
                    ['video_id' => $video->id],
                    $metrics
                );

                $updated++;
            }

            $this->info("✅ {$updated}개의 영상 참여도 동기화 완료!");
        }

        $this->info("🎉 모든 채널 참여도 동기화 완료.");
    }

    protected function parseDurationToSeconds(?string $duration): int
    {
        if (!$duration) return 0;

        try {
            $interval = new \DateInterval($duration);
            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
