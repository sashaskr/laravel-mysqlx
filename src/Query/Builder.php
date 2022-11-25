<?php

namespace Sashaskr\Mysqlx\Query;

use Sashaskr\Mysqlx\Connection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use mysql_xdevapi\Collection;
use RuntimeException;
use Illuminate\Support\Collection as IlluminateCollection;

class Builder extends BaseBuilder
{
    /** @var Collection */
    protected $collection;

    public $projections;

    public $timeout;

    public $hint;

    public $options = [];

    public $paginating = false;

    public $operators = [
        '=',
        '==',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        'like',
        'not like',
        'where',
        'all',
        'exists'
    ];

    public function __construct(Connection $connection, Processor $processor = null)
    {
        $this->grammar = new Grammar;
        $this->connection = $connection;
        $this->processor = $processor;
    }

    public function project($columns)
    {
        $this->projections = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    public function timeout($seconds)
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function hint($index)
    {
        $this->hint = $index;

        return $this;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $params = func_get_args();
        if (func_num_args() === 3) {
            $operator = &$params[1];

            if (Str::startsWith($operator, '$')) {
                $operator = substr($operator, 1);
            }
        }

        return call_user_func_array('parent::where', $params);
    }

    public function find($id, $columns = [])
    {
        return $this->where('_id', '=', $id)->first();
    }

    public function value($column)
    {
        $result = (array) $this->first([$column]);

        return Arr::get($result, $column);
    }

    public function get($columns = [])
    {
        return $this->getFresh($columns);
    }

    public function insert(array $values)
    {
        $batch = true;
        foreach ($values as $value) {
            if (! is_array($value)) {
                $batch = false;
                break;
            }
        }

        if (! $batch) {
            $values = [$values];
        }

        $result = $this->collection->add($values)->execute();  //insertMany($values);

        return 1 == (int) $result->isAcknowledged();
    }

    public function from($collection, $as = null)
    {
        if ($collection) {
            $this->collection = $this->connection->getCollection($collection);
        }

        return parent::from($collection);
    }

    public function cursor($columns = [])
    {
        $result = $this->getFresh($columns, true);
        if ($result instanceof LazyCollection) {
            return $result;
        }
        throw new RuntimeException('Query not compatible with cursor');
    }

    public function getFresh($columns = [], $returnLazy = false)
    {
        if ($this->columns === null) {
            $this->columns = $columns;
        }

        if (in_array('*', $this->columns)) {
            $this->columns = [];
        }

        $wheres = $this->compileWheres();

        $options = [];

        // Convert select columns to simple projections.
        foreach ($this->columns as $column) {
            $columns[$column] = true;
        }

        if ($this->orders) {
            $options['sort'] = $this->orders;
        }
        if ($this->offset) {
            $options['skip'] = $this->offset;
        }
        if ($this->limit) {
            $options['limit'] = $this->limit;
        }
        $options['typeMap'] = ['root' => 'array', 'document' => 'array'];

        if (count($this->options)) {
            $options = array_merge($options, $this->options);
        }


        // ->fields($projection) might be used according documentation
        $q = $this->buildFindQuery($wheres);
        dump($q);
        $results = $this->collection->find($q)->execute()->fetchAll();

        return new IlluminateCollection($results);
    }

    protected function buildFindQuery(array $wheres): string
    {
        $separator  = array_key_exists('or', $wheres) ? ' or ' : ' and ';

        $resultArray = [];

        foreach ($wheres as $operator => $where) {
            if ($operator === 'and' || $operator === 'or') {
                foreach ($where as $condition) {
                    $c = $this->getCondition($condition);
                    $resultArray[]= $c;
                }
            } else {
                $singleArray = [];
                $singleArray[$operator] = $where;
                $c = $this->getCondition($singleArray);
                $resultArray[] = $c;
            }
        }

        return implode($separator, $resultArray);
    }

    protected function getCondition(array $condition): string
    {
        $result = [];
        foreach ($condition as $column => $operation) {
            $result[0] = $column;
            if (is_array($operation)) {
                $key = array_keys($operation)[0];
                $value =  $operation[$key];
            } else {
                $key = '=';
                $value = $operation;
            }

            $result[1] = $key;
            $result[2] = $value;
            break; // hack: tmp is force to break to test only one condition;
        }

    return implode(' ', $result);
    }

    public function whereAll($column, array $values, $boolean = 'and', $not = false)
    {
        $type = 'all';

        $this->wheres[] = compact('column', 'type', 'boolean', 'values', 'not');

        return $this;
    }

    protected function compileWheres()
    {
        $wheres = $this->wheres ?: [];
        $compiled = [];

        foreach ($wheres as $i => &$where) {
            if (isset($where['operator'])) {
                $where['operator'] = strtolower($where['operator']);
            }

            if (isset($where['column']) && ($where['column'] == '_id' || Str::endsWith($where['column'], '._id'))) {
                if (isset($where['values'])) {
                    foreach ($where['values'] as &$value) {
                        $value = $this->convertKey($value);
                    }
                } // Single value.
                elseif (isset($where['value'])) {
                    $where['value'] = $this->convertKey($where['value']);
                }
            }

            if ($i == 0 && count($wheres) > 1 && $where['boolean'] == 'and') {
                $where['boolean'] = $wheres[$i + 1]['boolean'];
            }

            $method = "compileWhere{$where['type']}";
            $result = $this->{$method}($where);

            if ($where['boolean'] == 'or') {
                $result = ['or' => [$result]];
            }

            elseif (count($wheres) > 1) {
                $result = ['and' => [$result]];
            }

            $compiled = array_merge_recursive($compiled, $result);
        }

        return $compiled;
    }

    protected function compileWhereBasic(array $where)
    {
        extract($where);
        if (! isset($operator) || $operator == '=') {
            $query = [$column => $value];
        }  else {
            $query = [$column => [$operator => $value]];
        }

        return $query;
    }

    protected function compileWhereAll(array $where)
    {
        extract($where);

        return [$column => ['all' => array_values($values)]];
    }

    public function convertKey($id)
    {
        // TODO: here temp return , but _id is some weird format. Check with a mysql docs
        return $id;
    }

    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }

    public function newQuery()
    {
        return new self($this->connection, $this->processor);
    }

    protected function compileWhereIn(array $where)
    {
        extract($where);

        return [$column => ['in' => array_values($values)]];
    }

    public function insertGetId(array $values, $sequence = null)
    {
        return $this->collection->add($values)->execute()->getGeneratedIds();
    }

    public function update(array $values, array $options = [])
    {
        return $this->performUpdate($values, $options);
    }

    protected function performUpdate($query, array $options = [])
    {
        // Update multiple items by default.
        if (! array_key_exists('multiple', $options)) {
            $options['multiple'] = true;
        }

        $wheres = $this->compileWheres();
        $condition = sprintf('%s="%s"', '_id', $wheres['_id'][0]);
        $modificator = $this->collection->modify($condition);

        foreach ($query as $field => $value) {
            $modificator->set($field, $value);
        }
        $result = $modificator->execute();
//        $result = $this->collection->UpdateMany($wheres, $query, $options);
//        if (1 == (int) $result->isAcknowledged()) {
//            return $result->getModifiedCount() ? $result->getModifiedCount() : $result->getUpsertedCount();
//        }

        return 0;
    }
}
