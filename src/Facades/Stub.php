<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Stub
 * @package ShibuyaKosuke\LaravelCrudCommand\Facades
 *
 * @method static string|null findStub(string $name)
 * @method static array getCustomStubs()
 * @method static array getDefaultStubs()
 * @method static array getPluginStubs()
 * @method static array getPublishedStubs()
 * @method static array mergedStubs()
 * @method static void setCustomStubDir(string $customStubDir = null)
 *
 * @mixin \ShibuyaKosuke\LaravelCrudCommand\Services\StubService
 */
class Stub extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'stub-path';
    }
}
