<?php

/* SIMPLE SEARCH COMPONENT
 * Developed by OSCAR LECHE
 * V.1.0
 * DESCRIPTION: This is the object-based search component
*/

namespace Geekcow\Dbcore;

class Searchy
{
    private static $COMPARATORS = array(
        'eq' => '=',
        'ne' => '<>',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'matches' => 'LIKE',
        'nn' => 'IS NOT NULL'
    );

    /**
     * Builds the WHERE conditions of the q[column-comparator]=value parameters.
     *
     * When $bind is given the values are appended to it and the conditions use ? placeholders; otherwise the
     * values are written as literals escaped with $quote (or DataBase::escapeLiteral()). Column names must be
     * identifiers (column or table.column) and comparators must be known; any other filter turns into a
     * condition that matches nothing.
     *
     * @param array $params usually $_GET
     * @param array|null $bind receives the values for the placeholders
     * @param callable|null $quote quotes a string literal for the connection
     * @return string
     */
    public static function assemblySearch($params, ?array &$bind = null, ?callable $quote = null)
    {
        $conditions = array();
        if ((isset($params['q'])) && (is_array($params['q']))) {
            foreach ($params['q'] as $key => $value) {
                $items = explode('-', (string)$key);
                if (count($items) > 1) {
                    $conditions[] = self::buildCondition($items[0], $items[1], $value, $bind, $quote);
                }
            }
        }
        return implode(" AND ", $conditions);
    }

    private static function buildCondition($column, $comparer, $value, ?array &$bind, ?callable $quote)
    {
        $comparisson = self::buildComparisson($comparer);
        if (is_null($comparisson) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column)) {
            return '1 = 0';
        }
        if ($comparisson == 'IS NOT NULL') {
            return $column . ' ' . $comparisson;
        }
        if (is_array($value)) {
            return '1 = 0';
        }
        if ($comparisson == "LIKE") {
            $value = '%' . $value . '%';
        }
        if (is_array($bind)) {
            $bind[] = $value;
            return $column . ' ' . $comparisson . ' ?';
        }
        if (is_null($value)) {
            return $column . ' ' . $comparisson . ' NULL';
        }
        $literal = is_null($quote) ? DataBase::escapeLiteral($value) : $quote((string)$value);
        return $column . ' ' . $comparisson . ' ' . $literal;
    }

    private static function buildComparisson($comparer)
    {
        return self::$COMPARATORS[strtolower($comparer)] ?? null;
    }
}
