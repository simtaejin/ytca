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
            $videos = Video::where('channel_id', $channel->id)->get();
            $updated = 0;

            foreach ($videos as $video) {
                $metrics = $analytics->getVideoEngagementMetrics($video->youtube_video_id);

                if (!$metrics) {
                    $this->warn("⚠️ 영상 [{$video->title}] 참여도 데이터 없음.");
                    continue;
                }

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
}
