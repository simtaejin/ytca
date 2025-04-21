<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\YoutubeToken;

class RefreshYoutubeAccessTokens extends Command
{
    protected $signature = 'youtube:refresh-tokens';
    protected $description = '만료된 access_token을 refresh_token으로 갱신';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        $tokens = YoutubeToken::where('expires_at', '<=', now())->get();
        $tokens = YoutubeToken::all();

        foreach ($tokens as $token) {
            if (!$token->refresh_token) {
                $this->warn("❌ [{$token->email}] refresh_token 없음. 갱신 불가.");
                continue;
            }

            $this->info("🔄 [{$token->email}] access_token 갱신 중...");

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $token->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $token->update([
                    'access_token' => $data['access_token'],
                    'expires_at' => Carbon::now()->addSeconds($data['expires_in']),
                ]);

                $this->info("✅ 갱신 완료");
            } else {
                $this->error("❌ 실패: ".$response->body());
            }
        }
    }
}
