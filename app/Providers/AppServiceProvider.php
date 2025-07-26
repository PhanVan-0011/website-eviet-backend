<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Interfaces\SmsServiceInterface;
use App\Services\Sms\LogSmsService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton(ImageManager::class, function () {
            return new ImageManager(new Driver());
        });
            // Thêm dòng này
        $this->app->bind(SmsServiceInterface::class, LogSmsService::class);

        // SAU NÀY, KHI CÓ NHÀ CUNG CẤP, THAY ĐỔI 1 DÒNG NÀY:
        // $this->app->bind(SmsServiceInterface::class, VietGuysSmsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
