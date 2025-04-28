# 📊 채널 연령대 데이터 조회 및 분석 가이드

## 1. 기본 테이블 조회 (전체 보기)

```sql
SELECT 
    channel_id, 
    age_group, 
    viewer_percentage, 
    collected_at 
FROM 
    channel_age_group_stats 
ORDER BY 
    collected_at DESC, 
    channel_id ASC, 
    age_group ASC;
```

- 최신 수집 데이터부터 조회
- 채널별 + 연령대별 오름차순 정렬

---

## 2. 특정 채널만 조회

```sql
SELECT 
    age_group, 
    viewer_percentage, 
    collected_at 
FROM 
    channel_age_group_stats 
WHERE 
    channel_id = 2 -- 예시: The Story Insight
ORDER BY 
    collected_at DESC, 
    age_group ASC;
```

- 특정 채널 ID로 필터링

---

## 3. 최근 1회 수집된 연령대 데이터만 조회

```sql
SELECT 
    channel_id, 
    age_group, 
    viewer_percentage
FROM 
    channel_age_group_stats
WHERE 
    collected_at = (
        SELECT MAX(collected_at) FROM channel_age_group_stats
    )
ORDER BY 
    channel_id ASC, 
    age_group ASC;
```

- 최신 수집 시각 데이터만 조회

---

## 4. 예시 출력 결과

| 채널 ID | 연령대  | 시청자 비율(%) | 수집 시각 |
|---------|--------|----------------|------------|
| 2 | 18-24 | 12.8 | 2025-04-28 04:00:00 |
| 2 | 25-34 | 45.6 | 2025-04-28 04:00:00 |
| 2 | 35-44 | 25.3 | 2025-04-28 04:00:00 |
| ... | ... | ... | ... |

---

## 5. Laravel Tinker에서 조회

```bash
php artisan tinker
```

```php
ChannelAgeGroupStat::where('channel_id', 2)
    ->orderBy('collected_at', 'desc')
    ->orderBy('age_group')
    ->get()
    ->toArray();
```

- Laravel Tinker로도 데이터 직접 확인 가능

---

# ✅ 요약

| 항목 | 내용 |
|------|------|
| 추천 쿼리 | 최근 데이터 + 채널별 + 연령대별 조회 |
| 추가 활용 | 터미널 리포트, 대시보드 그래프, 장기 트렌드 분석 |