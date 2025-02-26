<?php
class Db {

    public $conn;
    private $hostname = "mysql";
    private $dbname = "barcode-app";
    private $username = "root";
    private $password = "root";
    private $port = "3306";

    private $select;
    private $where;
    private $from;
    private $update;
    private $insert;
    private $order;
    private $join = []; // Array to store join clauses

    public function __construct(){
        echo "DVSb"; exit;
        $this->conn = new Mysqli($this->hostname, $this->username, $this->password, $this->dbname, $this->port);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function last_query(){
        return $this->last_query;
    }

    public function run($query, $params = []){
        $this->last_query = $query;
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_double($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $bindParams[] = $param;
            }
            array_unshift($bindParams, $types);
            call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
        }
        $stmt->execute();
        return $stmt;
    }

    private function refValues($arr){
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }

    public function result_array($stmt){
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        return $data;
    }

    public function row_array($stmt){
        $result = $stmt->get_result();
        $data = $result->fetch_array(MYSQLI_ASSOC);
        return $data;
    }

    public function num_rows($stmt){
        $result = $stmt->get_result();
        $data = $result->num_rows;
        return $data;
    }

    public function get(){
        $sql = $this->select . $this->from . implode(" ", $this->join) . $this->where . $this->order . $this->limit;
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
        if (empty($this->where)) {
            if(strpos($args[0], "=") !== FALSE){
                $decoded = explode(" ",$args[0]);
                $this->where .= " WHERE `" . $decoded[0] . "` " . $decoded[1] . " ? ";
            }else{
                $this->where .= " WHERE `" . $args[0] . "` = ? ";
            }
        } else {
            if(strpos($args[0], "=") !== FALSE){
                $decoded = explode(" ",$args[0]);
                $this->where .= "AND `" . $decoded[0] . "` " . $decoded[1] . " ? ";
            }else{
                $this->where .= "AND `" . $args[0] . "` = ? ";
            }
        }
    }

    public function update($table, $data){
        $string = "UPDATE `$table` SET ";
        $params = [];
        foreach ($data as $col => $value) {
            $string .= "`$col` = ? ,";
            $params[] = $value;
        }
        $string = rtrim($string, ",");
        $this->update = $string;
        return $this->run($this->update . $this->where, $params);
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
        $this->insert = "INSERT INTO `$table` (`" . implode("`,`", $fieldNames) . "`) VALUES (" . implode(",", array_fill(0, count($fieldData), "?")) . ")";
        $stmt = $this->run($this->insert, $fieldData);
        return $this->conn->insert_id;
    }

    public function limit($limit){
        $this->limit = " LIMIT " . (int)$limit;
    }

    public function order($order){
        $this->order = " ORDER BY " . $order;
    }

    /**
     * Add a JOIN clause to the query.
     *
     * @param string $type Type of join (e.g., INNER, LEFT, RIGHT)
     * @param string $table Table to join
     * @param string $condition Join condition
     */
    public function join($type, $table, $condition){
        $this->join[] = " $type JOIN $table ON $condition ";
    }

    public function __destruct(){
        $this->conn->close();
    }
}
?>