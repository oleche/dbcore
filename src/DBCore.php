<?php

/* SIMPLE ORM CORE
 * Developed by OSCAR LECHE and DIEGO VASQUEZ
 * V.2.0
 * DESCRIPTION: This is the simple ORM core code, here is where all the methods usable for querying a
 * database table lays
 */

namespace Geekcow\Dbcore;

use Exception;
use PDO;

class DBCore extends DataBaseManager
{

    public const SELF = '_self';
    // inheritted attributes
    // $db
    public $columns = array();
    public $db_name;
    public $err_data;
    public $count;
    public $the_key = array();
    public $foreign_relations = array();
    public $foreign_keys = array();
    public $pages;
    public $affected_rows;
    public $columns_defs = array();
    public $fetched_result = array();
    private $ipp = 0;
    private $pagination = false;
    private $recursive;
    private $connection;

    // constructor
    public function __construct(
        DataBase $connection,
        string $db_name,
        array $db_columns,
        array $key,
        array $foreigns = null,
        int $ipp = 25,
        array $fullmap = array()
    ) {
        parent::__construct($connection);
        $this->db_name = $db_name;
        $this->foreign_relations = $foreigns;
        $this->columns_defs = $db_columns;
        $this->the_key = $key;
        $this->err_data = "";
        $this->connection = $connection;
        $this->ipp = $ipp;
        $this->recursive = true;
        $this->count = 0;
        foreach ($db_columns as $columnname) {
            $this->columns[$columnname] = null;
        }

        if (!is_null($foreigns)) {
            foreach ($foreigns as $relation => $v) {
                if ($this->foreign_relations[$relation][1] == $this::SELF) {
                    $this->foreign_relations[$relation][1] = clone $this;
                }
                $this->foreign_keys[] = $relation;
            }
        }

        if (!$this->tableExists()) {
            $this->createTable($fullmap);
        }
    }

    public function set_pagination($value)
    {
        $this->pagination = $value;
    }

    public function set_recursivity($value)
    {
        $this->recursive = $value;
    }

    public function set_ipp($value)
    {
        $this->ipp = $value;
    }

    public function get_columns()
    {
        return $this->columns_defs;
    }

