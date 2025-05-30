<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Mongo\VideoStat;
use App\Models\Video;
use App\Services\YoutubeApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncVideoStats extends Command
{
    protected $signature = 'youtube:sync-video-stats';
    protected $description = '최근 영상의 통계 데이터를 MongoDB에 저장합니다.';

    protected YoutubeApiService $youtube;

    public function __construct(YoutubeApiService $youtube)
    {
        parent::__construct();
        $this->youtube = $youtube;
    }

    public function handle()
    {
        $channels = Channel::where('is_active', true)->get();

        foreach ($channels as $channel) {
            $this->syncStatsForChannel($channel);
        }

        $this->info("📊 전체 채널 통계 수집 완료!");
    }

    protected function syncStatsForChannel(Channel $channel): void
    {
        $this->info("🔄 채널: {$channel->name} 통계 수집 중...");

        // 최근 7일 이내 게시된 영상들만 조회
        $videos = $channel->videos()
            ->where('published_at', '>=', now()->subDays(7))
            ->get();

        if ($videos->isEmpty()) {
            $this->warn("ℹ️ 최근 7일 이내 영상이 없습니다.");
            return;
        }

        $this->info("📹 {$videos->count()}개 영상 조회 중...");

        $videoIds = $videos->pluck('youtube_video_id')->toArray();

        $statsList = $this->youtube->getVideoDetails($videoIds);

        if (empty($statsList)) {
            $this->warn("⚠️ API 응답이 비어 있음: {$channel->name}");
            return;
        }

        $count = 0;

        foreach ($statsList as $stats) {
            $video = $videos->firstWhere('youtube_video_id', $stats['youtube_video_id']);

            if (!$video) continue;

            try {
                VideoStat::create([
                    'video_id' => $video->id,
                    'view_count' => $stats['view_count'],
                    'like_count' => $stats['like_count'],
                    'comment_count' => $stats['comment_count'],
                    'collected_at' => now(),
                ]);
                $count++;
            } catch (\Exception $e) {
                Log::error("📛 MongoDB 저장 실패: {$video->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("✅ {$channel->name} 채널: {$count}개 영상 통계 저장 완료");
    }
}
