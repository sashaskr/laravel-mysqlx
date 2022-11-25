<?php

namespace Sashaskr\Mysqlx\Schema;

use Closure;

class Builder extends \Illuminate\Database\Schema\Builder
{
    /**
     * @inheritdoc
     */
    public function hasColumn($table, $column)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasColumns($table, array $columns)
    {
        return true;
    }

    /**
     * Determine if the given collection exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasCollection($name)
    {
        $db = $this->connection->getMysqlxSchema();

        $collections = iterator_to_array(
            $db->getCollections([
            'filter' => [
                'name' => $name,
            ],
        ]), false
        );

        return (bool)count($collections);
    }

    /**
     * @inheritdoc
     */
    public function hasTable($collection)
    {
        return $this->hasCollection($collection);
    }

    public function collection($collection, Closure $callback)
    {
        $blueprint = $this->createBlueprint($collection);

        if ($callback) {
            $callback($blueprint);
        }
    }

    /**
     * @inheritdoc
     */
    public function table($collection, Closure $callback)
    {
        return $this->collection($collection, $callback);
    }

    public function create($collection, Closure $callback = null, array $options = [])
    {
        $blueprint = $this->createBlueprint($collection);

        $blueprint->create($options);

        if ($callback) {
            $callback($blueprint);
        }
    }

    public function dropIfExists($collection)
    {
        if ($this->hasCollection($collection)) {
            return $this->drop($collection);
        }

        return false;
    }

    public function drop($collection)
    {
        return $this->createBlueprint($collection)->drop();
    }

    protected function createBlueprint($collection, Closure $callback = null)
    {
        return new Blueprint($this->connection, $collection);
    }

    public function dropAllTables()
    {
        foreach ($this->getAllCollections() as $collection) {
            $this->drop($collection);
        }
    }

    public function getCollection($name)
    {
        $db = $this->connection->getMysqlxSchema();

        $collections = iterator_to_array(
            $db->getCollections([
                'filter' => [
                    'name' => $name,
                ],
            ]), false
        );

        return count($collections) ? current($collections) : false;
    }

    protected function getAllCollections()
    {
        $collections = [];
        foreach ($this->connection->getMysqlxSchema()->getCollections() as $collection) {
            $collections[] = $collection->getName();
        }

        return $collections;
    }

    public function getAllTables()
    {
        return $this->getAllCollections();
    }
}
