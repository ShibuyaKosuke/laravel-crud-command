<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Illuminate\Console\Command;

/**
 * Class CrudSetupCommand
 * @package ShibuyaKosuke\LaravelCrudCommand\Console
 */
class CrudSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crud generator setup command for Laravel.';

    public function handle()
    {
        $this->call('replace:model');
        $this->call(
            'vendor:publish',
            [
            '--provider' => 'ShibuyaKosuke\LaravelCrudCommand\Providers\CommandServiceProvider'
            ]
        );
        $this->call('trans:publish');
        $this->call('rule:publish');
    }
}
