<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use ShibuyaKosuke\LaravelCrudCommand\Console\BreadcrumbsRouteMakeCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\CheckCrudCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\ControllerMakeCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\CrudMakeCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\CrudSetupCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\MigrateMakeCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\MigrationCreator;
use ShibuyaKosuke\LaravelCrudCommand\Console\ModelMakeCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\PolicyMakeCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\RequestMakeCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\ScopeMakeCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\StubPublishCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\ViewComposerMakeCommand;
use ShibuyaKosuke\LaravelCrudCommand\Console\ViewMakeCommand;
use ShibuyaKosuke\LaravelCrudCommand\Services\StubService;

use function config;

/**
 * Class CommandServiceProvider
 * @package ShibuyaKosuke\LaravelCrudCommand\Providers
 */
class CommandServiceProvider extends ServiceProvider
{
    /**
     * array aliases
     */
    private const COMMANDS = [
        'command.crud.setup',
        'command.crud.make',
        'command.controller.crud',
        'command.model.crud',
        'command.scope.make',
        'command.request.crud',
        'command.policy.crud',
        'command.view.composer.crud',
        'command.view.crud',
        'command.stub.publish',
        'command.check.crud',
        'command.breadcrumbs.crud',
        'command.migrate.make'
    ];

    public function boot(): void
    {
        $this->registerCommands();

        $this->registerComposers();

        // Define policy classes
        Gate::guessPolicyNamesUsing(
            function ($modelClass) {
                return str_replace('\\Models\\', '\\Policies\\', $modelClass) . 'Policy';
            }
        );

        $this->publishes(
            [
                __DIR__ . '/../../resources/lang/' => resource_path('lang'),
                __DIR__ . '/../../config/make_crud.php' => config_path('make_crud.php'),
                __DIR__ . '/../../config/composers.php' => config_path('composers.php'),
            ]
        );
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/make_crud.php', 'make_crud');
        $this->mergeConfigFrom(__DIR__ . '/../../config/composers.php', 'composers');

        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang/', 'shibuyakosuke.laravel-crud-command');

        $this->registerStub();

        $this->publishes(
            [
                __DIR__ . '/../../resources/lang/' => resource_path('lang'),
            ]
        );
    }

    protected function registerStub(): void
    {
        $this->app->singleton(
            'stub-path',
            function ($app) {
                return new StubService($app);
            }
        );
    }

    protected function registerCommands(): void
    {
        // set migration.stub file path
        $this->app->extend(
            'migration.creator',
            function () {
                return new MigrationCreator($this->app['files'], $this->app->basePath('stubs'));
            }
        );

        // make:migration
        $this->app->extend(
            'command.migrate.make',
            function () {
                $creator = $this->app['migration.creator'];
                $composer = $this->app['composer'];
                return new MigrateMakeCommand($creator, $composer);
            }
        );

        // crud setup
        $this->app->singleton(
            'command.crud.setup',
            function () {
                return new CrudSetupCommand();
            }
        );

        // artisan make:crud
        $this->app->singleton(
            'command.crud.make',
            function () {
                return new CrudMakeCommand();
            }
        );

        // artisan make:model
        $this->app->singleton(
            'command.model.crud',
            function () {
                return new ModelMakeCommand($this->app['files']);
            }
        );

        // artisan make:scope
        $this->app->singleton(
            'command.scope.make',
            function () {
                return new ScopeMakeCommand($this->app['files']);
            }
        );

        // artisan make:controller
        $this->app->singleton(
            'command.controller.crud',
            function () {
                return new ControllerMakeCommand($this->app['files']);
            }
        );

        // artisan make:request
        $this->app->singleton(
            'command.request.crud',
            function () {
                return new RequestMakeCommand($this->app['files']);
            }
        );

        // artisan make:policy
        $this->app->singleton(
            'command.policy.crud',
            function () {
                return new PolicyMakeCommand($this->app['files']);
            }
        );

        // artisan make:viewcomposer
        $this->app->singleton(
            'command.view.composer.crud',
            function () {
                return new ViewComposerMakeCommand($this->app['files']);
            }
        );

        // artisan make:view
        $this->app->singleton(
            'command.view.crud',
            function () {
                return new ViewMakeCommand($this->app['files']);
            }
        );

        // artisan stub:publish
        if (version_compare($this->app->version(), 7, '=>')) {
            $this->app->extend(
                'command.stub.publish',
                function () {
                    return new StubPublishCommand();
                }
            );
        } else {
            $this->app->singleton(
                'command.stub.publish',
                function () {
                    return new StubPublishCommand();
                }
            );
        }

        $this->app->singleton(
            'command.check.crud',
            function () {
                return new CheckCrudCommand();
            }
        );

        $this->app->singleton(
            'command.breadcrumbs.crud',
            function () {
                return new BreadcrumbsRouteMakeCommand();
            }
        );

        $this->commands(self::COMMANDS);
    }

    /**
     * register Composers
     */
    protected function registerComposers(): void
    {
        $view_composers = config('composers', []);
        View::composers($view_composers);
    }

    /**
     * @return string[]
     */
    public function provides(): array
    {
        return array_merge(self::COMMANDS, ['stub-path']);
    }
}
