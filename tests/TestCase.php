<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Test;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use ShibuyaKosuke\LaravelCrudCommand\Providers\CommandServiceProvider;

/**
 * Class TestCase
 * @package ShibuyaKosuke\LaravelCrudCommand\Test
 */
abstract class TestCase extends OrchestraTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [CommandServiceProvider::class];
    }
}
