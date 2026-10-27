<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useTailwind();

        $host = request()->getHost();
        $isLocal = in_array($host, ['localhost', '127.0.0.1']) 
            || str_starts_with($host, '192.168.') 
            || str_starts_with($host, '10.') 
            || str_ends_with($host, '.test');

        if (!$isLocal || request()->server('HTTP_X_FORWARDED_PROTO') === 'https' || request()->isSecure()) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
