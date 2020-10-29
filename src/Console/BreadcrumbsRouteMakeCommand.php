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

    public function init()
    {
        $this->file = base_path("routes/breadcrumbs.php");
    }

    public function handle(): void
    {
        $this->init();

        /** @var Table $table */
        $table = $this->argument('table');

        $contents = $this->getFile();

        $append = $this->getCode($table);

        File::append($this->file, $append);
    }

    private function getFile()
    {
        if (!File::exists($this->file)) {
            File::put($this->file, "<?php\n");
        }
        return File::get($this->file);
    }

    private function getCode(Table $table)
    {
        $searches = [
            '{{ model }}',
            '{{ tables }}',
            '{{ table }}',
        ];
        $replaces = [
            $table->model_name,
            $table->TABLE_NAME,
            Str::singular($table->TABLE_NAME)
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
            $lines[] = 'Breadcrumbs::for(\'{{ tables }}.index\', function ($trail) {';
            $lines[] = '    $trail->parent(\'home\');';
            $lines[] = '    $trail->add(trans(\'tables.{{ tables }}\'), route(\'{{ tables }}.index\'));';
            $lines[] = '});';
            $lines[] = '';
        }

        if (!Breadcrumbs::has("{$table->TABLE_NAME}.create")) {
            $lines[] = 'Breadcrumbs::for(\'{{ tables }}.create\', function ($trail) {';
            $lines[] = '    $trail->parent(\'{{ tables }}.index\');';
            $lines[] = '    $trail->add(trans(\'pages.create\'), route(\'{{ tables }}.create\'));';
            $lines[] = '});';
            $lines[] = '';
        }

        if (!Breadcrumbs::has("{$table->TABLE_NAME}.show")) {
            $lines[] = 'Breadcrumbs::for(\'{{ tables }}.show\', function ($trail, ${{ table }}) {';
            $lines[] = '    $trail->parent(\'{{ tables }}.index\');';
            if ($table->columns->pluck('COLUMN_NAME')->contains('name')) {
                $lines[] = '    $trail->add(${{ table }}->name, route(\'{{ tables }}.show\', ${{ table }}));';
            } else {
                $lines[] = sprintf('    $trail->add(\'%s\' . ${{ table }}->id, route(\'{{ tables }}.show\', ${{ table }}));', $table->model_name);
            }
            $lines[] = '});';
            $lines[] = '';
        }

        if (!Breadcrumbs::has("{$table->TABLE_NAME}.edit")) {
            $lines[] = 'Breadcrumbs::for(\'{{ tables }}.edit\', function ($trail, ${{ table }}) {';
            $lines[] = '    $trail->parent(\'{{ tables }}.show\', ${{ table }});';
            $lines[] = '    $trail->add(trans(\'pages.edit\'), route(\'{{ tables }}.edit\', ${{ table }}));';
            $lines[] = '});';
            $lines[] = '';
        }

        return str_replace($searches, $replaces, implode(PHP_EOL, $lines));
    }
}
