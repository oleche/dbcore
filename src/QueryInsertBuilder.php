<?php

namespace Geekcow\Dbcore;

class QueryInsertBuilder extends QueryBuilder
{

    private $columns;
    private $values;

    public function __construct()
    {
        $this->conditions = [];
        $this->table = '';
        $this->columns = [];
        $this->values = [];
    }

    public function withTable(string $table): QueryInsertBuilder
    {
        $this->table = $table;
        return $this;
    }

    public function withColumns(array $columns = ["*"]): QueryInsertBuilder
    {
        $this->columns = $columns;
        return $this;
    }

    public function withValues(array $values): QueryInsertBuilder
    {
        foreach ($values as $value) {
            $this->values[] = $this->filterValue($value);
        }
        return $this;
    }

    public function withValue($value): QueryInsertBuilder
    {
        $this->values[] = $this->filterValue($value);
        return $this;
    }

    public function withColumnValue(string $column, $value): QueryInsertBuilder
    {
        $this->columns[] = $column;
        $this->values[] = $this->filterValue($value);
        return $this;
    }

    public function toSql(): string
    {
        return $this->buildInsert() . $this->buildColumns() . $this->buildValues();
    }

    private function buildValues(): string
    {
        return 'VALUES ( ' . implode(', ', $this->values) . ' ) ';
    }

    private function buildInsert(): string
    {
        return 'INSERT INTO ' . $this->table;
    }

    private function buildColumns(): string
    {
        if (!in_array('*', $this->columns)) {
            return ' ( ' . implode(', ', $this->columns) . ' ) ';
        }
        return '';
    }
}