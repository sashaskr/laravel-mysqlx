<?php

namespace Sashaskr\Mysqlx\Schema;

use Sashaskr\Mysqlx\Connection;
use Illuminate\Support\Arr;
use mysql_xdevapi\Collection;

class Blueprint extends \Illuminate\Database\Schema\Blueprint
{
    /** @var Connection */
    protected $connection;

    /** @var Collection */
    protected $collection;

    /** @var array  */
    protected $columns = [];

    public function __construct(Connection $connection, $collection)
    {
        $this->connection = $connection;

        $this->collection = $this->connection->getCollection($collection);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __call($method, $args)
    {
        // Dummy.
        return $this;
    }

    public function create()
    {
        $collection = $this->collection->getName();

        $db = $this->connection->getMysqlxSchema();

        // Ensure the collection is created.
        $db->createCollection($collection);
    }

    // TODO: finish it according the php.net
    public function index($columns = null, $name = null, $options = [])
    {
        return $this->createIndex($columns, $name, $options);
    }

    private function createIndex($columns = null, $name = null, $options = [], $unique = false)
    {
        $columns = $this->fluent($columns);

        if (is_array($columns) && is_int(key($columns))) {
            $transform = [];

            foreach ($columns as $column) {
                $transform[$column] = 1;
            }

            $columns = $transform;
        }

        // ['col1' => [], 'col2' => [],]

        // Check in mysql provider how they handle index types
        // [['field' => 'name', 'type' => 'INTEGER', 'required' => true(false) ]]
        $fields = [];

        foreach ($options as $fieldName => $option) {
            $field = [
                'field' => sprintf('$.%s', is_int($fieldName) ? Arr::get($option, 'field') : $fieldName),
                'type' => Arr::get($option, 'type'),
                'required' => (bool)Arr::get($option, 'required', false),
            ];

            $fields[] = $field;
        }

        $index = [];
        $index['fields'] = $fields;
        $index['unique'] = $unique;
        $this->collection->createIndex($name, json_encode($index, JSON_THROW_ON_ERROR));

        return $this;
    }

    public function dropIndex($index)
    {
        $this->collection->dropIndex($index);
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param $columns
     * @param $name
     * @param $options
     * @return $this|\Illuminate\Database\Schema\IndexDefinition|\Illuminate\Support\Fluent|m.\Sashaskr\Mysqlx\Schema\Blueprint.createIndex
     * @throws \JsonException
     */
    public function unique($columns = null, $name = null, $options = [])
    {
        return $this->createIndex($columns, $name, $options, true);
    }

    protected function fluent($columns = null)
    {
        if ($columns === null) {
            return $this->columns;
        }

        if (is_string($columns)) {
            return $this->columns = [$columns];
        }

        return $this->columns = $columns;
    }

    public function hasIndex($name)
    {
        return (bool)$this->getIndex($name);
    }

    public function getIndex($name)
    {
        $db = $this->connection->getMysqlxSchema();

        $sql = sprintf(
            'SHOW INDEX FROM %s.%s;',
            $db->getName(),
            $this->collection->getName()
        );

        foreach ($this->connection->sql($sql) as $index) {
            if ($index['Key_name'] === $name) {
                return $index;
            }
        }
        return false;
    }
}
