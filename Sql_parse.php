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

// action constants: 'size', 'domain', 'options', 'decimals', 'drop_table'
//                   'drop_index', 'drop_sequence'

// token definitions
// operators:    'ge', 'le', 'ne', 'eq', 'gt', 'lt', 'and', 'or', 'not'
// verbs:        'create', 'drop', 'insert', 'delete', 'select', 'update',
//               'alter'
// conjunctions: 'by', 'as', 'on', 'between', 'into', 'from', 'where'
// modifiers:    'asc', 'desc', 'like', 'rlike', 'clike', 'slike', 'step',
//               'primary', 'key', 'unique', 'limit', 'distinct', 'order',
//               'check', 'varying', 'autoincrement'
// nouns:        'all', 'table', 'sequence', 'value', 'values', 'null',
//               'index', 'constraint', 'default', 'notnull'
// types:        'float', 'fixed', 'int', 'uint', 'bool', 'char', 'varchar',
//               'text', 'date', 'money', 'time', 'ipv4', 'set', 'enum',
//               'timestamp'
// functions:    'avg_func', 'count_func', 'max_func', 'min_func', 'sun_func',
//               'nextval_func', 'currval_func', 'setval_func'

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

// {{{ symbol definitions
    var $symtab = array(
        '<='=>       'le',
        '>='=>       'ge',
        '<>'=>       'ne',
        '<'=>        'lt',
        '='=>        'eq',
        '>'=>        'gt',
        'or'=>       'or',
        'not'=>      'not',
        'and'=>      'and',
        'insert'=>   'insert',
        'select'=>   'select',
        'delete'=>   'delete',
        'create'=>   'create',
        'update'=>   'update',
        'drop'=>     'drop',
        'as'=>       'as',
        'into'=>     'into',
        'on'=>       'on',
        'between'=>  'between',
        'where'=>    'where',
        'from'=>     'from',
        'by'=>       'by',
        'distinct'=> 'distinct',
        'primary'=>  'primary',
        'like'=>     'like',
        'null'=>     'null',
        'asc'=>      'asc',
        'desc'=>     'desc',
        'unique'=>   'unique',
        'table'=>    'table',
        'index'=>    'index',
        'clike'=>    'clike',
        'slike'=>    'slike',
        'rlike'=>    'rlike',
        'all'=>      'all',
        'key'=>      'key',
        'sequence'=> 'sequence',
        'default'=>  'default',
        'order'=>    'order',
        'check'=>    'check',
        'step'=>     'step',
        'auto_increment'=> 'autoincrement',
        'value'=>    'value',
        'values'=>   'values',
        'constraint'=> 'constraint',
        'varying'=>  'varying',
        'avg'=>      'avg_func',
        'count'=>    'count_func',
        'max'=>      'max_func',
        'min'=>      'min_func',
        'sum'=>      'sun_func',
        'nextval'=>  'nextval_func',
        'currval'=>  'currval_func',
        'setval'=>   'setval_func',
        'limit'=>    'limit',
        'time'=>     'time',
        'timestamp'=>'timestamp',
        'tinyint'=>  'int',
        'integer'=>  'int',
        'bigint'=>   'int',
        'int'=>      'int',
        'smallint'=> 'int',
        'uint'=>     'uint',
        'float'=>    'float',
        'real'=>     'float',
        'double'=>   'float',
        'numeric'=>  'fixed',
        'decimal'=>  'fixed',
        'character'=>'char',
        'char'=>     'char',
        'varchar'=>  'varchar',
        'money'=>    'money',
        'date'=>     'date',
        'text'=>     'text',
        'ipv4'=>     'ipv4',
        'ipaddr'=>   'ipv4',
        'set'=>      'set',
        'enum'=>     'enum',
        'bool'=>     'bool',
        'boolean'=>  'bool',
    );
// }}}

