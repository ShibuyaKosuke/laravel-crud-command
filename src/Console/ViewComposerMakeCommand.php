<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ShibuyaKosuke\LaravelCrudCommand\Facades\Stub;
use ShibuyaKosuke\LaravelCrudCommand\Schema\Table;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ViewComposerMakeCommand
 * @package ShibuyaKosuke\LaravelCrudCommand\Console
 */
class ViewComposerMakeCommand extends GeneratorCommand
{
    protected $name = 'crud:view-composer';

    protected $description = 'Create a new View composer class for CRUD.';

    protected $type = 'ViewComposer';

    private $content;

    /**
     * @return string
     */
    protected function getStub()
    {
        return Stub::findStub('view.composer.stub');
    }

    /**
     * @return bool|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        if (
            (!$this->hasOption('force') ||
                !$this->option('force')) &&
            $this->alreadyExists($this->getNameInput())
        ) {
            $this->error($this->type . ' already exists!');
            return false;
        }

        $this->makeDirectory($path);

        $this->content = $this->sortImports($this->buildClass($name));

        $this->uses();
        $this->belongsTo();

        $this->files->put($path, $this->content);

        $this->config();

        $this->info($this->type . ' created successfully.');
    }

    protected function uses()
    {
        /** @var Collection $tables */
        $tables = Table::getByName($this->option('table'))
            ->relations['belongs_to'];

        if ($tables->count()) {
            $replacement = $tables->unique('related_model')->reject(
                function ($table) {
                    return in_array($table['relation_name'], ['createdBy', 'updatedBy', 'deletedBy', 'restoredNy']);
                }
            )->map(
                function ($table) {
                    return sprintf('use App\\Models\\%s;', $table['related_model']);
                }
            );
            $replacement->push("\n");
        } else {
            $replacement = collect();
        }

        $this->content = str_replace("{{ models }}\n", $replacement->implode(PHP_EOL), $this->content);
    }

    /**
     * @return void
     */
    protected function belongsTo()
    {
        $tables = Table::getByName($this->option('table'))
            ->relations['belongs_to'];

        $content = collect();
        $content->push('public function compose(View $view)');
        $content->push('{');
        $content->push('    $params = $this->request->query();');

        if ($tables->count()) {
            $tables->reject(
                function ($table) {
                    return in_array($table['relation_name'], ['createdBy', 'updatedBy', 'deletedBy', 'restoredNy']);
                }
            )->each(
                function ($table) use ($content) {
                    if (config('make_crud.use_cache')) {
                        $line = sprintf(
                            '    $%s = %s::remember(config(\'make_crud.cache_time\'))->get();',
                            Str::plural($table['relation_name']),
                            $table['related_model']
                        );
                    } else {
                        $line = sprintf(
                            '    $%s = %s::all();',
                            Str::plural($table['relation_name']),
                            $table['related_model']
                        );
                    }
                    $content->push($line);
                }
            )->implode(PHP_EOL);
        }

        $content->push(
            sprintf(
                '    $view->with(compact(%s));',
                $tables->reject(
                    function ($table) {
                        return in_array($table['relation_name'], ['createdBy', 'updatedBy', 'deletedBy', 'restoredNy']);
                    }
                )->map(
                    function ($table) {
                        return sprintf('\'%s\'', Str::plural($table['relation_name']));
                    }
                )->prepend('\'params\'')
                    ->implode(', ')
            )
        );

        $content->push('}');

        $this->append($content->implode(PHP_EOL));
    }

    /**
     * @param $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        $path = sprintf('app/Http/ViewComposers/%s.php', str_replace('App\\', '', $name));
        return $this->laravel->basePath($path);
    }

    /**
     * @param string|null $content
     */
    protected function append(string $content = null)
    {
        $indent = '    ';
        $lines = array_map(
            function ($line) use ($indent) {
                return $indent . $line;
            },
            explode("\n", $content)
        );

        $this->content = trim($this->content);
        $this->content = trim($this->content, '}');
        foreach ($lines as $line) {
            $this->content .= $line . "\n";
        }
        $this->content .= "}\n";
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function config()
    {
        $file = config_path('composers.php');
        $config = app('config');
        $table = $this->option('table');
        $model = Str::studly(Str::singular($table));
        $class = sprintf('App\Http\ViewComposers\%sComposer', $model);
        if (array_key_exists($class, $config['composers'])) {
            return;
        }
        $line = sprintf(
            '    \App\Http\ViewComposers\%1$sComposer::class => [\'%2$s.index\', \'%2$s.create\', \'%2$s.edit\'],',
            $model,
            $table
        );
        if (\File::exists($file) && $content = \File::get($file)) {
            $content = explode(PHP_EOL, trim($content));
            array_pop($content);
            array_push($content, $line);
            array_push($content, '];' . PHP_EOL);
        } else {
            $content = [];
            $content[] = '<?php';
            $content[] = '';
            $content[] = 'return [';
            $content[] = $line;
            $content[] = '];';
            $content[] = '';
        }
        $content = implode(PHP_EOL, $content);
        \File::put($file, $content);
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(
            parent::getOptions(),
            [
                ['crud', null, InputOption::VALUE_NONE, 'Generate a view composer class for crud.'],
                ['force', null, InputOption::VALUE_NONE, 'Generate a view composer class.'],
                ['with-trashed', 'c', InputOption::VALUE_NONE, 'Generate a view composer class.'],
                ['table', null, InputOption::VALUE_NONE, 'Generate a view composer class for table.']
            ]
        );
    }
}
