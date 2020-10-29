<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use ShibuyaKosuke\LaravelCrudCommand\Facades\Stub;

class MigrationCreator extends \Illuminate\Database\Migrations\MigrationCreator
{
    protected function getStub($table, $create)
    {
        if (is_null($table)) {
            $stub = 'migration.stub';
        } elseif ($create) {
            $stub = 'migration.create.stub';
        } else {
            $stub = 'migration.update.stub';
        }
        $stub = Stub::findStub($stub);

        return $this->files->get($stub);
    }
}
