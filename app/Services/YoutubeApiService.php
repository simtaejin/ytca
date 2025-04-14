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

}

