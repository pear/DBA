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

    function executeSql(&$db, $filename) {
        $sqlfile = implode('', file($filename));
        $parser = new Sql_Parser($sqlfile);
        $typeMap = array(
            SQL_INT=>DBA_INTEGER,
            SQL_FIXED=>DBA_FIXED,
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

        $optionMap = array(
            SQL_SIZE=>DBA_SIZE,
            SQL_DEFAULT=>DBA_DEFAULT,
            SQL_NOTNULL=>DBA_NOTNULL,
            SQL_DOMAIN=>DBA_DOMAIN,
            SQL_AUTOINCREMENT=>DBA_AUTOINCREMENT,
        );

        while ($parser->token != TOK_END_OF_INPUT) {
            $tree = $parser->parse();
            if (is_null($tree)) {
                return;
            } elseif (PEAR::isError($tree)) {
                return $tree;
            } else {
                switch ($tree[SQL_COMMAND]) {
                    case SQL_CREATE_TABLE:
                        $name = $tree[SQL_NAME];
                        $schema = array();
                        foreach ($tree[SQL_FIELDS] as $field) {
                            $fieldName = $field[SQL_NAME];
                            $schema[$fieldName][DBA_TYPE] = $typeMap[$field[SQL_TYPE]];
                            
                            foreach ($optionMap as $sql=>$dba) {
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
                    case SQL_INSERT:
                        foreach ($tree[SQL_FIELDS] as $key=>$field) {
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
