<?php
require_once 'PEAR.php';
require_once 'DB/DBA/DBA_Relational.php';
require_once 'DB/DBA/DBA_Sql.php';
require_once 'DB/DBA/Sql_parse.php';

echo "Got here\n";
$db = new DBA_Relational('./', 'simple');

$statements = explode(';', implode('', file('employment.sql')));

foreach ($statements as $statement) {
    echo $statement;
    $results = executeSql($db, $statement);

    if (PEAR::isError($results)) {
        echo $results->getMessage();
    } else {
        echo $results;
    }
}
$db->close();

?>
