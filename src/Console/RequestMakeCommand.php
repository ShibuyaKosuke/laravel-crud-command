<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Console\RequestMakeCommand as RequestMakeCommandBase;
use ShibuyaKosuke\LaravelCrudCommand\Facades\Stub;
use ShibuyaKosuke\LaravelCrudCommand\Schema\Table;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class RequestMakeCommand
 * @package ShibuyaKosuke\LaravelCrudCommand\Console
 */
class RequestMakeCommand extends RequestMakeCommandBase
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'crud:request';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('crud')) {
            $stub = 'request.crud.stub';
        } else {
            $stub = 'request.stub';
        }
        return Stub::findStub($stub);
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        if ((!$this->hasOption('force') || !$this->option('force')) && $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type . ' already exists!');

            return false;
        }

        $this->makeDirectory($path);

        $content = $this->sortImports($this->buildClass($name));

        $content = str_replace('{{ modelVariables }}', $this->option('table'), $content);
        $content = str_replace('{{ column_trans }}', $this->replaceColumnTrans(), $content);

        $this->files->put($path, $content);

        $this->info($this->type . ' created successfully.');
    }

    protected function replaceColumnTrans()
    {
        $table = Table::getByName($this->option('table'));
        return $table->relations['belongs_to']->map(
            function ($keyColumnUsage) {
                return sprintf(
                    "'%s' => trans('columns.%s.name')",
                    $keyColumnUsage['ownColumn'],
                    $keyColumnUsage['otherTable']
                );
            }
        )->implode(",\n                ");
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['crud', 'c', InputOption::VALUE_NONE, 'Generate a resource request class.'],
            ['table', null, InputOption::VALUE_NONE, 'Generate a resource request class.'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the request class already exists'],
        ];
    }
}
