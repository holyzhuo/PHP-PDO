<?php

/**
 * Created by PhpStorm.
 * User: holyzhuo
 * Date: 2017/7/9
 * Time: 11:17
 */
require(dirname(__FILE__) . "/PDO.Log.class.php");
class DB
{
    private $_host;
    private $_dbName;
    private $_dbUser;
    private $_dbPassword;
    private $_pdo;
    private $_sQuery;
    private $_connected = false;
    private $_log;
    private $_parameters;

    public $success = false;
    public $rowCount   = 0;
    public $columnCount   = 0;
    public $querycount = 0;

    public function __construct($host, $dbName, $dbUser, $dbPassword)
    {
        $this->_log        = new Log();
        $this->_host       = $host;
        $this->_dbName     = $dbName;
        $this->_dbUser     = $dbUser;
        $this->_dbPassword = $dbPassword;
        $this->connect();
        $this->_parameters = array();

    }

    private function connect()
    {
        try {
            $this->_pdo = new PDO('mysql:dbname=' . $this->_dbName . ';host=' . $this->_host . ';charset=utf8',
                $this->_dbUser,
                $this->_dbPassword,
                array(
                    //For PHP 5.3.6 or lower
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                    PDO::ATTR_EMULATE_PREPARES => false,

                    //长连接
                    //PDO::ATTR_PERSISTENT => true,

                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  //设置错误报告，抛出异常
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true   //使用buffer，结果集在php内存中,不在数据库
                )
            );
            /*
            //For PHP 5.3.6 or lower
            $this->_pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
            $this->_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //$this->_pdo->setAttribute(PDO::ATTR_PERSISTENT, true);//长连接
            $this->_pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            */
            $this->_connected = true;

        }
        catch (PDOException $e) {
            echo $this->ExceptionLog($e->getMessage());
            die();
        }

    }

    public function closeConnection()
    {
        $this->_pdo = null;
    }

    private function ExceptionLog($message, $sql = "")
    {
        $exception = 'Unhandled Exception. <br />';
        $exception .= $message;
        $exception .= "<br /> You can find the error back in the log.";

        if (!empty($sql)) {
            $message .= "\r\nRaw SQL : " . $sql;
        }
        $this->_log->write($message, $this->_dbName . md5($this->_dbPassword));
        //Prevent search engines to crawl
        header("HTTP/1.1 500 Internal Server Error");
        header("Status: 500 Internal Server Error");
        return $exception;
    }

    private function init($query, $parameters = "")
    {
        if (!$this->_connected) {
            $this->connect();
        }
        try {
            $this->_parameters = $parameters;
            $this->_sQuery     = $this->_pdo->prepare($this->buildParams($query, $this->_parameters));

            if (!empty($this->_parameters)) {
                //set array start from 1
                if (array_key_exists(0, $parameters)) {
                    $parametersType = true;
                    array_unshift($this->_parameters, "");
                    unset($this->_parameters[0]);
                } else {
                    $parametersType = false;
                }
                var_dump($this->_parameters);echo "</br>";
                foreach ($this->_parameters as $column => $value) {
                    $this->_sQuery->bindParam($parametersType ? intval($column) : ":" . $column, $this->_parameters[$column]); //It would be query after loop end(before 'sQuery->execute()').It is wrong to use $value.
                }
            }

            $this->success = $this->_sQuery->execute();
            $this->querycount++;
        }
        catch (PDOException $e) {
            echo $this->ExceptionLog($e->getMessage(), $this->buildParams($query));
            die();
        }

        $this->_parameters = array();
    }

    private function buildParams($query, $params = null)
    {
        if (!empty($params)) {
            $rawStatement = explode(" ", $query);
            foreach ($rawStatement as $value) {
                //replace where in (?)
                if (strtolower($value) == 'in') {
                    return str_replace("(?)", "(" . implode(",", array_fill(0, count($params), "?")) . ")", $query);
                }
            }
        }
        return $query;
    }

    public function query($query, $params = null, $fetchmode = PDO::FETCH_ASSOC)
    {
        $query        = trim($query);
        $rawStatement = explode(" ", $query);
        $this->init($query, $params);
        $statement = strtolower($rawStatement[0]);
        if ($statement === 'select' || $statement === 'show') {
            return $this->_sQuery->fetchAll($fetchmode);
        } elseif ($statement === 'insert' || $statement === 'update' || $statement === 'delete') {
            return $this->_sQuery->rowCount();
        } else {
            return NULL;
        }
    }

    public function getRow($query, $params = null, $fetchmode = PDO::FETCH_ASSOC){
        $res = $this->query($query, $params, $fetchmode);
        return $res[0];
    }

    public function getOne($query, $params = null, $fetchmode = PDO::FETCH_NUM){
        $res = $this->query($query, $params, $fetchmode);
        return $res[0][0];
    }

    public function showSql(){
        return $this->_sQuery->debugDumpParams();
    }

    public function lastInsertId()
    {
        return $this->_pdo->lastInsertId();
    }


}