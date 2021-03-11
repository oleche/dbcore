<?php

/*********************************************
 *
 *  Modificaciones:
 *  Diego Vásquez -- levantar excepciones al detectar error de mysql -- 23/10/2009
 *  Oscar Leche -- Refactoring -- 11/05/2016
 *
 *********************************************/

namespace Geekcow\Dbcore;

use Geekcow\Dbcore\utils\QueryUtils;

class DataBaseManager extends QueryUtils
{
    public $db;

    public function __construct(DataBase $connection)
    {
        //$this->db = new DataBase($server, $user, $password, $database);
        $this->db = $connection;
    }

    public function __destruct()
    {
        $this->db->close();
    }

    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function rollBack()
    {
        $this->db->rollBack();
    }
}
