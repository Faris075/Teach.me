<?php

namespace App\Providers;

use App\Events\GradePublished;
use App\Listeners\SendGradePublishedNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(GradePublished::class, SendGradePublishedNotification::class);
    }
}
