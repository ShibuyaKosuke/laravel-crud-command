<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ShibuyaKosuke\LaravelCrudCommand\Facades\Stub;
use ShibuyaKosuke\LaravelCrudCommand\Schema\Column;
use ShibuyaKosuke\LaravelCrudCommand\Schema\Table;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ViewMakeCommand
 * @package ShibuyaKosuke\LaravelCrudCommand\Console
 */
class ViewMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'crud:view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new blade files for CRUD.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'view';

    /**
     * @var string[]
     */
    protected $ignoredColumns = [];

    public function __construct(Filesystem $files)
    {
        parent::__construct($files);

        $config = \config('make_crud.columns');
        $this->ignoredColumns = Arr::flatten($config);
    }

    /**
     * @return string|void
     */
    protected function getStub()
    {
        if ($this->argument('name') === 'index' && $this->option('with-export')) {
            $stub = sprintf('%s.export.blade.stub', $this->argument('name'));
        } else {
            $stub = sprintf('%s.blade.stub', $this->argument('name'));
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

        $content = $this->modelReplacement($content);

        $content = $this->replaceViews($content);

        $this->files->put($path, $content);

        $this->info(
            $this->type . ': ' .
            $this->getTableName() . '/' .
            $this->argument('name') .
            '.blade.php created successfully.'
        );
    }

    /**
     * @param $content
     * @return string|string[]
     */
    protected function modelReplacement($content)
    {
        $modelClass = str_replace('/', '\\', 'App/' . $this->option('model'));
        $replacements = [
            '{{ bladeParentFile }}' => config('make_crud.blade_parent_file'),
            '{{ table }}' => Str::snake(Str::plural(class_basename($modelClass))),
            '{{ route }}' => Str::kebab(Str::plural(class_basename($modelClass))),
            '{{route}}' => Str::kebab(Str::plural(class_basename($modelClass))),
            'DummyModelVariable' => lcfirst(Str::snake(class_basename($modelClass))),
            '{{ modelVariable }}' => lcfirst(Str::snake(class_basename($modelClass))),
            '{{modelVariable}}' => lcfirst(Str::snake(class_basename($modelClass))),
            '{{ modelVariables }}' => Str::plural(lcfirst(Str::snake(class_basename($modelClass)))),
            '{{modelVariables}}' => Str::plural(lcfirst(Str::snake(class_basename($modelClass)))),
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * @param $content
     * @return mixed|string|string[]
     */
    protected function replaceViews($content)
    {
        $name = $this->argument('name');

        $content = $this->headerReplacements($content);

        switch ($name) {
            case 'index':
                $content = $this->tableHeadingReplacements($content);
                $content = $this->tableBodyReplacements($content);
                $content = $this->paginationReplacements($content);
                break;
            case 'table':
                $content = $this->tableHeadingReplacements($content);
                $content = $this->tableBodyReplacements($content);
                break;
            case 'show':
                $content = $this->dlElementReplacements($content);
                break;
            case 'create':
            case 'edit':
            case 'filter':
                $content = $this->formElementReplacements($content);
                break;
            default:
                break;
        }
        return $content;
    }

    /**
     * @param $content
     * @return mixed
     */
    protected function dlElementReplacements($content)
    {
        $indent = str_repeat('    ', 5);
        $replacement = [];

        /** @var Table $table */
        $table = $this->getTableObject();

        $belongs_to = $table->relations['belongs_to'];

        $table->columns->each(
            function (Column $column) use (&$replacement, $belongs_to) {
                $replacement[] = sprintf(
                    '<dt %s>{{ __(\'columns.%s.%s\') }}</dt>',
                    config('make_crud.view.show.horizontal') ? sprintf('class="%s"', config('make_crud.view.show.dt')) : '',
                    ($column->belongs_to && !in_array($column->COLUMN_NAME, config('make_crud.columns.author'))) ?
                        $column->belongs_to->TABLE_NAME :
                        $column->TABLE_NAME,
                    ($column->belongs_to && !in_array($column->COLUMN_NAME, config('make_crud.columns.author'))) ?
                        'name' :
                        $column->COLUMN_NAME
                );

                if ($belongs_to->pluck('ownColumn')->contains($column->COLUMN_NAME)) {
                    $replacement[] = $belongs_to->filter(
                        function ($item) use ($column) {
                            return $item['ownColumn'] == $column->COLUMN_NAME;
                        }
                    )->map(
                        function ($item) use ($column) {
                            return sprintf(
                                '<dd %s>{{ $%s->%s->%s }}</dd>',
                                config('make_crud.view.show.horizontal') ? sprintf('class="%s"', config('make_crud.view.show.dd')) : '',
                                Str::singular($column->TABLE_NAME),
                                $item['relation_name'],
                                'name'
                            );
                        }
                    )->first();
                } else {
                    $replacement[] = vsprintf(
                        '<dd %s>{{ $%s->%s }}</dd>',
                        [
                            config('make_crud.view.show.horizontal') ? sprintf('class="%s"', config('make_crud.view.show.dd')) : '',
                            Str::snake(Str::singular($column->TABLE_NAME)),
                            Str::snake(Str::singular($column->COLUMN_NAME))
                        ]
                    );
                }
            }
        );
        $dl = sprintf('<dl%s>', config('make_crud.view.show.horizontal') ? ' class="row"' : '') . PHP_EOL;
        $dl .= $indent . implode(PHP_EOL . $indent, $replacement) . PHP_EOL;
        $dl .= '                </dl>';
        return str_replace('{{ dlElements }}', $dl, $content);
    }

    /**
     * @param string $content
     * @return mixed
     */
    protected function formElementReplacements($content)
    {
        $table = $this->getTableObject();

        $indent = str_repeat('    ', 4);

        $replacement = $table->columns
            ->reject(
                function (Column $column) {
                    return in_array($column->COLUMN_NAME, $this->ignoredColumns, true);
                }
            )
            ->map(
                function (Column $column) {
                    return $this->formElement($column);
                }
            );

        return str_replace('{{ FormElements }}', $replacement->implode(PHP_EOL . $indent), $content);
    }

    /**
     * @param Column $column
     * @return string
     */
    private function formElement(Column $column): ?string
    {
        $name = sprintf('\'%s\'', $column->COLUMN_NAME);

        $table = $this->getTableObject();
        $belongs_to = $table->relations['belongs_to'];

        $trans = ($column->belongs_to) ?
            sprintf('__(\'columns.%s.name\')', $column->belongs_to->TABLE_NAME) :
            sprintf('__(\'columns.%s.%s\')', $column->TABLE_NAME, $column->COLUMN_NAME);

        $label = ($column->IS_NULLABLE === 'NO' && $this->argument('name') !== 'filter') ?
            sprintf('[\'html\' => %s . \'%s\']', $trans, config('make_crud.required_html')) :
            $trans;

        if ($this->argument('name') === 'create') {
            $old = sprintf('old(\'%s\')', $column->COLUMN_NAME);
        } elseif ($this->argument('name') === 'edit') {
            $old = vsprintf(
                'old(\'%s\', $%s->%s)',
                [
                    $column->COLUMN_NAME,
                    Str::singular($column->TABLE_NAME),
                    $column->COLUMN_NAME
                ]
            );
        } elseif ($this->argument('name') === 'filter') {
            $old = sprintf('$params[\'%s\'] ?? \'\'', $column->COLUMN_NAME);
        }

        $option = sprintf('[\'placeholder\' => %s]', $trans);

        if ($belongs_to->pluck('ownColumn')->contains($column->COLUMN_NAME)) {
            $list = $belongs_to->filter(
                function ($item) use ($column) {
                    return $item['ownColumn'] === $column->COLUMN_NAME;
                }
            )->map(
                function ($item) use ($column) {
                    return sprintf(
                        '$%s->pluck(\'name\', \'id\')',
                        Str::plural($item['relation_name'])
                    );
                }
            )->first();
        } else {
            $list = null;
        }

        if ($column->belongs_to) {
            return vsprintf('{{ LaraForm::select(%s, %s, %s, %s, %s) }}', [$name, $label, $list, $old, $option]);
        } else {
            return vsprintf('{{ LaraForm::text(%s, %s, %s, %s) }}', [$name, $label, $old, $option]);
        }
    }

    /**
     * @param $content
     * @return string|string[]
     */
    protected function headerReplacements($content)
    {
        /** @var Table $table */
        $table = $this->getTableObject();

        $replacement = sprintf(
            '{{ __(\'pages.%s\', [\'table\' => __(\'tables.%s\')]) }}',
            $this->argument('name'),
            $table->TABLE_NAME
        );

        return str_replace('{{ pageTitle }}', $replacement, $content);
    }

    /**
     * @param $content
     * @return string
     */
    protected function tableHeadingReplacements($content): string
    {
        $indent = ($this->argument('name') === 'index') ? str_repeat('    ', 8) : str_repeat('    ', 2);
        $table = $this->getTableObject();

        $html = ($this->argument('name') === 'index' && $this->option('sortable')) ?
            '<th class="sortable %s">' .
            '{{ Html::linkRoute(\'%s.index\', __(\'columns.%s.%s\'), [\'sort\' => \'%s\', \'order\' => request(\'order\') === \'asc\' ? \'desc\' : \'asc\']) }}' .
            '</th>' :
            '<th>{{ __(\'columns.%s.%s\') }}</th>';

        $replacement = $table->columns
            ->map(
                function (Column $column) use ($html) {
                    if ($this->argument('name') === 'index' && $this->option('sortable')) {
                        if ($column->belongs_to && !in_array($column->COLUMN_NAME, config('make_crud.columns.author'), true)) {
                            $values = [
                                sprintf("@if(request('sort') == '%s')sort-{{ request('order') }}@endif", $column->COLUMN_NAME),
                                $column->TABLE_NAME,
                                $column->belongs_to->TABLE_NAME,
                                'name',
                                $column->COLUMN_NAME
                            ];
                        } else {
                            $values = [
                                sprintf("@if(request('sort') == '%s')sort-{{ request('order') }}@endif", $column->COLUMN_NAME),
                                $column->TABLE_NAME,
                                $column->TABLE_NAME,
                                $column->COLUMN_NAME,
                                $column->COLUMN_NAME
                            ];
                        }
                    } else {
                        if ($column->belongs_to && !in_array($column->COLUMN_NAME, config('make_crud.columns.author'), true)) {
                            $values = [
                                $column->belongs_to->TABLE_NAME,
                                'name'
                            ];
                        } else {
                            $values = [
                                $column->TABLE_NAME,
                                $column->COLUMN_NAME
                            ];
                        }
                    }
                    return vsprintf($html, $values);
                }
            );
        if ($this->argument('name') === 'index') {
            $replacement->prepend('<th>#</th>');
        }
        $replacement = $replacement->implode(PHP_EOL . $indent);

        return str_replace('{{ TableHeadRow }}', $replacement, $content);
    }

    /**
     * @param $content
     * @return string
     */
    protected function tableBodyReplacements($content): string
    {
        $index = ($this->argument('name') === 'index');
        $indent = $index ? str_repeat('    ', 9) : str_repeat('    ', 3);
        $table = $this->getTableObject();

        $replacement = collect();
        $replacement->add(
            sprintf(
                '@foreach($%s as $%s)',
                Str::snake($table->TABLE_NAME),
                Str::singular($table->TABLE_NAME)
            )
        );

        $model = Str::singular($table->TABLE_NAME);
        $tr = $this->option('with-trashed') ? sprintf('<tr @if($%s->trashed()) class="table-danger" @endif>', $model) : '<tr>';

        $replacement->add(str_repeat('    ', $index ? 8 : 2) . $tr);

        if ($this->argument('name') === 'index') {
            $replacement->add(
                sprintf(
                    '%s<td>{{ Html::linkRoute(\'%s.show\', ' .
                    '__(\'%s\'), compact(\'%s\'), ' .
                    '[\'class\' => \'btn btn-sm btn-outline-primary\']) }}</td>',
                    $indent,
                    $table->TABLE_NAME,
                    'buttons.show',
                    $model
                )
            );
        }

        $belongs_to = $table->relations['belongs_to'];

        $table->columns->each(
            function (Column $column) use ($replacement, $indent, $belongs_to) {
                if ($belongs_to->pluck('ownColumn')->contains($column->COLUMN_NAME)) {
                    $row = $belongs_to->filter(
                        function ($item) use ($column) {
                            return $item['ownColumn'] === $column->COLUMN_NAME;
                        }
                    )->map(
                        function ($item) use ($indent, $column) {
                            return sprintf(
                                '%s<td>{{ $%s->%s->name }}</td>',
                                $indent,
                                Str::singular($column->TABLE_NAME),
                                $item['relation_name']
                            );
                        }
                    )->first();
                } else {
                    $row = sprintf(
                        '%s<td>{{ $%s->%s }}</td>',
                        $indent,
                        Str::singular($column->TABLE_NAME),
                        $column->COLUMN_NAME
                    );
                }
                $replacement->add($row);
            }
        );
        $replacement->add(str_repeat('    ', $index ? 8 : 2) . '</tr>');
        $replacement->add(str_repeat('    ', $index ? 7 : 1) . '@endforeach');

        return str_replace('{{ TableRowsBody }}', $replacement->implode(PHP_EOL), $content);
    }

    /**
     * @param $content
     * @return string|string[]
     */
    protected function paginationReplacements($content)
    {
        /** @var Table $table */
        $table = $this->getTableObject();

        $replacement = sprintf('{{ $%s->appends($params)->links() }}', Str::snake($table->TABLE_NAME));

        return str_replace('{{ pagination }}', $replacement, $content);
    }

    /**
     * @return string
     */
    protected function getTableName(): string
    {
        $class = $this->qualifyClass($this->option('model'));
        return (new $class())->getTable();
    }

    /**
     * @return Model
     */
    protected function getTableObject(): Model
    {
        return Table::getByName($this->getTableName());
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        $table = $this->getTableName();
        return $this->laravel->resourcePath("/views/{$table}/{$this->argument('name')}.blade.php");
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return array_merge(
            parent::getOptions(),
            [
                ['crud', 'c', InputOption::VALUE_NONE, 'Generate a resource request class.'],
                ['force', null, InputOption::VALUE_NONE, 'Create the class even if the controller already exists'],
                ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given model.'],
                [
                    'sortable',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Generate a sorting data functions'
                ],
                [
                    'with-export',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Generate a exporting data functions'
                ],
                [
                    'with-trashed',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Display trashed data'
                ],
                [
                    'with-filter',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Generate filter function'
                ],
            ]
        );
    }
}
