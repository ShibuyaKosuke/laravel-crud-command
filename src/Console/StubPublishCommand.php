<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use ShibuyaKosuke\LaravelCrudCommand\Facades\Stub;

/**
 * Class StubPublishCommand
 * @package ShibuyaKosuke\LaravelCrudCommand\Console
 */
class StubPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stub:publish {--force : Overwrite any existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all stubs that are available for customization';

    /**
     * Get stub file names
     * @return Collection|array[]
     */
    private function getFiles(): Collection
    {
        if (!is_dir($stubsPath = $this->laravel->basePath('stubs'))) {
            (new Filesystem())->makeDirectory($stubsPath);
        }

        $files = [];
        foreach (Stub::mergedStubs() as $file) {
            $item = collect();
            $item->put('from', $file);
            $item->put('to', $stubsPath . '/' . basename($file));
            $files[basename($file)] = $item;
        }
        return collect(array_values($files));
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->getFiles()->each(
            function ($item) {
                if (!file_exists($item['to']) || $this->option('force')) {
                    copy($item['from'], $item['to']);
                }
            }
        );

        $this->info('Stubs published successfully.');
    }
}
