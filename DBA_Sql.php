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
    foreach (array(',','(',')',"\'") as $symbol) {
        $query = str_replace($symbol, " $symbol ", $query);
    }
    return $query;
}

function getToken() {
    return strtok(" \n\t");
}

function getString() {
    $string = strtok(" \n\t");
    echo $string;
    $quote = substr($string, 0, 1);
    if (($quote != '"') && ($quote != "'")) {
        // this was not quoted, just return the token
        return $string;
    } else {
        // strip the first quote
        $string = substr($string, 1).' ';
        // read until the next quote
        $string .= strtok($quote);
        // was this an escaped quote?
        while (substr($string, -1) == "\\") {
            // insert the quote, keep reading
            $string = substr($string,0,-2).$quote.substr(strtok($quote), 1);
        }
        return $string;
    }
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
                $tables[$tableName][$fieldName]['type'] = $fieldType;
                $token = strtolower(DBA_Sql::getToken());
                // parse field-specific parameters
                switch ($fieldType) {
                    case 'int':
                    case 'integer':
                    case 'float':
                    case 'numeric':
                        if ($token == '(') {
                            $tables[$tableName][$fieldName]['size'] =
                                                    DBA_Sql::getToken();
                            $token = DBA_Sql::getToken();
                            if ($token == ',') {
                                $tables[$tableName][$fieldName]
                                    ['decimal'] = DBA_Sql::getToken();
                                $token = DBA_Sql::getToken();
                            }
                            if ($token != ')') {
                                return PEAR::raiseError(
                                "Parse error at $token on $rawquery");
                            }
                            $token = DBA_Sql::getToken();
                        }
                        break;
                    case 'varchar':
                        if ($token == '(') {
                            $tables[$tableName][$fieldName]['size'] =
                                DBA_Sql::getToken();
                            if (DBA_Sql::getToken() != ')') {
                                return PEAR::raiseError(
                                    "Parse error at $token on $rawquery");
                            }
                            $token = DBA_Sql::getToken();
                        }
                        break;
                    case 'enum':
                    case 'set':
                        if ($token != '(') {
                            return PEAR::raiseError('Missing domain on'.
                                                     $fieldName);
                        }
                        $element = 0;
                        while ($token != ')') {
                            $tables[$tableName][$fieldName]['domain']
                                [$element] = DBA_Sql::getString();
                            $token = DBA_Sql::getToken();
                            if (($token == ',') || ($token == ')')) {
                                ++$element;
                            } else {
                                return PEAR::raiseError('Invalid element at '
                                                        .$token);
                            }
                        }
                        $token = DBA_Sql::getToken();
                        break;
                    default:
                }

                // parse extra options
                while (!(($token == ',') || ($token == ')'))) {
                    switch ($token) {
                        case 'default':
                            $tables[$tableName][$fieldName]['default'] =
                                DBA_Sql::getString();
                            break;
                        case 'auto_increment':
                            $tables[$tableName][$fieldName]['autoincrement']=
                                $token;
                            break;
                        case 'not':
                            $token = strtolower(DBA_Sql::getToken());
                            if ($token != 'null') {
                                return PEAR::raiseError(
                                "Parse error at $token on $rawquery");
                            } else {
                                $tables[$tableName][$fieldName]['notnull']=true;
                            }
                            break;
                        case 'null':
                            $tables[$tableName][$fieldName]['null']=true;
                            break;
                    }
                    $token = strtolower(DBA_Sql::getToken());
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
