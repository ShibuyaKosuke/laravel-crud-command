<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Routing\Console\ControllerMakeCommand as ControllerMakeCommandBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ShibuyaKosuke\LaravelCrudCommand\Facades\Stub;
use ShibuyaKosuke\LaravelCrudCommand\Schema\Table;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ControllerMakeCommand
 * @package ShibuyaKosuke\LaravelCrudCommand\Console
 */
class ControllerMakeCommand extends ControllerMakeCommandBase
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'crud:controller';

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     * @see GeneratorCommand::resolveStubPath() Override
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $stub = null;
        if ($this->option('crud')) {
            if ($this->option('with-export')) {
                $stub = 'controller.crud.export.stub';
            } else {
                $stub = 'controller.crud.stub';
            }
        } elseif ($this->option('parent')) {
            $stub = 'controller.nested.stub';
        } elseif ($this->option('model')) {
            $stub = 'controller.model.stub';
        } elseif ($this->option('invokable')) {
            $stub = 'controller.invokable.stub';
        } elseif ($this->option('resource')) {
            $stub = 'controller.stub';
        }

        if ($this->option('api') && is_null($stub)) {
            $stub = 'controller.crud.api.stub';
        }

        $stub = $stub ?? 'controller.plain.stub';

        return Stub::findStub($stub);
    }

    /**
     * Build the model replacement values.
     *
     * @param array $replace
     * @return array
     */
    protected function buildModelReplacements(array $replace)
    {
        $modelClass = $this->parseModel($this->option('model'));

        return array_merge(
            $replace,
            [
                'DummyFullModelClass' => $modelClass,
                '{{ namespacedModel }}' => $modelClass,
                '{{namespacedModel}}' => $modelClass,
                'DummyModelClass' => class_basename($modelClass),
                '{{ model }}' => class_basename($modelClass),
                '{{model}}' => class_basename($modelClass),
                '{{ table }}' => Str::snake(Str::plural(class_basename($modelClass))),
                '{{table}}' => Str::snake(Str::plural(class_basename($modelClass))),
                '{{ route }}' => Str::kebab(Str::plural(class_basename($modelClass))),
                '{{route}}' => Str::kebab(Str::plural(class_basename($modelClass))),
                '{{ with }}' => $this->relations(),
                'DummyModelVariable' => lcfirst(Str::snake(class_basename($modelClass))),
                '{{ modelVariable }}' => lcfirst(Str::snake(class_basename($modelClass))),
                '{{modelVariable}}' => lcfirst(Str::snake(class_basename($modelClass))),
                '{{ modelVariables }}' => Str::plural(lcfirst(Str::snake(class_basename($modelClass)))),
                '{{modelVariables}}' => Str::plural(lcfirst(Str::snake(class_basename($modelClass)))),
            ]
        );
    }

    /**
     * @return string
     */
    protected function relations()
    {
        $modelClass = $this->parseModel($this->option('model'));
        $table_name = Str::snake(Str::plural(class_basename($modelClass)));
        $table = Table::getByName($table_name);

        /** @var Collection $belongs_to */
        $belongs_to = $table->relations['belongs_to']->map(
            function ($belongs_to) {
                return sprintf('\'%s\'', $belongs_to['relation_name']);
            }
        );
        if ($belongs_to->isEmpty()) {
            return '';
        }
        $code = sprintf('with([%s])->', $belongs_to->implode(', '));
        if ($this->option('with-trashed')) {
            $code .= 'withTrashed()->';
        }
        return $code;
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
                ['crud', null, InputOption::VALUE_NONE, 'Generate a resource controller class for crud.'],
                ['with-trashed', 'c', InputOption::VALUE_NONE, 'Generate a resource controller class.'],
                ['with-export', null, InputOption::VALUE_NONE, 'Generate a resource controller class with export.'],
            ]
        );
    }
}
