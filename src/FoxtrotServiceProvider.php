<?php

namespace Btph\FoxtrotSdk\Facade;

use Btph\FoxtrotSdk\Foxtrot;
use Illuminate\Support\ServiceProvider;

class FoxtrotServiceProvider extends ServiceProvider
{
    public function boot()
    {
        return $this->app->bind("foxtrot-sdk", fn(mixed $param) => new Foxtrot($param));
    }
}
