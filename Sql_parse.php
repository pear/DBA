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

require_once 'PEAR.php';
require_once 'DB/DBA/Sql_lex.php';

/**
 * A sql parser
 *
 * @author  Brent Cook <busterb@mail.utexas.edu>
 * @version 0.19
 * @access  public
 * @package DBA
 */
class Sql_Parser
{
    var $lexer;
    var $token;

// symbol definitions
    var $functions = array();
    var $types = array();
    var $symbols = array();
    var $operators = array();
    var $typeClasses = array();

// {{{ function Sql_Parser($string = null)
    function Sql_Parser($string = null) {
        include 'DB/DBA/Sql_dialect_ansi.php';
        $this->symbols = array_flip(explode(' ', implode(' ', $dialect)));
        $this->types = array_flip(explode(' ',$dialect['types']));
        $this->functions = array_flip(explode(' ',$dialect['functions']));
        $this->operators = array_flip(explode(' ',$dialect['operators']));
        $this->typeClasses = $typeClasses;
        if (is_string($string)) {
            $this->lexer = new Lexer($string);
            $this->lexer->symbols =& $this->symbols;
        }
    }
// }}}
 
// {{{ getParams(&$values, &$types)
    function getParams(&$values, &$types) {
        $values = array();
        $types = array();
        while ($this->token != ')') {
            $this->getTok();
            if ($this->isVal() || ($this->token == 'ident')) {
                $values[] = $this->lexer->tokText;
                $types[] = $this->token;
            } elseif ($this->token == ')') {
                return false;
            } else {
                return $this->raiseError('Expected a value');
            }
            $this->getTok();
            if (($this->token != ',') && ($this->token != ')')) {
                return $this->raiseError('Expected , or )');
            }
        }
    }
// }}}

    // {{{ raiseError($message)
    function raiseError($message) {
        $end = 0;
        if ($this->lexer->string != '') {
            while (($this->lexer->string{$this->lexer->lineBegin+$end} != "\n")
                && ($this->lexer->string{$this->lexer->lineBegin+$end})) {
                ++$end;
            }
        }
        $message = 'Parse error: '.$message.' on line '.
            ($this->lexer->lineNo+1)."\n";
        $message .= substr($this->lexer->string, $this->lexer->lineBegin, $end)."\n";
        $message .= str_repeat(' ', ($this->lexer->tokPtr - 
                               $this->lexer->lineBegin -
                               strlen($this->lexer->tokText)))."^";
        $message .= ' found: '.$this->lexer->tokText."\n";

        return PEAR::raiseError($message);
    }
    // }}}

    // {{{ isType()
    function isType() {
        return isset($this->types[$this->token]);
    }
    // }}}

    // {{{ isVal()
    function isVal() {
       return (($this->token == 'real_val') ||
               ($this->token == 'int_val') ||
               ($this->token == 'text_val'));
    }
    // }}}

    // {{{ isFunc()
    function isFunc() {
        return isset($this->functions[$this->token]);
    }
    // }}}

    // {{{ isReserved()
    function isReserved() {
        return isset($this->symbols[$this->token]);
    }
    // }}}

