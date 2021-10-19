<?php namespace devpirates\MVC\Base;
use \devpirates\MVC\ResponseInfo;

abstract class Repo {
    /**
     * Class name for PDO to map to
     * 
     * @var string
     */
    protected $className;
    /**
     * Column string for sql queries
     *
     * @var string
     */
    protected $columnString;
    /**
     * Column array for sql queries
     *
     * @var array
     */
    protected $columnArr;
    /**
     * Table Name
     *
     * @var string
     */
    protected $table;
    /**
     * Id column name
     *
     * @var string
     */
    protected $idColumn;
    /**
     * @var \PDO
     */
    protected $db;

    protected function __construct(string $class, string $idCol = "uid", string $table = null, ?array $columnArr = null) {
        global $app;
        $this->db = $app->DB;
        $this->className = $class;
        $this->idColumn = strtolower($idCol);
        $this->table = isset($table) ? $table : $class . 's';
        $this->table = strtolower($this->table);
        $this->setColumnString($columnArr);
    }

    /**
     * This function sets up the column string and array for the requested object
     *
     * @param array|null $columnArr
     */
    private function setColumnString(?array $columnArr) {
        // If defined columns have not been passed in, reflect them
        if (isset($columnArr) == false || count($columnArr) <= 0) {
            $obj = null;
            try { // try to instantiate a new object from the class name
                $className = $this->className;
                $obj = new $className();
            } catch (\Throwable $th) { }
            if (isset($obj) && is_object($obj)) { // if the object exists
                $reflect = new \ReflectionClass($obj); // build reflection object
                $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC); // get public properties
                $columnArr = array(); // set property names to $columnsArr
                foreach ($props as $value) {
                    $columnArr[] = $value->getName();
                }
            } else { // otherwise use *
                $columnArr = ["*"];
            }
        }

