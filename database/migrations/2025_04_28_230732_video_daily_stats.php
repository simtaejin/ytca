<?php

// database/migrations/xxxx_xx_xx_create_video_daily_stats_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('video_daily_stats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('video_id')
                ->constrained()
                ->onDelete('cascade'); // 영상 삭제 시 연쇄 삭제

            $table->date('date')->index(); // 일자 (예: 2025-04-15)

            $table->unsignedBigInteger('view_count')->default(0)
                ->comment('누적 조회수');
            $table->unsignedBigInteger('like_count')->default(0)
                ->comment('누적 좋아요 수');
            $table->unsignedBigInteger('comment_count')->default(0)
                ->comment('누적 댓글 수');

            $table->timestamp('collected_at')->nullable()
                ->comment('수집 시각 (API 호출 기준)');

            $table->timestamps(); // created_at, updated_at

            $table->unique(['video_id', 'date']); // 하루에 하나만 저장
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_daily_stats');
    }
};
