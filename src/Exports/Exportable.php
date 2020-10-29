<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Exports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Trait Exportable
 * @package ShibuyaKosuke\LaravelCrudCommand\Exports
 */
trait Exportable
{
    /**
     * @param string $fileType csv|xlsx|pdf
     * @return BinaryFileResponse
     */
    public function export(string $fileType): BinaryFileResponse
    {
        /** @var Model[]|Collection $models */
        $models = $this->getModels()->get();
        $fileName = sprintf('%s.%s', $models->first()->getTable(), $fileType);
        return (new Export($models))->download($fileName);
    }
}