        // set columns str to empty string
        $this->columnString = '';
        $count = count($columnArr); // get columnCount
        $current = 0;
        $this->columnArr = array();
        foreach ($columnArr as $key => $value) { // for each defined column
            $isKeyString = gettype($key) == 'string';
            if (isset($value) && strlen($value) > 0) { // if the value isn't empty
                if ($isKeyString) { // if the key is a string
                    $this->columnString .= "`$key` as `$value`"; // add `column` as `name` sql
                    $this->columnArr[strtolower($key)] = $key;
                } else {
                    $this->columnString .= "`$value`"; // otherwise add `column` sql
                    $this->columnArr[strtolower($value)] = $value;
                }
            } else if ($isKeyString) { // if the value isn't set, and the key is a string
                $this->columnString .= "`$key`"; // add `column` sql
                $this->columnArr[strtolower($key)] = $key;
            }
            if ($current < $count - 1) { // add commas except for on last column
                $this->columnString .= ",";
            }
            $current++;
        }
    }

    /**
     * This function attempts to execute the provided sql and returns a ReponseInfo object
     *
     * @param string $sql
     * @param array|null $params
     * @return ResponseInfo
     */
    protected function _execute(string $sql, ?array $params = null) : ResponseInfo {
        try {
            $statement = $this->db->prepare($sql);
            $statement->execute($params);
            if ($statement->rowCount() > 0) {
                return ResponseInfo::Success();
            } else {
                return ResponseInfo::Error("Failed to execute statement");
            }
        } catch (\Throwable $th) {
            return ResponseInfo::Error($th->getMessage());
        }
    }

    /**
     * This function takes a sql query and params and returns a list of repo objects found from the query
     *
     * @param string $sql
     * @param array|null $params
     * @return array|null
     */
    protected function _query(string $sql, ?array $params = null) : ?array {
        try {
            $statement = $this->db->prepare($sql);
            $statement->execute($params);
            $objs = $statement->fetchAll(\PDO::FETCH_CLASS, $this->className);
            return $objs;
        } catch (\Throwable $th) {
            return null;
        }
    }

    /**
     * Returns entire table contents as array of defined class
     *
     * @param integer|null $limit
     * @param string|null $orderByCol
     * @param boolean $orderByAscending
     * @return array|null
     */
    protected function _getAll(?int $limit = 0, ?string $orderByCol = null, bool $orderByAscending = true) : ?array {
        $sql = "SELECT $this->columnString FROM `$this->table`";
        if (isset($orderByCol) && isset($this->columnArr[strtolower($orderByCol)])) {
            $sql .= " ORDER BY `$orderByCol`";
            if ($orderByAscending === false) {
                $sql .= " DESC";
            }
        }
        if (isset($limit) && $limit > 0) {
            $sql .= " LIMIT $limit";
        }
        return $this->_query($sql);
    }

    /**
     * Returns entire table paged contents as array of defined class
     *
     * @param integer $page
     * @param integer $pageSize
     * @param string $orderByCol
     * @param boolean $orderByAscending
     * @return array|null
     */
    protected function _getAllPaged(int $page, int $pageSize, string $orderByCol, bool $orderByAscending = true) : ?array {
        try {
            $sql = "SELECT $this->columnString FROM `$this->table`";
            if (isset($orderByCol) && isset($this->columnArr[strtolower($orderByCol)])) {
                $sql .= " ORDER BY `$orderByCol`";
                if ($orderByAscending === false) {
                    $sql .= " DESC";
                }
            }
            $offset = ($page - 1) * $pageSize;
            $sql .= " LIMIT $offset, $pageSize";
            $results = $this->_query($sql);
            $totalRecords = $this->_getCount();
            
            return array("results" => $results, "totalRecords" => $totalRecords, "pages" => $totalRecords / $pageSize);
        } catch (\Throwable $th) {
            return null;
        }
    }

    /**
     * Returns count of table
     *
     * @return integer
     */
    protected function _getCount() : int {
        try {
            $statement = $this->db->prepare("SELECT COUNT($this->idColumn) FROM `$this->table`");
            $statement->execute();
            return $statement->fetchColumn();
        } catch (\Throwable $th) {
            return -1;
        }
    }

    /**
     * Queries the table for a particular row by Id
     * Returns an instance of the defined class for the repo filled with the row data
     *
     * @param string|int $id
     * @return object|null
     */
    protected function _getById($id) : ?object {
        try {
            $statement = $this->db->prepare("SELECT $this->columnString FROM `$this->table` WHERE `$this->idColumn`=? LIMIT 1");
            $statement->setFetchMode(\PDO::FETCH_CLASS, $this->className);
            $statement->execute([$id]);
            $object = $statement->fetch();
            return $object;
        } catch (\Throwable $th) {
            return null;
        }
    }

    /**
     * Inserts object into table using sql injection safe column mapping
     * 
     * @param object|null $obj
     * @return ResponseInfo
     */
    protected function _insert(?object $obj) : ResponseInfo {
        if (isset($obj)) {
            try {
                $insertCols = array();
                $objectArr = get_object_vars($obj);
                foreach ($objectArr as $key => $value) {
                    if (gettype($key) == 'string') {
                        $loweredKey = strtolower($key);
                        if ($loweredKey !== $this->idColumn && isset($this->columnArr[$loweredKey])) {
                            if (isset($this->columnArr[$loweredKey])) {
                                $insertCols[$loweredKey] = gettype($value) === 'boolean' ? ($value ? 1 : 0) : $value;
                            }
                        }
                    }
                }
                
                $colCount = count($insertCols);
                if ($colCount > 0) {
                    $colStr = '';
                    $valStr = '';
                    $current = 1;
                    foreach ($insertCols as $key => $value) {
                        $colStr .= "`$key`";
                        $valStr .= ":$key";
                        if ($current++ < $colCount) {
                            $colStr .= ',';
                            $valStr .= ',';
                        }
                    }
                    $statement = $this->db->prepare("INSERT INTO `$this->table` ($colStr) VALUES ($valStr)");
                    $statement->execute($insertCols);
                    if ($statement->rowCount() == 0) {
                        throw new \Exception("Failed to insert object into table");
                    } else {
                        return ResponseInfo::Success($this->db->lastInsertId($this->idColumn));
                    }
                } else {
                    throw new \Exception("Invalid object");
                }
            } catch (\Throwable $th) {
                return ResponseInfo::Error($th->getMessage());
            }
        } else {
            return ResponseInfo::Error("Object cannot be null");
        }
    }

    /**
     * Updates a row in the table using sql injection safe column mapping
     * 
     * @param object|null $obj
     * @return ResponseInfo
     */
    protected function _update(?object $obj) : ResponseInfo {
        if (isset($obj)) {
            try {
                $updateCols = array();
                $objectArr = get_object_vars($obj);
                $hasId = false;
                foreach ($objectArr as $key => $value) {
                    if (gettype($key) == 'string') {
                        $loweredKey = strtolower($key);
                        if ($loweredKey !== $this->idColumn) {
                            if (isset($this->columnArr[$loweredKey])) {
                                $updateCols[$loweredKey] = gettype($value) === 'boolean' ? ($value ? 1 : 0) : $value;
                            }
                        } else if (isset($value)) {
                            $idType = getType($value);
                            $hasId = ($idType == 'string' && strlen($idType) > 0) || ($idType == 'int' && $idType > 0);
                            $updateCols['_id_'] = $value;
                        }
                    }
                }
                if ($hasId === false) {
                    throw new \Exception("Object has no id value");
                }
                
                $colCount = count($updateCols);
                if ($colCount > 0) {
                    $setStr = '';
                    $current = 1;
                    foreach ($updateCols as $key => $value) {
                        if ($key !== "_id_") {
                            $setStr .= "`$key`=:$key";
                            if ($current < $colCount) {
                                $setStr .= ',';
                            }
                        }
                        $current++;
                    }
                    $statement = $this->db->prepare("UPDATE `$this->table` SET $setStr WHERE `$this->idColumn`=:_id_");
                    $statement->execute($updateCols);
                    if ($statement->rowCount() == 0) {
                        throw new \Exception("Failed to insert object into table");
                    } else {
                        return ResponseInfo::Success();
                    }
                } else {
                    throw new \Exception("Invalid object");
                }
            } catch (\Throwable $th) {
                return ResponseInfo::Error($th->getMessage());
            }
        } else {
            return ResponseInfo::Error("Object cannot be null");
        }
    }

    /**
     * Deletes a row by id for configured table
     * 
     * @param any $id
     * @return ResponseInfo
     */
    protected function _delete(mixed $id) : ResponseInfo {
        try {
            $statement = $this->db->prepare("DELETE FROM `$this->table` WHERE `$this->idColumn`=?");
            $statement->execute([$id]);
            if ($statement->rowCount() > 0) {
                return ResponseInfo::Success();
            } else {
                return ResponseInfo::Error("Failed to delete requested item");
            }
        } catch (\Throwable $th) {
            return ResponseInfo::Error($th->getMessage());
        }
    }
}
?>