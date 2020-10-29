<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ShibuyaKosuke\LaravelCrudCommand\Schema\Table;
use Symfony\Component\Console\Application;

/**
 * Class CrudMakeCommand
 * @package ShibuyaKosuke\LaravelCrudCommand\Console
 */
class CrudMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {table} {--force} {--sortable} {--api} {--with-api} {--with-trashed} {--with-export} {--with-filter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crud generator for Laravel.';

    /**
     * @var Collection|Table[]
     */
    protected $tables;

    /**
     * @var bool
     */
    protected bool $force;

    /**
     * @var bool
     */
    protected bool $sortable;

    /**
     * @var bool
     */
    protected bool $api;

    /**
     * @var bool
     */
    protected bool $withApi;

    /**
     * @var bool
     */
    protected bool $trashed;

    /**
     * @var bool
     */
    protected bool $export;

    /**
     * @var bool
     */
    protected bool $filter;

    private function parseOptions(): void
    {
        $this->force = $this->option('force') === true;
        $this->sortable = $this->option('sortable') || config('make_crud.sortable');
        $this->api = $this->option('api') === true;
        $this->withApi = $this->option('with-api') || config('make_crud.api');
        $this->trashed = $this->option('with-trashed') === true;
        $this->export = $this->option('with-export') || config('make_crud.export');
        $this->filter = $this->option('with-filter') || config('make_crud.filter');
    }

    /**
     * @return Collection|Table[]
     */
    protected function getExistTables(): Collection
    {
        if ($this->tables) {
            return $this->tables;
        }
        $this->tables = Table::all();
        return $this->tables;
    }

    /**
     * @param string $table
     * @return bool
     */
    protected function isExistTable(string $table): ?bool
    {
        try {
            if (!$this->tables->pluck(Table::columnName('TABLE_NAME'))->contains($table)) {
                throw new InvalidArgumentException("Error: table not exists: {$table}");
            }
            return true;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            exit();
        }
    }

    /**
     * Command execution
     */
    public function handle(): void
    {
        try {
            $this->parseOptions();

            $this->getExistTables();
            $param_table = $this->argument('table');
            $this->isExistTable($param_table);

            $this->tables->filter(
                function (Table $table) use ($param_table) {
                    return $table->TABLE_NAME === $param_table;
                }
            )->each(
                function (Table $table) {
                    $this->callback($table);
                }
            );
        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->line($e->getTraceAsString());
        }
    }

    /**
     * @param Table $table
     * @throws FileNotFoundException
     */
    protected function callback(Table $table): void
    {
        $this->line('==== ' . $table->model_name . ' ====');

        $this->globalScope($table);
        $this->model($table);
        $this->controller($table);
        $this->request($table);
        $this->policy($table);
        $this->composer($table);
        $this->view($table);
        $this->checkRoutes();
        $this->checkRoutesForApi();
        $this->breadcrumbs($table);
    }

    /**
     * route for web
     */
    protected function checkRoutes(): void
    {
        if ($this->api) {
            return;
        }

        /** @var Application $app */
        $app = $this->getApplication();
        $version = $app->getVersion();

        /** @var Table $table */
        $table = Table::getByName($this->argument('table'));
        $routes = File::get(base_path('routes/web.php'));

        if (version_compare($version, 8, '<')) {
            $line = sprintf(
                'Route::resource(\'%s\', \'%sController\');' . PHP_EOL,
                $table->TABLE_NAME,
                $table->model_name
            );
            $export = sprintf(
                'Route::get(\'%s/export/{fileType}\', \'%sController@export\')->name(\'%s.export\');' . PHP_EOL,
                $table->TABLE_NAME,
                $table->model_name,
                $table->TABLE_NAME
            );
        } else {
            $line = sprintf(
                'Route::resource(\'%s\', App\Http\Controllers\%sController::class);' . PHP_EOL,
                $table->TABLE_NAME,
                $table->model_name
            );
            $export = sprintf(
                'Route::get(\'%s/export/{fileType}\', [App\Http\Controllers\%sController::class, \'export\'])->name(\'%s.export\');' . PHP_EOL,
                $table->TABLE_NAME,
                $table->model_name,
                $table->TABLE_NAME
            );
        }

        if (!Str::contains($routes, $line)) {
            file_put_contents(base_path('routes/web.php'), $line, FILE_APPEND);
            $this->getOutput()->writeln(sprintf('Append to web.php: %s', $line));
        }

        if ($this->option('with-export') && !Str::contains($routes, $export)) {
            file_put_contents(base_path('routes/web.php'), $export, FILE_APPEND);
            $this->getOutput()->writeln(sprintf('Append to web.php: %s', $export));
        }
    }

