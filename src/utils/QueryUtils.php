<?php

/* QUERY UTILS
 * Developed by OSCAR LECHE
 * V.1.0
 * DESCRIPTION: Contains all methods related to perform query strings transformations
*/

namespace Geekcow\Dbcore\utils;

use Geekcow\Dbcore\DataBase;
use Geekcow\Dbcore\QueryBuilder;
use Geekcow\Dbcore\QueryInsertBuilder;

class QueryUtils
{
    protected function buildDelete($dbname, $key_names)
    {
        return "DELETE a FROM " . $dbname . " a WHERE " . $key_names . ";";
    }

    protected function buildInsert($dbname, $columns, $query)
    {
        return "INSERT INTO " . $dbname . " (" . $columns . ") VALUES ( " . $query . " ); ";
    }

    protected function buildUpdate($dbname, $query, $key_names)
    {
        return "UPDATE " . $dbname . " SET " . $query . " WHERE " . $key_names;
    }

    /**
     * Builds the key/value pairs of $columns. When $params is given the values are added to it and the pairs use
     * ? placeholders, otherwise the values are written as escaped literals.
     */
    protected function assembleQuery(
        $columns,
        $is_insert = false,
        $is_from = false,
        $values = null,
        $modifier = "",
        ?array &$params = null
    ) {
        if (!is_null($values)) {
            return $this->iterateOverKeys($columns, $values, $is_insert, $is_from, $modifier, $params);
        } else {
            return $this->iterateOverKeys($columns, $columns, $is_insert, $is_from, $modifier, $params);
        }
    }

    protected function assemblyInsertUsingBuilder(string $table, array $columns): QueryBuilder
    {
        $qb = new QueryInsertBuilder();
        $qb = $qb->withTable($table);
        return $qb->withColumnValues($columns);
    }

    private function iterateOverKeys(
        $columns,
        $values,
        $is_insert = false,
        $is_from = false,
        $modifier = "",
        ?array &$params = null
    ) {
        $query = "";
        $count = 0;

        foreach ($columns as $key => $value) {
            $the_key = $key;
            if (is_numeric($the_key)) {
                $the_key = $value;
            }

            if ($count > 0) {
                $query .= $is_from ? ' AND ' : ', ';
            }
            if (is_array($params)) {
                $query .= $this->bindPair($modifier . $the_key, $values[$the_key] ?? null, $params, $is_insert);
            } else {
                $query .= $this->keyValuePair($modifier . $the_key, $values[$the_key] ?? null, $is_insert);
            }
            $count++;
        }

        return $query;
    }

    /**
     * Returns "key = ?" (or just "?" for inserts) and appends the value to $params
     */
    protected function bindPair($key, $value, array &$params, $is_insert = false)
    {
        $params[] = $value;
        return (!$is_insert ? $key . ' = ' : '') . '?';
    }

    /**
     * Returns "key=literal" with the value escaped. Prefer bindPair(), which keeps the value out of the SQL.
     */
    protected function keyValuePair($key, $value, $is_insert = false)
    {
        return (!$is_insert ? $key . '=' : "") . $this->toLiteral($value);
    }

    /**
     * Writes a value as an SQL literal: NULL, numbers as they are, booleans as 1/0 and everything else as an
     * escaped quoted string (so text like "007" keeps its leading zeros)
     */
    protected function toLiteral($value)
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
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $value = (string)$value;
        if (isset($this->db) && $this->db instanceof DataBase) {
            return $this->db->quote($value);
        }
        return DataBase::escapeLiteral($value);
    }

    public static function getType($var)
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
