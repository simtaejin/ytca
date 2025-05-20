<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VideoDailyStat;
use App\Models\VideoDailyReport;
use Carbon\Carbon;

class PrepareDailyReport extends Command
{
    protected $signature = 'youtube:prepare-daily-report';
    protected $description = '전날 기준으로 일일 활동 데이터를 GPT 리포트 형식으로 출력 및 저장합니다.';

    // 📌 등급 기준 & 출력 라벨 통합 설정
    protected array $gradeConfig = [
        ['min' => 4000, 'key' => 'S', 'label' => '🔥 대박 영상 (S급)'],
        ['min' => 1000, 'key' => 'A', 'label' => '🎯 중박 영상 (A급)'],
        ['min' => 500,  'key' => 'B', 'label' => '✅ 소박 영상 (B급)'],
        ['min' => 1,    'key' => 'C', 'label' => '💤 쪽박 영상 (C급)'],
        ['min' => 0,    'key' => 'D', 'label' => '☠️ 0조회 영상 (D급)'],
    ];

    public function handle()
    {
        $targetDate = now()->subDay()->toDateString();

        $this->info("🔎 {$targetDate} 기준 데이터 준비 중...");

        $todayStats = VideoDailyStat::with('video.channel')
            ->where('date', $targetDate)
            ->get();

        if ($todayStats->isEmpty()) {
            $this->error("❌ {$targetDate} 데이터가 없습니다.");
            return;
        }

        $lines = [];
        $lines[] = "[{$targetDate} 유튜브 채널 활동 분석 요청]\n";

        // 📦 채널별 그룹
        $grouped = $todayStats->groupBy(fn($s) => $s->video->channel->name ?? 'Unknown');

        foreach ($grouped as $channelName => $stats) {
            $lines[] = "🔹 채널: {$channelName}";
            $lines[] = "- 총 조회수 증가: " . $stats->sum('view_increase');
            $lines[] = "- 총 좋아요 수 증가: " . $stats->sum('like_increase');
            $lines[] = "- 총 댓글 수 증가: " . $stats->sum('comment_increase');

            // Top 3 영상
            $lines[] = "Top 3 영상:";
            $topVideos = $stats->sortByDesc('view_increase')->take(3);
            foreach ($topVideos as $i => $s) {
                $title = $s->video->title ?? '제목 없음';
                $lines[] = ($i + 1) . ". {$title} (+{$s->view_increase} 조회수)";
            }
            $lines[] = "";

            // 등급별 그룹 초기화
            $grades = [];
            foreach ($this->gradeConfig as $config) {
                $grades[$config['key']] = [];
            }

            // 등급별 분류
            foreach ($stats as $s) {
                $views = $s->view_count;
                foreach ($this->gradeConfig as $config) {
                    if ($views >= $config['min']) {
                        $grades[$config['key']][] = $s;
                        break;
                    }
                }
            }

            // 등급별 출력
            foreach ($this->gradeConfig as $config) {
                $group = $grades[$config['key']] ?? [];
                if (!empty($group)) {
                    $lines[] = $config['label'];
                    foreach ($group as $s) {
                        $title = $s->video->title ?? '제목 없음';
                        $views = $s->view_count;
                        $lines[] = "- “{$title}” → {$views}회";
                    }
                    $lines[] = "";
                }
            }
        }

        $lines[] = "이 데이터를 기반으로";
        $lines[] = "- 오늘의 특징 요약";
        $lines[] = "- 성장 포인트";
        $lines[] = "- 주목할 변화";
        $lines[] = "- 다음 콘텐츠 전략 제안";
        $lines[] = "을 작성해 주세요.";

        // 📌 등급 기준 설명 추가
        $lines[] = "\n---";
        $lines[] = "📊 영상 등급 기준 (조회수 기준)";
        foreach ($this->gradeConfig as $config) {
            $lines[] = "- {$config['label']}: 조회수 {$config['min']} 이상";
        }

        $compiledPrompt = implode("\n", $lines);

        // ✨ GPT 응답 저장 (현재는 mock)
        $gptAnswer = $this->getGptAnswerMock();

        VideoDailyReport::updateOrCreate(
            ['date' => $targetDate],
            [
                'prompt' => $compiledPrompt,
                'gpt_answer' => $gptAnswer,
            ]
        );

        // 📄 출력
        $this->info("\n📄 GPT 프롬프트 ↓↓↓\n");
        foreach ($lines as $line) {
            $this->line($line);
        }

        $this->info("\n✅ 프롬프트 저장 완료 (video_daily_reports.date = {$targetDate})");
    }

    // ✅ 등급 판별 함수 (label 추출용)
    protected function classifyVideoGrade(int $views): string
    {
        foreach ($this->gradeConfig as $config) {
            if ($views >= $config['min']) {
                return $config['label'];
            }
        }

        return '등급 없음';
    }

    protected function getGptAnswerMock(): string
    {
        return 'GPT 응답은 추후 연동 예정';
    }
}
