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

class Sql
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

function getTokenL()
{
    return strtolower(strtok(" \n\t"));
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
                    'littleint','tinyint','int2','int8','oid'));
}

function isFloatType ($type) {
    return in_array($type, array('float','real','double','float4','float8'));
}
 
function isFixedType ($type) {
    return in_array($type, array('numeric','decimal'));
}
 
function isNumberType ($type) {
    return (Sql::isFixedType($type) || Sql::isFloatType($type)
            || Sql::isIntType($type));
}

function isSetType($type) {
    return in_array($type, array('enum','set'));
}

function isLogicalType($type) {
    return in_array($type, array('bool','boolean'));
}

function isStringType($type) {
    return in_array($type, array('text','varchar','tinytext','mediumtext',
                    'longtext','char','character'));
}

function isBinaryType($type) {
    return in_array($type, array('tinyblob','mediumblob','longblob','blob'));
}

function isTimeType($type) {
    return in_array($type, array('date','time','timestamp','datetime',
                    'interval'));
}

function isType($type)
{
    return (Sql::isIntType($type) || Sql::isFixedType($type)
            || Sql::isSetType($type) || Sql::isStringType($type)
            || Sql::isBinaryType($type) || Sql::isTimeType($type)
            || Sql::isFloatType($type) || Sql::isLogicalType($type));
}

function parseCreate($rawquery)
{
    $query = Sql::cookQuery($rawquery);
    $command = strtolower(strtok($query, " \n\t"));

    if ($command != 'create') {
        return PEAR::raiseError('DBA: not a "create" query');
    }
    
    $thing = Sql::getTokenL();
    if ($thing == 'table') {
        $tableName = Sql::getToken();
        $token = Sql::getToken();
        if ($token != '(') {
            return PEAR::raiseError(
                "No field definitions found in table $tableName");
            exit;
        } else {
            while ($token && ($token != ')')) {
                $fieldName = Sql::getToken();

                if ($fieldName == ')') {
                    // we must have had a trailing comma in the field list
                    $token = $fieldName;
                    break;
                }

                $fieldType = Sql::getTokenL();
                $table[$fieldName]['type'] = $fieldType;
                $token = Sql::getTokenL();

                // parse field-specific parameters
                if (Sql::isIntType($fieldType)) {
                    if ($token == '(') {
                        $table[$fieldName]['size'] = Sql::getToken();
                        if (Sql::getToken() != ')') {
                            return PEAR::raiseError(
                                "expected ) on $fieldName, $fieldType");
                        }
                        $token = Sql::getTokenL();
                    }
                } elseif (Sql::isNumberType($fieldType)) {
                    if ($token != '(') {
                        if (Sql::isFixedType($fieldType)) {
                            return PEAR::raiseError(
                                "expected ( on $fieldName, $fieldType");
                        }
                    } else {
                        $table[$fieldName]['size'] = Sql::getToken();
                        $token = Sql::getToken();
                        if ($token == ',') {
                            $table[$fieldName]['decimals'] = Sql::getToken();
                            $token = Sql::getToken();
                        }
                        if ($token != ')') {
                            return PEAR::raiseError(
                                "expected ) on $fieldName, $fieldType");
                        }
                        $token = Sql::getTokenL();
                    }
                } elseif (Sql::isStringType($fieldType)) {
                    if ($token == '(') {
                        $table[$fieldName]['size'] = Sql::getToken();
                        if (Sql::getToken() != ')') {
                            return PEAR::raiseError(
                                "expected ) on $fieldName, $fieldType");
                        }
                        $token = Sql::getTokenL();
                    }
                } elseif (Sql::isSetType($fieldType)) {
                    if ($token != '(') {
                        return PEAR::raiseError('Missing domain on '
                                                .$fieldName);
                    }
                    $element = 0;
                    while ($token && ($token != ')')) {
                        $table[$fieldName]['domain'][$element] =
                            Sql::getString();
                        $token = Sql::getToken();
                        if ($token == ',') {
                            ++$element;
                        } elseif ($token != ')') {
                            return PEAR::raiseError('Invalid element at '
                                                    .$token);
                        }
                    }
                    $token = Sql::getTokenL();
                }

                // parse extra options
                while ($token && !(($token == ',') || ($token == ')')
                       || ($token == ';'))) {
                    switch ($token) {
                        case 'default':
                            $table[$fieldName]['default'] =Sql::getString();
                            break;
                        case 'primary':
                            $token = Sql::getTokenL();
                            if ($token != 'key') {
                                return PEAR::raiseError(
                                    "Expected 'key', got $token on $fieldName,".
                                    $fieldType);
                            } else {
                                $table[$fieldName]['primarykey'] = true;
                            }
                            break;
                        case 'not':
                            $token = Sql::getTokenL();
                            if ($token != 'null') {
                                return PEAR::raiseError(
                                    "Parse error at $token on $rawquery");
                            } else {
                                $table[$fieldName]['notnull'] = true;
                            }
                            break;
                        case 'auto_increment': case 'zerofill':
                        case 'unique':
                            $table[$fieldName][$token] = true;
                            break;
                        case 'unsigned': case 'zerofill':
                            if (!Sql::isNumberType($fieldType)) {
                                return PEAR::raiseError(
                                   "$fieldType cannot have the $token option");
                            } else {
                                $table[$fieldName][$token] = true;
                            }
                            break;
                        case 'varying':
                            if (!Sql::isStringType($fieldType)) {
                                return PEAR::raiseError(
                                   "$fieldType cannot vary in length");
                            } else {
                                $table[$fieldName]['type'] = 'varchar';
                                if (Sql::getToken() != '(') {
                                    PEAR::raiseError('expecting (');
                                } else {
                                    $table[$fieldName]['size']=Sql::getToken();
                                    if (Sql::getToken() != ')') {
                                        PEAR::raiseError('expecting )');
                                    }
                                }
                            }
                    }
                    // grab the next option
                    $token = Sql::getTokenL();
                }
            }
            if ($token != ')') {
                echo ("Unexpected end of input with $token\n");
            }
        }
    } else {
        return PEAR::raiseError("Do not know how to create $thing");
    }
    return array($tableName, $table);
}
}
?>
