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
     * Values bound to the ? placeholders of the conditions, in order
     *
     * @var array
     */
    protected $params = [];

    /**
     * Generates the SQL query string, with ? placeholders for the values (see getParams()).
     *
     * @return string The constructed SQL query.
     */
    abstract public function toSql(): string;

    /**
     * Values for the ? placeholders of toSql(), in order. DBCore binds them when the builder is executed.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

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
        $bind = [];
        $search = Searchy::assemblySearch($params, $bind);
        if ($search != "") {
            $this->addToCondition($search, "");
            $this->params = array_merge($this->params, $bind);
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
        if (!$is_column_value) {
            $this->params[] = $value;
            $value = '?';
        }
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
        $this->params[] = $from;
        $this->params[] = $to;
        $condition_predicate = ' BETWEEN ? AND ?';

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

    /**
     * Writes a value as an escaped SQL literal (strings are always quoted). The builders bind values instead.
     */
    public function filterValue($value)
    {
        if (is_null($value)) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }
        return DataBase::escapeLiteral(is_array($value) ? json_encode($value) : $value);
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