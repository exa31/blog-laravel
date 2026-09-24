<?php

use App\Jobs\GenerateSyntheticActivityJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Automatically generate background engagement periodically
Schedule::job(new GenerateSyntheticActivityJob(postsCount: 1, likesCount: 4, commentsCount: 2, repliesCount: 2))
    ->everyThirtyMinutes()
    ->withoutOverlapping();
