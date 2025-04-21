<?php

use App\Models\Channel;
use App\Models\Video;
use App\Models\YoutubeToken;
use App\Services\YoutubeAnalyticsService;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/oauth/google', function () {
    $clientId = env('GOOGLE_CLIENT_ID');
    $redirectUri = urlencode(env('GOOGLE_REDIRECT_URI'));

    $scope = urlencode(implode(' ', [
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/yt-analytics.readonly',
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
        'openid',
    ]));

    $url = "https://accounts.google.com/o/oauth2/v2/auth?"
        ."client_id={$clientId}"
        ."&redirect_uri={$redirectUri}"
        ."&response_type=code"
        ."&scope={$scope}"
        ."&access_type=offline"
        ."&prompt=consent";

    return redirect($url);
});

Route::get('/oauth/google/callback', function () {
    $googleUser = Socialite::driver('google')->stateless()->user();

    // 1. 토큰 저장 or 갱신
    $youtubeToken = YoutubeToken::updateOrCreate(
        ['google_id' => $googleUser->getId()],
        [
            'email' => $googleUser->getEmail() ?? "{$googleUser->getId()}@brandaccount",
            'display_name' => $googleUser->getName(),
            'access_token' => $googleUser->token,
            'refresh_token' => $googleUser->refreshToken ?? null,
            'expires_at' => now()->addSeconds($googleUser->expiresIn),
            'token_type' => 'Bearer',
            'scope' => implode(' ', $googleUser->approvedScopes ?? []),
        ]
    );

    // 2. 연결된 채널 목록 가져오기
    $response = Http::withToken($youtubeToken->access_token)
        ->get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'id,snippet',
            'mine' => 'true',
        ]);

    $channels = $response->json()['items'] ?? [];

    // 3. channels 테이블 업데이트
    foreach ($channels as $item) {
        Channel::where('youtube_channel_id', $item['id'])->update([
            'youtube_token_id' => $youtubeToken->id,
        ]);
    }

    return '✅ 토큰 저장 & 채널 연결 완료!';
});


Route::get('/test-analytics', function () {
    // 테스트할 영상 ID (videos 테이블에 있어야 함)
    $videoId = '0UHQf9djCnU'; // ← 테스트할 YouTube 영상 ID

    // 1. 영상 조회
    $video = Video::where('youtube_video_id', $videoId)->firstOrFail();

    // 2. 채널 & 토큰 연결
    $channel = $video->channel;
    $token = $channel->youtubeToken;

    if (!$token) {
        return response()->json(['error' => '❌ 해당 채널에 연결된 토큰이 없습니다.'], 400);
    }

    // 3. 통계 요청
    $analytics = new YoutubeAnalyticsService($token);
    $startDate = '2025-04-19';
    $endDate = '2025-04-21';

    $stats = $analytics->getVideoStats($videoId, $startDate, $endDate);

    // 4. 결과 확인만
    return response()->json([
        '영상 ID' => $stats[0] ?? null,
        '조회수' => $stats[1] ?? null,
        '시청 시간(분)' => $stats[2] ?? null,
        '평균 시청 시간(초)' => $stats[3] ?? null,
    ]);
});
