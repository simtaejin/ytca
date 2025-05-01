<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Video;
use App\Models\VideoDailyStat;
use App\Models\VideoDailyReport;
use Carbon\Carbon;

class PrepareDailyReport extends Command
{
    protected $signature = 'youtube:prepare-daily-report';
    protected $description = '전날 기준으로 일일 활동 데이터를 GPT 리포트 형식으로 출력 및 저장합니다.';

    public function handle()
    {
        $targetDate = now()->subDay()->toDateString();
        $previousDate = Carbon::parse($targetDate)->subDay()->toDateString();

        $this->info("🔎 {$targetDate} 기준 데이터 준비 중...");

        $todayStats = VideoDailyStat::with('video.channel')
            ->where('date', $targetDate)
            ->get();

        if ($todayStats->isEmpty()) {
            $this->error("❌ {$targetDate} 데이터가 없습니다.");
            return;
        }

        $yesterdayStats = VideoDailyStat::where('date', $previousDate)->get()->keyBy('video_id');

        $groupedByChannel = $todayStats->groupBy(fn($stat) => $stat->video->channel->name ?? 'Unknown');

        // 📦 프롬프트 문자열 조립
        $lines = [];
        $lines[] = "[{$targetDate} 유튜브 채널 활동 분석 요청]\n";

        foreach ($groupedByChannel as $channelName => $stats) {
            $viewSum = $stats->sum('view_increase');
            $likeSum = $stats->sum('like_increase');
            $commentSum = $stats->sum('comment_increase');

            $lines[] = "🔹 채널: {$channelName}";
            $lines[] = "- 총 조회수 증가: {$viewSum}";
            $lines[] = "- 총 좋아요 수 증가: {$likeSum}";
            $lines[] = "- 총 댓글 수 증가: {$commentSum}";
            $lines[] = "Top 3 영상:";
            $topVideos = $stats->sortByDesc('view_increase')->take(3);
            foreach ($topVideos as $i => $video) {
                $title = $video->video->title ?? 'Untitled';
                $lines[] = ($i + 1) . ". {$title} (+{$video->view_increase} 조회수)";
            }
            $lines[] = "";
        }

        $lines[] = "이 데이터를 기반으로";
        $lines[] = "- 오늘의 특징 요약";
        $lines[] = "- 성장 포인트";
        $lines[] = "- 주목할 변화";
        $lines[] = "- 다음 콘텐츠 전략 제안";
        $lines[] = "을 작성해 주세요.";

        $compiledPrompt = implode("\n", $lines);

        // ✨ GPT 응답 (지금은 미연동)
        $gptAnswer = $this->getGptAnswerMock();

        // 💾 프롬프트 + 응답 DB 저장
        VideoDailyReport::updateOrCreate(
            ['date' => $targetDate],
            [
                'prompt' => $compiledPrompt,
                'gpt_answer' => $gptAnswer
            ]
        );

        $this->info("\n📄 GPT에 복붙할 리포트 프롬프트 ↓↓↓\n");
        foreach ($lines as $line) {
            $this->line($line);
        }

        $this->info("\n✅ 프롬프트 저장 완료 (video_daily_reports.date = {$targetDate})");
    }

    // 추후 GPT 연동을 위한 함수 자리
    protected function getGptAnswerMock(): string
    {
        return '추후연동';
    }
}
