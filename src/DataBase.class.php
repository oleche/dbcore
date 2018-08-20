<?php
/*********************************************
 *
 *  Modificaciones:
 * Diego Vásquez -- levantar excepciones al detectar error de mysql -- 23/10/2009
 * Oscar Leche -- Refactoring -- 16/08/2017
 *
 *********************************************/

// metodos gen&#65533;ricos para la base de datos

class DataBase
{
    var $host; // Host name
    var $username; // Mysql username
    var $password; // Mysql password
    var $db_name; // Database name
    var $db;

    protected static $instance = null;

    public final static function getInstance($configfile = "config.ini")
    {
        $config = parse_ini_file($configfile);

        $server      = $config['server'];
        $db_user     = $config['db_user'];
        $db_pass     = $config['db_pass'];
        $db_database = $config['database'];

        if (!self::$instance) {
            self::$instance = new DataBase($server, $db_user, $db_pass, $db_database);
            //INSTANCE CREATED
        }
        return self::$instance;
    }

    // constructor
    private function __construct($_host, $_username, $_password, $_db_name)
    {
        $this->host     = $_host;
        $this->username = $_username;
        $this->password = $_password;
        $this->db_name  = $_db_name;

        // Connect to server and select databse.
        $this->db = new PDO("mysql:host=$_host;dbname=$_db_name", $_username, $_password) or die("cannot connect");
        $result = $this->db->query("SET NAMES 'utf8';");
        $result = $this->db->query("SET CHARACTER_SET 'utf8';");

    }

    function Execute($sql)
    {
        $result = $this->db->query($sql);
        $stmt = $this->db->prepare($sql);
        // si existe un error se levanta una excepci&#65533;n
        if (!$stmt->execute()) {
            throw new Exception($stmt->errorInfo()[0] . ": " . $stmt->errorInfo()[2]);
        }
        return $stmt;
    }

    function LastID(){
      return $this->db->lastInsertId();
    }

    // valida informaci&#65533;n que se adjuntar&#65533; a un SQL para prevenir el SQL Injection
    function CheckSQL($param)
    {
        $param = stripslashes($param);
        $param = $this->db->quote($param);
        return $param;
    }

    function BeginTransaction()
    {
        $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT,0);
        $this->db->query("START TRANSACTION ISOLATION LEVEL SERIALIZATION;");
        $this->db->beginTransaction();
    }

    function Commit()
    {
        $this->db->setAttribute( PDO::ATTR_AUTOCOMMIT, 1 );
        $this->db->commit();
    }

    function Rollback()
    {
        $this->db->setAttribute( PDO::ATTR_AUTOCOMMIT, 1 );
        $this->db->rollBack();
    }

    function Close()
    {
        //mysqli_close($this->db);
    }
}
?>
