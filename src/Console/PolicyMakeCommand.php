<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Illuminate\Foundation\Console\PolicyMakeCommand as PolicyMakeCommandBase;
use ShibuyaKosuke\LaravelCrudCommand\Facades\Stub;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class PolicyMakeCommand
 * @package ShibuyaKosuke\LaravelCrudCommand\Console
 */
class PolicyMakeCommand extends PolicyMakeCommandBase
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'crud:policy';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $stub = $this->option('model') ? 'policy.stub' : 'policy.plain.stub';
        return Stub::findStub($stub);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(
            parent::getOptions(),
            [
                ['crud', 'c', InputOption::VALUE_NONE, 'Generate a resource policy class.'],
                ['force', null, InputOption::VALUE_NONE, 'Create the class even if the policy class already exists'],
            ]
        );
    }
}
