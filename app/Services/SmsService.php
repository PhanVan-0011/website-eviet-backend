<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $client;
    protected $apiKey;
    protected $secretKey;
    protected $brandname;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.esms.api_key');
        $this->secretKey = config('services.esms.secret_key');
        $this->brandname = config('services.esms.brandname');
    }

    public function sendOtp($phone, $code)
    {
        $url = 'http://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_post_json/';
        $message = "Ma OTP cua ban la: $code. Vui long khong chia se ma nay. Het han sau 5 phut.";

        $data = [
            'ApiKey' => $this->apiKey,
            'SecretKey' => $this->secretKey,
            'Brandname' => $this->brandname,
            'Phone' => $phone,
            'Content' => $message,
            'SmsType' => 2, // Tin nhắn chăm sóc khách hàng
            'Sandbox' => 1,
        ];

        try {
            $response = $this->client->post($url, [
                'json' => $data,
            ]);

            $result = json_decode($response->getBody(), true);

            if ($result['CodeResult'] == 100) {
                return true; // Gửi thành công
            } else {
                Log::error('eSMS Error: ' . json_encode($result));
                return false;
            }
        } catch (\Exception $e) {
            Log::error('eSMS Exception: ' . $e->getMessage());
            return false;
        }
    }
}