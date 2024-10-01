<?php
namespace PatrickHull;
use PatrickHull\MysqlWrapper\DbConnection;
use PatrickHull\MysqlWrapper\SelectQuery;

$db = New DbConnection("localhost", "root", "Welcome1");
try {
    $link = $db->connect();
} catch (\Exception $e) {
    die($e->getMessage());
}


$query = New SelectQuery($link);
$query->database = "test_db";
$query->table = "table_with_data";
$result = $query->Execute();
print_r($result);