// {{{ function Sql_Parser($string = null)
    function Sql_Parser($string = null) {
        if (is_string($string)) {
            $this->lexer = new Lexer($string);
            $this->lexer->symtab =& $this->symtab;
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
                               strlen($this->lexer->tokText)))."^\n";

        return PEAR::raiseError($message);
    }
    // }}}

    // {{{ isType()
    function isType() {
        $types = array(
            'time'=>true,
            'timestamp'=>true,
            'int'=>true,
            'uint'=>true,
            'float'=>true,
            'fixed'=>true,
            'char'=>true,
            'varchar'=>true,
            'money'=>true,
            'date'=>true,
            'text'=>true,
            'ipv4'=>true,
            'set'=>true,
            'enum'=>true,
            'bool'=>true,
        );
        return isset($types[$this->token]);
    }
    // }}}

    // {{{ isVal()
    function isVal() {
       return (($this->token == 'real_val') ||
               ($this->token == 'int_val') ||
               ($this->token == 'text_val'));
    }
    // }}}

    function isFunc() {
    }

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
                case ('constraint'):
                    $this->getTok();
                    if ($this->token = 'ident') {
                        $options['constraint']['name'] = $this->lexer->tokText;
                        $nextConstraint = true;
                        $haveValue = false;
                    } else {
                        return $this->raiseError('Expected "ident"');
                    }
                    break;
                case ('default'):
                    $this->getTok();
                    if ($this->isVal()) {
                        $value = $this->lexer->tokText;
                    } else {
                        return $this->raiseError('Expected default value');
                    }
                    break;
                case ('primary'):
                    $this->getTok();
                    if ($this->token == 'key') {
                        $value = true;
                    } else {
                        return $this->raiseError('Expected "key"');
                    }
                    break;
                case ('not'):
                    $this->getTok();
                    if ($this->token == 'null') {
                        $value = true;
                    } else {
                        return $this->raiseError('Expected "null"');
                    }
                    break;
                case ('check'): case ('varying'): case ('unique'):
                    $this->getTok();
                    if ($this->token != '(') {
                        return $this->raiseError('Expected (');
                    }
                    $this->getTok();
                    if ($this->isVal()) {
                        $value = $this->lexer->tokText;
                    } else {
                        return $this->raiseError('Expected value');
                    }
                    $this->getTok();
                    if ($this->token != ')') {
                        return $this->raiseError('Expected )');
                    }
                    break;
                case ('autoincrement'):
                    $value = true;
                    break;
                case ('null'):
                    $haveValue = false;
                    break;
                default:
                    return $this->raiseError('Unexpected token '
                                        .$this->lexer->tokText);
            }
            if ($haveValue) {
                if ($nextConstraint) {
                    $options['constraint'][$option] = $value;
                    $nextConstraint = false;
                } else {
                    $options[$option] = $value;
                }
            }
            $this->getTok();
        }
        return $options;
    }
    // }}}

    // {{{ &parseFieldList()
    function &parseFieldList()
    {
        if ($this->lexer->lex() != '(') {
            return $this->raiseError('Expected (');
        }

        $fields = array();
        $i = 0;
        while (1) {

            // parse field identifier
            $this->getTok();
            if ($this->token == ')') {
                return $fields;
            }

            if ($this->token == 'ident') {
                $fields[$i]['name'] = $this->lexer->tokText;
            } else {
                return $this->raiseError('Expected identifier');
            }

            // parse field type
            $this->getTok();
            if ($this->isType($this->token)) {
                $fields[$i]['type'] = $this->token;
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
                switch ($fields[$i]['type']) {
                    case 'fixed': case 'float':
                        if (isset($values[0])) {
                        if ($types[0] == 'int_val') {
                            $fields[$i]['size'] = $values[0];
                            if (isset($types[1])) {
                            if ($types[1] == 'int_val') {
                                $fields[$i]['decimals'] = $values[1];
                            } else { 
                                return $this->raiseError('Expected an integer '.
                                                            'for second parameter');
                            }}
                        } else {
                            return $this->raiseError('Expected an integer '.
                                                     'for second parameter');
                        }}
                        break;
                    case 'char': case 'varchar':
                        if (sizeof($values) != 1) {
                            return $this->raiseError('Expected 1 parameter');
                        }
                        if ($types[0] != 'int_val') {
                            return $this->raiseError('Expected an integer');
                        }
                        $fields[$i]['size'] = $values[0];
                        break;
                    case 'int':
                        if (sizeof($values) > 1) {
                            return $this->raiseError('Expected 1 parameter');
                        }
                        if ($types[0] != 'int_val') {
                            return $this->raiseError('Expected an integer');
                        }
                        $fields[$i]['size'] = $values[0];
                        break;
                    case 'enum': case 'set':
                        if (!sizeof($values)) {
                            return $this->raiseError('Expected a domain');
                        }
                        $fields[$i]['domain'] = $values;
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

            $fields[$i] += $options;

            if ($this->token == ')') {
                return $fields;
            } elseif ($this->token == null) {
                return $this->raiseError('Expected )');
            }

            ++$i;
        }
    }
    // }}}

    // {{{ parse($string)
    function parse($string = null)
    {
        if (is_string($string)) {
            $this->lexer = new Lexer($string);
            $this->lexer->symtab =& $this->symtab;
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
                            $tree['name'] = $this->lexer->tokText;
                            $fields =& $this->parseFieldList();
                            if (PEAR::isError($fields)) {
                                return $fields;
                            }
                            $tree['fields'] = $fields;
                        } else {
                            return $this->raiseError('Expected table name');
                        }
                        break;
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
                        $tree['name'] = $this->lexer->tokText;
                    } else {
                        return $this->raiseError('Expected table name');
                    }
                    $this->getTok();
                    if ($this->token == '(') {
                        $results = $this->getParams($values, $types);
                        if (PEAR::isError($results)) {
                            return $results;
                        } else {
                            if (sizeof($values)) {
                                $tree['fields'] = $values;
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
                            if ($tree['fields'] && 
                               (sizeof($tree['fields']) != sizeof($values))) {
                               return $this->raiseError('field/value mismatch');
                            }
                            if (sizeof($values)) {
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
            case 'update':
            case 'delete':
            case 'select':
            case 'alter':
        }
        $this->getTok();
        if ($this->token == ';') {
            return $tree;
        } else {
            return $this->raiseError('Expected ;');
        }
    }
    // }}}
}
?>
