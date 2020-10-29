<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use ShibuyaKosuke\LaravelCrudCommand\Facades\Stub;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ModelMakeCommand
 * @package ShibuyaKosuke\LaravelCrudCommand\Console
 */
class ScopeMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:scope';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new global scope class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Scope';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return Stub::findStub('scope.stub');
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'] . '/Scopes/' . str_replace('\\', '/', $name) . '.php';
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
        if (parent::handle()) {
            $this->info($this->type . ' created successfully.');
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(
            parent::getOptions(),
            [
                ['force', null, InputOption::VALUE_NONE, 'Generate a scope class for crud.'],
            ]
        );
    }
}