    /**
     * Executes a select based on the information of the model
     * @param string $query the search query that will go as a Where, if empty "" the WHERE statement won't be added
     * @param false $custom replaces all the automatic query with a custom query.
     * @param null $order array countaining the columns that want to be ordered by
     * @param bool $asc use only with @order. Default ascending or ASC set as false changes it to DESC
     * @param int $page use with pagination set as true. The page that needs to be queried
     * @return array|false
     */
    public function fetch($query = "", $custom = false, $order = null, $asc = true, $page = 0)
    {
        $this->err_data = "";
        $count = 0;
        $order_text = "";
        if (!is_null($order)) {
            $order_text = " ORDER BY ";
            foreach ($order as $keys) {
                if ($count > 0) {
                    $order_text .= ' , ';
                }
                $order_text .= $keys;
                $count++;
            }
            if ($asc) {
                $order_text .= " ASC";
            } else {
                $order_text .= " DESC";
            }
        }

        if (!$custom) {
            if ($query != "") {
                $query = " WHERE " . $query;
            }

            $sql = 'SELECT * FROM ' . $this->db_name . '' . $query . ' ' . $order_text;
            $this->count = $this->fixedCount(
                'SELECT count(*) FROM ' . $this->db_name . '' . $query . ' ' . $order_text . ';'
            );
            if ($this->pagination) {
                $this->pages = ceil($this->count / $this->ipp);
                $sql .= ' LIMIT ' . ($page * $this->ipp) . ',' . $this->ipp . ';';
            } else {
                $sql .= ';';
            }
        } else {
            if (
              (int)method_exists($query, 'toSql') > 0 &&
              is_callable(array($query, 'toSql'))
            ) {
                $query->forCount(true);
                $count_sql = $query->toSql();
                $query->forCount(false);
                $sql = $query->toSql();

                $this->count = $this->fixedCount(
                    $count_sql
                );
                if ($this->pagination) {
                    $this->pages = ceil($this->count / $this->ipp);
                    $sql .= ' LIMIT ' . ($page * $this->ipp) . ',' . $this->ipp . ';';
                } else {
                    $sql .= ';';
                }
            } else {
                $sql = $query;
            }
        }

        $retorno = array();
        //You can echo the sql query here: echo '<b>'.$sql.'</b><br/>';

        try {
            $result = $this->db->Execute($sql);

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                //$row = array_map('utf8_encode', $row);
                if (!$custom) {
                    $rowobj = new DBCore($this->connection, $this->db_name, $this->columns_defs, $this->the_key);
                    foreach ($this->columns_defs as $definitions) {
                        if (in_array($definitions, $this->foreign_keys)) {
                            if ($this->foreign_relations[$definitions][1] == $this::SELF) {
                                if (is_null($row[$definitions])) {
                                    $rowobj->columns[$definitions] = $row[$definitions];
                                } else {
                                    //can go to further entities or just stays in one level
                                    if ($this->recursive) {
                                        $this->fetch_id(
                                            array(
                                                $this->foreign_relations[$definitions][0] => $row[$definitions]
                                            )
                                        );
                                        $rowobj->columns[$definitions] = $this->columns;
                                    } else {
                                        $rowobj->columns[$definitions] = $row[$definitions];
                                    }
                                }
                            } else {
                                if ($this->recursive) {
                                    $this->foreign_relations[$definitions][1]->fetch_id(
                                        array(
                                            $this->foreign_relations[$definitions][0] => $row[$definitions]
                                        )
                                    );
                                    $rowobj->columns[$definitions] = $this->foreign_relations[$definitions][1]->columns;
                                } else {
                                    $rowobj->columns[$definitions] = $row[$definitions];
                                }
                            }
                        } else {
                            $rowobj->columns[$definitions] = $row[$definitions];
                        }
                    }
                    $retorno[] = $rowobj;
                } else {
                    $retorno[] = $row;
                }
            }
        } catch (Exception $ex) {
            // si existe un error se deshace la transacci&#65533;n
            //throw new Exception("(RecuperarCuentas) " . $ex->getMessage());
            $this->err_data = $ex->getMessage();
            return false;
        }
        $this->fetched_result = array();
        foreach ($retorno as $k => $q_item) {
            $this->fetched_result[] = $q_item->columns;
        }
        return $retorno;
    }

    public function fetch_obj_in($objs, $cond = "", $order = null, $asc = true, $page = 0)
    {
        $consulta = "";
        $this->err_data = "";

        $valid = false;
        $statements = array();
        $tables = array();
        $ftables = array();
        $fobjs = array();

        $base_letter = "A";
        $tables[] = $this->db_name . " " . $base_letter;
        $base_table = $base_letter;

        foreach ($objs as $obj) {
            $base_letter++;
            $tables[] = $obj->db_name . " " . $base_letter;
            foreach ($this->foreign_relations as $fkey => $value) {
                if ($value[0] == $obj->db_name) {
                    $fobjs[] = $fkey;
                    $ftables[$fkey] = $obj->columns;
                    if (in_array($value[1], $obj->the_key)) {
                        $statements[] .= $this->keyValuePair(
                            $base_letter . "." . $value[1],
                            $obj->columns[$value[1]]
                        );
                        $statements[] = $base_letter . "." . $value[1] . "=" . $base_table . "." . $fkey;
                    }
                }
            }
        }

        $tablestr = "";
        $count = 0;
        foreach ($tables as $table) {
            if ($count != 0) {
                $tablestr .= ", ";
            }
            $tablestr .= $table;
            $count++;
        }

        $joinstr = "";
        $count = 0;
        foreach ($statements as $statement) {
            if ($count != 0) {
                $joinstr .= " AND ";
            }
            $joinstr .= $statement;
            $count++;
        }

        if ($cond != "") {
            $joinstr .= " AND " . $cond;
        }

        $count = 0;
        $order_text = "";
        if (!is_null($order)) {
            $order_text = " ORDER BY ";
            foreach ($order as $keys) {
                if ($count > 0) {
                    $order_text .= ' , ';
                }
                $order_text .= $keys;
                $count++;
            }
            if ($asc) {
                $order_text .= "ASC";
            } else {
                $order_text .= "DESC";
            }
        }

        $sql = 'SELECT ' . $base_table . '.* FROM ' . $tablestr . ' WHERE ' . $joinstr . ' ' . $order_text;
        $this->count = $this->fixedCount(
            'SELECT count(' . $base_table . '.*) FROM ' . $tablestr . ' WHERE ' . $joinstr . ' ' . $order_text . ';'
        );
        if ($this->pagination) {
            $this->pages = ceil($this->count / $this->ipp);
            $sql .= ' LIMIT ' . ($page * $this->ipp) . ',' . $this->ipp . ';';
        } else {
            $sql .= ';';
        }

        $retorno = array();

        try {
            $result = $this->db->Execute($sql);

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                //$row = array_map('utf8_encode', $row);
                $rowobj = new DBCore($this->connection, $this->db_name, $this->columns_defs, $this->the_key);
                foreach ($this->columns_defs as $defs) {
                    if (in_array($defs, $fobjs)) {
                        $rowobj->columns[$defs] = $ftables[$defs];
                    } else {
                        if (in_array($defs, $this->foreign_keys)) {
                            if ($this->foreign_relations[$defs][1] == $this::SELF) {
                                if (is_null($row[$defs])) {
                                    $rowobj->columns[$defs] = $row[$defs];
                                } else {
                                    //can go to further entities or just stays in one level
                                    if ($this->recursive) {
                                        $this->fetch_id(
                                            array(
                                                $this->foreign_relations[$defs][0] => $row[$defs]
                                            )
                                        );
                                        $rowobj->columns[$defs] = $this->foreign_relations[$defs][1]->columns;
                                    } else {
                                        $rowobj->columns[$defs] = $row[$defs];
                                    }
                                }
                            } else {
                                if ($this->recursive) {
                                    $this->foreign_relations[$defs][1]->fetch_id(
                                        array(
                                            $this->foreign_relations[$defs][0] => $row[$defs]
                                        )
                                    );
                                    $rowobj->columns[$defs] = $this->foreign_relations[$defs][1]->columns;
                                } else {
                                    $rowobj->columns[$defs] = $row[$defs];
                                }
                            }
                        } else {
                            $rowobj->columns[$defs] = $row[$defs];
                        }
                    }
                }
                $retorno[] = $rowobj;
            }
        } catch (Exception $ex) {
            // si existe un error se deshace la transacci&#65533;n
            //throw new Exception("(RecuperarCuentas) " . $ex->getMessage());
            $this->err_data = $ex->getMessage();
            return false;
        }
        return $retorno;
    }

    /**
     * Executes a select based on the information of the model and per Keys
     * @param $id array containing the key columns that want to be used as query params column => value
     * @param null $order array countaining the columns that want to be ordered by
     * @param bool $asc use only with @order. Default ascending or ASC set as false changes it to DESC
     * @param string $cond the search query that will go as a Where, if empty "" the WHERE statement won't be added
     * @param int $page use with pagination set as true. The page that needs to be queried
     * @return bool
     */
    public function fetch_id($id, $order = null, $asc = true, $cond = "", $page = 0)
    {
        $this->err_data = "";

        $result = false;

        $key_names = "";

        if (count($id) > 0) {
            $key_names = $this->assembleQuery($this->the_key, false, true, $id);
        }

        if ($cond != "") {
            $key_names .= ($key_names != "") ? " AND " : "";
            $key_names .= $cond;
        }

        $count = 0;
        $order_text = "";
        if (!is_null($order)) {
            $order_text = " ORDER BY ";
            foreach ($order as $keys) {
                if ($count > 0) {
                    $order_text .= ' , ';
                }
                $order_text .= $keys;
                $count++;
            }
            if ($asc) {
                $order_text .= "ASC";
            } else {
                $order_text .= "DESC";
            }
        }

        $sql = 'SELECT * FROM ' . $this->db_name . ' WHERE ' . $key_names . ' ' . $order_text;
        $this->count = $this->fixedCount(
            'SELECT count(*) FROM ' . $this->db_name . ' WHERE ' . $key_names . ' ' . $order_text . ';'
        );
        if ($this->pagination) {
            $this->pages = ceil($this->count / $this->ipp);
            $sql .= ' LIMIT ' . ($page * $this->ipp) . ',' . $this->ipp . ';';
        } else {
            $sql .= ';';
        }

        try {
            if (is_null($id)) {
                $this->err_data = "No id present";
                return false;
            } else {
                //echo $sql.'<br/>';
                $result = $this->db->Execute($sql);

                if ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    //$row = array_map('utf8_encode', $row);
                    /*foreach($this->columns_defs as $definitions){
                    $this->columns[$definitions] = $row[$definitions];
                    }*/
                    foreach ($this->columns_defs as $defs) {
                        if (in_array($defs, $this->foreign_keys)) {
                            //if its a relationship with its own entity
                            if ($this->foreign_relations[$defs][1] == $this::SELF) {
                                if (is_null($row[$defs])) {
                                    $this->columns[$defs] = $row[$defs];
                                } else {
                                    //can go to further entities or just stays in one level
                                    if ($this->recursive) {
                                        $this->columns[$defs][1]->fetch_id(
                                            array(
                                                $this->foreign_relations[$defs][0] => $row[$defs]
                                            )
                                        );
                                        $this->columns[$defs] = $this->foreign_relations[$defs][1]->columns;
                                    } else {
                                        $this->columns[$defs] = $row[$defs];
                                    }
                                }
                            } else {
                                if ($this->recursive) {
                                    $this->foreign_relations[$defs][1]->fetch_id(
                                        array(
                                            $this->foreign_relations[$defs][0] => $row[$defs]
                                        )
                                    );
                                    $this->columns[$defs] = $this->foreign_relations[$defs][1]->columns;
                                } else {
                                    $this->columns[$defs] = $row[$defs];
                                }
                            }
                        } else {
                            $this->columns[$defs] = $row[$defs];
                        }
                    }
                } else {
                    $this->err_data = 'No items found';
                    return false;
                }
            }
        } catch (Exception $ex) {
            // si existe un error se deshace la transacci&#65533;n
            //throw new Exception("(RecuperarCuentas) " . $ex->getMessage());
            $this->err_data = $ex->getMessage();
            return false;
        }
        return true;
    }

    /*
 * Method: count
 *
 *
 * @query
 * @group
 * @custom
 * */

    /**
     * Executes a count command based on the information of the model and per Keys, it returns an array with value
     * of the total count based on the conditions in the first slot
     *
     * @param string $query the complementary query to add after the WHERE sentence
     * @param null $group if a group is needed
     * @param false $custom executes a custom query from scratch. Will return the entire result so the needed would be
     *                      to return an array with the count in the first slot
     * @return array|false
     */
    public function count($query = "", $group = null, $custom = false)
    {
        $this->err_data = "";

        $result = false;

        $count = 0;

        if ($query != "" && !$custom) {
            $query = " WHERE " . $query;
        }

        $group_query = "";
        $group_text = "";
        if (!is_null($group)) {
            $group_text = " GROUP BY ";
            foreach ($group as $keys => $condition) {
                if ($count > 0) {
                    $group_text .= ' , ';
                }
                $group_text .= $condition;
                $group_query .= ',' . $keys;
                $count++;
            }

            $count = 0;
            $group_text .= " ORDER BY ";
            foreach ($group as $keys => $condition) {
                if ($count > 0) {
                    $group_text .= ' , ';
                }
                $group_text .= $condition;
                $group_query .= ',' . $keys;
                $count++;
            }
        }

        $retorno = array();
        if (!$custom) {
            $sql = 'SELECT count(*) as count ' . $group_query .
                ' FROM ' . $this->db_name . ' ' . $query . ' ' . $group_text . ';';
        } else {
            $sql = $query;
        }


        try {
            $result = $this->db->Execute($sql);

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $retorno[] = $row;
            }
        } catch (Exception $ex) {
            // si existe un error se deshace la transacci&#65533;n
            //throw new Exception("(RecuperarCuentas) " . $ex->getMessage());
            $this->err_data = $ex->getMessage();
            return false;
        }
        return $retorno;
    }

    /**
     * Determines if the an entry already exists by fetching a set of columns determined by the parameters
     *
     * @param array $uniquenessKeys The set of keys that need to be validated
     * @param array $values The set of values that will be evaluated
     * @return bool
     */
    public function isUnique($uniquenessKeys = array(), $values = array())
    {
        $count = 0;
        $query = "";
        foreach ($this->uniqueness as $key) {
            if ($count > 0) {
                $query .= " AND ";
            }

            if (isset($values[$key])) {
                $query .= $this->keyValuePair($key, $values[$key]);
            }

            $count++;
        }

        if ($query != "") {
            $query = " WHERE " . $query;
        }

        $items = $this->fixedCount('SELECT count(*) FROM ' . $this->db_name . '' . $query . ';');

        if ($items == 0) {
            return true;
        }

        return false;
    }

    /**
     * Executes a delete based on the information of the model and per Keys, return true or
     * false based on the status of the deletion
     *
     * @param null $conditions the statement after the WHERE command
     * @return bool
     */
    public function delete($conditions = null)
    {
        $this->err_data = "";
        $key_names = "";

        if (!is_null($conditions)) {
            $key_names = $conditions;
        } else {
            $key_names = $this->assembleQuery($this->the_key, false, true, $this->columns, "a.");
        }

        $sql = $this->buildDelete($this->db_name, $key_names);

        try {
            $this->beginTransaction();

            $this->db->Execute($sql);

            $this->commit();
        } catch (Exception $ex) {
            $this->rollBack();
            //throw new Exception("DELETE) " . $e->getMessage());
            $this->err_data = $ex->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Executes an update command based on the information of the model and per Keys, it returns a true or
     * false value based on the result of the update
     *
     * @param null $conditions the complementary query to add after the WHERE sentence
     * @param array $set a custom set of key->value for each column in the table to be updated.
     * @param array $from the same structure as @set but for assembling a WHERE statement
     * @return bool
     */
    public function update($conditions = null, $set = array(), $from = array())
    {
        $query = "";
        try {
            $this->beginTransaction();

            $query = new QueryUpdateBuilder();
            if (empty($set)) {
                $query = $query->withValues($this->columns);
            } else {
                $query = $query->withValues($set);
            }

            $key_names = "";
            if (empty($from)) {
                foreach ($this->the_key as $key => $value) {
                    $the_key = $key;
                    if (is_numeric($the_key)) {
                        $the_key = $value;
                    }
                    $query = $query->withWhere($key, $this->columns[$the_key]);
                }
            } else {
                foreach ($from as $key => $value) {
                    $query = $query->withWhere($key, $value);
                }
            }

            $sql = $query->toSql();

            if (!is_null($conditions)) {
                $sql .= ' AND ' . $conditions;
            }

            //echo $sql;

            $result = $this->db->Execute($sql);

            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            $this->err_data = "(UPDATE) " . $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Method: insert
     * Executes a insert command based on the information of the model, it returns the key of the new element
     *
     **/
    public function insert()
    {
        $query = "";
        $result = false;

        try {
            $this->beginTransaction();

            $query = $this->assemblyInsertUsingBuilder($this->db_name, $this->columns);

            $this->db->Execute($query->toSql());

            $result = $this->db->LastID();
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            $this->err_data = "(INSERT) " . $e->getMessage();
            return false;
        }
        return $result;
    }

    private function tableExists()
    {
        try {
            $sql = "SELECT 1 FROM $this->db_name LIMIT 1";
            $result = $this->db->Execute($sql);
        } catch (Exception $e) {
            return false;
        }

        return $result !== false;
    }

    private function createTable($map = array())
    {
        $query = "";
        $primary = array();
        $keys = array();
        $relation = array();
        $unique = array();

        $query = "CREATE TABLE IF NOT EXISTS `$this->db_name` (";
        foreach ($map as $k => $m) {
            if (isset($m['pk']) && $m['pk']) {
                $primary[] = $k;
            }
            $incremental = "";
            if (isset($m['incremental']) && $m['incremental']) {
                $incremental = " AUTO_INCREMENT";
            }
            if (isset($m['unique']) && $m['unique']) {
                $unique[] = $k;
            }
            if (isset($m['foreign'])) {
                $relation[$k] = $m;
                $keys[] = $k;
            }
            $null = "";
            if (isset($m['null'])) {
                $null = ($m['null']) ? "DEFAULT NULL" : "NOT NULL";
            }
            $query .= "`$k` " . $this->buildType($m) . " " . $null . $incremental . ", ";
        }
        if (count($primary) > 0) {
            $query .= "PRIMARY KEY (";
            foreach ($primary as $k => $v) {
                $query .= "`$v`, ";
            }
            $query = rtrim($query, ", ");
            $query .= "), ";
        }
        if (count($keys) > 0) {
            foreach ($keys as $k => $v) {
                $query .= "KEY `$v` (`$v`), ";
            }
        }
        if (count($primary) > 0) {
            $query .= "CONSTRAINT `UC_$this->db_name` UNIQUE (";
            foreach ($primary as $k => $v) {
                $query .= "`$v`, ";
            }
            $query = rtrim($query, ", ");
            $query .= "), ";
        }
        $query = rtrim($query, ", ");

        $query .= ") ENGINE=InnoDB DEFAULT CHARSET=latin1";

        try {
            $this->beginTransaction();

            $this->db->Execute($query);

            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            $this->err_data = $e->getMessage();
        }

        if (count($relation) > 0) {
            $query = "ALTER TABLE `$this->db_name` ";
            $count = 1;
            foreach ($relation as $key => $value) {
                $query .= "ADD CONSTRAINT `" . $this->db_name . "_ibfk_" . $count .
                    "` FOREIGN KEY (`$key`) REFERENCES `" . $value['foreign'][1]->db_name .
                    "` (`" . $value['foreign'][0] . "`) ON DELETE CASCADE ON UPDATE CASCADE, ";
                $count++;
            }
            $query = rtrim($query, ", ");

            try {
                $this->beginTransaction();

                $this->db->Execute($query);

                $this->commit();
            } catch (Exception $e) {
                $this->rollBack();
                $this->err_data = $e->getMessage();
            }
        }
    }

    private function buildType($row)
    {
        $type = "";
        switch ($row['type']) {
            case 'string':
                $type = "varchar(" . ((isset($row['length'])) ? $row['length'] : "25") . ")";
                break;
            case 'int':
                $type = "int(11)";
                break;
            case 'boolean':
                $type = "tinyint(1)";
                break;
            case 'datetime':
                $type = "datetime";
                break;
            case 'date':
                $type = "date";
                break;
            case 'double':
                $type = "double";
                break;
            case 'decimal':
                $type = "decimal(10,2)";
                break;
            default:
                $type = "text";
                break;
        }
        return $type;
    }

    /**
     * Executes a command based on a count SQL (previously sent), and will return only the numeric
     * value of the count result
     *
     * @param string $cond the count query
     * @return false|int|mixed
     */
    private function fixedCount($cond = "")
    {
        $this->err_data = "";

        $result = false;

        $count = 0;
        $sql = $cond;

        try {
            $result = $this->db->Execute($sql);

            if ($row = $result->fetch(PDO::FETCH_NUM)) {
                $count = $row[0];
            }
        } catch (Exception $ex) {
            // si existe un error se deshace la transacci&#65533;n
            //throw new Exception("(RecuperarCuentas) " . $ex->getMessage());
            $this->err_data = $ex->getMessage();
            return false;
        }
        return $count;
    }
}
