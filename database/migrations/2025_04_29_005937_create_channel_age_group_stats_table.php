<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_age_group_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->onDelete('cascade'); // 채널 ID
            $table->string('age_group', 10)->comment('연령대 구간 (예: 18-24)');
            $table->float('viewer_percentage')->comment('해당 연령대 시청자 비율 (%)');
            $table->timestamp('collected_at')->comment('수집 시각');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_age_group_stats');
    }
};
