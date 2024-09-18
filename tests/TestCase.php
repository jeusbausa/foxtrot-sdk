<?php

namespace Orwallet\FoxtrotSdk\Tests;

use Orwallet\FoxtrotSdk\ServiceProvider\FoxtrotServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            FoxtrotServiceProvider::class
        ];
    }
}
