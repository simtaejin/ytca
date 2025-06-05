<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('video_engagements', function (Blueprint $table) {
            $table->float('engagement_score')->default(0)->after('average_view_percentage'); // 좋아요+댓글+공유 / 조회수
            $table->float('watch_quality')->default(0)->after('engagement_score');           // 시청시간 / 조회수
            $table->string('video_grade', 2)->nullable()->after('watch_quality');            // 등급 (A~F)
        });
    }

    public function down(): void
    {
        Schema::table('video_engagements', function (Blueprint $table) {
            $table->dropColumn(['engagement_score', 'watch_quality', 'video_grade']);
        });
    }
};
