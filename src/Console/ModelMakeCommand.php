<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\ModelMakeCommand as ModelMakeCommandBase;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ShibuyaKosuke\LaravelCrudCommand\Facades\Stub;
use ShibuyaKosuke\LaravelCrudCommand\Schema\Column;
use ShibuyaKosuke\LaravelCrudCommand\Schema\Table;
use ShibuyaKosuke\LaravelCrudCommand\Schema\Types;
use Symfony\Component\Console\Input\InputOption;

use function config;

/**
 * Class ModelMakeCommand
 * @package ShibuyaKosuke\LaravelCrudCommand\Console
 */
class ModelMakeCommand extends ModelMakeCommandBase
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @var Table
     */
    protected $table;

    /**
     * @var Column[]
     */
    protected $columns;

    /**
     * @var string[]
     */
    protected $ignoredColumns = [];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'crud:model';

    /**
     * ModelMakeCommand constructor.
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
        
        $this->ignoredColumns = Arr::flatten(config('make_crud.columns'));
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        $stub = null;
        if ($this->option('crud')) {
            if ($this->option('table') === 'users') {
                $stub = 'model.users.crud.stub';
            } else {
                $stub = 'model.crud.stub';
            }
        } elseif ($this->option('pivot')) {
            $stub = 'model.pivot.stub';
        } else {
            $stub = 'model.stub';
        }

        return Stub::findStub($stub);
    }

    /**
     * get table columns
     * @return Column[]|Collection
     */
    protected function getColumns()
    {
        if ($this->columns) {
            return $this->columns;
        }
        $this->table = Table::getByName($this->option('table'));

        if (is_null($this->table)) {
            return collect([]);
        }
        $this->columns = $this->table->columns;
        return $this->columns;
    }

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function handle(): void
    {
        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        if ((!$this->hasOption('force') || !$this->option('force')) && $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type . ' already exists!');

            return;
        }

        $this->makeDirectory($path);

        $this->content = $this->sortImports($this->buildClass($name));

        $this->addComment();
        $this->traits();
        $this->database();
        $this->paginate();
        $this->fillable();
        $this->dates();
        $this->globalScope();
        $this->scope();
        $this->attributes();
        $this->belongsTo();
        $this->hasMany();
        $this->belongsToMany();

        $this->files->put($path, $this->content);

        $this->info($this->type . ' created successfully.');
    }

    /**
     * get PHP data type from database column type
     * @param string $dbType
     * @return string
     * @throws Exception
     */
    protected function convertDataType(string $dbType): string
    {
        return Types::convertDataType($dbType);
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * @return array|array[]
     */
    protected function getOptions(): array
    {
        return array_merge(
            parent::getOptions(),
            [
                ['crud', null, InputOption::VALUE_NONE, 'Generate a model class for crud.'],
                ['table', null, InputOption::VALUE_NONE, 'Generate a model class for table.'],
                ['sortable', null, InputOption::VALUE_OPTIONAL, 'Generate a sorting data functions'],
            ]
        );
    }

    /**
     * Comment
     * @throws Exception
     */
    protected function addComment(): void
    {
        $comments = [];

        /** @var Collection $columns */
        $columns = $this->getColumns();
        $comments[] = sprintf(' * %s %s', $this->table->model_name, $this->table->TABLE_COMMENT);
        if ($columns->isEmpty()) {
            return;
        }

        $columns->each(
            function (Column $column) use (&$comments) {
                $comments[] = sprintf(
                    ' * @property %s %s %s',
                    $this->convertDataType($column->DATA_TYPE),
                    $column->COLUMN_NAME,
                    $column->COLUMN_COMMENT
                );
            }
        );

        $relations = $this->table->relations;

        $relations['belongs_to']->each(
            function ($belongs_to) use (&$comments) {
                if (in_array($belongs_to['ownColumn'], config('make_crud.columns.author'), true)) {
                    $comments[] = sprintf(
                        ' * @property %s %s %s',
                        $belongs_to['related_model'],
                        $belongs_to['relation_name'],
                        trans(sprintf('columns.%s.%s', $belongs_to['ownTable'], $belongs_to['ownColumn']))
                    );
                } else {
                    $comments[] = sprintf(
                        ' * @property %s %s %s',
                        $belongs_to['related_model'],
                        $belongs_to['relation_name'],
                        $belongs_to['comment']
                    );
                }
            }
        );

        $relations['has_many']->reject(
            function ($has_many) {
                return in_array($has_many['otherColumn'], config('make_crud.columns.author'), true);
            }
        )->each(
            function ($has_many) use (&$comments) {
                $comments[] = sprintf(
                    ' * @property %s[] %s %s',
                    $has_many['related_model'],
                    $has_many['relation_name'],
                    $has_many['comment']
                );
            }
        );

        $relations['belongs_to_many']->each(
            function ($belongs_to_many) use (&$comments) {
                $comments[] = sprintf(
                    ' * @property %s[] %s %s',
                    $belongs_to_many['related_model'],
                    $belongs_to_many['otherTable'],
                    $belongs_to_many['comment']
                );
            }
        );

        $this->content = str_replace(['{{ phpdoc }}'], implode(PHP_EOL, $comments), $this->content);
    }

    /**
     * Traits
     */
    protected function traits(): void
    {
        $traits = [
            $this->timestamp(),
            $this->softDeletes(),
            $this->authorObservable()
        ];
        if ($this->option('crud') && $this->option('table') === 'users') {
            array_unshift($traits, 'use Notifiable;');
        }
        $traits[] = 'use Rememberable;';

        $this->append(implode("\n", $traits));
    }

    /**
     * use timestamp
     * @return string|null
     */
    protected function timestamp(): ?string
    {
        $column_names = $this->getColumns()->pluck('COLUMN_NAME');
        foreach ($column_names as $column_name) {
            if (in_array($column_name, config('make_crud.columns.timestamps'), true)) {
                return "use Timestamp;";
            }
        }
        return null;
    }

    /**
     * use softDeletes
     * @return string|null
     */
    protected function softDeletes(): ?string
    {
        $column_names = $this->getColumns()->pluck('COLUMN_NAME');
        foreach ($column_names as $column_name) {
            if (in_array($column_name, config('make_crud.columns.soft_delete'), true)) {
                return "use SoftDeletes;";
            }
        }
        return null;
    }

    /**
     * use AuthorObservable
     * @return string
     */
    protected function authorObservable(): ?string
    {
        $column_names = $this->getColumns()->pluck('COLUMN_NAME');
        foreach ($column_names as $column_name) {
            if (in_array($column_name, config('make_crud.columns.author'), true)) {
                return "use AuthorObservable;";
            }
        }
        return null;
    }

    /**
     * database property
     */
    protected function database(): void
    {
        $table = $this->option('table');
        $singular = Str::singular($table);
        $plural = Str::plural($singular);
        if ($plural !== $table) {
            $this->append(sprintf("protected \$database = '%s';", $table));
        }
    }

    /**
     * paginate
     */
    protected function paginate(): void
    {
        $this->append(sprintf("protected \$perPage = %d;", config('make_crud.defaultPerPage')));
    }

    /**
     * fillable
     */
    protected function fillable(): void
    {
        $columns = $this->getColumns()
            ->reject(
                function (Column $column) {
                    return in_array($column->COLUMN_NAME, $this->ignoredColumns, true);
                }
            )
            ->pluck('COLUMN_NAME')
            ->map(
                function ($column_name) {
                    return '    \'' . $column_name . "'";
                }
            );

        $this->append("protected \$fillable = [\n" . $columns->implode(", \n") . "\n];");
    }

    /**
     * dates
     */
    protected function dates(): void
    {
        /** @var Collection $columns */
        $columns = $this->getColumns()
            ->reject(
                function (Column $column) {
                    return in_array($column->COLUMN_NAME, $this->ignoredColumns, true);
                }
            )
            ->filter(
                function (Column $column) {
                    return in_array($column->DATA_TYPE, ['datetime', 'timestamp']);
                }
            )
            ->map(
                function (Column $column) {
                    return '    \'' . $column->COLUMN_NAME . "',";
                }
            );

        if ($columns->isEmpty()) {
            return;
        }

        $this->append("protected \$dates = [\n" . $columns->implode("\n") . "\n];");
    }

    /**
     * Global Scope
     */
    protected function globalScope(): void
    {
        $html = [];
        $html[] = '/**';
        $html[] = ' * Add global Scopes';
        $html[] = ' */';
        $html[] = 'protected static function booted(): void';
        $html[] = '{';
        $html[] = '    parent::boot();';
        $html[] = '    static::addGlobalScope(new %sScope);';
        $html[] = '}';

        $modelName = explode('\\', $this->qualifyClass($this->getNameInput()));
        $shortClassName = end($modelName);
        $contents = sprintf(implode(PHP_EOL, $html), $shortClassName);
        $this->append($contents);
    }

    /**
     * Scope
     */
    protected function scope(): void
    {
        $html = [];
        $html[] = '/**';
        $html[] = ' * @param Builder $query';
        $html[] = ' * @param Request $request';
        $html[] = ' * @return Builder';
        $html[] = ' */';
        $html[] = 'public function scopeSearch(Builder $query, Request $request): Builder';
        $html[] = '{';
        $html[] = '    return $query%s;';
        $html[] = '}';
        $columns = $this->getColumns()->reject(
            function (Column $column) {
                return in_array($column->COLUMN_NAME, $this->ignoredColumns, true);
            }
        )->map(
            function (Column $column) {
                if (in_array($column->DATA_TYPE, ['int', 'bigint', 'smallint', 'tinyint'])) {
                    return sprintf(
                        '->when($request->get(\'%1$s\'), function (Builder $query) use ($request) {' . PHP_EOL .
                        '        $query->where(\'%1$s\', \'=\', $request->get(\'%1$s\'));' . PHP_EOL .
                        '    })',
                        $column->COLUMN_NAME
                    );
                }

                if (in_array($column->DATA_TYPE, ['timestamp', 'datetime', 'date'])) {
                    return sprintf(
                        '->when($request->get(\'%1$s\'), function (Builder $query) use ($request) {' . PHP_EOL .
                        '        $query->whereDate(\'%1$s\', $request->get(\'%1$s\'));' . PHP_EOL .
                        '    })',
                        $column->COLUMN_NAME
                    );
                }

                return sprintf(
                    '->when($request->get(\'%1$s\'), function (Builder $query) use ($request) {' . PHP_EOL .
                    '        $query->where(\'%1$s\', \'like\', \'%%\' . $request->get(\'%1$s\') . \'%%\');' . PHP_EOL .
                    '    })',
                    $column->COLUMN_NAME
                );
            }
        );

        if ($this->option('sortable')) {
            $columns->add(
                '->when($request->get(\'sort\') && $request->has(\'order\'), function (Builder $query) use ($request) {' . PHP_EOL .
                '        $query->orderBy($request->get(\'sort\'), $request->get(\'order\'))' . PHP_EOL .
                '            ->when($request->get(\'sort\') !== $this->primaryKey, function (Builder $query) {' . PHP_EOL .
                '                $query->orderBy($this->primaryKey, \'asc\');' . PHP_EOL .
                '            });' . PHP_EOL .
                '    })'
            );
        }

        $content = sprintf(implode(PHP_EOL, $html), $columns->implode(null));
        $this->append($content);
    }

    /**
     * Attributes
     */
    protected function attributes(): void
    {
        if ($this->getColumns()->pluck('COLUMN_NAME')->contains('name')) {
            return;
        }
        $content = [];
        $content[] = '/**';
        $content[] = ' * @return string|null';
        $content[] = ' */';
        $content[] = 'public function getNameAttribute()';
        $content[] = '{';
        $content[] = "    return \$this->title;";
        $content[] = '}';
        $this->append(implode("\n", $content));
    }

    /**
     * BelongsTo
     */
    protected function belongsTo(): void
    {
        if (is_null($this->table)) {
            return;
        }
        $this->table->relations['belongs_to']->reject(
            function ($table) {
                return in_array($table['relation_name'], config('make_crud.columns.author'), true);
            }
        )->each(
            function ($belongsTo) {
                $content = [];
                $content[] = '/**';
                $content[] = ' * ' . $belongsTo['comment'];
                $content[] = ' * @return BelongsTo';
                $content[] = ' */';

                $content[] = "public function " . $belongsTo['relation_name'] . '(): BelongsTo';
                $content[] = '{';

                $return = (Str::snake($belongsTo['related_model']) . '_id' !== $belongsTo['ownColumn']) ?
                    "    return \$this->belongsTo({$belongsTo['related_model']}::class, '{$belongsTo['ownColumn']}')" :
                    "    return \$this->belongsTo({$belongsTo['related_model']}::class)";
                $return .= ($belongsTo['nullable']) ? "->withDefault();" : ";";

                $content[] = $return;
                $content[] = '}';

                $this->append(implode("\n", $content));
            }
        );
    }

    /**
     * HasMany
     */
    protected function hasMany(): void
    {
        $this->table->relations['has_many']->reject(
            function ($hasMany) {
                return in_array($hasMany['otherColumn'], config('make_crud.columns.author'), true);
            }
        )->each(
            function ($hasMany) {
                $content = [];
                $content[] = '/**';
                $content[] = ' * ' . $hasMany['comment'];
                $content[] = ' * @return HasMany';
                $content[] = ' */';
                $content[] = "public function " . $hasMany['relation_name'] . '(): HasMany';
                $content[] = '{';
                $content[] = "    return \$this->hasMany({$hasMany['related_model']}::class);";
                $content[] = '}';
                $this->append(implode("\n", $content));
            }
        );
    }

    /**
     * belongsToMany
     */
    protected function belongsToMany(): void
    {
        $this->table->relations['belongs_to_many']->each(
            function ($belongs_to_many) {
                $content = [];
                $content[] = '/**';
                $content[] = ' * ' . $belongs_to_many['comment'];
                $content[] = ' * @return BelongsToMany';
                $content[] = ' */';
                $content[] = "public function " . $belongs_to_many['relation_name'] . '(): BelongsToMany';
                $content[] = '{';
                $content[] = "    return \$this->belongsToMany(" . $belongs_to_many['related_model'] . "::class);";
                $content[] = '}';
                $this->append(implode("\n", $content));
            }
        );
    }

    /**
     * @param string|null $content
     */
    protected function append(string $content = null): void
    {
        $indent = '    ';
        $lines = array_map(
            static function ($line) use ($indent) {
                return $indent . $line;
            },
            explode("\n", $content)
        );

        $this->content = trim($this->content);
        $this->content = trim($this->content, '}');
        $this->content .= "\n";
        foreach ($lines as $line) {
            $this->content .= $line . "\n";
        }
        $this->content .= "}\n";
    }
}
