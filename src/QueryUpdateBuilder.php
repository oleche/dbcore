<?php

namespace Geekcow\Dbcore;

class QueryUpdateBuilder extends QueryBuilder
{
    private $tables;

    private $values;

    public function __construct()
    {
        $this->conditions = [];
        $this->tables = [];
        $this->values = [];
    }

    public function withTable(string $table): QueryUpdateBuilder
    {
        $this->tables[] = $table;
        return $this;
    }

    public function withValue(string $column, $value, bool $is_column_value = false): QueryUpdateBuilder
    {
        $value = (!$is_column_value) ? $this->filterValue($value) : $value;
        $condition = $column . ' = ' . $value;
        $this->values[] = $condition;
        return $this;
    }

    public function buildUpdate(): string
    {
        return 'UPDATE ' . implode(', ', $this->tables);
    }

    public function buildSet(): string
    {
        return ' SET ' . implode(', ', $this->values);
    }

    public function toSql(): string
    {
        return $this->buildUpdate() . $this->buildSet() . $this->buildConditions();
    }
}