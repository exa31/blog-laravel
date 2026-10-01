<?php

namespace App\Providers;

use App\Events\AppleIdRevoked;
use App\Filament\Responses\FilamentLogoutResponse;
use App\Listeners\HandleAppleIdRevocation;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            LogoutResponse::class,
            FilamentLogoutResponse::class
        );
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
        Event::listen(
            SocialiteWasCalled::class,
            [AppleExtendSocialite::class, 'handle']
        );

        // Register Apple Server-to-Server notification revocation listener
        Event::listen(
            AppleIdRevoked::class,
            HandleAppleIdRevocation::class
        );
    }
}
