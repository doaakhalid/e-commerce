<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\PriceChanged;
use App\Listeners\PriceChangedListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
        SendEmailVerificationNotification::class,
        PriceChanged::class => [
        PriceChangedListener::class,
        ],
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
        Event::listen(
            PriceChanged::class,
            [PriceChangedListener::class, 'handle']
        );
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
