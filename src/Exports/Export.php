<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

/**
 * Class Export
 * @package ShibuyaKosuke\LaravelCrudCommand\Exports
 */
class Export implements FromView, WithCustomCsvSettings
{
    use \Maatwebsite\Excel\Concerns\Exportable;

    /**
     * @var Model[]|Collection
     */
    private $models;

    /**
     * Exportable constructor.
     * @param Model[]|Collection $models
     */
    public function __construct(Collection $models)
    {
        $this->models = $models;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        $table_name = $this->models->first()->getTable();
        ${$table_name} = $this->models;
        return \view(sprintf('%s.table', $table_name), compact($table_name));
    }

    /**
     * @return array
     */
    public function getCsvSettings(): array
    {
        return [
            'use_bom' => config('make_crud.use_bom')
        ];
    }
}
