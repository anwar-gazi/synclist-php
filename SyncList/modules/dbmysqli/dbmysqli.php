<?php

class QueryResult
{
    /**
     * null if no data
     */
    public $row;
    /** @var array */
    public $rows;
    /** @var integer */
    public $num_rows;

    function __construct()
    {
    }
}

/**
 * A mysqli wrapper
 */
final class DBMySQLi extends SyncListModule
{
    /** @var \mysqli */
    private $link;

    /** @var \SyncListModule\mPDO\mPDO */
    public $pdo;

    public function __construct($kernel, $config, $module_path)
    {
        parent::__construct($kernel, $config, $module_path);
    }

    public function connect($hostname, $username, $password, $database)
    {
        $this->pdo = $this->load->module('mpdo', $hostname, $username, $password, $database);

        $this->link = new \mysqli($hostname, $username, $password, $database);
        if ($this->link->connect_error) {
            trigger_error('Error: Could not make a database link (' . $this->link->connect_errno . ') ' . $this->link->connect_error);
            exit();
        }
        $this->link->set_charset("utf8mb4");
        $this->link->query("SET SQL_MODE = ''");

        return $this;
    }

    /** standard methods */
    /**
     * @param string $sql
     * @return QueryResult or bool
     */
    public function query($sql)
    {
        $query = $this->link->query($sql);
        if (!$this->link->errno) {
            if ($query instanceof \mysqli_result) {
                $data = array();

                while ($row = $query->fetch_assoc()) {
                    $data[] = $row;
                }

                $result = new QueryResult();
                $result->num_rows = $query->num_rows;
                $result->row = isset($data[0]) ? $data[0] : array();
                $result->rows = $data;

                $query->close();

                return $result;
            } else {
                return true;
            }
        } else {
            trigger_error('Error: ' . $this->link->error . '<br />Error No: ' . $this->link->errno . '<br />' . $sql);
        }
    }

    /**
     * @param $sql
     * @param string $class_name
     * @return bool|QueryResult boolean if there is a problem with the query or the query doesnt return data(like inserty, update queries)
     */
    public function get_object($sql, $class_name = 'stdClass')
    {
        $query = $this->link->query($sql);

        if (!$this->link->errno) {
            if ($query instanceof \mysqli_result) {
                $data = array();

                while ($row = $query->fetch_object($class_name)) {
                    $data[] = $row;
                }

                $result = new QueryResult();
                $result->num_rows = $query->num_rows;
                $result->row = isset($data[0]) ? $data[0] : null;
                $result->rows = $data;

                $query->close();

                return $result;
            } else {
                return true;
            }
        } else {
            trigger_error('Error: ' . $this->link->error . '<br />Error No: ' . $this->link->errno . '<br />' . $sql);
            return false;
        }
    }

    public function escape($value)
    {
        return $this->link->real_escape_string($value);
    }

    public function countAffected()
    {
        return $this->link->affected_rows;
    }

    public function getLastId()
    {
        return $this->link->insert_id;
    }

    public function getLastError()
    {
        return $this->link->error;
    }

    public function __destruct()
    {
        if ($this->link) {
            $this->link->close();
        }
    }

    public function ping()
    {
        $this->link->ping();
    }

    public function set_charset_utf8true()
    {
        $this->link->set_charset('utf8mb4');
    }

    public function get_link()
    {
        return $this->link;
    }

}
