<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Video;
use App\Models\VideoStat;
use App\Services\YoutubeApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncVideoStats extends Command
{
    protected $signature = 'youtube:sync-video-stats';
    protected $description = '각 채널별 최근 영상의 통계를 MongoDB에 저장합니다.';
    protected YoutubeApiService $youtube;

    public function __construct(YoutubeApiService $youtube)
    {
        parent::__construct();
        $this->youtube = $youtube;
    }

    public function handle(): void
    {
        $channels = Channel::where('is_active', true)
            ->whereNotNull('youtube_channel_id')
            ->get();

        foreach ($channels as $channel) {
            $this->info("🔄 채널: {$channel->name} 통계 수집 중...");

            $recentVideos = Video::where('channel_id', $channel->id)
                ->where('published_at', '>=', now()->subDays(7))
                ->pluck('youtube_video_id');

            if ($recentVideos->isEmpty()) {
                $this->warn("⚠️ 최근 7일간 영상 없음: {$channel->name}");
                continue;
            }

            $this->info("📹 {$recentVideos->count()}개 영상 조회 중...");
            Log::info("📦 수집 영상 목록", $recentVideos->toArray());

            $videoStats = $this->youtube->getVideoDetails($recentVideos->toArray());

            if (empty($videoStats)) {
                $this->warn("⚠️ API 응답이 비어 있음: {$channel->name}");
                Log::warning("⚠️ {$channel->name} API 응답 없음", [
                    'channel_id' => $channel->youtube_channel_id,
                    'video_ids' => $recentVideos->toArray()
                ]);
                continue;
            }

            $saved = 0;

            foreach ($videoStats as $stat) {
                if (!isset($stat['youtube_video_id'])) {
                    Log::warning("⚠️ 영상 ID 누락", $stat);
                    continue;
                }

                try {
                    VideoStat::create([
                        'video_id'      => $stat['youtube_video_id'],
                        'channel_id'    => $channel->youtube_channel_id,
                        'collected_at'  => now()->format('Y-m-d\TH:i:00\Z'),
                        'view_count'    => (int) $stat['view_count'],
                        'like_count'    => (int) $stat['like_count'],
                        'comment_count' => (int) $stat['comment_count'],
                        'created_at'    => now(),
                    ]);

                    Log::info("✅ 저장 완료", [
                        'video_id' => $stat['youtube_video_id'],
                        'views' => $stat['view_count'],
                        'likes' => $stat['like_count'],
                        'comments' => $stat['comment_count'],
                    ]);

                    $saved++;
                } catch (\Throwable $e) {
                    Log::error("❌ 저장 실패", [
                        'video_id' => $stat['youtube_video_id'] ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->info("✅ {$channel->name} 채널: {$saved}개 영상 통계 저장 완료");
        }

        $this->info("📊 전체 채널 통계 수집 완료!");
    }
}
