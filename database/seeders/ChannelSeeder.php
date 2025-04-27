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
        // 유튜브 API 서비스 인스턴스화
        $youtube = new YoutubeApiService();

        // 여러 유튜브 채널 ID를 배열로 지정
        $channelIds = [
            'UCSjfoQp2Ipcj72m_R3VLmmA', // The Story Insight
            'UCyQ_nURfpt9dAVVrcjWEb_A', // Monthly Story
            'UCCBI8VvF_zf_oTuBqBeXZ7Q', // 3분확인 1분사실
            'UCSqfke-1UpoETpwos34FqwA', // AI 이슈 톡톡
        ];

        $userId = 2;

        // 각 채널 ID에 대해 반복 처리
        foreach ($channelIds as $channelId) {
            // 유튜브 채널 정보 가져오기
            $info = $youtube->getChannelInfo($channelId);

            if (!$info) {
                $this->command->error("❌ 유튜브 채널 정보를 가져오지 못했습니다: $channelId");
                continue; // 다음 채널로 넘어감
            }

            // 채널 정보를 데이터베이스에 저장하거나 업데이트
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

            // 성공 메시지 출력
            $this->command->info("✅ 채널 등록 완료: {$info['snippet']['title']}");
        }
    }
}
