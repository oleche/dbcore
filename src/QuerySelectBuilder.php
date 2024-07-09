<?php

namespace Geekcow\Dbcore;

class QuerySelectBuilder extends QueryBuilder
{
    private $columns;
    private $for_count;
    private $tables;
    private $join_tables;
    private $group_tables;

    public function __construct()
    {
        $this->conditions = [];
        $this->for_count = false;
        $this->columns = ["*"];
        $this->tables = [];
        $this->join_tables = [];
        $this->group_tables = [];
    }

    public function withTable(string $table): QuerySelectBuilder
    {
        $this->tables[] = $table;
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

    public function withGroup(string $column = ''): QuerySelectBuilder
    {
        if (trim($column) != '') {
            $this->group_tables[] = $column;
        }
        return $this;
    }

    public function withJoin(string $table, QuerySelectBuilder $on, $type = 'JOIN'): QuerySelectBuilder
    {
        $this->join_tables[] = ' ' . $type . ' ' . $table . ' ON ' . $on->buildConditions(true);
        return $this;
    }

    public function toSql(): string
    {
        return $this->buildSelect() . $this->buildFrom() . $this->buildJoin() .  $this->buildConditions() . $this->buildGroup();
    }

    private function buildFrom(): string
    {
        return ' FROM ' . implode(', ', $this->tables);
    }
    private function buildGroup(): string
    {
        if (count($this->group_tables) == 0 || $this->for_count){
            return '';
        }
        return ' GROUP BY ' . implode(', ', $this->group_tables);
    }

    private function buildJoin(): string
    {
        return implode('', $this->join_tables);
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

}

?>