    // {{{ isOperator()
    function isOperator() {
        if (isset($this->operators[$this->token])) {
            if ($this->token == 'is') {
                $this->getTok();
                if ($this->token == 'null') {
                    $this->token = 'is null';
                    return true;
                } elseif ($this->token == 'not') {
                    $this->getTok();
                    if ($this->token == 'null') {
                        $this->token = 'is not null';
                        return true;
                    } else {
                        return false;
                    }
                }
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
    // }}}

    // {{{ getTok()
    function getTok() {
        $this->token = $this->lexer->lex();
        //echo $this->token."\t".$this->lexer->tokText."\n";
    }
    // }}}

    // {{{ &parseFieldOptions()
    function &parseFieldOptions()
    {
        // parse field options
        $nextConstraint = false;
        $options = array();
        while (($this->token != ',') && ($this->token != ')') &&
                ($this->token != null)) {
            $option = $this->token;
            $haveValue = true;
            switch ($option) {
                case 'constraint':
                    $this->getTok();
                    if ($this->token = 'ident') {
                        $constraintName = $this->lexer->tokText;
                        $namedConstraint = true;
                        $haveValue = false;
                    } else {
                        return $this->raiseError('Expected a constraint name');
                    }
                    break;
                case 'default':
                    $this->getTok();
                    if ($this->isVal()) {
                        $constraintOpts['type'] = 'default_value';
                        $constraintOpts['value'] = $this->lexer->tokText;
                    } elseif ($this->isFunc()) {
                        $results =& $this->parseFunctionOpts();
                        if (PEAR::isError($results)) {
                            return $results;
                        }
                        $results['type'] = 'default_function';
                        $constraintOpts =& $results;
                    } else {
                        return $this->raiseError('Expected default value');
                    }
                    break;
                case 'primary':
                    $this->getTok();
                    if ($this->token == 'key') {
                        $constraintType = 'primary_key';
                        $constraintValue = true;
                    } else {
                        return $this->raiseError('Expected "key"');
                    }
                    break;
                case 'not':
                    $this->getTok();
                    if ($this->token == 'null') {
                        $constraintType = 'not_null';
                        $constraintValue = true;
                    } else {
                        return $this->raiseError('Expected "null"');
                    }
                    break;
                case 'check': case 'varying': case 'unique':
                    $this->getTok();
                    if ($this->token != '(') {
                        return $this->raiseError('Expected (');
                    }
                    $this->getTok();
                    if ($this->isVal()) {
                        $constraintType = $option;
                        $constraintValue = $this->lexer->tokText;
                    } else {
                        return $this->raiseError('Expected value');
                    }
                    $this->getTok();
                    if ($this->token != ')') {
                        return $this->raiseError('Expected )');
                    }
                    break;
                case 'null':
                    $haveValue = false;
                    break;
                default:
                    return $this->raiseError('Unexpected token '
                                        .$this->lexer->tokText);
            }
            if ($haveValue) {
                if ($namedConstraint) {
                    $options['constraints'][$constraintName] = array(
                        'type' => $constraintType,
                        'value' => $constraintValue);
                    $namedConstraint = false;
                } else {
                    $options['constraints'][] = array(
                        'type' => $constraintType,
                        'value' => $constraintValue);
                }
            }
            $this->getTok();
        }
        return $options;
    }
    // }}}

    // {{{ &parseSearchClause()
    function &parseSearchClause()
    {
        $clause = array();
        $this->getTok();
        // parse the first argument
        if ($this->token == 'not') {
            $clause['neg'] = true;
            $this->getTok();
        }
        if ($this->isReserved()) {
            return $this->raiseError('Expected a column name or value');
        }
        $clause['arg_1']['value'] = $this->lexer->tokText;
        $clause['arg_1']['type'] = $this->token;

        // parse the operator
        $this->getTok();
        if (!$this->isOperator()) {
            return $this->raiseError('Expected an operator');
        }
        $clause['op'] = $this->token;

        // parse the second argument
        $this->getTok();
        if ($this->isReserved()) {
            return $this->raiseError('Expected a column name or value');
        }
        $clause['arg_2']['value'] = $this->lexer->tokText;
        $clause['arg_2']['type'] = $this->token;
        $this->getTok();
        if (($this->token == 'and') || ($this->token == 'or')) {
            $subClause = $this->parseSearchClause();
            if (PEAR::isError($subClause)) {
                return $subClause;
            } else {
                return array('arg_1' => $clause,
                            'op' => $this->token,
                            'arg_2' => $subClause);
            }
        } else {
            $this->lexer->unget();
            return $clause;
        }
    }
    // }}}

    // {{{ &parseFieldList()
    function &parseFieldList()
    {
        $this->getTok();
        if ($this->token != '(') {
            return $this->raiseError('Expected (');
        }

        $fields = array();
        while (1) {
            // parse field identifier
            $this->getTok();
            if ($this->token == 'ident') {
                $name = $this->lexer->tokText;
            } elseif ($this->token == ')') {
                return $fields;
            } else {
                return $this->raiseError('Expected identifier');
            }

            // parse field type
            $this->getTok();
            if ($this->isType($this->token)) {
                $fields[$name]['type'] = $this->token;
            } else {
                return $this->raiseError('Expected a valid type');
            }

            // parse type parameters
            $this->getTok();
            if ($this->token == '(') {
                $results = $this->getParams($values, $types);
                if (PEAR::isError($results)) {
                    return $results;
                }
                $typeClass = $this->typeClasses[$fields[$name]['type']];
                switch ($typeClass) {
                    case 'real':
                        if (isset($values[0])) {
                        if ($types[0] == 'int_val') {
                            $fields[$name]['length'] = $values[0];
                            if (isset($types[1])) {
                            if ($types[1] == 'int_val') {
                                $fields[$name]['decimals'] = $values[1];
                            } else { 
                                return $this->raiseError('Expected an integer '.
                                                            'for second parameter');
                            }}
                        } else {
                            return $this->raiseError('Expected an integer '.
                                                     'for second parameter');
                        }}
                        break;
                    case 'char':
                        if (sizeof($values) != 1) {
                            return $this->raiseError('Expected 1 parameter');
                        }
                        if ($types[0] != 'int_val') {
                            return $this->raiseError('Expected an integer');
                        }
                        $fields[$name]['length'] = $values[0];
                        break;
                    case 'integer':
                        if (sizeof($values) > 1) {
                            return $this->raiseError('Expected 1 parameter');
                        }
                        if ($types[0] != 'int_val') {
                            return $this->raiseError('Expected an integer');
                        }
                        $fields[$name]['length'] = $values[0];
                        break;
                    case 'set':
                        if (!sizeof($values)) {
                            return $this->raiseError('Expected a domain');
                        }
                        $fields[$name]['domain'] = $values;
                        break;
                    default:
                        if (sizeof($values)) {
                            return $this->raiseError('Unexpected (');
                        }
                }
                $this->getTok();
            }

            $options =& $this->parseFieldOptions();
            if (PEAR::isError($options)) {
                return $options;
            }

            $fields[$name] += $options;

            if ($this->token == ')') {
                return $fields;
            } elseif (is_null($this->token)) {
                return $this->raiseError('Expected )');
            }
        }
    }
    // }}}

    // {{{ &parseFunctionOpts()
    function &parseFunctionOpts()
    {
        $function = $this->token;
        $opts['name'] = $function;
        $this->getTok();
        if ($this->token != '(') {
            return $this->raiseError('Expected "("');
        }
        switch ($function) {
            case 'count':
                $this->getTok();
                switch ($this->token) {
                    case 'distinct':
                        $opts['distinct'] = true;
                        $this->getTok();
                        if ($this->token != 'ident') {
                            return $this->raiseError('Expected a column name');
                        }
                    case 'ident': case '*':
                        $opts['arg'] = $this->lexer->tokText;
                        break;
                    default:
                        return $this->raiseError('Invalid argument');
                }
                break;
            case 'avg': case 'min': case 'max': case 'sum':
            default:
                $this->getTok();
                $opts['arg'] = $this->lexer->tokText;
                break;
        }
        $this->getTok();
        if ($this->token != ')') {
            return $this->raiseError('Expected "("');
        }
        return $opts;
    }
    // }}}

    // {{{ parse($string)
    function parse($string = null)
    {
        if (is_string($string)) {
            $this->lexer = new Lexer($string);
            $this->lexer->symbols =& $this->symbols;
        } else {
            if (!is_object($this->lexer)) {
                return $this->raiseError('No initial string specified');
            }
        }

        $tree = array();
        // query
        $this->getTok();
        switch ($this->token) {
            case null:
                return;
            // {{{ 'create'
            case 'create':
                $this->getTok();
                switch ($this->token) {
                    case 'table':
                        $tree['command'] = 'create_table';
                        $this->getTok();
                        if ($this->token == 'ident') {
                            $tree['table_name'] = $this->lexer->tokText;
                            $fields =& $this->parseFieldList();
                            if (PEAR::isError($fields)) {
                                return $fields;
                            }
                            $tree['column_defs'] = $fields;
                            $tree['column_names'] = array_keys($fields);
                        } else {
                            return $this->raiseError('Expected table name');
                        }
                        break;
                    case 'index':
                        $tree['command'] = 'create_index';
                        break;
                    case 'constraint':
                        $tree['command'] = 'create_constraint';
                        break;
                    case 'sequence':
                        $tree['command'] = 'create_sequence';
                        break;
                    default:
                        return $this->raiseError('Cannot create '
                                                 .$this->lexer->tokText);
                }
                break;
            // }}}
            // {{{ 'insert'
            case 'insert':
                $this->getTok();
                if ($this->token == 'into') {
                    $tree['command'] = 'insert';
                    $this->getTok();
                    if ($this->token == 'ident') {
                        $tree['table_name'] = $this->lexer->tokText;
                        $this->getTok();
                    } else {
                        return $this->raiseError('Expected table name');
                    }
                    if ($this->token == '(') {
                        $results = $this->getParams($values, $types);
                        if (PEAR::isError($results)) {
                            return $results;
                        } else {
                            if (sizeof($values)) {
                                $tree['column_names'] = $values;
                            }
                        }
                        $this->getTok();
                    }
                    if ($this->token == 'values') {
                        $this->getTok();
                        $results = $this->getParams($values, $types);
                        if (PEAR::isError($results)) {
                            return $results;
                        } else {
                            if ($tree['column_defs'] && 
                               (sizeof($tree['column_defs']) != sizeof($values))) {
                               return $this->raiseError('field/value mismatch');
                            }
                            if (sizeof($values)) {
                                foreach ($values as $key=>$value) {
                                    $values[$key] = array('value'=>$value,
                                                          'type'=>$types[$key]);
                                }
                                $tree['values'] = $values;
                            } else {
                               return $this->raiseError('No fields to insert');
                            }
                        }
                    } else {
                        return $this->raiseError('Expected "values"');
                    }
                } else {
                    return $this->raiseError('Expected "into"');
                }
                break;
            // }}}
            // {{{ 'update'
            case 'update':
                $tree['command'] = 'update';
                $this->getTok();
                if ($this->token == 'ident') {
                    $tree['table_name'] = $this->lexer->tokText;
                } else {
                    return $this->raiseError('Expected table name');
                }
                $this->getTok();
                if ($this->token != 'set') {
                    return $this->raiseError('Expected "set"');
                }
                while (true) {
                    $this->getTok();
                    if ($this->token != 'ident') {
                        return $this->raiseError('Expected a column name');
                    }
                    $tree['column_names'][] = $this->lexer->tokText;
                    $this->getTok();
                    if ($this->token != '=') {
                        return $this->raiseError('Expected =');
                    }
                    $this->getTok();
                    if (!$this->isVal($this->token)) {
                        return $this->raiseError('Expected a value');
                    }
                    $tree['values'][] = array('value'=>$this->lexer->tokText,
                                              'type'=>$this->token);
                    $this->getTok();
                    if ($this->token == 'where') {
                        $clause =& $this->parseSearchClause();
                        if (PEAR::isError($clause)) {
                            return $clause;
                        }
                        $tree['where_clause'] =& $clause;
                        break;
                    } elseif ($this->token != ',') {
                        return $this->raiseError('Expected "where" or ","');
                    }
                }
                break;
            // }}}
            // {{{ 'delete'
            case 'delete':
                $tree['command'] = 'delete';
            // }}}
            // {{{ 'select'
            case 'select':
                $tree['command'] = 'select';
                $this->getTok();
                if (($this->token == 'distinct') || ($this->token == 'all')) {
                    $tree['set_quantifier'] = $this->token;
                    $this->getTok();
                }
                if ($this->token == '*') {
                    $tree['column_names'][] = '*';
                    $this->getTok();
                } elseif ($this->token == 'ident') {
                    while ($this->token == 'ident') {
                        $tree['column_names'][] = $this->lexer->tokText;
                        $this->getTok();
                        if ($this->token == ',') {
                            $this->getTok();
                        }
                    }
                } elseif ($this->isFunc()) {
                    if (!isset($tree['set_quantifier'])) {
                        $result =& $this->parseFunctionOpts();
                        if (PEAR::isError($result)) {
                            return $result;
                        }
                        $tree['set_function'] =& $result;
                        $this->getTok();
                    } else {
                        return $this->raiseError('Cannot use "'.
                                $tree['set_quantifier'].'" with '.$this->token);
                    }
                } else {
                    return $this->raiseError('Expected columns or a set function');
                }
                if ($this->token != 'from') {
                    return $this->raiseError('Expected "from"');
                }
                $this->getTok();
                while ($this->token == 'ident') {
                    $tree['table_names'][] = $this->lexer->tokText;
                    $this->getTok();
                    if ($this->token == ',') {
                        $this->getTok();
                    } elseif (($this->token == 'where') ||
                              ($this->token == 'order') ||
                              ($this->token == 'limit') ||
                              (is_null($this->token))) {
                        break;
                    }
                }
                while (!is_null($this->token)) {
                    switch ($this->token) {
                        case 'where':
                            $clause =& $this->parseSearchClause();
                            if (PEAR::isError($clause)) {
                                return $clause;
                            }
                            $tree['where_clause'] =& $clause;
                            break;
                        case 'order':
                        case 'limit':
                        default:
                            return $this->raiseError('Unexpected clause');
                    }
                }
                break;
            // }}}
        }
        return $tree;
/*
        $this->getTok();
        if ($this->token == ';') {
            return $tree;
        } else {
            return $this->raiseError('Expected ;');
        }
*/
    }
    // }}}
}
?>
