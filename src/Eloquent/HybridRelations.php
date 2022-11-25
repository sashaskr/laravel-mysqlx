<?php

namespace Sashaskr\Mysqlx\Eloquent;

use Sashaskr\Mysqlx\Relations\HasOne;

trait HybridRelations
{
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        if (! is_subclass_of($related, Model::class)) {
            return parent::hasOne($related, $foreignKey, $localKey);
        }
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $instance = new $related;

        $localKey = $localKey ?: $this->getKeyName();

        return new HasOne($instance->newQuery(), $this, $foreignKey, $localKey);
    }
}
