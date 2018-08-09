<?php
/* SIMPLE ORM CORE
 * Developed by OSCAR LECHE and DIEGO VASQUEZ
 * V.2.0
 * DESCRIPTION: This is the simple ORM core code, here is where all the methods usable for querying a database table lays
 */

include_once("DataBaseManager.php");

class DBManager extends DataBaseManager
{

    const SELF = '_self';
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
		var $connection;
    var $fetched = false;


    // constructor
    function __construct($connection, $db_name, $db_columns, $key, $foreigns = null, $ipp = 25)
    {
        parent::__construct($connection);
        $this->db_name           = $db_name;
        $this->foreign_relations = $foreigns;
        $this->columns_defs      = $db_columns;
        $this->the_key           = $key;
        $this->err_data          = "";
        $this->connection        = $connection;
        $this->ipp               = $ipp;
        $this->recursive         = true;
        $this->count             = 0;
        foreach ($db_columns as $columnname)
            $this->columns[$columnname] = null;
        if (!is_null($foreigns)) {
            foreach ($foreigns as $relation => $v) {
                if ($this->foreign_relations[$relation][1] == $this::SELF) {
                    $this->foreign_relations[$relation][1] = clone $this;
                }
                $this->foreign_keys[] = $relation;
            }
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

    /*
     * Method: Fetch
     * Executes a select based on the information of the model
     *
     * @query		the search query that will go as a Where, if empty "" the WHERE statement won't be added
     * @custom		replaces all the automatic query with a custom query. It also returns the response with its column model and replaces all the column_defs
     * @order		array countaining the columns that want to be ordered by
     * @asc			use only with @order. Default ascending or ASC set as false changes it to DESC
     * @page		use with pagination set as true. The page that needs to be queried
     * */
    public function fetch($query = "", $custom = false, $order = null, $asc = true, $page = 0)
    {
        $this->err_data = "";
        $count          = 0;
        $order_text     = "";
        if (!is_null($order)) {
            $order_text = " ORDER BY ";
            foreach ($order as $keys) {
                if ($count > 0)
                    $order_text .= ' , ';
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

            $sql         = 'SELECT * FROM ' . $this->db_name . '' . $query . ' ' . $order_text;
            $this->count = $this->fixed_count('SELECT count(*) FROM ' . $this->db_name . '' . $query . ' ' . $order_text . ';');
            if ($this->pagination) {
                $this->pages = ceil($this->count / $this->ipp);
                $sql .= ' LIMIT ' . ($page * $this->ipp) . ',' . $this->ipp . ';';
            } else
                $sql .= ';';
        } else {
            $sql = $query;
        }

        $retorno = array();
        //You can echo the sql query here: echo '<b>'.$sql.'</b><br/>';

        try {
            $result = $this->db->Execute($sql);

            while ($row = mysqli_fetch_assoc($result)) {
                //$row = array_map('utf8_encode', $row);
                if (!$custom) {
                    $rowobj = new DBManager($this->connection, $this->db_name, $this->columns_defs, $this->the_key);
                    foreach ($this->columns_defs as $definitions) {
                        if (in_array($definitions, $this->foreign_keys)) {
                            if ($this->foreign_relations[$definitions][1] == $this::SELF) {
                                if (is_null($row[$definitions]))
                                    $rowobj->columns[$definitions] = $row[$definitions];
                                else {
                                    //can go to further entities or just stays in one level
                                    if ($this->recursive) {
                                        $this->fetch_id(array(
                                            $this->foreign_relations[$definitions][0] => $row[$definitions]
                                        ));
                                        $rowobj->columns[$definitions] = $this->columns;
                                    } else {
                                        $rowobj->columns[$definitions] = $row[$definitions];
                                    }
                                }
                            } else {
                                if ($this->recursive) {
                                    $this->foreign_relations[$definitions][1]->fetch_id(array(
                                        $this->foreign_relations[$definitions][0] => $row[$definitions]
                                    ));
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
        }
        catch (Exception $ex) {
            // si existe un error se deshace la transacci&#65533;n
            //throw new Exception("(RecuperarCuentas) " . $ex->getMessage());
            $this->err_data = $ex->getMessage();
            return FALSE;
        }
				$this->fetched_result = array();
				foreach ($retorno as $k => $q_item) {
					$this->fetched_result[] = $q_item->columns;
  			}
        return $retorno;
    }

    public function fetch_obj_in($objs, $cond = "", $order = null, $asc = true, $page = 0)
    {
        $consulta       = "";
        $this->err_data = "";

        $valid      = false;
        $statements = array();
        $tables     = array();
        $ftables    = array();
        $fobjs      = array();

        $base_letter = "A";
        $tables[]    = $this->db_name . " " . $base_letter;
        $base_table  = $base_letter;

        foreach ($objs as $obj) {
            $base_letter++;
            $tables[] = $obj->db_name . " " . $base_letter;
            foreach ($this->foreign_relations as $fkey => $value) {
                if ($value[0] == $obj->db_name) {
                    $fobjs[]        = $fkey;
                    $ftables[$fkey] = $obj->columns;
                    if (in_array($value[1], $obj->the_key)) {
                        $statements[] = $base_letter . "." . $value[1] . "=" . ((($this->GetType($obj->columns[$value[1]]) == 'boolean' || $this->GetType($obj->columns[$value[1]]) == 'float' || $this->GetType($obj->columns[$value[1]]) == 'integer' || $this->GetType($obj->columns[$value[1]]) == 'numeric' || $this->GetType($obj->columns[$value[1]]) == 'NULL')) ? '' : "'") . (($this->GetType($obj->columns[$value[1]]) == 'NULL') ? 'NULL' : $obj->columns[$value[1]]) . ((($this->GetType($obj->columns[$value[1]]) == 'boolean' || $this->GetType($obj->columns[$value[1]]) == 'float' || $this->GetType($obj->columns[$value[1]]) == 'integer' || $this->GetType($obj->columns[$value[1]]) == 'numeric' || $this->GetType($obj->columns[$value[1]]) == 'NULL')) ? '' : "'");
                        $statements[] = $base_letter . "." . $value[1] . "=" . $base_table . "." . $fkey;
                    }
                }
            }
        }

        $tablestr = "";
        $count    = 0;
        foreach ($tables as $table) {
            if ($count != 0)
                $tablestr .= ", ";
            $tablestr .= $table;
            $count++;
        }

        $joinstr = "";
        $count   = 0;
        foreach ($statements as $statement) {
            if ($count != 0)
                $joinstr .= " AND ";
            $joinstr .= $statement;
            $count++;
        }

        if ($cond != "") {
            $joinstr .= " AND " . $cond;
        }

        $count      = 0;
        $order_text = "";
        if (!is_null($order)) {
            $order_text = " ORDER BY ";
            foreach ($order as $keys) {
                if ($count > 0)
                    $order_text .= ' , ';
                $order_text .= $keys;
                $count++;
            }
            if ($asc) {
                $order_text .= "ASC";
            } else {
                $order_text .= "DESC";
            }
        }

        $sql         = 'SELECT ' . $base_table . '.* FROM ' . $tablestr . ' WHERE ' . $joinstr . ' ' . $order_text;
        $this->count = $this->fixed_count('SELECT count(' . $base_table . '.*) FROM ' . $tablestr . ' WHERE ' . $joinstr . ' ' . $order_text . ';');
        if ($this->pagination) {
            $this->pages = ceil($this->count / $this->ipp);
            $sql .= ' LIMIT ' . ($page * $this->ipp) . ',' . $this->ipp . ';';
        } else
            $sql .= ';';

        $retorno = array();

        try {
            $result = $this->db->Execute($sql);

            while ($row = mysqli_fetch_assoc($result)) {
                //$row = array_map('utf8_encode', $row);
                $rowobj = new DBManager($this->connection, $this->db_name, $this->columns_defs, $this->the_key);
                foreach ($this->columns_defs as $definitions) {
                    if (in_array($definitions, $fobjs)) {
                        $rowobj->columns[$definitions] = $ftables[$definitions];
                    } else {
                        if (in_array($definitions, $this->foreign_keys)) {
                            if ($this->foreign_relations[$definitions][1] == $this::SELF) {
                                if (is_null($row[$definitions]))
                                    $rowobj->columns[$definitions] = $row[$definitions];
                                else {
                                    //can go to further entities or just stays in one level
                                    if ($this->recursive) {
                                        $this->fetch_id(array(
                                            $this->foreign_relations[$definitions][0] => $row[$definitions]
                                        ));
                                        $rowobj->columns[$definitions] = $this->foreign_relations[$definitions][1]->columns;
                                    } else {
                                        $rowobj->columns[$definitions] = $row[$definitions];
                                    }
                                }
                            } else {
                                if ($this->recursive) {
                                    $this->foreign_relations[$definitions][1]->fetch_id(array(
                                        $this->foreign_relations[$definitions][0] => $row[$definitions]
                                    ));
                                    $rowobj->columns[$definitions] = $this->foreign_relations[$definitions][1]->columns;
                                } else {
                                    $rowobj->columns[$definitions] = $row[$definitions];
                                }
                            }
                        } else {
                            $rowobj->columns[$definitions] = $row[$definitions];
                        }

                    }

                }
                $retorno[] = $rowobj;
            }
        }
        catch (Exception $ex) {
            // si existe un error se deshace la transacci&#65533;n
            //throw new Exception("(RecuperarCuentas) " . $ex->getMessage());
            $this->err_data = $ex->getMessage();
            return FALSE;
        }
        return $retorno;

    }

    /*
     * Method: fetch_id
     * Executes a select based on the information of the model and per Keys
     *
     * @id			array containing the key columns that want to be used as query params column => value
     * @order		array countaining the columns that want to be ordered by
     * @asc			use only with @order. Default ascending or ASC set as false changes it to DESC
     * @cond		the search query that will go as a Where, if empty "" the WHERE statement won't be added
     * @page		use with pagination set as true. The page that needs to be queried
     * */
    public function fetch_id($id, $order = null, $asc = true, $cond = "", $page = 0)
    {
        $consulta       = "";
        $this->err_data = "";

        $result = false;

        $key_names = "";
        $count     = 0;

        if (count($id) > 0)
            foreach ($this->the_key as $keys) {

                if ($count > 0)
                    $key_names .= ' AND ';

                $key_names .= $keys . "=" . ((($this->GetType($id[$keys]) == 'boolean' || $this->GetType($id[$keys]) == 'float' || $this->GetType($id[$keys]) == 'integer' || $this->GetType($id[$keys]) == 'numeric' || $this->GetType($id[$keys]) == 'NULL')) ? "" : "'") . (($this->GetType($id[$keys]) == 'NULL') ? 'NULL' : $id[$keys]) . ((($this->GetType($id[$keys]) == 'boolean' || $this->GetType($id[$keys]) == 'float' || $this->GetType($id[$keys]) == 'integer' || $this->GetType($id[$keys]) == 'numeric' || $this->GetType($id[$keys]) == 'NULL')) ? "" : "'");
                $count++;
            }

        if ($cond != "") {
            $key_names .= ($key_names != "") ? " AND " : "";
            $key_names .= $cond;
        }

        $count      = 0;
        $order_text = "";
        if (!is_null($order)) {
            $order_text = " ORDER BY ";
            foreach ($order as $keys) {
                if ($count > 0)
                    $order_text .= ' , ';
                $order_text .= $keys;
                $count++;
            }
            if ($asc) {
                $order_text .= "ASC";
            } else {
                $order_text .= "DESC";
            }
        }

        $sql         = 'SELECT * FROM ' . $this->db_name . ' WHERE ' . $key_names . ' ' . $order_text;
        $this->count = $this->fixed_count('SELECT count(*) FROM ' . $this->db_name . ' WHERE ' . $key_names . ' ' . $order_text . ';');
        if ($this->pagination) {
            $this->pages = ceil($this->count / $this->ipp);
            $sql .= ' LIMIT ' . ($page * $this->ipp) . ',' . $this->ipp . ';';
        } else
            $sql .= ';';

        try {
            if (is_null($id)) {
                $this->err_data = "No id present";
                return FALSE;
            } else {
                //echo $sql.'<br/>';
                $result = $this->db->Execute($sql);

                if ($row = mysqli_fetch_assoc($result)) {
                    //$row = array_map('utf8_encode', $row);
                    /*foreach($this->columns_defs as $definitions){
                    $this->columns[$definitions] = $row[$definitions];
                    }*/
                    foreach ($this->columns_defs as $definitions) {
                        if (in_array($definitions, $this->foreign_keys)) {
                            //if its a relationship with its own entity
                            if ($this->foreign_relations[$definitions][1] == $this::SELF) {
                                if (is_null($row[$definitions]))
                                    $this->columns[$definitions] = $row[$definitions];
                                else {
                                    //can go to further entities or just stays in one level
                                    if ($this->recursive) {
                                        $this->columns[$definitions][1]->fetch_id(array(
                                            $this->foreign_relations[$definitions][0] => $row[$definitions]
                                        ));
                                        $this->columns[$definitions] = $this->foreign_relations[$definitions][1]->columns;
                                    } else {
                                        $this->columns[$definitions] = $row[$definitions];
                                    }
                                }
                            } else {
                                if ($this->recursive) {
                                    $this->foreign_relations[$definitions][1]->fetch_id(array(
                                        $this->foreign_relations[$definitions][0] => $row[$definitions]
                                    ));
                                    $this->columns[$definitions] = $this->foreign_relations[$definitions][1]->columns;
                                } else {
                                    $this->columns[$definitions] = $row[$definitions];
                                }
                            }
                        } else {
                            $this->columns[$definitions] = $row[$definitions];
                        }
                    }
                } else {
									$this->err_data = 'No items found';
									return FALSE;
								}
            }
        }
        catch (Exception $ex) {
            // si existe un error se deshace la transacci&#65533;n
            //throw new Exception("(RecuperarCuentas) " . $ex->getMessage());
            $this->err_data = $ex->getMessage();
            return FALSE;
        }
        return TRUE;
    }

		/*
     * Method: count
     * Executes a count command based on the information of the model and per Keys, it returns an array with value of the total count based on the conditions in the first slot
     *
     * @query		the complementary query to add after the WHERE sentence
     * @group		if a group is needed
     * @custom	exectues a custom query from scratch. Will return the entire result so the needed would be to return an array with the count in the first slot
     * */
    public function count($query = "", $group = null, $custom = false)
    {
        $this->err_data = "";

        $result = false;

        $count = 0;

        if ($query != "" && !$custom) {
            $query = " WHERE " . $query;
        }

        $group_query = "";
        $group_text  = "";
        if (!is_null($group)) {
            $group_text = " GROUP BY ";
            foreach ($group as $keys => $condition) {
                if ($count > 0)
                    $group_text .= ' , ';
                $group_text .= $condition;
                $group_query .= ',' . $keys;
                $count++;
            }

            $count = 0;
            $group_text .= " ORDER BY ";
            foreach ($group as $keys => $condition) {
                if ($count > 0)
                    $group_text .= ' , ';
                $group_text .= $condition;
                $group_query .= ',' . $keys;
                $count++;
            }
        }

        $retorno = array();
        if (!$custom) {
            $sql = 'SELECT count(*) as count ' . $group_query . ' FROM ' . $this->db_name . ' ' . $query . ' ' . $group_text . ';';
        } else {
            $sql = $query;
        }


        try {
            $result = $this->db->Execute($sql);

            while ($row = mysqli_fetch_assoc($result)) {
                $retorno[] = $row;
            }
        }
        catch (Exception $ex) {
            // si existe un error se deshace la transacci&#65533;n
            //throw new Exception("(RecuperarCuentas) " . $ex->getMessage());
            $this->err_data = $ex->getMessage();
            return FALSE;
        }
        return $retorno;
    }

		/*
     * Method: delete
     * Executes a delete based on the information of the model and per Keys, return true or false based on the status of the deletion
     *
     * @conditions	the statement after the WHERE command
     * */
    public function delete($conditions = null)
    {
        $this->err_data = "";
        $key_names      = "";

        if (!is_null($conditions)) {
            $key_names = $conditions;
        } else {
            $count = 0;
            foreach ($this->the_key as $keys) {
                if ($count > 0)
                    $key_names .= ' AND ';
                $key_names .= 'a.' . $keys . '=' . ((($this->GetType($this->columns[$keys]) == 'boolean' || $this->GetType($this->columns[$keys]) == 'float' || $this->GetType($this->columns[$keys]) == 'integer' || $this->GetType($this->columns[$keys]) == 'numeric' || $this->GetType($this->columns[$keys]) == 'NULL')) ? '' : "'") . (($this->GetType($this->columns[$keys]) == 'NULL') ? 'NULL' : $this->columns[$keys]) . ((($this->GetType($this->columns[$keys]) == 'boolean' || $this->GetType($this->columns[$keys]) == 'float' || $this->GetType($this->columns[$keys]) == 'integer' || $this->GetType($this->columns[$keys]) == 'numeric' || $this->GetType($this->columns[$keys]) == 'NULL')) ? '' : "'");
                $count++;
            }
        }

        $sql = "DELETE a FROM " . $this->db_name . " a WHERE " . $key_names . ";";

        try {
            $this->BeginTransaction();

            $this->db->Execute($sql);

            $this->Commit();
        }
        catch (Exception $ex) {
            $this->RollBack();
            //throw new Exception("DELETE) " . $e->getMessage());
            $this->err_data = $ex->getMessage();
            return FALSE;
        }

        return TRUE;
    }

		/*
     * Method: update
     * Executes an update command based on the information of the model and per Keys, it returns a true or false value based on the result of the update
     *
     * @conditions		the complementary query to add after the WHERE sentence
     * @set						a custom set of key->value for each column in the table to be updated.
     * @from					the same structure as @set but for assembling a WHERE statement
     * */
    public function update($conditions = null, $set = array(), $from = array())
    {
        $query = "";
        try {
            $this->BeginTransaction();

            if (empty($set))
                $query = $this->assemble_query(true);
            else {
                $count = 0;
                foreach ($set as $key => $value) {
                    if ($count > 0)
                        $query .= ', ';
                    $query .= $key . '=' . ((($this->GetType($value) == 'boolean' || $this->GetType($value) == 'float' || $this->GetType($value) == 'integer' || $this->GetType($value) == 'numeric' || $this->GetType($value) == 'NULL')) ? '' : "'") . (($this->GetType($value) == 'NULL') ? 'NULL' : $value) . ((($this->GetType($value) == 'boolean' || $this->GetType($value) == 'float' || $this->GetType($value) == 'integer' || $this->GetType($value) == 'numeric' || $this->GetType($value) == 'NULL')) ? '' : "'");
                    $count++;
                }
            }

            $key_names = "";
            if (empty($from)) {
                $count = 0;
                foreach ($this->the_key as $keys) {
                    if ($count > 0)
                        $key_names .= ' AND ';
                    $key_names .= $keys . '=' . ((($this->GetType($this->columns[$keys]) == 'boolean' || $this->GetType($this->columns[$keys]) == 'float' || $this->GetType($this->columns[$keys]) == 'integer' || $this->GetType($this->columns[$keys]) == 'numeric' || $this->GetType($this->columns[$keys]) == 'NULL')) ? '' : "'") . (($this->GetType($this->columns[$keys]) == 'NULL') ? 'NULL' : $this->columns[$keys]) . ((($this->GetType($this->columns[$keys]) == 'boolean' || $this->GetType($this->columns[$keys]) == 'float' || $this->GetType($this->columns[$keys]) == 'integer' || $this->GetType($this->columns[$keys]) == 'numeric' || $this->GetType($this->columns[$keys]) == 'NULL')) ? '' : "'");
                    $count++;
                }
            } else {
                $count = 0;
                foreach ($from as $key => $value) {
                    if ($count > 0)
                        $key_names .= ' AND ';
                    $key_names .= $key . '=' . ((($this->GetType($value) == 'boolean' || $this->GetType($value) == 'float' || $this->GetType($value) == 'integer' || $this->GetType($value) == 'numeric' || $this->GetType($value) == 'NULL')) ? '' : "'") . (($this->GetType($value) == 'NULL') ? 'NULL' : $value) . ((($this->GetType($value) == 'boolean' || $this->GetType($value) == 'float' || $this->GetType($value) == 'integer' || $this->GetType($value) == 'numeric' || $this->GetType($value) == 'NULL')) ? '' : "'");
                    $count++;
                }
            }

            $sql = "UPDATE " . $this->db_name . " SET " . $query . " WHERE " . $key_names;

            if (!is_null($conditions)) {
                $sql .= ' AND ' . $conditions;
            }

            //echo $sql;

            $result = $this->db->Execute($sql);

            $this->Commit();
        }
        catch (Exception $e) {
            $this->RollBack();
            $this->err_data = $e->getMessage();
            return FALSE;
            //throw new Exception("(UPDATE) " . $e->getMessage());
        }
        return TRUE;

    }

		/*
     * Method: insert
     * Executes a insert command based on the information of the model, it returns the key of the new element
     *
     * */
    public function insert()
    {
        $query  = "";
        $result = FALSE;

        try {
            $this->BeginTransaction();

            $query   = $this->assemble_query();
            $columns = $this->assemble_insert_columns();

            $sql = "INSERT INTO " . $this->db_name . " (" . $columns . ") VALUES ( " . $query . " ) ";

            $this->db->Execute($sql);
            $result = mysqli_insert_id($this->db->link);

            $this->Commit();
        }
        catch (Exception $e) {
            $this->RollBack();
            $this->err_data = $e->getMessage();
            return FALSE;
        }
        return $result;
    }

    private function assemble_insert_columns()
    {
        $query = "";
        $count = 0;

        foreach ($this->columns as $key => $value) {
            if ($count > 0)
                $query .= ', ';
            $query .= $key;
            $count++;
        }

        return $query;
    }

    private function assemble_query($is_insert = false)
    {
        $query = "";
        $count = 0;

        foreach ($this->columns as $key => $value) {
            if ($count > 0)
                $query .= ', ';
            $query .= ($is_insert ? $key . '=' : "") . ((($this->GetType($value) == 'boolean' || $this->GetType($value) == 'float' || $this->GetType($value) == 'integer' || $this->GetType($value) == 'numeric' || $this->GetType($value) == 'NULL')) ? '' : "'") . (($this->GetType($value) == 'NULL') ? 'NULL' : $value) . ((($this->GetType($value) == 'boolean' || $this->GetType($value) == 'float' || $this->GetType($value) == 'integer' || $this->GetType($value) == 'numeric' || $this->GetType($value) == 'NULL')) ? '' : "'");
            $count++;
        }

        return $query;
    }

		/*
     * Method: fixed_count
     * Executes a command based on a count SQL (previously sent), and will return only the numeric value of the count result
     *
     * @cond		the count query
     * */
		private function fixed_count($cond = "")
    {
        $this->err_data = "";

        $result = false;

        $count = 0;
        $sql   = $cond;

        try {
            $result = $this->db->Execute($sql);

            if ($row = mysqli_fetch_array($result)) {
                $count = $row[0];
            }
        }
        catch (Exception $ex) {
            // si existe un error se deshace la transacci&#65533;n
            //throw new Exception("(RecuperarCuentas) " . $ex->getMessage());
            $this->err_data = $ex->getMessage();
            return FALSE;
        }
        return $count;
    }

}
?>