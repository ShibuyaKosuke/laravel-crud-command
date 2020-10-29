<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Exports;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Interface Exporter
 * @package ShibuyaKosuke\LaravelCrudCommand\Exports
 */
interface Exporter
{
    public function getModels(): Builder;

    public function export(string $fileType): BinaryFileResponse;
}
