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
        Schema::create('youtube_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('google_id')->unique(); // 구글 유저 ID
            $table->string('email')->nullable();   // 이메일
            $table->string('display_name')->nullable();   // 사람이 식별하기 위한 이름
            $table->string('access_token', 2048);
            $table->string('refresh_token', 2048)->nullable(); // 처음 없을 수도 있음
            $table->dateTime('expires_at');
            $table->string('token_type')->nullable();
            $table->text('scope')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_tokens');
    }
};
