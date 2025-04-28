<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_monthly_stats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('video_id')
                ->constrained()
                ->onDelete('cascade'); // 영상 삭제되면 함께 삭제

            $table->string('month', 7)->index()->comment('월 (예: 2025-04)');

            $table->unsignedBigInteger('view_count')->default(0)->comment('월 마지막 누적 조회수');
            $table->unsignedBigInteger('like_count')->default(0)->comment('월 마지막 누적 좋아요 수');
            $table->unsignedBigInteger('comment_count')->default(0)->comment('월 마지막 누적 댓글 수');

            $table->bigInteger('view_increase')->default(0)->comment('월간 조회수 증가량');
            $table->bigInteger('like_increase')->default(0)->comment('월간 좋아요 수 증가량');
            $table->bigInteger('comment_increase')->default(0)->comment('월간 댓글 수 증가량');

            $table->timestamps();

            $table->unique(['video_id', 'month']); // 영상별 월마다 하나만
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_monthly_stats');
    }
};
