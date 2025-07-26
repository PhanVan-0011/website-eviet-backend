<?php
namespace App\Services\Sms;

use App\Interfaces\SmsServiceInterface;
use Illuminate\Support\Facades\Log;

class LogSmsService implements SmsServiceInterface
{
    public function send(string $phoneNumber, string $message): bool
    {
        Log::info("==== SMS to {$phoneNumber} ====");
        Log::info($message);
        Log::info("==============================");
        return true; // Luôn giả định là gửi thành công
    }
}