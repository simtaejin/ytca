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
    protected $description = 'YouTube Analytics API를 사용해 영상별 참여도(시청 시간 등)를 동기화합니다.';

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
                // 🎯 Analytics API에서 시청 시간 관련 데이터만 가져옴
                $analyticsData = $analytics->getVideoEngagementMetrics($video->youtube_video_id, $video->published_at);
                if (!$analyticsData) {
                    $this->warn("⚠️ 영상 [{$video->title}] 참여도 데이터 없음.");
                    continue;
                }

                // 🎯 영상 기본 메타데이터에서 views, likes, comments 사용
                $views = max($video->view_count, 1); // 0 나눗셈 방지
                $likes = $video->like_count;
                $comments = $video->comment_count;

                // 🎯 engagement_score 계산 (shares 제외)
                $engagementScore = ($likes + $comments) / $views;

                // 🎯 watch_quality 계산
                $watchQuality = $analyticsData['estimated_minutes_watched'] / $views;

                // 🎯 영상 길이에 따른 기대 시청 시간 (정규화 기준)
                $videoDuration = $this->parseDurationToSeconds($video->duration);
                $expectedWatchTime = max($videoDuration * 0.7, 30); // 최소 30초
                $normalizedWatch = min($analyticsData['average_view_duration'] / $expectedWatchTime, 1.0);

                // 🎯 정규화 및 종합 점수
                $normalizedEngagement = min($engagementScore / 0.05, 1.0);
                $combinedScore = round(($normalizedEngagement * 0.5) + ($normalizedWatch * 0.5), 2);

                // 🎯 등급 판별
                $grade = match (true) {
                    $combinedScore >= 0.8 => 'A',
                    $combinedScore >= 0.6 => 'B',
                    $combinedScore >= 0.4 => 'C',
                    default => 'D',
                };

                // 🎯 저장
                VideoEngagement::updateOrCreate(
                    ['video_id' => $video->id],
                    [
                        'views' => $views,
                        'likes' => $likes,
                        'comments' => $comments,
                        'subscribers_gained' => $analyticsData['subscribers_gained'] ?? 0,
                        'estimated_minutes_watched' => $analyticsData['estimated_minutes_watched'] ?? 0,
                        'average_view_duration' => $analyticsData['average_view_duration'] ?? 0,
                        'average_view_percentage' => $analyticsData['average_view_percentage'] ?? 0,
                        'engagement_score' => round($engagementScore, 4),
                        'watch_quality' => round($watchQuality, 4),
                        'combined_score' => $combinedScore,
                        'video_grade' => $grade,
                    ]
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
