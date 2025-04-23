<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class YoutubeApiService
{
    public function getChannelInfo(string $channelId): ?array
    {
        $response = Http::get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'snippet,statistics',
            'id' => $channelId,
            'key' => config('services.youtube.key'),
        ]);

        $json = $response->json();

        if ($response->ok() && isset($json['items']) && count($json['items']) > 0) {
            return $json['items'][0];
        }

        return null;
    }

    // ✅ 채널 ID → 업로드 플레이리스트 ID 조회
    public function getUploadsPlaylistId(string $youtubeChannelId): ?string
    {
        $response = Http::get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'contentDetails',
            'id' => $youtubeChannelId,
            'key' => config('services.youtube.key'),
        ]);

        $json = $response->json();

        if ($response->ok() && isset($json['items'][0]['contentDetails']['relatedPlaylists']['uploads'])) {
            return $json['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
        }

        return null;
    }

    // 다음 단계 메서드 틀도 미리 준비해둘게요 👇
    public function getVideoIdsFromPlaylist(string $playlistId): array
    {
        $videoIds = [];
        $pageToken = null;

        do {
            $response = Http::get('https://www.googleapis.com/youtube/v3/playlistItems', [
                'part' => 'contentDetails',
                'playlistId' => $playlistId,
                'maxResults' => 50,
                'pageToken' => $pageToken,
                'key' => config('services.youtube.key'),
            ]);

            $json = $response->json();

            if (!$response->ok() || !isset($json['items'])) {
                break;
            }

            foreach ($json['items'] as $item) {
                if (isset($item['contentDetails']['videoId'])) {
                    $videoIds[] = $item['contentDetails']['videoId'];
                }
            }

            // 다음 페이지 토큰 설정 (없으면 null로 종료됨)
            $pageToken = $json['nextPageToken'] ?? null;

            if ($pageToken) {
                sleep(1); // 1초 기다림 (1000ms)
                // 또는 usleep(500000); // 0.5초 쉬기
            }

        } while ($pageToken);

        return $videoIds;
    }

    public function getVideoDetails(array $videoIds, ?string $accessToken = null): array
    {
        if (empty($videoIds)) return [];

        $results = [];

        foreach (array_chunk($videoIds, 50) as $chunk) {
            $ids = implode(',', $chunk);

            $request = Http::asJson();

            if ($accessToken) {
                $request = $request->withToken($accessToken);
            } else {
                $request = $request->withHeaders(['key' => config('services.youtube.key')]);
            }

            $response = $request->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'snippet,statistics,contentDetails,status',
                'id' => $ids,
            ]);

            $json = $response->json();

            if (!$response->ok() || !isset($json['items'])) {
                continue;
            }

            foreach ($json['items'] as $item) {
                $duration = $item['contentDetails']['duration'] ?? null;
                $seconds = $this->parseDurationToSeconds($duration);

                $results[] = [
                    'youtube_video_id' => $item['id'],
                    'title' => $item['snippet']['title'] ?? null,
                    'description' => $item['snippet']['description'] ?? null,
                    'thumbnail_url' => $item['snippet']['thumbnails']['default']['url'] ?? null,
                    'published_at' => $item['snippet']['publishedAt'] ?? null,
                    'duration' => $duration,
                    'view_count' => $item['statistics']['viewCount'] ?? 0,
                    'like_count' => $item['statistics']['likeCount'] ?? 0,
                    'comment_count' => $item['statistics']['commentCount'] ?? 0,
                    'privacy_status' => $item['status']['privacyStatus'] ?? 'public',
                    'video_type' => ($seconds > 0 && $seconds <= 60) ? 'shorts' : 'normal',
                ];
            }

            usleep(500000);
        }

        return $results;
    }

    protected function parseDurationToSeconds(?string $duration): int
    {
        if (!$duration) return 0;

        try {
            $interval = new \DateInterval($duration);
            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getMyUploadedVideos(string $accessToken): array
    {
        $videoIds = [];
        $pageToken = null;

        // STEP 1: 내 영상 검색 (비공개 포함)
        do {
            $response = Http::withToken($accessToken)->get('https://www.googleapis.com/youtube/v3/search', [
                'part' => 'id',
                'forMine' => 'true',
                'type' => 'video',
                'maxResults' => 50,
                'pageToken' => $pageToken,
            ]);

            $json = $response->json();

            if (!$response->ok() || !isset($json['items'])) {
                break;
            }

            foreach ($json['items'] as $item) {
                if (isset($item['id']['videoId'])) {
                    $videoIds[] = $item['id']['videoId'];
                }
            }

            $pageToken = $json['nextPageToken'] ?? null;
            usleep(500000);
        } while ($pageToken);

        // STEP 2: 상세 정보 조회
        return $this->getVideoDetails($videoIds, $accessToken);
    }
}

