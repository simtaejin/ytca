<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('youtube_channel_id')->unique(); // 실제 유튜브 채널 ID
            $table->string('name'); // 채널 이름
            $table->string('profile_image_url')->nullable(); // 썸네일
            $table->text('description')->nullable(); // 채널 소개
            $table->integer('subscriber_count')->default(0); // 구독자 수
            $table->integer('video_count')->default(0); // 전체 영상 수
            $table->integer('view_count')->default(0); // 총 조회수

            $table->timestamp('synced_at')->nullable(); // 유튜브 API로 마지막 동기화한 시간
            $table->boolean('is_active')->default(true);    // 사용자가 관리 중인 채널 여부
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
