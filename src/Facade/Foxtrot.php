<?php

namespace Orwallet\FoxtrotSdk\Facade;

use Illuminate\Support\Facades\Facade;

class Foxtrot extends Facade
{
    protected static function getFacadeAccessor()
    {
        return "foxtrot-sdk";
    }
}
