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
    $endquote = ($quote == '(') ? ')' : $quote;

    if (($quote != '"') && ($quote != "'") && ($quote != '(')) {
        // this is a bare string, just return the token
        return $string;

    } elseif ((substr($string, -1) == $endquote) &&
              (substr($string, -2) != "\\$endquote") &&
              (strlen($string) > 1)) {
        // corner case of a single quoted word
        return substr($string, 1, -1);
    } else {
        // strip the first quote
        $string = substr($string, 1).' ';
        // read until the next quote
        $string .= strtok($endquote);
        // was this an escaped quote?
        while (substr($string, -1) == "\\") {
            // insert the quote, keep reading
            $string = substr($string,0,-2).$endquote.substr(strtok($endquote), 1);
        }
        return $string;
    }
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
        $tableDefn =& Sql::parseTableDefinition();
        return array($tableName, $tableDefn);
    } else {
        return PEAR::raiseError("Do not know how to create $thing");
    }
}

function &parseTableDefinition()
{
    $token = Sql::getToken();
    if ($token != '(') {
        return PEAR::raiseError('No field definitions');
    } else {
        while ($token && ($token != ')')) {

            // check if we're at the end of our rope, otherwise
            // it's another day at the office
            if (($token = SQL::getToken()) == ')') {
                break;
            }
            $fieldName = $token;

            $table[$fieldName]['type'] = $fieldType = Sql::getTokenL();

            // parse field-specific parameters
            $token = Sql::getTokenL();
            if ($token == '(') {
                $element = 0;
                while ($token && ($token != ')')) {
                    $table[$fieldName]['opts'][$element] = Sql::getString();
                    $token = Sql::getToken();
                    if ($token == ',') {
                        ++$element;
                    } else
                    if ($token != ')') {
                        return PEAR::raiseError("Expected ')', got '$token' on '$fieldName'");
                    }
                }
                $token = Sql::getTokenL();
            }

            // parse extra options
            while ($token && !(($token == ',') || ($token == ')'))){
                switch ($token) {
                    case 'default':
                        $table[$fieldName]['default'] = Sql::getString();
                        break;
                    case 'primary':
                        $token = Sql::getTokenL();
                        if ($token != 'key') {
                            return PEAR::raiseError(
                                "Expected 'key', got $token on '$fieldName'");
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
                    case 'check': case 'varying':
                        $table[$fieldName][$token] = Sql::getString();
                        break;
                    case 'year':case'month':case 'day':case 'hour':case 'minute':case 'second':
                        $table[$fieldName]['from'] = $token;
                        $token = Sql::getTokenL();
                        if ($token != 'to') {
                            return PEAR::raiseError(
                                "Expected 'to', got $token on '$fieldName'");
                        }
                        $table[$fieldName]['to'] = Sql::getTokenL();
                        break;
                    default:
                        $table[$fieldName][$token] = true;
                        break;
                }
                // grab the next option
                $token = Sql::getTokenL();
            }
        }
        if ($token != ')') {
            echo ("Unexpected end of input with $token\n");
        }
        return $table;
    }
}

}
?>
