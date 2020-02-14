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

    public function __construct($connection)
    {
        //$this->db = new DataBase($server, $user, $password, $database);
        $this->db = $connection;
    }

    public function __destruct()
    {
        $this->db->Close();
    }

    public function BeginTransaction()
    {
        $this->db->BeginTransaction();
    }

    public function Commit()
    {
        $this->db->Commit();
    }

    public function RollBack()
    {
        $this->db->RollBack();
    }
}
?>
