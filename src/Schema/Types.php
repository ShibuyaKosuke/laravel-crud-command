<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Schema;

class Types
{
    /**
     * @var string default database
     */
    private static $database;

    /**
     * @var array[]
     */
    private static $dataTypes = [
        'mysql' => [
            'int' => 'int',
            'bigint' => 'int',
            'smallint' => 'int',
            'tinyint' => 'int',
            'float' => 'float',
            'decimal' => 'int',
            'double' => 'double',
            'numeric' => 'double',
            'char' => 'string',
            'varchar' => 'string',
            'tinytext' => 'string',
            'mediumtext' => 'string',
            'longtext' => 'string',
            'text' => 'string',
            'tinyblob' => 'string',
            'mediumblob' => 'string',
            'longblob' => 'string',
            'blob' => 'string',
            'date' => 'Carbon',
            'year' => 'int',
            'datetime' => 'Carbon',
            'timestamp' => 'Carbon',
            'geometry' => false,
            'point' => false,
            'linestring' => false,
            'polygon' => false,
            'multipoint' => false,
            'multilinestring' => false,
            'multipolygon' => false,
            'geometrycollection' => false
        ],
        'pgsql' => [
            'int' => 'int',
            'bigint' => 'int',
            'smallint' => 'int',
            'tinyint' => 'int',
            'float' => 'float',
            'decimal' => 'int',
            'double' => 'double',
            'numeric' => 'double',
            'char' => 'string',
            'character varying' => 'string',
            'varchar' => 'string',
            'tinytext' => 'string',
            'mediumtext' => 'string',
            'longtext' => 'string',
            'text' => 'string',
            'tinyblob' => 'string',
            'mediumblob' => 'string',
            'longblob' => 'string',
            'blob' => 'string',
            'date' => 'Carbon',
            'year' => 'int',
            'timestamp without time zone' => 'Carbon',
            'datetime' => 'Carbon',
            'timestamp' => 'Carbon',
            'geometry' => false,
            'point' => false,
            'linestring' => false,
            'polygon' => false,
            'multipoint' => false,
            'multilinestring' => false,
            'multipolygon' => false,
            'geometrycollection' => false
        ]
    ];

    private static function getDefaultDatabase()
    {
        return config('database.default');
    }

    /**
     * @return string[]
     */
    public static function all()
    {
        return self::$dataTypes[self::getDefaultDatabase()];
    }

    /**
     * @return array
     */
    public static function dataTypes()
    {
        return array_keys(self::all());
    }

    /**
     * @param string $dbType
     * @return string
     * @throws \Exception
     */
    public static function convertDataType(string $dbType)
    {
        $types = static::all();
        if (array_key_exists($dbType, $types) && $types[$dbType]) {
            return $types[$dbType];
        }
        throw new \Exception(sprintf('Data type is not supported.: %s', $dbType));
    }
}
