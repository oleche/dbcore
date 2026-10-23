<?php

namespace Geekcow\Dbcore;

class QueryUpdateBuilder extends QueryBuilder
{
    private $tables;

    private $values;

    /**
     * Values bound to the SET placeholders (they come before the WHERE ones)
     *
     * @var array
     */
    private $value_params;

    public function __construct()
    {
        $this->conditions = [];
        $this->tables = [];
        $this->values = [];
        $this->value_params = [];
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

    /**
     * @param bool $is_column_value true when $value is a value (bound), false when it is an SQL expression
     */
    private function buildAssignmentCondition(string $column, $value, bool $is_column_value = false): string
    {
        if ($is_column_value) {
            $this->value_params[] = $value;
            $value = '?';
        }
        return $column . ' = ' . $value;
    }

    public function getParams(): array
    {
        return array_merge($this->value_params, $this->params);
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