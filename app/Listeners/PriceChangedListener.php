<?php

namespace App\Listeners;

use App\Events\PriceChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PriceChangedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PriceChanged $event): void
    {
        Log::info("Price changed for product [{$event->product->id}] in {$event->country->code} to {$event->newPrice}");
        
    }
}
