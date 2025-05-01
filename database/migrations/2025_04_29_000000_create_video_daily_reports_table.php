<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('video_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique()->comment('리포트 기준 날짜');
            $table->text('prompt')->comment('GPT에 보낼 프롬프트');
            $table->longText('gpt_answer')->nullable()->comment('GPT의 답변 내용');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_daily_reports');
    }
};