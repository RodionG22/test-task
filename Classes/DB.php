<?php
class DB
{
    public $connection;
    public $lastq;
    public function __construct()
    {
        $this->connection = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        if ($this->connection->connect_error) {
            throw new \Exception('Error: ' . $this->connection->error . '<br />Error No: ' . $this->connection->errno);
        }
        $this->connection->set_charset("utf8");
        $this->connection->query("SET SQL_MODE = ''");
    }
    /**
     * @throws Exception
     */
    public function query($sql, $one_column = false)
    {
        $this->lastq=$sql;
        $query = $this->connection->query($sql);
        if (!$this->connection->error) {
            if ($query instanceof \mysqli_result) {
                if ($one_column) {
                    $result=array();
                    foreach ($query->fetch_all() as $dat) {
                        $result[]=$dat[0];
                    }
                    return $result;
                } else {
                    return $query->fetch_all(MYSQLI_ASSOC);
                }
            } else {
                return true;
            }
        } else {
            echo $sql."\n";
            
            echo('Error: ' . $this->connection->error . '<br />Error No: ' . $this->connection->errno . '<br />' . $sql);
        }

    }
    public function last_id(){
        return $this->connection->insert_id;
    }   
}