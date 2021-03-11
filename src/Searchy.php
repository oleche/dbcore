<?php

/* SIMPLE SEARCH COMPONENT
 * Developed by OSCAR LECHE
 * V.1.0
 * DESCRIPTION: This is the object-based search component
*/

namespace Geekcow\Dbcore;

use Geekcow\Dbcore\utils\QueryUtils;

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

    public static function assemblySearch($params)
    {
        $query = "";
        if ((isset($params['q'])) && (is_array($params['q']))) {
            $params = $params['q'];
            $count = 0;
            foreach ($params as $key => $value) {
                $count++;
                $items = explode('-', $key);
                if (count($items) > 1) {
                    $column = $items[0];
                    $comparisson = self::buildComparisson($items[1]);
                    if ($items[1] == 'nn') {
                        $query .= $column . ' ' . $comparisson;
                    } else {
                        $query .= $column . ' ' . $comparisson . ' '
                            . (((QueryUtils::getType($value) == 'boolean'
                                || QueryUtils::getType($value) == 'float'
                                || QueryUtils::getType($value) == 'integer'
                                || QueryUtils::getType($value) == 'numeric'
                                || QueryUtils::getType($value) == 'NULL')) ? '' : "'")
                            . (($comparisson == "LIKE") ? "%" : "")
                            . ((QueryUtils::getType($value) == 'NULL') ? 'NULL' : $value)
                            . (($comparisson == "LIKE") ? "%" : "")
                            . (((QueryUtils::getType($value) == 'boolean'
                                || QueryUtils::getType($value) == 'float'
                                || QueryUtils::getType($value) == 'integer'
                                || QueryUtils::getType($value) == 'numeric'
                                || QueryUtils::getType($value) == 'NULL')) ? '' : "'");
                    }

                    if ((count($params) > 1) && ($count < count($params))) {
                        $query .= " AND ";
                    }
                }
            }
        }
        return $query;
    }

    private static function buildComparisson($comparer)
    {
        return self::$COMPARATORS[strtolower($comparer)];
    }
}
