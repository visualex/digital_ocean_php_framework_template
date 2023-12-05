<?php

namespace Muffin\Webservice;

/**
 * Represents a single endpoint in a database schema.
 *
 * Can either be populated using the reflection API's
 * or by incrementally building an instance using
 * methods.
 */
class Schema
{

    /**
     * The name of the endpoint
     *
     * @var string
     */
    protected $_repository;

    /**
     * Columns in the endpoint.
     *
     * @var array
     */
    protected $_columns = [];

    /**
     * A map with columns to types
     *
     * @var array
     */
    protected $_typeMap = [];

    /**
     * Indexes in the endpoint.
     *
     * @var array
     */
    protected $_indexes = [];

    /**
     * Constraints in the endpoint.
     *
     * @var array
     */
    protected $_constraints = [];

    /**
     * Options for the endpoint.
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Whether or not the endpoint is temporary
     *
     * @var bool
     */
    protected $_temporary = false;

    /**
     * The valid keys that can be used in a column
     * definition.
     *
     * @var array
     */
    protected static $_columnKeys = [
        'type' => null,
        'baseType' => null,
        'length' => null,
        'precision' => null,
        'null' => null,
        'default' => null,
        'comment' => null,
        'primaryKey' => null
    ];

    /**
     * Additional type specific properties.
     *
     * @var array
     */
    protected static $_columnExtras = [
        'string' => [
            'fixed' => null,
        ],
        'integer' => [
            'unsigned' => null,
            'autoIncrement' => null,
        ],
        'biginteger' => [
            'unsigned' => null,
            'autoIncrement' => null,
        ],
        'decimal' => [
            'unsigned' => null,
        ],
        'float' => [
            'unsigned' => null,
        ],
    ];

    /**
     * Constructor.
     *
     * @param string $endpoint The endpoint name.
     * @param array $columns The list of columns for the schema.
     */
    public function __construct($endpoint, array $columns = [])
    {
        $this->_repository = $endpoint;
        foreach ($columns as $field => $definition) {
            $this->addColumn($field, $definition);
        }
    }

    /**
     * Get the name of the endpoint.
     *
     * @return string
     */
    public function name()
    {
        return $this->_repository;
    }

    /**
     * Add a column to the endpoint.
     *
     * ### Attributes
     *
     * Columns can have several attributes:
     *
     * - `type` The type of the column. This should be
     *   one of CakePHP's abstract types.
     * - `length` The length of the column.
     * - `precision` The number of decimal places to store
     *   for float and decimal types.
     * - `default` The default value of the column.
     * - `null` Whether or not the column can hold nulls.
     * - `fixed` Whether or not the column is a fixed length column.
     *   This is only present/valid with string columns.
     * - `unsigned` Whether or not the column is an unsigned column.
     * - `primaryKey` Whether or not the column is a primary key.
     *   This is only present/valid for integer, decimal, float columns.
     *
     * In addition to the above keys, the following keys are
     * implemented in some database dialects, but not all:
     *
     * - `comment` The comment for the column.
     *
     * @param string $name The name of the column
     * @param array $attrs The attributes for the column.
     * @return $this
     */
    public function addColumn($name, $attrs)
    {
        if (is_string($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $valid = static::$_columnKeys;
        if (isset(static::$_columnExtras[$attrs['type']])) {
            $valid += static::$_columnExtras[$attrs['type']];
        }
        $attrs = array_intersect_key($attrs, $valid);
        $this->_columns[$name] = $attrs + $valid;
        $this->_typeMap[$name] = $this->_columns[$name]['type'];

        return $this;
    }

    /**
     * Get the column names in the endpoint.
     *
     * @return array
     */
    public function columns()
    {
        return array_keys($this->_columns);
    }

    /**
     * Get column data in the endpoint.
     *
     * @param string $name The column name.
     * @return array|null Column data or null.
     */
    public function column($name)
    {
        if (!isset($this->_columns[$name])) {
            return null;
        }
        $column = $this->_columns[$name];
        unset($column['baseType']);

        return $column;
    }

    /**
     * Sets the type of a column, or returns its current type
     * if none is passed.
     *
     * @param string $name The column to get the type of.
     * @param string $type The type to set the column to.
     * @return string|null Either the column type or null.
     */
    public function columnType($name, $type = null)
    {
        if (!isset($this->_columns[$name])) {
            return null;
        }
        if ($type !== null) {
            $this->_columns[$name]['type'] = $type;
            $this->_typeMap[$name] = $type;
        }

        return $this->_columns[$name]['type'];
    }

    /**
     * Returns the base type name for the provided column.
     * This represent the schema type a more complex class is
     * based upon.
     *
     * @param string $column The column name to get the base type from
     * @return string The base type name
     */
    public function baseColumnType($column)
    {
        if (isset($this->_columns[$column]['baseType'])) {
            return $this->_columns[$column]['baseType'];
        }

        $type = $this->columnType($column);

        if ($type === null) {
            return null;
        }

        if (Type::map($type)) {
            $type = Type::build($type)->getBaseType();
        }

        return $this->_columns[$column]['baseType'] = $type;
    }

    /**
     * Returns an array where the keys are the column names in the schema
     * and the values the schema type they have.
     *
     * @return array
     */
    public function typeMap()
    {
        return $this->_typeMap;
    }

    /**
     * Check whether or not a field is nullable
     *
     * Missing columns are nullable.
     *
     * @param string $name The column to get the type of.
     * @return bool Whether or not the field is nullable.
     */
    public function isNullable($name)
    {
        if (!isset($this->_columns[$name])) {
            return true;
        }

        return ($this->_columns[$name]['null'] === true);
    }

    /**
     * Get a hash of columns and their default values.
     *
     * @return array
     */
    public function defaultValues()
    {
        $defaults = [];
        foreach ($this->_columns as $name => $data) {
            if (!array_key_exists('default', $data)) {
                continue;
            }
            if ($data['default'] === null && $data['null'] !== true) {
                continue;
            }
            $defaults[$name] = $data['default'];
        }

        return $defaults;
    }

    /**
     * Get the column(s) used for the primary key.
     *
     * @return array Column name(s) for the primary key. An
     *   empty list will be returned when the endpoint has no primary key.
     */
    public function primaryKey()
    {
        $primaryKeys = [];
        foreach ($this->_columns as $name => $data) {
            if ((!array_key_exists('primaryKey', $data)) || ($data['primaryKey'] !== true)) {
                continue;
            }

            $primaryKeys[] = $name;
        }

        return $primaryKeys;
    }

    /**
     * Get/set the options for a endpoint.
     *
     * Endpoint options allow you to set platform specific endpoint level options.
     *
     * @param array|null $options The options to set, or null to read options.
     * @return $this|array Either the endpoint instance, or an array of options when reading.
     */
    public function options($options = null)
    {
        if ($options === null) {
            return $this->_options;
        }
        $this->_options = array_merge($this->_options, $options);

        return $this;
    }
}
