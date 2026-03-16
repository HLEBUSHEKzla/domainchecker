<?php

namespace App\Providers;

use App\Services\Monitoring\Checkers\ContentChecker;
use App\Services\Monitoring\Checkers\DnsChecker;
use App\Services\Monitoring\Checkers\HttpChecker;
use App\Services\Monitoring\Checkers\RedirectChecker;
use App\Services\Monitoring\Checkers\SearchVisibilityChecker;
use App\Services\Monitoring\Checkers\SslChecker;
use App\Services\Monitoring\MonitoringService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MonitoringService::class, function ($app) {
            return new MonitoringService(
                $app->make(DnsChecker::class),
                $app->make(SslChecker::class),
                $app->make(HttpChecker::class),
                $app->make(RedirectChecker::class),
                $app->make(ContentChecker::class),
                $app->make(SearchVisibilityChecker::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
