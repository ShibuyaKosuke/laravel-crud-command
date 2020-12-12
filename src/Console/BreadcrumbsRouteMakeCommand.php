<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ShibuyaKosuke\LaravelCrudBreadcrumbs\Facades\Breadcrumbs;
use ShibuyaKosuke\LaravelCrudCommand\Schema\Table;

class BreadcrumbsRouteMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:breadcrumbs {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate files for breadcrumbs.';

    /**
     * @var string
     */
    protected $file;

    /**
     * @return void
     */
    public function init(): void
    {
        $this->file = base_path("routes/breadcrumbs.php");
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->init();

        /** @var Table $table */
        $table = $this->argument('table');

        $contents = $this->getFile();

        $append = $this->getCode($table);

        File::append($this->file, $append);
    }

    /**
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function getFile(): string
    {
        if (!File::exists($this->file)) {
            File::put($this->file, "<?php\n");
        }
        return File::get($this->file);
    }

    /**
     * @param Table $table
     * @return string|string[]
     */
    private function getCode(Table $table)
    {
        $searches = [
            '{{ model }}',
            '{{ tables }}',
            '{{ table }}',
            '{{ route }}',
        ];
        $replaces = [
            $table->model_name,
            $table->TABLE_NAME,
            Str::singular($table->TABLE_NAME),
            Str::kebab($table->TABLE_NAME)
        ];

        $lines = [];
        $lines[] = '';

        if (!Breadcrumbs::has("home")) {
            $lines[] = 'Breadcrumbs::for(\'home\', function ($trail) {';
            $lines[] = '    $trail->add(trans(\'home\'), route(\'home\'));';
            $lines[] = '});';
            $lines[] = '';
        }

        if (!Breadcrumbs::has("{$table->TABLE_NAME}.index")) {
            $lines[] = 'Breadcrumbs::for(\'{{ route }}.index\', function ($trail) {';
            $lines[] = '    $trail->parent(\'home\');';
            $lines[] = '    $trail->add(trans(\'tables.{{ tables }}\'), route(\'{{ route }}.index\'));';
            $lines[] = '});';
            $lines[] = '';
        }

        if (!Breadcrumbs::has("{$table->TABLE_NAME}.create")) {
            $lines[] = 'Breadcrumbs::for(\'{{ route }}.create\', function ($trail) {';
            $lines[] = '    $trail->parent(\'{{ route }}.index\');';
            $lines[] = '    $trail->add(trans(\'pages.create\'), route(\'{{ route }}.create\'));';
            $lines[] = '});';
            $lines[] = '';
        }

        if (!Breadcrumbs::has("{$table->TABLE_NAME}.show")) {
            $lines[] = 'Breadcrumbs::for(\'{{ route }}.show\', function ($trail, ${{ table }}) {';
            $lines[] = '    $trail->parent(\'{{ route }}.index\');';
            if ($table->columns->pluck('COLUMN_NAME')->contains('name')) {
                $lines[] = '    $trail->add(${{ table }}->name, route(\'{{ route }}.show\', ${{ table }}));';
            } else {
                $lines[] = sprintf('    $trail->add(\'%s\' . ${{ table }}->id, route(\'{{ route }}.show\', ${{ table }}));', $table->model_name);
            }
            $lines[] = '});';
            $lines[] = '';
        }

        if (!Breadcrumbs::has("{$table->TABLE_NAME}.edit")) {
            $lines[] = 'Breadcrumbs::for(\'{{ route }}.edit\', function ($trail, ${{ table }}) {';
            $lines[] = '    $trail->parent(\'{{ route }}.show\', ${{ table }});';
            $lines[] = '    $trail->add(trans(\'pages.edit\'), route(\'{{ route }}.edit\', ${{ table }}));';
            $lines[] = '});';
            $lines[] = '';
        }

        return str_replace($searches, $replaces, implode(PHP_EOL, $lines));
    }
}
