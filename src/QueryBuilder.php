<?php

namespace Geekcow\Dbcore;

abstract class QueryBuilder
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var array
     */
    protected $conditions;

    /**
     * Generates the SQL query string.
     *
     * @return string The constructed SQL query.
     */
    abstract public function toSql(): string;

    /**
     * Adds a condition to the query.
     *
     * @param string $column The column name to apply the condition on.
     * @param string $predicate The condition to be applied.
     * @param bool $and Specifies whether the condition is an AND (true) or OR (false).
     */
    protected function addToCondition($column, $predicate, $and = true)
    {
        $prefix_value = ($and) ? ' AND ' : ' OR ';
        $prefix = count($this->conditions) > 0 ? $prefix_value : '';
        $this->conditions[] = $prefix . $column . $predicate;
    }

    /**
     * Adds a search condition to the query based on provided parameters.
     *
     * @param array $params Parameters to construct the search condition.
     * @return QueryBuilder Returns the instance of the QueryBuilder for method chaining.
     */
    public function withSearchy(array $params): QueryBuilder
    {
        $search = Searchy::assemblySearch($params);
        if ($search != "") {
            $this->addToCondition($search, "");
        }
        return $this;
    }

    /**
     * Adds a WHERE condition to the query.
     *
     * @param string $column The column name to apply the condition on.
     * @param mixed $value The value to compare in the condition.
     * @param string $comparator The comparison operator (e.g., '=', '>', '<').
     * @param bool $and Specifies whether the condition is an AND (true) or OR (false).
     * @param bool $is_column_value Specifies whether the value is a column value (true) or not (false).
     * @return QueryBuilder Returns the instance of the QueryBuilder for method chaining.
     */
    public function withWhere(string $column, $value, string $comparator = '=', bool $and = true, bool $is_column_value = false): QueryBuilder
    {
        $value = (!$is_column_value) ? $this->filterValue($value) : $value;
        $condition_predicate =  ' ' . $comparator . ' ' . $value;

        $this->addToCondition($column, $condition_predicate, $and);

        return $this;
    }

    /**
     * Adds a BETWEEN condition to the query.
     *
     * @param string $column The column name to apply the BETWEEN condition on.
     * @param mixed $from The starting value for the BETWEEN condition.
     * @param mixed $to The ending value for the BETWEEN condition.
     * @param bool $and Specifies whether the condition is an AND (true) or OR (false).
     * @return QueryBuilder Returns the instance of the QueryBuilder for method chaining.
     */
    public function withWhereBetween(string $column, $from, $to, $and = true): QueryBuilder
    {
        $condition_predicate = ' BETWEEN ' .  $this->filterValue($from) . " AND " . $this->filterValue($to);

        $this->addToCondition($column, $condition_predicate, $and);

        return $this;
    }

    public function buildConditions($only_conditions = false): string
    {
        if (count($this->conditions) == 0) {
            return '';
        }
        $condition_prefix = (!$only_conditions) ? ' WHERE ' : '';
        $condition_string = $condition_prefix . implode('', $this->conditions);
        return $condition_string;
    }

    public function filterValue($value)
    {
        return
            (($this->getType($value) == 'boolean'
                || $this->getType($value) == 'float'
                || $this->getType($value) == 'integer'
                || $this->getType($value) == 'numeric'
                || $this->getType($value) == 'NULL') ? '' : "'")
            . (($this->getType($value) == 'NULL') ? 'NULL' : $value)
            . (($this->getType($value) == 'boolean'
                || $this->getType($value) == 'float'
                || $this->getType($value) == 'integer'
                || $this->getType($value) == 'numeric'
                || $this->getType($value) == 'NULL') ? '' : "'");
    }

    public function getType($var): string
    {
        if (is_array($var)) {
            return "array";
        }
        if (is_bool($var)) {
            return "boolean";
        }
        if (is_float($var)) {
            return "float";
        }
        if (is_int($var)) {
            return "integer";
        }
        if (is_null($var) || !isset($var)) {
            return "NULL";
        }
        if (is_numeric($var)) {
            return "numeric";
        }
        if (is_object($var)) {
            return "object";
        }
        if (is_resource($var)) {
            return "resource";
        }
        if (is_string($var)) {
            return "string";
        }
        return "unknown";
    }
}