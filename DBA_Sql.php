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

require_once "PEAR.php";

class DBA_Sql
{

    // adds spaces around special characters
    function cookQuery($query)
    {
        foreach (array(',','(',')','==','!=','>','<','<=','>=') as $symbol) {
            $query = str_replace($symbol, " $symbol ", $query);
        }
        return $query;
    }

    function getToken() {
        return strtok(" \n\t");
    }

    function parseCreate($rawquery) {

        $query = DBA_Sql::cookQuery($rawquery);
        $command = strtolower(strtok($query, " \n\t"));
        if ($command != 'create') {
            return PEAR::raiseError('DBA: not a "create" query');
        }
        
        $thing = strtolower(DBA_Sql::getToken());
        if ($thing == 'table') {
            $tableName = DBA_Sql::getToken();
            $token = DBA_Sql::getToken();
            if ($token != '(') {
                return PEAR::raiseError(
                   "Parse error at $token on $rawquery");
                exit;
            } else {
                while ($token && ($token != ')')) {
                    $fieldName = DBA_Sql::getToken();
                    $fieldType = DBA_Sql::getToken();
                    $tables[$tableName][$fieldName]['type'] = 
                                                            $fieldType;
                    $token = strtolower(DBA_Sql::getToken());
                    while (!(($token == ',') || ($token == ')'))) {
                        switch ($token) {
                            case '(':
                                $size = DBA_Sql::getToken();
                                $tables[$tableName][$fieldName]
                                                    ['size'] = $size;
                                $token = DBA_Sql::getToken();
                                if ($token != ')') {
                                    return PEAR::raiseError(
                                    "Parse error at $token on $rawquery");
                                }
                                $token = DBA_Sql::getToken();
                                break;
                            case 'default':
                                $default = DBA_Sql::getToken();
                                $tables[$tableName][$fieldName]
                                                    ['default'] = $default;
                                $token = DBA_Sql::getToken();
                                break;
                            case 'auto_increment':
                                $tables[$tableName][$fieldName]
                                                    ['autoincrement']=$token;
                                $token = DBA_Sql::getToken();
                                break;
                            case 'not':
                                $token = strtolower(DBA_Sql::getToken());
                                if ($token != 'null') {
                                    return PEAR::raiseError(
                                    "Parse error at $token on $rawquery");
                                } else {
                                    $tables[$tableName][$fieldName]
                                                        ['notnull']=true;
                                }
                                $token = DBA_Sql::getToken();
                                break;
                            case 'null':
                                $tables[$tableName][$fieldName]
                                                    ['null']=true;
                            $token = DBA_Sql::getToken();
                            break;
                        }
                    }
                }
            }
        } else {
            return PEAR::raiseError("Do not know how to create $thing");
        }
        return $tables;
    }
}
?>
