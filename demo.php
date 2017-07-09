<?php
/**
 * Created by PhpStorm.
 * User: holyzhuo
 * Date: 2017/7/9
 * Time: 21:58
 */
require 'conn/PDO.class.php';
define('DBHost', '127.0.0.1');
define('DBName', 'test');
define('DBUser', 'root');
define('DBPassword', 'root');

$DB = new Db(DBHost, DBName, DBUser, DBPassword);
$res = $DB->query("select * from table where id=:id", array('id'=>6));
print_r($res);