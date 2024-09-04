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
        $condition = $this->buildAssignmentCondition($column, $value, $is_column_value);
        $this->values[] = $condition;
        return $this;
    }

    public function withValues(array $values): QueryUpdateBuilder
    {
        foreach ($values as $column => $value) {
            $this->values[] = $this->buildAssignmentCondition($column, $value, true);
        }
        return $this;
    }

    private function buildAssignmentCondition(string $column, $value, bool $is_column_value = false): string
    {
        $value = ($is_column_value) ? $this->filterValue($value) : $value;
        return $column . ' = ' . $value;
    }

    private function buildUpdate(): string
    {
        return 'UPDATE ' . implode(', ', $this->tables);
    }

    private function buildSet(): string
    {
        return ' SET ' . implode(', ', $this->values);
    }

    public function toSql(): string
    {
        return $this->buildUpdate() . $this->buildSet() . $this->buildConditions();
    }
}