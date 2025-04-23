# 🎬 Video Scenes 데이터 입력 및 ngrok 사용 예시

## 📌 목적
- `video_scenes` 테이블에 영상의 내레이션과 이미지 프롬프트를 타임라인에 맞춰 입력합니다.
- 개발 환경에서 외부 접속을 위해 `ngrok`을 사용합니다.

---

## 📥 INSERT 쿼리

```sql
INSERT INTO video_scenes (
  video_id,
  narration_json,
  image_prompt_json,
  note,
  created_at,
  updated_at
)
VALUES (
  1,
  '[
    { "scene_num": "1-1", "narration": "이건 도입부였다." },
    { "scene_num": "1-2", "narration": "장면이 이어졌다." }
  ]',
  '[
    { "scene_num": "1-1", "image_prompt": "A person entering a dark hallway —ar 16:9 cinematic" },
    { "scene_num": "1-2", "image_prompt": "Close-up of shocked face —ar 16:9 dramatic lighting" }
  ]',
  'scene_num을 세부적으로 나누어 타이밍 맞춘 영상',
  NOW(),
  NOW()
);
```

### 🧾 설명
- `video_id`: 영상 ID (여기선 1번)
- `narration_json`: 장면별 내레이션을 JSON 형식으로 저장
- `image_prompt_json`: 장면별 이미지 생성 프롬프트
- `note`: 해당 데이터의 설명
- `created_at`, `updated_at`: 현재 시간 기준 자동 입력

---

## 🌐 외부 접속용 ngrok 사용법

```bash
ngrok http 80 --host-header=ytca-backend.test
```

### 💡 설명
- 로컬 개발 서버(예: `ytca-backend.test`)를 외부에서 접근 가능하도록 `ngrok`으로 터널링합니다.
- 기본적으로 `localhost:80`에 접속하는 것과 동일합니다.

---

## 🗒️ 비고
- `scene_num`을 활용해 장면의 순서를 명확히 표현
- 추후 영상 생성 자동화에 필요한 기본 데이터 구성 포맷
