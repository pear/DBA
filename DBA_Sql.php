<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2002 Brent Cook                                        |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA|
// +----------------------------------------------------------------------+
//
// $Id$
//

/**
 * Functions for executing SQL queries on a DBM database
 * @author  Brent Cook <busterb@mail.utexas.edu>
 * @version 0.18
 * @access  public
 * @package DBA
 */
require_once 'PEAR.php';
require_once 'DB/DBA/Sql_parse.php';
require_once 'DB/DBA/DBA_Relational.php';

function executeSql(&$db, $sqlText) {
    $parser = new Sql_Parser($sqlText);

    $typeMap = array(
        'int'=>DBA_INTEGER,
        'integer'=>DBA_INTEGER,
        'numeric'=>DBA_FIXED,
        'float'=>DBA_FLOAT,
        'real'=>DBA_FLOAT,
        'char'=>DBA_CHAR,
        'varchar'=>DBA_VARCHAR,
        'text'=>DBA_TEXT,
        'bool'=>DBA_BOOLEAN,
        'boolean'=>DBA_BOOLEAN,
        'enum'=>DBA_ENUM,
        'set'=>DBA_SET,
        'timestamp'=>DBA_TIMESTAMP,
    );

    $constraintMap = array(
        'length'=>DBA_SIZE,
        'type'=>DBA_DEFAULT,
        'not_null'=>DBA_NOTNULL,
        'domain'=>DBA_DOMAIN,
        'auto_increment'=>DBA_AUTOINCREMENT,
    );

    while ($parser->token != TOK_END_OF_INPUT) {
        $tree = $parser->parse();
        if (is_null($tree)) {
            return;
        } elseif (PEAR::isError($tree)) {
            return $tree;
        } else {
            switch ($tree['command']) {
                case 'create':
                    $name = $tree[SQL_NAME];
                    $schema = array();
                    foreach ($tree[SQL_FIELDS] as $field) {
                        $fieldName = $field[SQL_NAME];
                        $schema[$fieldName][DBA_TYPE] = $typeMap[$field[SQL_TYPE]];
                        
                        foreach ($constraintMap as $sql=>$dba) {
                            if (isset($field[$sql]))
                                $schema[$fieldName][$dba] = $field[$sql];
                        }
                    }
                    if (!$db->tableExists($name)) {
                        $result = $db->createTable($name, $schema);
                        if (PEAR::isError($result)) {
                            return $result;
                        }
                    }
                break;
                case 'insert':
                    foreach ($tree['field_names'] as $key=>$field) {
                        $row[$field] = $tree[SQL_VALUES][$key];
                    }
                    $db->insert($name, $row);
                    if (PEAR::isError($result)) {
                        return $result;
                    }
                break;
            }
        }
    }
}
?>
