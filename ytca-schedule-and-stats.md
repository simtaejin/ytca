# YTCA 스케줄 & 통계 시스템 설계 기록

## ✅ 전체 시스템 목표
- YouTube API를 통해 채널 영상 정보 수집
- videos 테이블 최신화
- 일간, 주간, 월간 스냅샷 저장
- 추후 GPT 요약 리포트 확장 가능성 고려

---

## ✅ 주요 테이블 설계

### 1. videos
- YouTube 영상 정보 저장
- 최신 메타데이터(조회수, 좋아요, 댓글 수 등) 갱신

### 2. video_daily_stats
- 매일 videos의 스냅샷 기록
- view_count, like_count, comment_count
- view_increase, like_increase, comment_increase 추가

### 3. video_weekly_stats
- 7일간의 증가량 합산 + 마지막 날 누적값 저장
- start_date, end_date 단위로 관리

### 4. video_monthly_stats
- 30일간의 증가량 합산 + 마지막 날 누적값 저장
- month (YYYY-MM) 단위로 관리

---

## ✅ Artisan 커맨드 설계

| 커맨드 | 설명 |
|--------|------|
| `youtube:sync-videos` | 채널 영상 목록 및 메타데이터 최신화 |
| `youtube:sync-daily-stats` | 매일 videos 테이블 기준 일간 스냅샷 저장 |
| `youtube:sync-weekly-stats` | 7일치 daily stats를 기반으로 주간 통계 저장 |
| `youtube:sync-monthly-stats` | 30일치 daily stats를 기반으로 월간 통계 저장 |

---

## ✅ 스케줄 등록 (routes/console.php)

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('youtube:sync-videos')
    ->twiceDaily(2, 14)
    ->withoutOverlapping();

Schedule::command('youtube:sync-daily-stats')
    ->dailyAt('03:00')
    ->withoutOverlapping();

Schedule::command('youtube:sync-weekly-stats')
    ->weeklyOn(1, '04:00')
    ->withoutOverlapping();

Schedule::command('youtube:sync-monthly-stats')
    ->monthlyOn(1, '04:15')
    ->withoutOverlapping();
```

---

## ✅ 크론탭 설정

```bash
* * * * * cd /your-project-path && php artisan schedule:run >> /dev/null 2>&1
```

- 매 1분마다 schedule:run 호출
- Laravel이 알아서 스케줄 실행

---

## ✅ 전체 스케줄 흐름

| 주기 | 시간           | 커맨드 |
|------|--------------|--------|
| 매일 | 02:00, 14:00 | youtube:sync-videos |
| 매일 | 03:00        | youtube:sync-daily-stats |
| 매주 월요일 | 04:00        | youtube:sync-weekly-stats |
| 매월 1일 | 04:15        | youtube:sync-monthly-stats |

---

## ✅ 주요 흐름 요약

```plaintext
1. youtube:sync-videos → videos 테이블 최신화
2. youtube:sync-daily-stats → video_daily_stats에 일일 스냅샷 저장
3. youtube:sync-weekly-stats → video_weekly_stats에 주간 데이터 저장
4. youtube:sync-monthly-stats → video_monthly_stats에 월간 데이터 저장
```

---

## ✅ 현재 시스템 상태

| 항목 | 상태 |
|------|------|
| videos 관리 | ✅ 완료 |
| video_daily_stats 저장 | ✅ 완료 |
| video_weekly_stats 저장 | ✅ 완료 |
| video_monthly_stats 저장 | ✅ 완료 |
| 스케줄러 자동화 | ✅ 완료 |
| GPT 리포트 기능 | ⏳ (추후 확장 가능)

---

# 🎯 메모

- daily_stats 증가량 기록 (`view_increase`, `like_increase`, `comment_increase`) 추가
- 주간/월간은 daily_stats를 기반으로 SUM 및 최종 누적값 계산
- 이후 GPT 요약 리포트 확장 예정 (weekly/monthly 데이터 활용)
