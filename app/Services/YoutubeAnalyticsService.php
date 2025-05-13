<?php

namespace App\Services;

use Carbon\Carbon;
use Google_Client;
use Google_Service_YouTubeAnalytics;
use App\Models\YoutubeToken;

class YoutubeAnalyticsService
{
    protected Google_Service_YouTubeAnalytics $analytics;

    public function __construct(string $accessToken)
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));

        $client->setAccessToken([
            'access_token' => $accessToken,
            'created' => time(),
        ]);

        $this->analytics = new Google_Service_YouTubeAnalytics($client);
    }

    public function getVideoStats(string $videoId, string $startDate, string $endDate): array
    {
        $response = $this->analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'metrics' => 'views,estimatedMinutesWatched,averageViewDuration',
            'dimensions' => 'video',
            'filters' => 'video=='.$videoId,
        ]);

        return $response->getRows()[0] ?? [];
    }

    public function getChannelAgeGroupStats(string $startDate, string $endDate): array
    {
        $response = $this->analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'metrics' => 'viewerPercentage',
            'dimensions' => 'ageGroup',
        ]);

        return $response->getRows() ?? [];
    }

    /**
     * 최근 7일간의 영상 참여도 지표 (views, likes, comments 등)
     */
    public function getVideoEngagementMetrics(string $videoId, string $publishedAt): ?array
    {
        $startDate = Carbon::parse($publishedAt)->toDateString();  // 영상 개시일
        $endDate = now()->toDateString();                          // 오늘

        $response = $this->analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'metrics' => implode(',', [
                'views',
                'likes',
                'comments',
                'shares',
                'subscribersGained',
                'estimatedMinutesWatched',
                'averageViewDuration',
                'averageViewPercentage',
            ]),
            'dimensions' => 'video',
            'filters' => 'video=='.$videoId,
        ]);

        $rows = $response->getRows();

        if (empty($rows)) {
            return null;
        }

        $data = $rows[0];

        return [
            'views' => $data[1] ?? 0,
            'likes' => $data[2] ?? 0,
            'comments' => $data[3] ?? 0,
            'shares' => $data[4] ?? 0,
            'subscribers_gained' => $data[5] ?? 0,
            'estimated_minutes_watched' => $data[6] ?? 0,
            'average_view_duration' => $data[7] ?? 0,
            'average_view_percentage' => isset($data[8]) ? $data[8] : null,
        ];
    }
}
