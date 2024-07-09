<?php

namespace Geekcow\Dbcore;

class QueryDeleteBuilder extends QueryBuilder
{
    public function withTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    private function buildDelete(): string
    {
        return 'DELETE FROM ' . $this->table;
    }

    public function toSql(): string
    {
        return $this->buildDelete() . $this->buildConditions();
    }
}