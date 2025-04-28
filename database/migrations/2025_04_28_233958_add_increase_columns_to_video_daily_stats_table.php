<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('video_daily_stats', function (Blueprint $table) {
            $table->bigInteger('view_increase')->default(0)
                ->after('view_count')
                ->comment('전일 대비 조회수 증가량');

            $table->bigInteger('like_increase')->default(0)
                ->after('like_count')
                ->comment('전일 대비 좋아요 수 증가량');

            $table->bigInteger('comment_increase')->default(0)
                ->after('comment_count')
                ->comment('전일 대비 댓글 수 증가량');
        });
    }

    public function down(): void
    {
        Schema::table('video_daily_stats', function (Blueprint $table) {
            $table->dropColumn(['view_increase', 'like_increase', 'comment_increase']);
        });
    }
};
