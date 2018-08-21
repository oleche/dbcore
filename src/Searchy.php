<?php
/* SIMPLE SEARCH COMPONENT
 * Developed by OSCAR LECHE
 * V.1.0
 * DESCRIPTION: This is the object-based search component
*/
namespace Geekcow\Dbcore;

class Searchy {
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

  public static function assemblySearch($params){
    $query = "";
    if ((isset($params['q'])) && (is_array($params['q']))){
      $params = $params['q'];
      $count = 0;
      foreach ($params as $key => $value) {
        $count++;
        $items = explode('-', $key);
        if (count($items) > 1){
          $column = $items[0];
          $comparisson = self::buildComparisson($items[1]);
          if ($items[1] == 'nn'){
            $query .= $column.' '.$comparisson;
          }else{
            $query .= $column.' '.$comparisson.' '
              .(((self::GetType($value) == 'boolean'
              || self::GetType($value) == 'float'
              || self::GetType($value) == 'integer'
              || self::GetType($value) == 'numeric'
              || self::GetType($value) == 'NULL'))?'':"'")
                .(($comparisson == "LIKE")?"%":"")
                .((self::GetType($value) == 'NULL')?'NULL':$value)
                .(($comparisson == "LIKE")?"%":"")
              .(((self::GetType($value) == 'boolean'
              || self::GetType($value) == 'float'
              || self::GetType($value) == 'integer'
              || self::GetType($value) == 'numeric'
              || self::GetType($value) == 'NULL'))?'':"'");
          }

          if ((count($params) > 1) && ($count < count($params))){
            $query .= " AND ";
          }
        }
      }
    }
    return $query;
	}

  private static function buildComparisson($comparer){
    return self::$COMPARATORS[strtolower($comparer)];
  }

  private static function GetType($var)
  {
      if (is_array($var)) return "array";
      if (is_bool($var)) return "boolean";
      if (is_float($var)) return "float";
      if (is_int($var)) return "integer";
      if (is_null($var)) return "NULL";
      if (is_numeric($var)) return "numeric";
      if (is_object($var)) return "object";
      if (is_resource($var)) return "resource";
      if (is_string($var)) return "string";
      return "unknown";
  }
}

?>
