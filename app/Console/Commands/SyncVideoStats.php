<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Video;
use Illuminate\Console\Command;
use App\Services\YoutubeApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncVideoStats extends Command
{
    protected $signature = 'youtube:sync-video-stats {--channel=}';
    protected $description = 'YouTube 영상의 통계 정보를 동기화합니다.';
    protected YoutubeApiService $youtube;

    public function __construct(YoutubeApiService $youtube)
    {
        parent::__construct();
        $this->youtube = $youtube;
    }

    public function handle()
    {
        $channelIdOption = $this->option('channel');

        $channels = $channelIdOption
            ? Channel::where('youtube_channel_id', $channelIdOption)->get()
            : Channel::where('is_active', true)->whereNotNull('youtube_channel_id')->get();

        foreach ($channels as $channel) {
            $this->syncStatsForChannel($channel);
        }

        $this->info("📊 전체 채널 통계 수집 완료!");
    }

    protected function syncStatsForChannel(Channel $channel): void
    {
        $this->info("🔄 채널: {$channel->name} 통계 수집 중...");

        $videos = $channel->videos()
            ->where('published_at', '>=', now()->subDays(7))
            ->pluck('youtube_video_id', 'id');

        $this->info("📹 {$videos->count()}개 영상 조회 중...");

        if ($videos->isEmpty()) {
            $this->warn("⚠️ 최근 7일간 게시된 영상이 없습니다.");
            return;
        }

        $details = $this->youtube->getVideoDetails($videos->values()->all());

        if (empty($details)) {
            $this->warn("⚠️ API 응답이 비어 있음: {$channel->name}");
            Log::warning('영상 통계 API 응답 없음', [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'video_ids' => $videos->values()->all(),
            ]);
            return;
        }

        $inserted = 0;

        foreach ($details as $videoDetail) {
            $videoId = $videoDetail['youtube_video_id'];
            $videoDbId = $videos->search($videoId);

            if (!$videoDbId) continue;

            DB::table('video_stats')->insert([
                'video_id' => $videoDbId,
                'view_count' => $videoDetail['view_count'],
                'like_count' => $videoDetail['like_count'],
                'comment_count' => $videoDetail['comment_count'],
                'collected_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $inserted++;
        }

        $this->info("✅ {$channel->name} 채널: {$inserted}개 영상 통계 저장 완료");
    }
}
