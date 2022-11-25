<?php

namespace Sashaskr\Mysqlx\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class BelongsTo extends \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    /**
     * Get the key for comparing against the parent key in "has" query.
     *
     * @return string
     */
    public function getHasCompareKey()
    {
        return $this->getOwnerKey();
    }

    /**
     * @inheritdoc
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->where($this->getOwnerKey(), '=', $this->parent->{$this->foreignKey});
        }
    }

    public function addEagerConstraints(array $models)
    {
        $key = $this->getOwnerKey();

        $this->query->whereIn($key, $this->getEagerModelKeys($models));
    }

    /**
     * @inheritdoc
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        return $query;
    }

    /**
     * Get the owner key with backwards compatible support.
     *
     * @return string
     */
    public function getOwnerKey()
    {
        return property_exists($this, 'ownerKey') ? $this->ownerKey : $this->otherKey;
    }

    /**
     * Get the name of the "where in" method for eager loading.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @return string
     */
    protected function whereInMethod(EloquentModel $model, $key)
    {
        return 'whereIn';
    }
}
