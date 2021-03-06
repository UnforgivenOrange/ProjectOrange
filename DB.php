<?php
/**
 * Created by Josh.
 * Date: 30/06/2018
 * Time: 17:01
 * Last Update: 30/06/2018 - 17:01
 */

namespace ProjectOrange;

use PDO;
use PDOStatement;

class DB
{

    /**
     * @var string
     */
    private $dsn, $user, $pass, $pdo;

    /**
     * @var PDOStatement
     */
    private $pdo_stmt;

    /**
     * DB constructor.
     * @param $database
     * @param $user
     * @param string $host
     * @param string $driver
     * @param string $password
     */
    public function __construct($database = 'database', $user = 'db_user', $host = 'localhost', $driver = 'mysql', $password = 'password')
    {
        $this->dsn = "{$driver}:dbname={$database};host={$host}";
        $this->user = $user;
        $this->pass = $password;
        $this->pdo = new PDO($this->dsn, $this->user, $this->pass);
    }


    /**
     * @param string $table
     * @param array $columns
     * @param array $where
     * @param string $extra
     * @return array
     */
    public function select(string $table, array $columns = [], array $where = [], string $extra = '')
    {

        // Defaults to all columns if none is specified
        $col_stmt = empty($columns) ? "*" : implode(',', $columns);

        // Ensures SQL is not malformed if $where is not specified
        $where_stmt = empty($where) ? "" : "where ";

        $where_arr = [];

        foreach ($where as $k => $v) {

            if (!is_array($v)) {
                $where_arr[] = "`{$k}` = :{$k}";
                continue;
            }

            $arr = array();

            $valArr = array();

            foreach($v as $key => $val)
            {
                $arr[$k . $key] = ":{$k}{$key}";

                $valArr[$k . $key] = $val;
            }

            unset($where[$k]);

            $where = array_merge($where, $valArr);


            $where_arr[] = "`{$k}` in (".implode(',', $arr).")";
        }

        $where_stmt .= implode(' and ', $where_arr);

        // PDO statement
        $stmt = "select {$col_stmt} from {$table} {$where_stmt} {$extra};";

        $this->pdo_stmt = $this->pdo->prepare($stmt);

        $this->pdo_stmt->execute($where);

        $result = $this->pdo_stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * @param string $table
     * @param array $columns_assoc
     * @return bool
     */
    public function insert(string $table, array $columns_assoc)
    {

        $col_keys = array_keys($columns_assoc);

        foreach ($col_keys as $k => $v) {
            $col_keys[$k] = "`{$v}`";
        }

        $keys = implode(',', $col_keys);

        $key_holders = [];

        foreach ($columns_assoc as $k => $v) {
            $key_holders[] = ":{$k}";
            if (is_array($v)) {
                $columns_assoc[$k] = json_encode($v);
            }
        }

        $values = implode(',', $key_holders);

        $stmt = "insert into {$table} ({$keys}) values ($values)";

        $this->pdo_stmt = $this->pdo->prepare($stmt);

        return $this->pdo_stmt->execute($columns_assoc);
    }

    /**
     * @param string $table
     * @param array $columns_assoc
     * @param array $where
     * @return bool
     */
    public function update(string $table, array $columns_assoc, array $where = [])
    {

        $where_stmt = empty($where) ? "" : "where ";

        $col_arr = [];

        foreach ($columns_assoc as $k => $v) {
            $col_arr[] = "`{$k}`=:{$k}";
            if (is_array($v)) {
                $columns_assoc[$k] = json_encode($v);
            }
        }

        $col_stmt = implode(',', $col_arr);


        $where_arr = [];

        foreach ($where as $k => $v) {
            $where_arr[] = "{$k}=:{$k}";
        }

        $where_stmt .= implode(' and ', $where_arr);

        $stmt = "update {$table} set {$col_stmt} {$where_stmt}";

        $this->pdo_stmt = $this->pdo->prepare($stmt);

        return $this->pdo_stmt->execute(array_merge($columns_assoc, $where));
    }

    /**
     * @param string $table
     * @param array $where
     * @return bool
     */
    public function delete(string $table, array $where)
    {

        $where_stmt = empty($where) ? "" : "where ";

        $where_arr = [];

        foreach ($where as $k => $v) {
            $where_arr[] = "{$k} = :{$k}";
        }

        $where_stmt .= implode(' and ', $where_arr);

        $stmt = "delete from {$table} {$where_stmt}";

        $this->pdo_stmt = $this->pdo->prepare($stmt);

        return $this->pdo_stmt->execute($where);
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $where
     * @param string $extra
     * @return array
     */
    public function row(string $table, array $columns = [], array $where = [], string $extra = '')
    {
        $result = $this->select($table, $columns, $where, $extra);

        return isset($result[0]) ? $result[0] : $result;
    }

    /**
     * @param string $stmt
     * @param array $params
     * @return array
     */
    public function query(string $stmt, array $params)
    {
        $this->pdo_stmt = $this->pdo->prepare($stmt);

        $this->pdo_stmt->execute($params);

        return $this->pdo_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function errorInfo()
    {
        return $this->pdo_stmt->errorInfo();
    }


    public function queryString()
    {
        return $this->pdo_stmt->queryString;
    }

}