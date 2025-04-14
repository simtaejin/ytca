<?php

namespace App\Console\Commands;

use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Channel;
use App\Services\YoutubeApiService;

class SyncYoutubeVideos extends Command
{
    protected $signature = 'youtube:sync-videos {--channel=}';
    protected $description = 'YouTube 채널의 영상 목록을 동기화합니다.';

    protected YoutubeApiService $youtube;

    public function __construct(YoutubeApiService $youtube)
    {
        parent::__construct();
        $this->youtube = $youtube;
    }

    public function handle()
    {
        $channelIdOption = $this->option('channel');

        // 특정 채널만 동기화할 경우
        if ($channelIdOption) {
            $channel = Channel::where('youtube_channel_id', $channelIdOption)->first();

            if (!$channel) {
                $this->error("❌ 채널을 찾을 수 없습니다: $channelIdOption");
                return;
            }

            $this->syncChannelVideos($channel);
            return;
        }

        // 전체 채널 동기화 (is_active + youtube_channel_id 존재)
        $channels = Channel::where('is_active', true)
            ->whereNotNull('youtube_channel_id')
            ->get();

        foreach ($channels as $channel) {
            $this->syncChannelVideos($channel);
        }

        $this->info("🎉 영상 동기화 완료!");
    }

    protected function syncChannelVideos(Channel $channel): void
    {
        $this->info("🔄 채널: {$channel->name} 영상 동기화 중...");

        // 1. 업로드 재생목록 ID 가져오기
        $playlistId = $this->youtube->getUploadsPlaylistId($channel->youtube_channel_id);

        if (!$playlistId) {
            $this->warn("⚠️ 업로드 리스트 ID를 찾을 수 없습니다.");
            return;
        }

        // 2. 영상 ID 목록 가져오기
        $videoIds = $this->youtube->getVideoIdsFromPlaylist($playlistId);

        if (empty($videoIds)) {
            $this->warn("⚠️ 영상 ID 목록을 가져오지 못했습니다.");
            return;
        }

        // 3. 영상 상세 정보 가져오기
        $videoDetails = $this->youtube->getVideoDetails($videoIds);

        $saved = 0;

        foreach ($videoDetails as $video) {
            Video::updateOrCreate(
                ['youtube_video_id' => $video['youtube_video_id']],
                [
                    'channel_id' => $channel->id,
                    'title' => $video['title'],
                    'description' => $video['description'],
                    'thumbnail_url' => $video['thumbnail_url'],
                    'published_at' => $video['published_at'] ? Carbon::parse($video['published_at'])->timezone('Asia/Seoul')->format('Y-m-d H:i:s') : null,
                    'duration' => $video['duration'],
                    'view_count' => $video['view_count'],
                    'like_count' => $video['like_count'],
                    'comment_count' => $video['comment_count'],
                    'video_type' => $video['video_type'] ?? 'normal',
                    'privacy_status' => $video['privacy_status'],
                    'synced_at' => now(),
                    'is_active' => true,
                ]
            );

            $saved++;
        }

        $this->info("✅ {$saved}개의 영상이 동기화되었습니다.");
    }
}
