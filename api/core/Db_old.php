<?php
class Db {

    public $conn;
    private $hostname = "mysql";
    private $dbname = "";
    private $username = "";
    private $password = "";
    private $port = "3306";

    private $select;
    private $where;
    private $from;
    private $update;
    private $insert;
    private $order;

    public function __construct(){
        $this->conn = new Mysqli($this->hostname, $this->username, $this->password, $this->dbname, $this->port);
    }

    public function last_query(){
        return $this->last_query;
    }

    public function run($query){
        $this->last_query = $query;
        $this->select = $this->from = $this->where = $this->order = $this->limit = null;
        $result = $this->conn->query($query);
        return $result;
    }

    public function result_array($result){
        $data = $result->fetch_all(MYSQLI_ASSOC);
        return $data;
    }

    public function row_array($result){
        $data = $result->fetch_array(MYSQLI_ASSOC);
        return $data;
    }

    public function num_rows($result){
        $data = $result->num_rows;
        return $data;
    }

    public function get(){
        $sql = $this->select . $this->from . $this->where . $this->order . $this->limit;
        return $this->run($sql);
    }

    public function from($from){
        $this->from = " FROM " . $from;
    }

    public function select($select){
        $this->select = "SELECT " . $select;
    }

    public function where(){
        $args = func_get_args();
        if (is_array($args[0])) {
            foreach ($args[0] as $key => $value) {
                $this->createWhere([$key, $value]);
            }
        } else {
            $this->createWhere($args);
        }
    }

    private function createWhere($args){
        $args[1] = $this->conn->real_escape_string($args[1]);
        if (empty($this->where)) {
            if(strpos($args[0], "=") !== FALSE){
                $decoded = explode(" ",$args[0]);
                $this->where .= " WHERE `" . $decoded[0] . "` " . $decoded[1] . " '" . $args[1] . "' ";
            }else{
                $this->where .= " WHERE `" . $args[0] . "` = '" . $args[1] . "' ";
            }
        } else {
            if(strpos($args[0], "=") !== FALSE){
                $decoded = explode(" ",$args[0]);
                $this->where .= "AND `" . $decoded[0] . "` " . $decoded[1] . " '" . $args[1] . "' ";
            }else{
                $this->where .= "AND `" . $args[0] . "` = '" . $args[1] . "' ";
            }
        }
    }

    public function update($table, $data){
        $string = "UPDATE `$table` SET ";
        foreach ($data as $col => $value) {
            $string .= "`$col` = '$value' ,";
        }
        $string = rtrim($string, ",");
        $this->update = $string;
        return $this->run($this->update . $this->where);
    }

    public function delete($table){
        $this->delete = "DELETE FROM $table ";
        return $this->run($this->delete . $this->where);
    }

    public function insert($table, $data) {
        $query = "SHOW COLUMNS FROM `$table`";
        $result = $this->run($query);
    
        if (!$result) {
            throw new Exception("Error fetching table schema: " . $this->conn->error);
        }
    
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['Key'] === 'PRI') {
                continue;
            }
    
            $type = strtolower($row['Type']);
            if (strpos($type, 'int') !== false) {
                $columns[$row['Field']] = 0; 
            } elseif (strpos($type, 'timestamp') !== false || strpos($type, 'datetime') !== false) {
                $columns[$row['Field']] = date('Y-m-d H:i:s'); 
            } elseif (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
                $columns[$row['Field']] = "";
            } else {
                $columns[$row['Field']] = $row['Default'];
            }
            if ($row['Null'] === 'YES' && $row['Default'] === null) {
                $columns[$row['Field']] = null;
            }
        }
        $dataWithDefaults = array_merge($columns, $data);
        $fieldNames = array_keys($dataWithDefaults);
        $fieldData = array_values($dataWithDefaults);
        $this->insert = "INSERT INTO `$table` (`" . implode("`,`", $fieldNames) . "`) VALUES ('" . implode("','", array_map([$this->conn, 'real_escape_string'], $fieldData)) . "')";
        $this->run($this->insert);
        return $this->conn->insert_id;
    }

    public function limit($limit){
        $this->limit = " LIMIT " . (int)$limit;
    }

    public function order($order){
        $this->order = " ORDER BY " . $order;
    }

    public function __destruct(){
        $this->conn->close();
    }
}
?>