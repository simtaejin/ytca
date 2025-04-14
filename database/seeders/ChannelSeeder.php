<?php

// database/seeders/ChannelSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Channel;
use App\Services\YoutubeApiService;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        $youtube = new YoutubeApiService();

        $channelId = '';
//        $channelId = 'UCSjfoQp2Ipcj72m_R3VLmmA'; //The Story Insight
//        $channelId = 'UCyQ_nURfpt9dAVVrcjWEb_A'; //Monthly Story
//        $channelId = 'UCCBI8VvF_zf_oTuBqBeXZ7Q'; //3분확인 1분사실

        $userId = 2;

        $info = $youtube->getChannelInfo($channelId);

        if (!$info) {
            $this->command->error("❌ 유튜브 채널 정보를 가져오지 못했습니다: $channelId");
            return;
        }

        Channel::updateOrCreate(
            ['youtube_channel_id' => $channelId],
            [
                'user_id' => $userId,
                'name' => $info['snippet']['title'],
                'description' => $info['snippet']['description'] ?? null,
                'profile_image_url' => $info['snippet']['thumbnails']['default']['url'] ?? null,
                'subscriber_count' => $info['statistics']['subscriberCount'] ?? 0,
                'video_count' => $info['statistics']['videoCount'] ?? 0,
                'view_count' => $info['statistics']['viewCount'] ?? 0,
                'synced_at' => now(),
                'is_active' => true,
            ]
        );

        $this->command->info("✅ 채널 등록 완료: {$info['snippet']['title']}");
    }
}

