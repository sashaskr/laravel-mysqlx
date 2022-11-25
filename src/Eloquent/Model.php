<?php

namespace Sashaskr\Mysqlx\Eloquent;

use Sashaskr\Mysqlx\Connection;
use Sashaskr\Mysqlx\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class Model extends BaseModel
{
    use HybridRelations;

    /**
     * The default name of connection to handle the x Protocol
     *
     * @var string
     */
    protected $connection = 'mysqlx';

    /**
     * The collection associated with the model.
     *
     * @var string
     */
    protected $collection;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = '_id';

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    public function getIdAttribute($value = null)
    {
        if (! $value && array_key_exists('_id', $this->attributes)) {
            $value = $this->attributes['_id'];
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getTable()
    {
        return $this->collection ?: parent::getTable();
    }

    /**
     * @inheritdoc
     */
    public function getAttribute($key)
    {
        if (!$key) {
            return;
        }

        // Dot notation support.
        if (Str::contains($key, '.') && Arr::has($this->attributes, $key)) {
            return $this->getAttributeValue($key);
        }

        // This checks for embedded relation support.
        if (
            method_exists($this, $key)
            && ! method_exists(self::class, $key)
            && ! $this->hasAttributeGetMutator($key)
        ) {
            return $this->getRelationValue($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * @inheritdoc
     */
    protected function getAttributeFromArray($key)
    {
        // Support keys in dot notation.
        if (Str::contains($key, '.')) {
            return Arr::get($this->attributes, $key);
        }

        return parent::getAttributeFromArray($key);
    }

    /**
     * @inheritdoc
     */
    public function setAttribute($key, $value)
    {
        // Convert _id to ObjectID.
        if ($key === '_id' && is_string($value)) {
            $builder = $this->newBaseQueryBuilder();

            $value = $builder->convertKey($value);
        } // Support keys in dot notation.
        elseif (Str::contains($key, '.')) {
            if (in_array($key, $this->getDates()) && $value) {
                $value = $this->fromDateTime($value);
            }

            Arr::set($this->attributes, $key, $value);

            return;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new QueryBuilder($connection, $connection->getPostProcessor());
    }

    public function getForeignKey()
    {
        return Str::snake(class_basename($this)).'_'.ltrim($this->primaryKey, '_');
    }

    public function setParentRelation(Relation $relation)
    {
        $this->parentRelation = $relation;
    }

    public function getParentRelation()
    {
        return $this->parentRelation;
    }
}
