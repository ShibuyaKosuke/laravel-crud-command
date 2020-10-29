<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;

/**
 * Class StubService
 * @package ShibuyaKosuke\LaravelCrudCommand\Services
 */
class StubService
{
    /**
     * @var Application
     */
    private Application $app;

    /**
     * Custom stub directory
     * @var string|null
     */
    private ?string $customStubDir = null;

    private string $pluginStubDir = __DIR__ . '/../Console/stubs';

    /**
     * Stub directories in laravel framework
     * @var array|string[]
     */
    protected array $defaultStubDirectories = [
        '/vendor/laravel/framework/src/Illuminate/Foundation/Console/stubs',
        '/vendor/laravel/framework/src/Illuminate/Database/Console/Factories/stubs',
        '/vendor/laravel/framework/src/Illuminate/Database/Console/Seeds/stubs',
        '/vendor/laravel/framework/src/Illuminate/Database/Migrations/stubs',
        '/vendor/laravel/framework/src/Illuminate/Routing/Console/stubs',
    ];

    /**
     * StubService constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param $customStubDir
     */
    public function setCustomStubDir(string $customStubDir = null): void
    {
        $this->customStubDir = $customStubDir;
    }

    /**
     * Stubs in laravel framework
     * @return array
     */
    public function getDefaultStubs(): array
    {
        $temps = [];
        foreach ($this->defaultStubDirectories as $default_stub_directory) {
            $dir = $this->app->basePath($default_stub_directory);
            $temps[] = glob(sprintf('%s/*.stub', realpath($dir)));
        }
        return $this->setKeyToArray(Arr::flatten($temps));
    }

    /**
     * Stubs in this library
     * @return array
     */
    public function getCustomStubs(): array
    {
        if (is_null($this->customStubDir)) {
            return [];
        }
        if ($files = glob(sprintf('%s/*.stub', realpath($this->customStubDir)))) {
            return $this->setKeyToArray($files);
        }
        return [];
    }

    /**
     * Stubs in this library
     * @return array
     */
    public function getPluginStubs(): array
    {
        if ($files = glob(sprintf('%s/*.stub', realpath($this->pluginStubDir)))) {
            return $this->setKeyToArray($files);
        }
        return [];
    }

    /**
     * Stubs published in application
     * @return array
     */
    public function getPublishedStubs(): array
    {
        if ($files = glob(sprintf('%s/*.stub', base_path('stubs')))) {
            return $this->setKeyToArray($files);
        }
        return [];
    }

    /**
     * Enabled Stubs
     * @return array
     */
    public function mergedStubs(): array
    {
        return array_merge($this->getDefaultStubs(), $this->getPluginStubs(), $this->getCustomStubs(), $this->getPublishedStubs());
    }

    /**
     * Find stub path by name
     * @param string $name
     * @return string|null
     */
    public function findStub(string $name): ?string
    {
        $stubs = $this->mergedStubs();
        return $stubs[$name] ?? null;
    }

    /**
     * Set key name to array.
     * @param array $array
     * @return array
     */
    private function setKeyToArray(array $array): array
    {
        return array_combine(
            array_map(
                static function ($item) {
                    return basename($item);
                },
                $array
            ),
            $array
        );
    }
}
