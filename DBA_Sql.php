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

function getToken()
{
    return strtok(" \n\t");
}

function getString() {
    $string = strtok(" \n\t");
    $quote = substr($string, 0, 1);
    if (($quote != '"') && ($quote != "'")) {
        // this was not quoted, just return the token
        return $string;
    } elseif ((substr($string, -1) == $quote) &&
              (substr($string, -2) != "\\$quote") &&
              (strlen($string) > 1)) {
        // corner case of a single quoted word
        return substr($string, 1, -1);
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

function isIntType ($type) {
    return in_array($type, array('int','integer','bigint','mediumint',
                    'littleint','tinyint'));
}

function isFloatType ($type) {
    return in_array($type, array('float','real','double'));
}
 
function isFixedType ($type) {
    return in_array($type, array('numeric','decimal'));
}
 
function isNumberType ($type) {
    return (DBA_Sql::isFixedType($type) || DBA_Sql::isFloatType($type)
            || DBA_Sql::isIntType($type));
}

function isSetType($type) {
    return in_array($type, array('enum','set'));
}

function isStringType($type) {
    return in_array($type, array('text','varchar','tinytext','mediumtext',
                    'longtext','char'));
}

function isBinaryType($type) {
    return in_array($type, array('tinyblob','mediumblob','longblob','blob'));
}

function isTimeType($type) {
    return in_array($type, array('date','time','timestamp','datetime'));
}

function isType($type)
{
    return (DBA_Sql::isIntType($type) || DBA_Sql::isFixedType($type)
            || DBA_Sql::isSetType($type) || DBA_Sql::isStringType($type)
            || DBA_Sql::isBinaryType($type) || DBA_Sql::isTimeType($type)
            || DBA_Sql::isFloatType($type));
}

function parseCreate($rawquery)
{
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
                "No field definitions found in table $tableName");
            exit;
        } else {
            while ($token && ($token != ')')) {
                $fieldName = DBA_Sql::getToken();
                $fieldType = DBA_Sql::getToken();
                $tables[$tableName][$fieldName]['type'] = $fieldType;
                $token = strtolower(DBA_Sql::getToken());
                // parse field-specific parameters
                if (DBA_Sql::isIntType($fieldType)) {
                    if ($token == '(') {
                        $tables[$tableName][$fieldName]['size'] =
                                                DBA_Sql::getToken();
                        if (DBA_Sql::getToken() != ')') {
                            return PEAR::raiseError(
                                "expected ) on $fieldName, $fieldType");
                        }
                        $token = DBA_Sql::getToken();
                    }
                } elseif (DBA_Sql::isNumberType($fieldType)) {
                    if ($token != '(') {
                        if (DBA_Sql::isFixedType($fieldType)) {
                            return PEAR::raiseError(
                                "expected ( on $fieldName, $fieldType");
                        }
                    } else {
                        $tables[$tableName][$fieldName]['size'] =
                                                DBA_Sql::getToken();
                        $token = DBA_Sql::getToken();
                        if ($token == ',') {
                            $tables[$tableName][$fieldName]
                                ['decimals'] = DBA_Sql::getToken();
                            $token = DBA_Sql::getToken();
                        }
                        if ($token != ')') {
                            return PEAR::raiseError(
                                "expected ) on $fieldName, $fieldType");
                        }
                        $token = DBA_Sql::getToken();
                    }
                } elseif (DBA_Sql::isStringType($fieldType)) {
                    if ($token == '(') {
                        $tables[$tableName][$fieldName]['size'] =
                            DBA_Sql::getToken();
                        if (DBA_Sql::getToken() != ')') {
                            return PEAR::raiseError(
                                "expected ) on $fieldName, $fieldType");
                        }
                        $token = DBA_Sql::getToken();
                    }
                } elseif (DBA_Sql::isSetType($fieldType)) {
                    if ($token != '(') {
                        return PEAR::raiseError('Missing domain on '
                                                .$fieldName);
                    }
                    $element = 0;
                    while ($token && ($token != ')')) {
                        $tables[$tableName][$fieldName]['domain']
                            [$element] = DBA_Sql::getString();
                        $token = DBA_Sql::getToken();
                        if ($token == ',') {
                            ++$element;
                        } elseif ($token != ')') {
                            return PEAR::raiseError('Invalid element at '
                                                    .$token);
                        }
                    }
                    $token = DBA_Sql::getToken();
                }

                // parse extra options
                while ($token && !(($token == ',') || ($token == ')'))) {
                    switch ($token) {
                        case 'default':
                            $tables[$tableName][$fieldName]['default'] =
                                DBA_Sql::getString();
                            break;
                        case 'primary':
                            $token = strtolower(DBA_Sql::getToken());
                            if ($token != 'key') {
                                return PEAR::raiseError(
                                    "Expected 'key', got $token on $fieldName,".
                                    $fieldType);
                            } else {
                             $tables[$tableName][$fieldName]['primarykey']=true;
                            }
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
                        case 'null': case 'auto_increment': case 'zerofill':
                            $tables[$tableName][$fieldName][$token]=true;
                            break;
                        case 'unsigned': case 'zerofill':
                            if (!DBA_Sql::isNumberType($fieldType)) {
                                return PEAR::raiseError(
                                   "$fieldType cannot have the $token option");
                            } else {
                                $tables[$tableName][$fieldName][$token]=true;
                            }
                    }
                    echo $token."\n";
                    $token = strtolower(DBA_Sql::getToken());
                }
            }
            if ($token != ')') {
                return PEAR::raiseError('Unexpected end of input');
            }
        }
    } else {
        return PEAR::raiseError("Do not know how to create $thing");
    }
    return $tables;
}
}
?>
