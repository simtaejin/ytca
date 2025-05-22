<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Models\Playlist;
use Illuminate\Console\Command;
use App\Models\Channel;
use App\Services\YoutubeApiService;

class SyncYoutubeChannels extends Command
{
    protected $signature = 'youtube:sync-channels {--channel=}';
    protected $description = 'YouTube 채널 정보를 API를 통해 동기화합니다.';

    protected YoutubeApiService $youtube;

    public function __construct(YoutubeApiService $youtube)
    {
        parent::__construct();
        $this->youtube = $youtube;
    }

    public function handle()
    {
        $channelId = $this->option('channel');

        if ($channelId) {
            $channel = Channel::where('youtube_channel_id', $channelId)->first();

            if (!$channel) {
                $this->error("❌ 채널을 찾을 수 없습니다: $channelId");
                return;
            }

            $this->syncChannel($channel);
            return;
        }

        $this->info("🔄 전체 활성 채널 동기화 시작...");

        $channels = Channel::where('is_active', true)
            ->whereNotNull('youtube_channel_id')
            ->get();

        foreach ($channels as $channel) {
            $this->syncChannel($channel);
        }

        $this->info("✅ 전체 채널 동기화 완료!");
    }

    protected function syncChannel(Channel $channel): void
    {
        $this->info("📡 채널 동기화 중: {$channel->youtube_channel_id}");

        $info = $this->youtube->getChannelInfo($channel->youtube_channel_id);

        if (!$info) {
            $this->warn("⚠️ 동기화 실패: {$channel->youtube_channel_id}");
            return;
        }

        $channel->update([
            'name' => $info['snippet']['title'],
            'description' => $info['snippet']['description'],
            'profile_image_url' => $info['snippet']['thumbnails']['default']['url'] ?? null,
            'subscriber_count' => $info['statistics']['subscriberCount'] ?? 0,
            'video_count' => $info['statistics']['videoCount'] ?? 0,
            'view_count' => $info['statistics']['viewCount'] ?? 0,
            'synced_at' => now(),
        ]);

        $this->info("✅ 동기화 완료: {$channel->name}");

        // ✅ 재생목록 동기화 추가
        $this->info("📚 재생목록 동기화 중...");

        $playlists = $this->youtube->getPlaylistsByChannel($channel->youtube_channel_id);

        foreach ($playlists as $playlist) {
            $playlistModel = Playlist::updateOrCreate(
                ['youtube_playlist_id' => $playlist['playlist_id']],
                [
                    'channel_id' => $channel->id,
                    'title' => $playlist['title'],
                    'description' => $playlist['description'] ?? null,
                    'thumbnail_url' => $playlist['thumbnail'] ?? null,
                ]
            );

            $videoIds = $this->youtube->getPlaylistItems($playlist['playlist_id']);

            foreach ($videoIds as $youtubeVideoId) {
                $video = Video::where('youtube_video_id', $youtubeVideoId)->first();
                if ($video) {
                    $playlistModel->videos()->syncWithoutDetaching([$video->id]);
                }
            }
        }

        $this->info("✅ 재생목록 동기화 완료: {$channel->name}");
    }
}
