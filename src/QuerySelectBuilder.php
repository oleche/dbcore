<?php

namespace Geekcow\Dbcore;

class QuerySelectBuilder
{
    private $conditions;
    private $columns;
    private $for_count;
    private $tables;
    private $join_tables;

    public function __construct()
    {
        $this->for_count = false;
        $this->columns = ["*"];
        $this->conditions = [];
        $this->tables = [];
        $this->join_tables = [];
    }

    public function withSearchy(array $params): QuerySelectBuilder
    {
        $search = Searchy::assemblySearch($params);
        if ($search != "") {
          $this->addToCondition($search, "");
        }
        return $this;
    }

    public function withColumns(array $columns = ["*"]): QuerySelectBuilder
    {
        $this->columns = $columns;
        return $this;
    }

    public function forCount(bool $for_count): QuerySelectBuilder
    {
        $this->for_count = $for_count;
        return $this;
    }

    public function withTable(string $table): QuerySelectBuilder
    {
        $this->tables[] = $table;
        return $this;
    }

    public function withJoin(string $table, QuerySelectBuilder $on, $type = 'JOIN')
    {
        $this->join_tables[] = ' ' . $type . ' ' . $table . ' ON ' . $on->buildConditions(true);
    }

    public function withWhereBetween(string $column, $from, $to, $and = true): QueryBuilder
    {
        $condition_predicate = ' BETWEEN ' .  $this->filterValue($from) . " AND " . $this->filterValue($to);

        $this->addToCondition($column, $condition_predicate, $and);

        return $this;
    }

    public function withWhere(string $column, $value, string $comparator = '=', bool $and = true, bool $is_column_value = false): QuerySelectBuilder
    {
        $value = (!$is_column_value) ? $this->filterValue($value) : $value;
        $condition_predicate =  ' ' . $comparator . ' ' . $value;

        $this->addToCondition($column, $condition_predicate, $and);

        return $this;
    }

    public function toSql(): string
    {
        return $this->buildSelect() . $this->buildFrom() . $this->buildJoin() .  $this->buildConditions();
    }

    private function buildFrom(): string
    {
        return ' FROM ' . implode(', ', $this->tables);
    }

    private function buildJoin(): string
    {
        return implode('', $this->join_tables);
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

    private function buildColumns(): string
    {
        if ($this->for_count) {
            return "count(*)";
        }
        return implode(', ', $this->columns);
    }

    private function buildSelect(): string
    {
        return 'SELECT ' . $this->buildColumns();
    }

    private function addToCondition($column, $predicate, $and = true)
    {
        $prefix_value = ($and) ? ' AND ' : ' OR ';
        $prefix = count($this->conditions) > 0 ? $prefix_value : '';
        $this->conditions[] = $prefix . $column . $predicate;
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

    public function getType($var)
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

?>
