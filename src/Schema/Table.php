<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Schema;

use Illuminate\Database\Eloquent\Relations\HasMany;
use ShibuyaKosuke\LaravelDatabaseUtilities\Models\Table as TableBase;
use function config;

/**
 * Class Table
 * @package ShibuyaKosuke\LaravelCrudCommand\Schema
 */
class Table extends TableBase
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->loadConfig();
    }

    public static function columnName($name)
    {
        if (config('database.default') === 'pgsql') {
            return strtolower($name);
        }
        return $name;
    }

    public static function getByName(string $table_name)
    {
        return self::query()->with(['columns'])->where(self::columnName('TABLE_NAME'), $table_name)->first();
    }

    public static function getTablesHavingComment()
    {
        return self::query()->where(self::columnName('TABLE_COMMENT'), '!=', '')->get();
    }

    private function loadConfig(): void
    {
        $this->config = config('make_crud.columns');
    }

    public function getColumnsWithoutTimestamps()
    {
        return $this->columns->reject(
            function ($column) {
                return in_array($column->COLUMN_NAME, $this->config['timestamps'], true);
            }
        );
    }

    public function getColumnsWithoutSoftDeletes()
    {
        return $this->columns->reject(
            function ($column) {
                return in_array($column->COLUMN_NAME, $this->config['soft_delete'], true);
            }
        );
    }

    public function getColumnsWithoutAuthor()
    {
        return $this->columns->reject(
            function ($column) {
                return in_array($column->COLUMN_NAME, $this->config['author'], true);
            }
        );
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class, self::columnName('TABLE_NAME'), self::columnName('TABLE_NAME'));
    }
}
