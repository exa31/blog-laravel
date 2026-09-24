<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }

        // Register SocialiteProviders Apple extension
        \Illuminate\Support\Facades\Event::listen(
            \SocialiteProviders\Manager\SocialiteWasCalled::class,
            [\SocialiteProviders\Apple\AppleExtendSocialite::class, 'handle']
        );

        // Register Apple Server-to-Server notification revocation listener
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\AppleIdRevoked::class,
            \App\Listeners\HandleAppleIdRevocation::class
        );
    }
}
