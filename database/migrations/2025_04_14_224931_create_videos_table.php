<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('channel_id')->constrained()->onDelete('cascade');

            $table->string('youtube_video_id')->unique(); // YouTube 영상 고유 ID
            $table->string('title');
            $table->text('description')->nullable(); // 영상 제목
            $table->string('thumbnail_url')->nullable(); //영상 썸네일 URL

            $table->dateTime('published_at')->nullable(); // 유튜브 게시 시각
            $table->string('duration')->nullable();       // 영상 길이 (ISO8601 형식 예: PT5M30S)

            $table->unsignedBigInteger('view_count')->default(0);    // 조회수
            $table->unsignedBigInteger('like_count')->default(0);    // 좋아요 수
            $table->unsignedBigInteger('comment_count')->default(0); // 댓글 수

            $table->enum('video_type', ['shorts', 'normal'])->default('normal'); // 쇼츠/일반 영상 구분
            $table->enum('privacy_status', ['public', 'unlisted', 'private'])->default('public'); // 공개 상태

            $table->timestamp('synced_at')->nullable(); // API로 마지막 동기화 시각
            $table->boolean('is_active')->default(true); // 유효한 영상인지 여부

            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