    /**
     * route for api
     * @return void
     */
    protected function checkRoutesForApi(): void
    {
        if (!$this->api && !$this->withApi) {
            return;
        }

        /** @var Application $app */
        $app = $this->getApplication();
        $version = $app->getVersion();

        /** @var Table $table */
        $table = Table::getByName($this->argument('table'));
        $routes = File::get(base_path('routes/api.php'));

        if (version_compare($version, 8, '<')) {
            $line = sprintf(
                'Route::resource(\'%s\', \'Api\\%sController\');' . PHP_EOL,
                $table->TABLE_NAME,
                $table->model_name
            );
        } else {
            $line = sprintf(
                'Route::resource(\'%s\', App\Http\Controllers\Api\%sController::class);' . PHP_EOL,
                $table->TABLE_NAME,
                $table->model_name
            );
        }

        if (!Str::contains($routes, $line)) {
            file_put_contents(base_path('routes/api.php'), $line, FILE_APPEND);
            $this->getOutput()->writeln(sprintf('Append to api.php: %s', $line));
        }
    }

    /**
     * @param Table $table
     */
    protected function globalScope(Table $table): void
    {
        $this->call(
            "make:scope",
            [
                'name' => sprintf('%sScope', $table->model_name),
                '--force' => $this->force
            ]
        );
    }

    /**
     * @param Table $table
     */
    protected function model(Table $table): void
    {
        $this->call(
            "crud:model",
            [
                'name' => sprintf('Models/%s', $table->model_name),
                '--table' => $table->TABLE_NAME,
                '--crud' => true,
                '--force' => $this->force,
                '--sortable' => $this->sortable,
            ]
        );
    }

    /**
     * @param Table $table
     */
    protected function controller(Table $table): void
    {
        if (!$this->api) {
            $this->call(
                "crud:controller",
                [
                    'name' => $table->controller_name,
                    '--model' => sprintf('Models/%s', $table->model_name),
                    '--crud' => true,
                    '--force' => $this->force,
                    '--with-trashed' => $this->trashed,
                    '--with-export' => $this->export
                ]
            );
        }
        if ($this->api || $this->withApi) {
            $this->call(
                "crud:controller",
                [
                    'name' => 'Api/' . $table->controller_name,
                    '--model' => sprintf('Models/%s', $table->model_name),
                    '--crud' => true,
                    '--force' => $this->force,
                    '--api' => $this->api,
                    '--with-trashed' => $this->trashed,
                    '--with-export' => $this->export
                ]
            );
        }
    }

    /**
     * @param Table $table
     */
    protected function request(Table $table): void
    {
        $this->call(
            "crud:request",
            [
                'name' => $table->request_name,
                '--table' => $table->TABLE_NAME,
                '--crud' => true,
                '--force' => $this->force
            ]
        );
    }

    /**
     * @param Table $table
     */
    protected function policy(Table $table): void
    {
        $this->call(
            "crud:policy",
            [
                'name' => "{$table->model_name}Policy",
                '--model' => sprintf('Models/%s', $table->model_name),
                '--crud' => true,
                '--force' => $this->force
            ]
        );
    }

    /**
     * @param Table $table
     */
    protected function composer(Table $table): void
    {
        if ($this->api) {
            return;
        }
        $this->call(
            "crud:view-composer",
            [
                'name' => "{$table->model_name}Composer",
                '--table' => $table->TABLE_NAME,
                '--force' => $this->force
            ]
        );
    }

    /**
     * @param Table $table
     */
    protected function view(Table $table): void
    {
        if ($this->api) {
            return;
        }
        foreach (['index', 'show', 'create', 'edit', 'table', 'filter'] as $name) {
            if ($name === 'filter' && !$this->option('with-filter')) {
                continue;
            }
            $this->call(
                "crud:view",
                [
                    'name' => $name,
                    '--model' => sprintf('Models/%s', $table->model_name),
                    '--crud' => true,
                    '--force' => $this->force,
                    '--sortable' => $this->sortable,
                    '--with-trashed' => $this->trashed,
                    '--with-export' => $this->export,
                    '--with-filter' => $this->filter
                ]
            );
        }
    }

    /**
     * @param Table $table
     */
    protected function breadcrumbs(Table $table): void
    {
        if ($this->api) {
            return;
        }
        $this->call(
            "make:breadcrumbs",
            [
            'table' => $table,
            ]
        );
    }
}
