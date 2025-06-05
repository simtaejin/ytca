
# 📊 YouTube 영상 등급화 기준 문서 (2025 수정판)

## 🎯 목적
이 문서는 YouTube 영상의 `engagement_score`와 `watch_quality`를 기반으로 영상별 **종합 점수**를 산출하고, 이에 따라 **A~D 등급**을 부여하는 기준을 정의합니다.  
특히, **영상의 전체 길이에 따라 시청 품질 기준을 조정**하여 Shorts 등 다양한 포맷에 유연하게 대응합니다.

---

## 1. 📈 지표 정의

### ✅ engagement_score (참여율)
> `engagement_score = (likes + comments + shares) / views`

- 사용자의 반응을 기반으로 한 참여도 측정  
- 예: 좋아요 10개 + 댓글 3개 + 공유 2회 / 조회수 300 → **0.05 (5%)**

---

### ✅ watch_quality (1인당 시청 시간)
> `watch_quality = estimated_minutes_watched / views`

- 영상 1회당 평균 시청 시간 (분 단위)
- 예: 300분 / 150뷰 → **2.0분**

---

## 2. ⚙️ 계산 로직

### 📌 Step 1: 시청 품질 기준 계산 (영상 길이 기반)

```php
$normalizedEngagement = min($engagement_score / 0.05, 1.0);   // 5% 이상이면 최대치
$expectedWatchTime    = max($videoDuration * 0.7, 30);         // 최소 기대치 30초
$normalizedWatch      = min($average_view_duration / $expectedWatchTime, 1.0);
```

---

### 📌 Step 2: 정규화 (Normalization)

```php
$normalizedEngagement = min($engagement_score / 0.05, 1.0);       // 5% 이상이면 최대 1.0
$normalizedWatch      = min($watch_quality / $watchThreshold, 1.0); // 영상 길이에 따른 기준 분모
```

---

### 📌 Step 3: 종합 점수 계산

```php
$combined_score = round(($normalizedEngagement * 0.5) + ($normalizedWatch * 0.5), 2);
```

- 각 항목을 0.0~1.0 사이로 정규화하여 **동일 가중치(50%)**로 평균

---

### 📌 Step 4: 등급 부여

```php
$combined_grade = match (true) {
    $combined_score >= 0.8 => 'A',
    $combined_score >= 0.6 => 'B',
    $combined_score >= 0.4 => 'C',
    default => 'D',
};
```

---

## 3. 🧠 A 등급이 나오기 위한 조건

다음 중 **하나 이상**을 만족하면 A 등급이 가능함:

| 조건 유형 | 요구 수준 | 설명 |
|-----------|-----------|------|
| 참여율 중심 | `engagement_score >= 0.05` (5%) | 좋아요 + 댓글 + 공유 수가 **총 조회수의 5% 이상** |
| 시청시간 중심 | `watch_quality ≥ 영상 길이에 따른 기준값` | 예: 1분 영상이면 0.7분 이상 시청 |
| 혼합형 | 예: `engagement_score = 0.04`, `watch_quality = 기준치 초과` | 정규화 평균이 0.8 이상이면 A 등급 가능 |

### 예시

- 영상 길이: 180초 (3분 → 기준치 1.5분)
- `engagement_score = 0.03 (정규화 0.6)`
- `watch_quality = 1.5 (정규화 1.0)`
- `combined_score = (0.6 + 1.0) / 2 = 0.8 → A 등급`

---

## 4. 📦 확장 고려사항

- 기준값은 채널별 평균 또는 업로드 주제군에 따라 **동적 조정** 가능
- 향후에는 **시리즈별 평균**, **동일 주제군 대비 상위 퍼센타일** 등으로 보정 가능
- Shorts와 Long-form 영상의 **별도 등급 알고리즘** 운영도 고려 가능

---

## ✅ 결과 저장 컬럼 (video_engagements 테이블)

| 컬럼명             | 설명                          |
|--------------------|-------------------------------|
| `engagement_score` | 참여율 계산 결과              |
| `watch_quality`    | 시청 품질 계산 결과           |
| `combined_score`   | 정규화 및 평균된 종합 점수    |
| `combined_grade`   | A~D 등급 결과                 |
