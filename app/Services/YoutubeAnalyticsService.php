<?php

namespace App\Services;

use Google_Client;
use Google_Service_YouTubeAnalytics;
use App\Models\YoutubeToken;

class YoutubeAnalyticsService
{
    protected Google_Service_YouTubeAnalytics $analytics;

    public function __construct(YoutubeToken $token)
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setAccessToken([
            'access_token' => $token->access_token,
            'expires_in' => $token->expires_at->diffInSeconds(now()),
            'refresh_token' => $token->refresh_token,
            'created' => $token->updated_at->timestamp,
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
            'filters' => 'video==' . $videoId,
        ]);

        return $response->getRows()[0] ?? [];
    }
}
