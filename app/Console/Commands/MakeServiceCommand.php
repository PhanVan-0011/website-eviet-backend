<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeServiceCommand extends Command
{
    protected $signature = 'make:service {name}';
    protected $description = 'Tạo file service trong app/Services với nội dung mặc định';

    public function handle()
    {
        $name = $this->argument('name');
        $servicePath = app_path("Services/{$name}.php");

        if (File::exists($servicePath)) {
            $this->error('❌ Service đã tồn tại!');
            return;
        }

        if (!File::isDirectory(app_path('Services'))) {
            File::makeDirectory(app_path('Services'), 0755, true);
        }

        $namespace = "App\Services";
        $classContent = <<<PHP
<?php

namespace {$namespace};

class {$name}
{
    public function __construct()
    {
        // Khởi tạo service
    }

    // Viết các method nghiệp vụ tại đây
}
PHP;

        File::put($servicePath, $classContent);
        $this->info("✅ Đã tạo service: {$name}");
    }
}
