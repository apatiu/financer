<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// บันทึกความมั่งคั่งสุทธิทุกสิ้นเดือน (ต้องมี cron เรียก `php artisan schedule:run` ทุกนาที)
Schedule::command('net-worth:snapshot')->lastDayOfMonth('23:55');
