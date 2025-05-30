<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        if ($response->ok() && isset($json['items'][0])) {
            return $json['items'][0];
        }

        Log::warning('채널 정보 조회 실패', [
            'channel_id' => $channelId,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    public function getUploadsPlaylistId(string $channelId): ?string
    {
        $response = Http::get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'contentDetails',
            'id' => $channelId,
            'key' => config('services.youtube.key'),
        ]);

        $json = $response->json();

        return $json['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? null;
    }

    public function getVideoIdsFromPlaylist(string $playlistId): array
    {
        return $this->collectVideoIdsFromPaginatedApi('https://www.googleapis.com/youtube/v3/playlistItems', [
            'part' => 'contentDetails',
            'playlistId' => $playlistId,
            'maxResults' => 50,
        ], 'contentDetails.videoId');
    }

    public function getPlaylistItems(string $playlistId): array
    {
        return $this->collectVideoIdsFromPaginatedApi('https://www.googleapis.com/youtube/v3/playlistItems', [
            'part' => 'contentDetails',
            'playlistId' => $playlistId,
            'maxResults' => 50,
        ], 'contentDetails.videoId');
    }

    public function getVideoDetails(array $videoIds, ?string $accessToken = null): array
    {
        if (empty($videoIds)) return [];

        $results = [];

        foreach (array_chunk($videoIds, 50) as $chunk) {
            $ids = implode(',', $chunk);

            $params = [
                'part' => 'snippet,statistics,contentDetails,status',
                'id' => $ids,
            ];

            if ($accessToken) {
                $response = Http::withToken($accessToken)
                    ->get('https://www.googleapis.com/youtube/v3/videos', $params);
            } else {
                $params['key'] = config('services.youtube.key');
                $response = Http::get('https://www.googleapis.com/youtube/v3/videos', $params);
            }

            $json = $response->json();

            if (!$response->ok() || !isset($json['items'])) {
                Log::warning('YouTube API 응답 오류 (getVideoDetails)', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'video_ids' => $ids,
                ]);
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

    public function getMyUploadedVideos(string $accessToken): array
    {
        $videoIds = [];
        $pageToken = null;

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

        return $this->getVideoDetails($videoIds, $accessToken);
    }

    public function getPlaylistsByChannel(string $channelId): array
    {
        $playlists = [];
        $pageToken = null;

        do {
            $response = Http::get('https://www.googleapis.com/youtube/v3/playlists', [
                'part' => 'id,snippet',
                'channelId' => $channelId,
                'maxResults' => 50,
                'pageToken' => $pageToken,
                'key' => config('services.youtube.key'),
            ]);

            $json = $response->json();

            if (!$response->ok() || !isset($json['items'])) {
                break;
            }

            foreach ($json['items'] as $item) {
                $playlists[] = [
                    'playlist_id' => $item['id'],
                    'title' => $item['snippet']['title'] ?? null,
                    'description' => $item['snippet']['description'] ?? null,
                    'thumbnail' => $item['snippet']['thumbnails']['default']['url'] ?? null,
                ];
            }

            $pageToken = $json['nextPageToken'] ?? null;
            usleep(300000);
        } while ($pageToken);

        return $playlists;
    }

    protected function parseDurationToSeconds(?string $duration): int
    {
        if (!$duration) return 0;

        try {
            $interval = new \DateInterval($duration);
            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (\Exception $e) {
            Log::warning('영상 시간 파싱 실패', [
                'duration' => $duration,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    protected function collectVideoIdsFromPaginatedApi(string $url, array $params, string $dotPath): array
    {
        $videoIds = [];
        $pageToken = null;

        do {
            $params['pageToken'] = $pageToken;
            $params['key'] = config('services.youtube.key');

            $response = Http::get($url, $params);
            $json = $response->json();

            if (!$response->ok() || !isset($json['items'])) {
                break;
            }

            foreach ($json['items'] as $item) {
                $value = data_get($item, $dotPath);
                if ($value) $videoIds[] = $value;
            }

            $pageToken = $json['nextPageToken'] ?? null;
            usleep(300000);
        } while ($pageToken);

        return $videoIds;
    }
}
