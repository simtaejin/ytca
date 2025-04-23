<?php
namespace App\Console\Commands;

use App\Models\Video;
use App\Models\Channel;
use Carbon\Carbon;
use Illuminate\Console\Command;
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

        if ($channelIdOption) {
            $channel = Channel::where('youtube_channel_id', $channelIdOption)->first();

            if (!$channel) {
                $this->error("❌ 채널을 찾을 수 없습니다: $channelIdOption");
                return;
            }

            $this->syncChannelVideos($channel);
            return;
        }

        $channels = Channel::where('is_active', true)
            ->whereNotNull('youtube_channel_id')
            ->get();

        foreach ($channels as $channel) {
            $this->syncChannelVideos($channel);
        }

        $this->info("🎉 전체 채널 영상 동기화 완료!");
    }

    protected function syncChannelVideos(Channel $channel): void
    {
        $this->info("🔄 채널: {$channel->name} 영상 동기화 중...");

        $accessToken = $channel->youtubeToken?->access_token;

        if ($accessToken) {
            $this->info("🔐 비공개 포함 영상 가져오는 중...");
            $videoDetails = $this->youtube->getMyUploadedVideos($accessToken);
        } else {
            $playlistId = $this->youtube->getUploadsPlaylistId($channel->youtube_channel_id);

            if (!$playlistId) {
                $this->warn("⚠️ 업로드 리스트 ID를 찾을 수 없습니다.");
                return;
            }

            $videoIds = $this->youtube->getVideoIdsFromPlaylist($playlistId);

            if (empty($videoIds)) {
                $this->warn("⚠️ 영상 ID 목록을 가져오지 못했습니다.");
                return;
            }

            $videoDetails = $this->youtube->getVideoDetails($videoIds);
        }

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
