<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Models\Channel;
use App\Models\Playlist;
use Illuminate\Console\Command;
use App\Services\YoutubeApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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

        $channels = $channelIdOption
            ? Channel::where('youtube_channel_id', $channelIdOption)->get()
            : Channel::where('is_active', true)->whereNotNull('youtube_channel_id')->get();

        foreach ($channels as $channel) {
            $this->syncChannelVideos($channel);
        }

        $this->info("🎉 전체 채널 영상 동기화 완료!");
    }

    protected function syncChannelVideos(Channel $channel): void
    {
        $this->info("🔄 채널: {$channel->name} 영상 동기화 중...");

        $videoDetails = [];

        if ($channel->youtubeToken?->access_token) {
            $this->info("🔐 비공개 포함 영상 가져오는 중...");
            $videoDetails = $this->youtube->getMyUploadedVideos($channel->youtubeToken->access_token);
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

            $this->info("📹 " . count($videoIds) . "개 영상 상세 정보 요청 중...");
            $videoDetails = $this->youtube->getVideoDetails($videoIds);

            if (empty($videoDetails)) {
                $this->warn("⚠️ 영상 상세 정보를 가져오지 못했습니다. (예: 삭제된 영상, API 응답 실패 등)");
                Log::warning('영상 상세 정보 비어있음', [
                    'channel_id' => $channel->id,
                    'channel_name' => $channel->name,
                    'video_ids' => $videoIds,
                ]);
                return;
            }
        }

        $this->info("📚 재생목록 불러오는 중...");
        $playlistMap = [];

        $playlists = $this->youtube->getPlaylistsByChannel($channel->youtube_channel_id);

        foreach ($playlists as $playlist) {
            $playlistId = $playlist['playlist_id'] ?? null;
            if (!$playlistId) continue;

            $playlistModel = Playlist::updateOrCreate(
                ['youtube_playlist_id' => $playlistId],
                [
                    'channel_id' => $channel->id,
                    'title' => $playlist['title'],
                    'description' => $playlist['description'] ?? null,
                    'thumbnail_url' => $playlist['thumbnail'] ?? null,
                ]
            );

            $videoIds = $this->youtube->getPlaylistItems($playlistId);
            foreach ($videoIds as $vid) {
                $playlistMap[$vid][] = $playlistModel->id;
            }
        }

        $saved = 0;

        foreach ($videoDetails as $video) {
            $videoModel = Video::updateOrCreate(
                ['youtube_video_id' => $video['youtube_video_id']],
                [
                    'channel_id' => $channel->id,
                    'title' => $video['title'],
                    'description' => $video['description'],
                    'thumbnail_url' => $video['thumbnail_url'],
                    'published_at' => $video['published_at']
                        ? Carbon::parse($video['published_at'])->timezone('Asia/Seoul')->format('Y-m-d H:i:s')
                        : null,
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

            if (isset($playlistMap[$video['youtube_video_id']])) {
                $videoModel->playlists()->syncWithoutDetaching($playlistMap[$video['youtube_video_id']]);
            }

            $saved++;
        }

        $this->info("✅ {$saved}개의 영상이 동기화되었습니다.");
    }
}
