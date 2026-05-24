<?php

namespace App\Providers;

use App\Events\GradePublished;
use App\Listeners\SendGradePublishedNotification;
use App\View\Compilers\ReadableBladeCompiler;
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
        // Use human-readable filenames in the Blade view cache
        $this->app->extend('blade.compiler', function ($compiler, $app) {
            return new ReadableBladeCompiler(
                $app['files'],
                $app['config']['view.compiled'],
                $app->basePath(),
                $app['config']['view.cache'] ?? true,
                $app['config']['view.compiled_extension'] ?? 'php',
            );
        });

        Event::listen(GradePublished::class, SendGradePublishedNotification::class);
    }
}
