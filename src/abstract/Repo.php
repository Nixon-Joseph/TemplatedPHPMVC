<?php

namespace devpirates\MVC\Base;

use devpirates\MVC\GUIDHelper;
use \devpirates\MVC\ResponseInfo;

abstract class Repo
{
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
     * If true, will generate GUID for entity ids if not present in insert
     *
     * @var bool
     */
    protected $generateGuidsForIds;
    /**
     * Overridible: fixes mappings from PDO class declaration
     * Most noticable on bit -> bool conversions - bool properties will come
     * with 1/0 values instead of true/false
     * 
     * @var callable
     */
    protected $fixPDOMapping;
    /**
     * @var \PDO
     */
    protected $db;

    protected function __construct(string $class, ?bool $generateGuidsForIds = false, string $idCol = "uid", string $table = null, ?array $columnArr = null)
    {
        global $app;
        $this->db = $app->DB;
        $this->className = $class;
        $this->idColumn = strtolower($idCol);
        $this->generateGuidsForIds = $generateGuidsForIds;
        $this->table = isset($table) ? $table : $class . 's';
        $this->table = strtolower($this->table);
        $this->setColumnString($columnArr);
        $this->fixPDOMapping = function (?object $entity): ?object {
            return $entity;
        };
    }

    /**
     * This function sets up the column string and array for the requested object
     *
     * @param array|null $columnArr
     */
    private function setColumnString(?array $columnArr)
    {
        // If defined columns have not been passed in, reflect them
        if (isset($columnArr) == false || count($columnArr) <= 0) {
            $obj = null;
            try { // try to instantiate a new object from the class name
                $className = $this->className;
                $obj = new $className();
            } catch (\Throwable $th) {
            }
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
    protected function _execute(string $sql, ?array $params = null): ResponseInfo
    {
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
    protected function _query(string $sql, ?array $params = null): ?array
    {
        try {
            $statement = $this->db->prepare($sql);
            $statement->execute($params);
            $objs = $statement->fetchAll(\PDO::FETCH_CLASS, $this->className);
            return array_map($this->fixPDOMapping, $objs);
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
     * @param array $filters - array of QueryFilter
     * @return array|null
     */
    protected function _getAll(?int $limit = 0, ?string $orderByCol = null, bool $orderByAscending = true, ?array $filters = null): ?array
    {
        $sql = "SELECT $this->columnString FROM `$this->table`";
        $params = null;
        if (isset($filters) && count($filters) > 0) {
            $filterResult = $this->_buildSqlFromQueryFilters($filters);
            if (isset($filterResult) && count($filterResult)) {
                $sql .= $filterResult['sql'];
                $params = $filterResult['params'];
            }
        }
        if (isset($orderByCol) && isset($this->columnArr[strtolower($orderByCol)])) {
            $sql .= " ORDER BY `$orderByCol`";
            if ($orderByAscending === false) {
                $sql .= " DESC";
            }
        }
        if (isset($limit) && $limit > 0) {
            $sql .= " LIMIT $limit";
        }
        return $this->_query($sql, $params);
    }

    /**
     * Returns entire table paged contents as array of defined class
     *
     * @param integer $page
     * @param integer $pageSize
     * @param string $orderByCol
     * @param boolean $orderByAscending
     * @param array $filters - array of QueryFilter
     * @return array|null
     */
    protected function _getAllPaged(int $page, int $pageSize, string $orderByCol, bool $orderByAscending = true, ?array $filters = null): ?array
    {
        try {
            $sql = "SELECT $this->columnString FROM `$this->table`";
            $params = null;
            if (isset($filters) && count($filters) > 0) {
                $filterResult = $this->_buildSqlFromQueryFilters($filters);
                if (isset($filterResult) && count($filterResult)) {
                    $sql .= $filterResult['sql'];
                    $params = $filterResult['params'];
                }
            }
            if (isset($orderByCol) && isset($this->columnArr[strtolower($orderByCol)])) {
                $sql .= " ORDER BY `$orderByCol`";
                if ($orderByAscending === false) {
                    $sql .= " DESC";
                }
            }
            $offset = intval(($page - 1) * $pageSize);
            $sql .= " LIMIT $offset, $pageSize";
            $results = $this->_query($sql, $params);
            $totalRecords = $this->_getCount($filters);

            return array("results" => array_map($this->fixPDOMapping, $results), "totalRecords" => $totalRecords, "pages" => ceil($totalRecords / $pageSize));
        } catch (\Throwable $th) {
            return null;
        }
    }

    /**
     * Builds a where clause for query - returns sql and params to match
     * 
     * @param array $filters
     * @param bool $hasWhere
     * @param int $depth
     * @param array $params
     * @param string $groupOperator
     * @return array|null
     */
    protected function _buildSqlFromQueryFilters(array $filters, bool $hasWhere = false, int $depth = 0, array $params = null, string $groupOperator = null): ?array
    {
        if (isset($filters) && count($filters) > 0) {
            $sql = "";
            $addedFilters = false;
            foreach ($filters as $index => $filter) {
                if (gettype($filter) == 'object' && get_class($filter) == 'devpirates\MVC\Base\QueryFilter') {
                    if ($index === 0 && !$hasWhere) {
                        $sql .= ' WHERE ';
                        $params = array();
                        $hasWhere = true;
                    }
                    if ($index > 0 && isset($groupOperator) && strlen($groupOperator)) {
                        $sql .= " $groupOperator ";
                    }
                    if (isset($filter->filters) && count($filter->filters)) {
                        $filterBuild = $this->_buildSqlFromQueryFilters($filter->filters, $hasWhere, $depth + $index, $params, $filter->groupOperator);
                        if (isset($filterBuild) && count($filterBuild)) {
                            $sql .= '(';
                            $sql .= $filterBuild['sql'];
                            $sql .= ')';
                            $params = array_merge($params, $filterBuild['params']);
                            $addedFilters = true;
                        }
                    } else if (isset($this->columnArr[strtolower($filter->column)])) {
                        $loweredKey = strtolower($filter->column);
                        $paramKey = isset($filter->placeholderOverride) && strlen($filter->placeholderOverride) ? $filter->placeholderOverride : $loweredKey . $depth . $index;
                        $sql .= "$loweredKey " . $filter->operator . " :$paramKey";
                        $params[$paramKey] = gettype($filter->value) === 'boolean' ? ($filter->value ? 1 : 0) : $filter->value;
                        $addedFilters = true;
                    }
                }
            }
            if ($addedFilters) {
                return array('sql' => $sql, 'params' => $params);
            }
        }
        return null;
    }

    /**
     * Returns count of table
     *
     * @param array $filters
     * @return integer
     */
    protected function _getCount(?array $filters = null): int
    {
        try {
            $sql = "SELECT COUNT($this->idColumn) FROM `$this->table`";
            $params = null;
            if (isset($filters) && count($filters) > 0) {
                $filterResult = $this->_buildSqlFromQueryFilters($filters);
                if (isset($filterResult) && count($filterResult)) {
                    $sql .= $filterResult['sql'];
                    $params = $filterResult['params'];
                }
            }
            $statement = $this->db->prepare($sql);
            $statement->execute($params);
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
    protected function _getById($id): ?object
    {
        try {
            $statement = $this->db->prepare("SELECT $this->columnString FROM `$this->table` WHERE `$this->idColumn`=? LIMIT 1");
            $statement->setFetchMode(\PDO::FETCH_CLASS, $this->className);
            $statement->execute([$id]);
            $object = $statement->fetch();
            return ($this->fixPDOMapping)($object);
        } catch (\Throwable $th) {
            return null;
        }
    }

    /**
     * Queries the table for a particular rows by included ids
     * Returns an array of instances of the defined class for the repo filled with the row data
     *
     * @param array $ids
     * @return array|null
     */
    protected function _getByIds(array $ids): ?array
    {
        try {
            $in  = str_repeat('?,', count($ids) - 1) . '?';
            $statement = $this->db->prepare("SELECT $this->columnString FROM `$this->table` WHERE `$this->idColumn` IN ($in)");
            $statement->execute($ids);
            $objs = $statement->fetchAll(\PDO::FETCH_CLASS, $this->className);
            return array_map($this->fixPDOMapping, $objs);
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
    protected function _insert(?object $obj): ResponseInfo
    {
        if (isset($obj)) {
            try {
                $insertCols = array();
                $objectArr = get_object_vars($obj);
                $returnKnownId = false;
                foreach ($objectArr as $key => $value) {
                    if (gettype($key) == 'string') {
                        $loweredKey = strtolower($key);
                        if ($loweredKey !== $this->idColumn && isset($this->columnArr[$loweredKey])) {
                            $insertCols[$loweredKey] = gettype($value) === 'boolean' ? ($value ? 1 : 0) : $value;
                        }
                    }
                }
                if ($this->generateGuidsForIds && !isset($insertCols[$this->idColumn])) {
                    $insertCols[$this->idColumn] = GUIDHelper::GUIDv4();
                    $returnKnownId = true;
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
                        return ResponseInfo::Success($returnKnownId ? $insertCols[$this->idColumn] : $this->db->lastInsertId($this->idColumn));
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
     * @param array $filters
     * @return ResponseInfo
     */
    protected function _update(?object $obj, ?array $filters = null): ResponseInfo
    {
        if (isset($obj)) {
            try {
                $updateCols = array();
                $objectArr = get_object_vars($obj);
                $hasId = false;
                $idValue = null;
                foreach ($objectArr as $key => $value) {
                    if (gettype($key) == 'string') {
                        $loweredKey = strtolower($key);
                        if ($loweredKey !== $this->idColumn) {
                            if (isset($this->columnArr[$loweredKey])) {
                                $updateCols[$loweredKey] = gettype($value) === 'boolean' ? ($value ? 1 : 0) : $value;
                            }
                        } else if (isset($value)) {
                            $idType = getType($value);
                            $hasId = ($idType == 'string' && strlen($idType) > 0) || (($idType == 'int' || $idType == 'integer') && $idType > 0);
                            $idValue = $value;
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
                    $sql = "UPDATE `$this->table` SET $setStr";
                    $params = array();
                    $idFilter = QueryFilter::Filter($this->idColumn, $idValue, '=', '_id_');
                    $filters = isset($filters) ? array(QueryFilter::FilterGroup(array_merge([$idFilter], $filters), 'AND')) : array($idFilter);
                    $filterResult = $this->_buildSqlFromQueryFilters($filters);
                    if (isset($filterResult) && count($filterResult)) {
                        $sql .= $filterResult['sql'];
                        $params = $filterResult['params'];
                    }
                    $statement = $this->db->prepare($sql);
                    $statement->execute(array_merge($updateCols, $params));
                    if ($statement->rowCount() == 0) {
                        throw new \Exception("Failed to update object on table");
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
     * @param mixed $id
     * @param array $filters
     * @return ResponseInfo
     */
    protected function _delete($id, ?array $filters = null): ResponseInfo
    {
        try {
            $sql = "DELETE FROM `$this->table`";
            $params = null;
            $idFilter = QueryFilter::Filter($this->idColumn, $id, '=', '_id_');
            $filters = isset($filters) ? array(QueryFilter::FilterGroup(array_merge([$idFilter], $filters), 'AND')) : array($idFilter);
            $filterResult = $this->_buildSqlFromQueryFilters($filters);
            if (isset($filterResult) && count($filterResult)) {
                $sql .= $filterResult['sql'];
                $params = $filterResult['params'];
            }
            $statement = $this->db->prepare($sql);
            $statement->execute($params);
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

class QueryFilter
{
    /**
     * Column name for comparison
     * 
     * @var string
     */
    public $column;
    /**
     * operator (=, !=, >, <, LIKE)
     *
     * @var string
     */
    public $operator;
    /**
     * string value for comparison
     *
     * @var mixed
     */
    public $value;
    /**
     * custom key for prepared statement value placeholder
     *
     * @var string
     */
    public $placeholderOverride;
    /**
     * Array of QueryFilters
     *
     * @var array
     */
    public $filters;
    /**
     * operator for all filters in $filters (AND, OR)
     *
     * @var array
     */
    public $groupOperator;

    public static function Filter(string $column, string $value, string $operator = '=', ?string $placeholderOverride = null): QueryFilter
    {
        $filter = new QueryFilter();
        $filter->column = $column;
        $filter->value = $value;
        $filter->operator = $operator;
        $filter->placeholderOverride = $placeholderOverride;
        return $filter;
    }

    public static function FilterGroup(array $filters, string $groupOperator = 'AND'): QueryFilter
    {
        $filter = new QueryFilter();
        $filter->filters = $filters;
        $filter->groupOperator = $groupOperator;
        return $filter;
    }
}
