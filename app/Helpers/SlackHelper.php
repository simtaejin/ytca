<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackHelper
{
    public static function sendReport(string $message): void
    {
        $webhookUrl = config('services.slack.report_webhook');

        if (!$webhookUrl) {
            Log::warning('Slack webhook URL이 설정되지 않았습니다.');
            return;
        }

        Http::post($webhookUrl, [
            'text' => $message,
        ]);
    }
}
