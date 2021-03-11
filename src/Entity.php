<?php

/* ENTITY component
 * Developed by OSCAR LECHE
 * V.1.0
 * DESCRIPTION: This is the Entity definition class. It will allow the entity object to
 * inherit all the database functions, pagination and searching. This is enabled to work with
 * link header pagination for fony-php
 */

namespace Geekcow\Dbcore;

/*
ENTITY MODEL
Fields:
  type:
    -int, string, boolean
  length:
    -numeric value
  foreign:
    -object from type entity describing class
  pk:
    -true or false
  unique:
    -true or false
  postable: if the field needs to be filled while dinamically created or updated.
    -true or false
*/

class Entity extends DBCore
{
    protected $ipp;
    public $table;
    public $connection;

    //pagination
    public $pagination_link = "";
    public $page;
    public $per_page;

    private $mapping;

    public function __construct($map, $table_name, $configfile = "config.ini")
    {
        $config = parse_ini_file($configfile);

        $this->ipp = $config['dbcore.ipp'] ?? '25';

        $this->connection = DataBase::getInstance($configfile);

        $col = array();//'id', 'name', 'format', 'max_size', 'max_dimensions', 'mime', 'type');
        $key = array();//'id');
        $foreign = null;//'user_id' => array('idusuario', $this->user));

        //Explore the mapping for columns, keys and foreign keys
        $this->mapping = $map;

        if (!is_null($this->mapping)) {
            foreach ($this->mapping as $k => $map) {
                $col[] = $k;
                if (is_array($map)) {
                    if (isset($map['pk']) && $map['pk']) {
                        $key[] = $k;
                    }
                }
                if (isset($map['foreign']) && is_array($map['foreign'])) {
                    if (is_null($foreign)) {
                        $foreign = array();
                    }
                    $foreign[$k] = $map['foreign'];
                }
            }
        }
        //end of exploration
        $table = $this->fromCamelCase($table_name);

        parent::__construct($this->connection, $table, $col, $key, $foreign, $this->ipp, $this->mapping);

        $a = get_class_vars(get_class($this));
    }

    public function get_mapping()
    {
        return $this->mapping;
    }

    /**
     * Handles the pagination via the Link http header. According to ietf rfc5988 definition for linking
     *
     */
    public function paginate($class)
    {
        if ($class->pages > 1) {
            $this->pagination_link = "Link: ";
            if (($this->page + 1) > 1) {
                $this->pagination_link .= "<" . $_SERVER['SCRIPT_URI'] . "?page=" . $this->page;
                $rest = $this->filterGets(array('page', 'request'));
                if ($rest != "") {
                    $this->pagination_link .= "&$rest";
                }
                $this->pagination_link .= '>; rel="prev",';
            }
            if (($this->page + 1) < $class->pages) {
                $this->pagination_link .= "<" . $_SERVER['SCRIPT_URI'] . "?page=" . ($this->page + 2);
                $rest = $this->filterGets(array('page', 'request'));
                if ($rest != "") {
                    $this->pagination_link .= "&$rest";
                }
                $this->pagination_link .= '>; rel="next",';
            }
            if (($this->page + 1) <= $class->pages) {
                $this->pagination_link .= "<" . $_SERVER['SCRIPT_URI'] . "?page=1";
                $rest = $this->filterGets(array('page', 'request'));
                if ($rest != "") {
                    $this->pagination_link .= "&$rest";
                }
                $this->pagination_link .= '>; rel="first",';
            }
            if (($this->page + 1) >= 1) {
                $this->pagination_link .= "<" . $_SERVER['SCRIPT_URI'] . "?page=" . $class->pages;
                $rest = $this->filterGets(array('page', 'request'));
                if ($rest != "") {
                    $this->pagination_link .= "&$rest";
                }
                $this->pagination_link .= '>; rel="last",';
            }
        }
    }

    /**
     * Set the page and the items per page based on the uri parameters per_page and page.
     *
     */
    public function set_paging(&$class, $params)
    {
        $this->per_page = (isset($params['per_page']) && trim($params['per_page']) != '') ?
            $params['per_page'] :
            $this->ipp;
        $this->page = (isset($params['page']) && trim($params['page']) != '') ? $params['page'] : 1;
        if ($this->page <= 0) {
            $this->page = 0;
        } else {
            $this->page -= 1;
        }
        $class->set_ipp($this->per_page);
        return $class;
    }

    public function assembly_search($params)
    {
        return Searchy::assemblySearch($params);
    }

    private function filterGets($ignored = array())
    {
        $query = "";
        $count = 0;
        foreach ($_GET as $key => $value) {
            if (!in_array($key, $ignored)) {
                if (is_array($value)) {
                    $key = $this->convertArrayKey($key, $value);
                    $value = $this->convertArrayValue($value);
                }
                $query .= $key . '=' . $value;
            }
            if ($count > 0) {
                $query .= "&";
            }
            $count++;
        }

        return $query;
    }

    private function convertArrayValue($thekey)
    {
        $output = "";
        foreach ($thekey as $key => $value) {
            $output = $value;
        }
        return $output;
    }

    private function convertArrayKey($thekey, $thevalue)
    {
        $output = "";
        foreach ($thevalue as $key => $value) {
            $output = $thekey . '[';
            $output .= $key . ']';
        }
        return $output;
    }

    private function fromCamelCase($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}
