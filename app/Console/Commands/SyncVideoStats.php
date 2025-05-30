<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\VideoStat;
use App\Services\YoutubeApiService;
use Carbon\Carbon;

class SyncVideoStats extends Command
{
    protected $signature = 'youtube:sync-video-stats';
    protected $description = 'MongoDB에 영상별 실시간 통계 수집 (5분 단위)';

    protected YoutubeApiService $youtube;

    public function __construct(YoutubeApiService $youtube)
    {
        parent::__construct();
        $this->youtube = $youtube;
    }

    public function handle()
    {
        $channels = Channel::where('is_active', true)
            ->whereNotNull('youtube_channel_id')
            ->get();

        $collectedAt = Carbon::now()->timezone('Asia/Seoul')->second(0)->toIso8601String();

        foreach ($channels as $channel) {
            $this->syncChannelStats($channel, $collectedAt);
        }

        $this->info('📊 전체 채널 통계 수집 완료!');
    }

    protected function syncChannelStats(Channel $channel, string $collectedAt): void
    {
        $this->info("🔄 채널: {$channel->name} 통계 수집 중...");

        $playlistId = $this->youtube->getUploadsPlaylistId($channel->youtube_channel_id);
        if (!$playlistId) {
            $this->warn("⚠️ 업로드 플레이리스트 ID를 찾을 수 없습니다.");
            return;
        }

        $videoIds = $this->youtube->getVideoIdsFromPlaylist($playlistId);
        if (empty($videoIds)) {
            $this->warn("⚠️ 영상 ID 목록이 비어 있습니다.");
            return;
        }

        $videoDetails = $this->youtube->getVideoDetails(array_slice($videoIds, 0, 50));

        $saved = 0;
        foreach ($videoDetails as $video) {
            VideoStat::create([
                'video_id' => $video['youtube_video_id'],
                'channel_id' => $channel->youtube_channel_id,
                'collected_at' => $collectedAt,
                'view_count' => (int)$video['view_count'],
                'like_count' => (int)$video['like_count'],
                'comment_count' => (int)$video['comment_count'],
                'created_at' => now(),
            ]);
            $saved++;
        }

        $this->info("✅ {$channel->name} 채널: {$saved}개 영상 통계 저장 완료");
    }
}
