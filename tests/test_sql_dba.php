<?php
require_once 'PEAR.php';
require_once 'DB/DBA/DBA_Relational.php';
require_once 'DB/DBA/DBA_Sql.php';
require_once 'DB/DBA/Sql_parse.php';

$db = new DBA_Relational('./', 'simple');
$results = executeSql($db, 'employment.sql');

if (PEAR::isError($results)) {
    echo $results->getMessage();
} else {
    echo $results;
}
$db->close();

?>
