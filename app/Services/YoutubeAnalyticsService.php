<?php

namespace App\Services;

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
    public function getVideoEngagementMetrics(string $videoId): ?array
    {
        $startDate = now()->subDays(7)->toDateString();
        $endDate = now()->toDateString();

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
            'views' => $data[0],
            'likes' => $data[1],
            'comments' => $data[2],
            'shares' => $data[3],
            'subscribers_gained' => $data[4],
            'estimated_minutes_watched' => $data[5],
            'average_view_duration' => $data[6],
            'average_view_percentage' => $data[7],
        ];
    }
}
