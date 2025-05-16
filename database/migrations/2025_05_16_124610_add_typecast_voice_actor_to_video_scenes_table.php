<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('video_scenes', function (Blueprint $table) {
            $table->string('typecast_voice_actor')->nullable()->comment('Typecast AI 성우 이름');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_scenes', function (Blueprint $table) {
            $table->dropColumn('typecast_voice_actor');
        });
    }

};
