<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Illuminate\Console\Command;
use ShibuyaKosuke\LaravelCrudCommand\Schema\Table;

class CheckCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check generated files for crud.';

    public function handle(): void
    {
        $this->info('Check classes');
        $this->table(
            ['#', 'Model', 'Controller', 'Request', 'Composer', 'Policy',],
            $this->checkClasses()->toArray()
        );

        $this->info('Check views');
        $this->table(
            ['#', 'index.blade.php', 'show.blade.php', 'create.blade.php', 'edit.blade.php', 'filter.blade.php', 'table.blade.php'],
            $this->checkViews()->toArray()
        );
    }

    protected function checkClasses()
    {
        return Table::getTablesHavingComment()
            ->mapWithKeys(
                function (Table $table) {
                    return [
                        $table->TABLE_NAME => array_merge(
                            ['table' => $table->TABLE_NAME],
                            array_map(
                                function ($file) {
                                    return file_exists($file) ? '     OK     ' : '==== NG ====';
                                },
                                [
                                    'Model' => app_path(sprintf('Models/%s.php', $table->model_name)),
                                    'Controller' => app_path(
                                        sprintf(
                                            'Http/Controllers/%s.php',
                                            $table->controller_name
                                        )
                                    ),
                                    'FormRequest' => app_path(
                                        sprintf(
                                            'Http/Requests/%s.php',
                                            $table->request_name
                                        )
                                    ),
                                    'Composer' => app_path(
                                        sprintf(
                                            'Http/ViewComposers/%s.php',
                                            $table->model_name . 'Composer'
                                        )
                                    ),
                                    'Policy' => app_path(sprintf('Policies/%s.php', $table->model_name . 'Policy')),
                                ]
                            )
                        )
                    ];
                }
            );
    }

    protected function checkViews()
    {
        return Table::getTablesHavingComment()
            ->mapWithKeys(
                function (Table $table) {
                    return [
                        $table->TABLE_NAME => array_merge(
                            ['table_names' => $table->TABLE_NAME],
                            array_map(
                                function ($file) {
                                    return file_exists($file) ? '       OK       ' : '====== NG ======';
                                },
                                [
                                    'index' => resource_path(
                                        sprintf('views/%s/%s.blade.php', $table->TABLE_NAME, 'index')
                                    ),
                                    'show' => resource_path(
                                        sprintf('views/%s/%s.blade.php', $table->TABLE_NAME, 'show')
                                    ),
                                    'create' => resource_path(
                                        sprintf('views/%s/%s.blade.php', $table->TABLE_NAME, 'create')
                                    ),
                                    'edit' => resource_path(
                                        sprintf('views/%s/%s.blade.php', $table->TABLE_NAME, 'edit')
                                    ),
                                    'filter' => resource_path(
                                        sprintf('views/%s/%s.blade.php', $table->TABLE_NAME, 'filter')
                                    ),
                                    'table' => resource_path(
                                        sprintf('views/%s/%s.blade.php', $table->TABLE_NAME, 'table')
                                    ),
                                ]
                            )
                        )
                    ];
                }
            );
    }
}
