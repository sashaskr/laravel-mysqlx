<?php

namespace Sashaskr\Mysqlx\Relations;

use Sashaskr\Mysqlx\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as EloquentModel;

abstract class EmbedsOneOrMany extends Relation
{
    /**
     * The local key of the parent model.
     *
     * @var string
     */
    protected $localKey;

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The "name" of the relationship.
     *
     * @var string
     */
    protected $relation;

    public function __construct(Builder $query, Model $parent, Model $related, $localKey, $foreignKey, $relation)
    {
        $this->query = $query;
        $this->parent = $parent;
        $this->related = $related;
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;
        $this->relation = $relation;

        if ($parentRelation = $this->getParentRelation()) {
            $this->query = $parentRelation->getQuery();
        }

        $this->addConstraints();
    }

    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->where($this->getQualifiedParentKeyName(), '=', $this->getParentKey());
        }
    }

    public function addEagerConstraints(array $models)
    {
        // There are no eager loading constraints.
    }

    public function match(array $models, Collection $results, $relation)
    {
        foreach ($models as $model) {
            $results = $model->$relation()->getResults();

            $model->setParentRelation($this);

            $model->setRelation($relation, $results);
        }

        return $models;
    }

    public function get($columns = ['*'])
    {
        return $this->getResults();
    }

    public function count()
    {
        return count($this->getEmbedded());
    }

    public function save(Model $model)
    {
        $model->setParentRelation($this);

        return $model->save() ? $model : false;
    }
}
