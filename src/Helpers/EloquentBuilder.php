<?php

namespace Sashaskr\Mysqlx\Helpers;

use Illuminate\Database\Eloquent\Builder;

class EloquentBuilder extends Builder
{
    use QueriesRelationships;
}
