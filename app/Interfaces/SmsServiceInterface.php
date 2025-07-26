<?php
namespace App\Interfaces;

interface SmsServiceInterface
{
    /**
     * Gửi tin nhắn SMS.
     *
     * @param string $phoneNumber Số điện thoại người nhận.
     * @param string $message Nội dung tin nhắn.
     * @return bool Trả về true nếu gửi thành công, false nếu thất bại.
     */
    public function send(string $phoneNumber, string $message): bool;
}