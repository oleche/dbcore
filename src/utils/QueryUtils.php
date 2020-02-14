<?php
/* QUERY UTILS
 * Developed by OSCAR LECHE
 * V.1.0
 * DESCRIPTION: Contains all methods related to perform query strings transformations
*/
namespace Geekcow\Dbcore\utils;

class QueryUtils {
  protected function build_delete($dbname, $key_names){
    return "DELETE a FROM " . $dbname . " a WHERE " . $key_names . ";";
  }

  protected function build_insert($dbname, $columns, $query){
    return "INSERT INTO " . $dbname . " (" . $columns . ") VALUES ( " . $query . " ); ";
  }

  protected function build_update($dbname, $query, $key_names){
    return "UPDATE " . $dbname . " SET " . $query . " WHERE " . $key_names;
  }

  protected function assemble_query($columns, $is_insert = false, $is_from = false, $values = null, $modifier = "")
  {
    if (!is_null($values)){
      return $this->iterate_over_keys($columns, $values, $is_insert, $is_from, $modifier);
    }else{
      return $this->iterate_over_keys($columns, $columns, $is_insert, $is_from, $modifier);
    }
  }

  private function iterate_over_keys($columns, $values, $is_insert = false, $is_from = false, $modifier = ""){
    $query = "";
    $count = 0;

    foreach ($columns as $key => $value) {
      $the_key = $key;
      if (is_numeric($the_key))
        $the_key = $value;

      if ($count > 0)
        $query .= $is_from ? ' AND ' : ', ';
      $query .= $this->key_value_pair($modifier.$the_key, $values[$the_key], $is_insert);
      $count++;
    }

    return $query;
  }

  protected function key_value_pair($key, $value, $is_insert = false){
    return (!$is_insert ? $key . '=' : "")
      . (($this->GetType($value) == 'boolean'
          || $this->GetType($value) == 'float'
          || $this->GetType($value) == 'integer'
          || $this->GetType($value) == 'numeric'
          || $this->GetType($value) == 'NULL') ? '' : "'")
      . (($this->GetType($value) == 'NULL') ? 'NULL' : $value)
      . (($this->GetType($value) == 'boolean'
          || $this->GetType($value) == 'float'
          || $this->GetType($value) == 'integer'
          || $this->GetType($value) == 'numeric'
          || $this->GetType($value) == 'NULL') ? '' : "'");
  }

  protected function GetType($var)
  {
      if (is_array($var))
          return "array";
      if (is_bool($var))
          return "boolean";
      if (is_float($var))
          return "float";
      if (is_int($var))
          return "integer";
      if (is_null($var) || !isset($var))
          return "NULL";
      if (is_numeric($var))
          return "numeric";
      if (is_object($var))
          return "object";
      if (is_resource($var))
          return "resource";
      if (is_string($var))
          return "string";
      return "unknown";
  }
}
