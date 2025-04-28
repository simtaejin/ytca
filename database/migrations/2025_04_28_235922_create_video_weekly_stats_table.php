<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_weekly_stats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('video_id')
                ->constrained()
                ->onDelete('cascade'); // 영상 삭제되면 같이 삭제

            $table->date('start_date')->index()->comment('주간 시작일');
            $table->date('end_date')->index()->comment('주간 종료일');

            $table->unsignedBigInteger('view_count')->default(0)->comment('주간 마지막 누적 조회수');
            $table->unsignedBigInteger('like_count')->default(0)->comment('주간 마지막 누적 좋아요 수');
            $table->unsignedBigInteger('comment_count')->default(0)->comment('주간 마지막 누적 댓글 수');

            $table->bigInteger('view_increase')->default(0)->comment('주간 조회수 증가량');
            $table->bigInteger('like_increase')->default(0)->comment('주간 좋아요 수 증가량');
            $table->bigInteger('comment_increase')->default(0)->comment('주간 댓글 수 증가량');

            $table->timestamps();

            $table->unique(['video_id', 'start_date', 'end_date']); // 주간 단위로 유니크하게
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_weekly_stats');
    }
};
