<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('video_engagements', function (Blueprint $table) {
            $table->id();

            // 🔗 videos 테이블과 1:1 관계 (외래키)
            $table->foreignId('video_id')
                ->constrained()
                ->onDelete('cascade'); // 영상이 삭제되면 연관 참여도도 삭제

            // 📊 시청자 참여도 지표
            $table->unsignedBigInteger('views')->default(0);         // 조회수
            $table->unsignedBigInteger('likes')->default(0);         // 좋아요 수
            $table->unsignedBigInteger('comments')->default(0);      // 댓글 수
            $table->unsignedBigInteger('shares')->default(0);        // 공유 수
            $table->unsignedBigInteger('subscribers_gained')->default(0); // 이 영상으로 얻은 구독자 수

            // 🧠 시청 지속 시간 관련 지표
            $table->unsignedBigInteger('estimated_minutes_watched')->default(0); // 총 시청 시간 (분)
            $table->float('average_view_duration')->default(0);      // 평균 시청 시간 (초)
            $table->float('average_view_percentage')->default(0);    // 평균 시청 비율 (%)

            // 📅 수집 시간 (created_at 기준)
            $table->timestamps(); // created_at = 수집 시각으로 사용
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_engagements');
    }
};
