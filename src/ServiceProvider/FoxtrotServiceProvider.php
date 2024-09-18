<?php

namespace Orwallet\FoxtrotSdk\ServiceProvider;

use Orwallet\FoxtrotSdk\Foxtrot;
use Illuminate\Support\ServiceProvider;

class FoxtrotServiceProvider extends ServiceProvider
{
    public function boot()
    {
        return $this->app->bind("foxtrot-sdk", fn() => new Foxtrot());
    }
}
