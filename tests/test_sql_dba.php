<?php

require_once 'PEAR.php';
require_once '../Sql_parse.php';
require_once 'DB/DBA/DBA_Relational.php';

$sqlfile = implode('', file('tables.sql'));

$parser = new Sql_Parser($sqlfile);
$db = new DBA_Relational('./', 'simple');

$tokenMap = array(
    SQL_SIZE=>DBA_SIZE,
    SQL_DEFAULT=>DBA_DEFAULT,
    SQL_NOTNULL=>DBA_NOTNULL,
    SQL_DOMAIN=>DBA_DOMAIN,
    SQL_INT=>DBA_INTEGER,
    SQL_FIXED=>DBA_NUMERIC,
    SQL_FLOAT=>DBA_FLOAT,
    SQL_CHAR=>DBA_CHAR,
    SQL_VARCHAR=>DBA_VARCHAR,
    SQL_TEXT=>DBA_TEXT,
    SQL_BOOL=>DBA_BOOLEAN,
    SQL_ENUM=>DBA_ENUM,
    SQL_SET=>DBA_SET,
    SQL_TIMESTAMP=>DBA_TIMESTAMP,
    SQL_DATE=>DBA_TIMESTAMP,
    SQL_TIME=>DBA_TIMESTAMP
);

while (1) {
    $tree = $parser->parse();
    if (PEAR::isError($tree)) {
        break;
    } else {
        switch ($tree[SQL_COMMAND]) {
            case SQL_CREATE_TABLE:
                $name = $tree[SQL_NAME];
                $schema = array();
                foreach ($tree[SQL_FIELDS] as $field) {
                    $fieldName = $field[SQL_NAME];
                    $schema[$fieldName][DBA_TYPE] = $tokenMap[$field[SQL_TYPE]];
                    if ($field[SQL_SIZE])
                        $schema[$fieldName][DBA_SIZE] = $field[SQL_SIZE];
                    if ($field[SQL_DEFAULT])
                        $schema[$fieldName][DBA_DEFAULT] = $field[SQL_DEFAULT];
                    if ($field[SQL_NOTNULL])
                        $schema[$fieldName][DBA_NOTNULL] = $field[SQL_NOTNULL];
                    if ($field[SQL_DOMAIN])
                        $schema[$fieldName][DBA_DOMAIN] = $field[SQL_DOMAIN];
                }
                if ($result = PEAR::isError($db->createTable($name, $schema))) {
                    echo $result."\n";
                } else {
                    echo "Created $name successfully!\n";
                }
                break;
        }
    }
}
?>
