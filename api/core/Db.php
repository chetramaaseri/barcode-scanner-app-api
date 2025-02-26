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
    private $limit;
    private $order;
    private $join = []; // Array to store join clauses
    private $params = []; // Array to store parameter values for binding
    private $last_query; // Store the last executed query for debugging

    /**
     * Constructor for the Db class.
     * Initializes the database connection.
     *
     * @throws Exception If the connection fails.
     */
    public function __construct() {
        $this->conn = new Mysqli($this->hostname, $this->username, $this->password, $this->dbname, $this->port);
        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }
    }

    /**
     * Returns the last executed query.
     *
     * @return string The last executed query.
     */
    public function last_query() {
        return $this->last_query;
    }

    /**
     * Executes a prepared SQL query.
     *
     * @param string $query The SQL query to execute.
     * @param array $params Optional parameters to bind to the query.
     * @return mysqli_stmt The prepared statement object.
     * @throws Exception If query preparation or execution fails.
     */
    public function run($query, $params = []) {
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
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $this->params = []; // Reset parameters after execution
        return $stmt;
    }

    /**
     * Converts an array of values into an array of references.
     *
     * @param array $arr The array to convert.
     * @return array The array of references.
     */
    private function refValues($arr) {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }

    /**
     * Fetches all rows from the result set as an associative array.
     *
     * @param mysqli_stmt $stmt The prepared statement object.
     * @return array The result set as an associative array.
     * @throws Exception If fetching results fails.
     */
    public function result_array($stmt) {
        $result = $stmt->get_result();
        if ($result === false) {
            throw new Exception("Fetch result failed: " . $stmt->error);
        }
        $data = $result->fetch_all(MYSQLI_ASSOC);
        return $data;
    }

    /**
     * Fetches a single row from the result set as an associative array.
     *
     * @param mysqli_stmt $stmt The prepared statement object.
     * @return array|null The row as an associative array, or null if no rows are found.
     * @throws Exception If fetching results fails.
     */
    public function row_array($stmt) {
        $result = $stmt->get_result();
        if ($result === false) {
            throw new Exception("Fetch result failed: " . $stmt->error);
        }
        $data = $result->fetch_array(MYSQLI_ASSOC);
        return $data;
    }

    /**
     * Returns the number of rows in the result set.
     *
     * @param mysqli_stmt $stmt The prepared statement object.
     * @return int The number of rows.
     * @throws Exception If fetching results fails.
     */
    public function num_rows($stmt) {
        $result = $stmt->get_result();
        if ($result === false) {
            throw new Exception("Fetch result failed: " . $stmt->error);
        }
        $data = $result->num_rows;
        return $data;
    }

    /**
     * Builds and executes a SELECT query based on the current state of the query builder.
     *
     * @return mysqli_stmt The prepared statement object.
     */
    public function get() {
        $sql = $this->select . $this->from . implode(" ", $this->join) . $this->where . $this->order . $this->limit;
        return $this->run($sql, $this->params);
    }

    /**
     * Sets the FROM clause for the query.
     *
     * @param string $from The table name.
     * @return $this The current instance for method chaining.
     */
    public function from($from) {
        $this->from = " FROM " . $from;
        return $this;
    }

    /**
     * Sets the SELECT clause for the query.
     *
     * @param string $select The columns to select.
     * @return $this The current instance for method chaining.
     */
    public function select($select) {
        $this->select = "SELECT " . $select;
        return $this;
    }

    /**
     * Adds a WHERE clause to the query.
     *
     * @param mixed ...$args The conditions for the WHERE clause.
     * @return $this The current instance for method chaining.
     */
    public function where() {
        $args = func_get_args();
        if (is_array($args[0])) {
            foreach ($args[0] as $key => $value) {
                $this->createWhere([$key, $value]);
                $this->params[] = $value; // Store the parameter value
            }
        } else {
            $this->createWhere($args);
            $this->params[] = $args[1]; // Store the parameter value
        }
        return $this;
    }

    /**
     * Helper method to construct the WHERE clause.
     *
     * @param array $args The conditions for the WHERE clause.
     */
    private function createWhere($args) {
        $column = $args[0];
        $value = $args[1];

        // Split the column into table alias and column name (if applicable)
        if (strpos($column, '.') !== false) {
            list($tableAlias, $columnName) = explode('.', $column);
            $column = $tableAlias . '.`' . $columnName . '`';
        } else {
            $column = '`' . $column . '`';
        }

        if (empty($this->where)) {
            $this->where .= " WHERE $column = ? ";
        } else {
            $this->where .= " AND $column = ? ";
        }
    }

    /**
     * Sets the LIMIT clause for the query.
     *
     * @param int $limit The number of rows to limit.
     * @return $this The current instance for method chaining.
     */
    public function limit($limit) {
        $this->limit = " LIMIT " . (int)$limit;
        return $this;
    }

    /**
     * Sets the ORDER BY clause for the query.
     *
     * @param string $order The column and direction to order by.
     * @return $this The current instance for method chaining.
     */
    public function order($order) {
        $this->order = " ORDER BY " . $order;
        return $this;
    }

    /**
     * Adds a JOIN clause to the query.
     *
     * @param string $type Type of join (e.g., INNER, LEFT, RIGHT)
     * @param string $table Table to join
     * @param string $condition Join condition
     * @return $this The current instance for method chaining.
     */
    public function join($type, $table, $condition) {
        $this->join[] = " $type JOIN $table ON $condition ";
        return $this;
    }

    /**
     * Builds and executes an INSERT query.
     *
     * @param string $table The table to insert into.
     * @param array $data An associative array of column-value pairs.
     * @return int The ID of the inserted row.
     * @throws Exception If fetching table schema fails.
     */
    public function insert($table, $data) {
        $fieldNames = array_keys($data);
        $fieldData = array_values($data);
        $this->insert = "INSERT INTO `$table` (`" . implode("`,`", $fieldNames) . "`) VALUES (" . implode(",", array_fill(0, count($fieldData), "?")) . ")";
        $stmt = $this->run($this->insert, $fieldData);
        return $this->conn->insert_id;
    }

    /**
     * Builds and executes an UPDATE query.
     *
     * @param string $table The table to update.
     * @param array $data An associative array of column-value pairs.
     * @return mysqli_stmt The prepared statement object.
     */
    public function update($table, $data) {
        $string = "UPDATE `$table` SET ";
        $params = [];
        foreach ($data as $col => $value) {
            $string .= "`$col` = ? ,";
            $params[] = $value;
        }
        $string = rtrim($string, ",");
        $this->update = $string;
        return $this->run($this->update . $this->where, array_merge($params, $this->params));
    }

    /**
     * Builds and executes a DELETE query.
     *
     * @param string $table The table to delete from.
     * @return mysqli_stmt The prepared statement object.
     */
    public function delete($table) {
        $this->delete = "DELETE FROM `$table` ";
        return $this->run($this->delete . $this->where, $this->params);
    }

    /**
     * Destructor for the Db class.
     * Closes the database connection.
     */
    public function __destruct() {
        $this->conn->close();
    }
}
?>