<?php

namespace Sashaskr\Mysqlx\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    protected $passthru = [
        'average',
        'avg',
        'count',
        'dd',
        'doesntExist',
        'dump',
        'exists',
        'getBindings',
        'getConnection',
        'getGrammar',
        'insert',
        'insertGetId',
        'insertOrIgnore',
        'insertUsing',
        'max',
        'min',
        'pluck',
        'pull',
        'push',
        'raw',
        'sum',
        'toSql',
    ];

    public function insert(array $values)
    {
        return parent::insert($values);
    }

    public function getConnection()
    {
        return $this->query->getConnection();
    }

    public function raw($expression = null)
    {
        $results = $this->query->raw($expression);
        return $results;
    }

    public function insertGetId(array $values, $sequence = null)
    {
        // Intercept operations on embedded models and delegate logic
        // to the parent relation instance.
//        if ($relation = $this->model->getParentRelation()) {
//            $relation->performInsert($this->model, $values);
//
//            return $this->model->getKey();
//        }

        return parent::insertGetId($values, $sequence);
    }

    public function update(array $values, array $options = [])
    {
        return $this->toBase()->update($this->addUpdatedAtColumn($values), $options);
    }

    protected function addUpdatedAtColumn(array $values)
    {
        if (! $this->model->usesTimestamps() || $this->model->getUpdatedAtColumn() === null) {
            return $values;
        }

        $column = $this->model->getUpdatedAtColumn();
        $values = array_merge(
            [$column => $this->model->freshTimestampString()],
            $values
        );

        return $values;
    }
}
