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
require_once 'Sql_lex.php';

define('SQL_COMMAND',30);
define('SQL_NAME',31);
define('SQL_TYPE',32);
define('SQL_FIELDS',33);
define('SQL_SIZE',34);
define('SQL_DOMAIN',35);
define('SQL_OPTIONS',36);
define('SQL_DECIMALS',37);

define('SQL_CREATE_TABLE',40);
define('SQL_CREATE_INDEX',41);
define('SQL_CREATE_SEQUENCE',42);
define('SQL_DROP_TABLE',43);
define('SQL_DROP_INDEX',44);
define('SQL_DROP_SEQUENCE',45);

// {{{ token definitions
// logical operators
define('SQL_GE',100);
define('SQL_LE',101);
define('SQL_NE',102);
define('SQL_EQ',103);
define('SQL_GT',104);
define('SQL_LT',105);
define('SQL_AND',106);
define('SQL_OR',107);
define('SQL_NOT',108);
// verbs
define('SQL_CREATE',110);
define('SQL_DROP',111);
define('SQL_INSERT',112);
define('SQL_DELETE',113);
define('SQL_SELECT',114);
define('SQL_UPDATE',115);
// conjunctions
define('SQL_BY',120);
define('SQL_AS',121);
define('SQL_ON',122);
define('SQL_BETWEEN',123);
define('SQL_INTO',124);
define('SQL_FROM',125);
define('SQL_WHERE',126);
// modifiers
define('SQL_ASC',130);
define('SQL_DESC',131);
define('SQL_LIKE',132);
define('SQL_RLIKE',133);
define('SQL_CLIKE',134);
define('SQL_SLIKE',135);
define('SQL_STEP',136);
define('SQL_SET',137);
define('SQL_PRIMARY',138);
define('SQL_KEY',139);
define('SQL_UNIQUE',140);
define('SQL_LIMIT',141);
define('SQL_DISTINCT',142);
define('SQL_ORDER',143);
define('SQL_CHECK',144);
define('SQL_VARYING',145);
define('SQL_AUTOINCREMENT',146);
// nouns
define('SQL_ALL',150);
define('SQL_TABLE',151);
define('SQL_SEQUENCE',152);
define('SQL_VALUE',153);
define('SQL_VALUES',154);
define('SQL_NULL',155);
define('SQL_INDEX',156);
define('SQL_SET_FUNCT',157);
define('SQL_CONSTRAINT',158);
define('SQL_DEFAULT',159);
// types
define('SQL_FLOAT',171);
define('SQL_FIXED',172);
define('SQL_INT',173);
define('SQL_UINT',174);
define('SQL_BOOL',175);
define('SQL_CHAR',176);
define('SQL_VARCHAR',177);
define('SQL_TEXT',178);
define('SQL_DATE',179);
define('SQL_MONEY',180);
define('SQL_TIME',181);
define('SQL_IPV4',182);
define('SQL_SET',183);
define('SQL_ENUM',184);
// }}}

class Sql_Parser
{
    var $lexer;
    var $token;

// {{{ symbol definitions
    var $symtab = array(
        '<='=>       SQL_LE,
        '>='=>       SQL_GE,
        '<>'=>       SQL_NE,
        '<'=>        SQL_LT,
        '='=>        SQL_EQ,
        '>'=>        SQL_GT,
        'or'=>       SQL_OR,
        'not'=>      SQL_NOT,
        'and'=>      SQL_AND,
        'insert'=>   SQL_INSERT,
        'select'=>   SQL_SELECT,
        'delete'=>   SQL_DELETE,
        'create'=>   SQL_CREATE,
        'update'=>   SQL_UPDATE,
        'drop'=>     SQL_DROP,
        'as'=>       SQL_AS,
        'into'=>     SQL_INTO,
        'on'=>       SQL_ON,
        'between'=>  SQL_BETWEEN,
        'where'=>    SQL_WHERE,
        'from'=>     SQL_FROM,
        'by'=>       SQL_BY,
        'distinct'=> SQL_DISTINCT,
        'primary'=>  SQL_PRIMARY,
        'like'=>     SQL_LIKE,
        'null'=>     SQL_NULL,
        'asc'=>      SQL_ASC,
        'desc'=>     SQL_DESC,
        'unique'=>   SQL_UNIQUE,
        'table'=>    SQL_TABLE,
        'index'=>    SQL_INDEX,
        'clike'=>    SQL_CLIKE,
        'slike'=>    SQL_SLIKE,
        'rlike'=>    SQL_RLIKE,
        'all'=>      SQL_ALL,
        'key'=>      SQL_KEY,
        'sequence'=> SQL_SEQUENCE,
        'default'=>  SQL_DEFAULT,
        'order'=>    SQL_ORDER,
        'check'=>    SQL_CHECK,
        'set'=>      SQL_SET,
        'step'=>     SQL_STEP,
        'auto_increment'=> SQL_AUTOINCREMENT,
        'value'=>    SQL_VALUE,
        'values'=>   SQL_VALUES,
        'constraint'=> SQL_CONSTRAINT,
        'varying'=>  SQL_VARYING,
        'avg'=>      SQL_SET_FUNCT,
        'count'=>    SQL_SET_FUNCT,
        'max'=>      SQL_SET_FUNCT,
        'min'=>      SQL_SET_FUNCT,
        'sum'=>      SQL_SET_FUNCT,
        'nextval'=>  SQL_SET_FUNCT,
        'limit'=>    SQL_LIMIT,
        'time'=>     SQL_TIME,
        'tinyint'=>  SQL_INT,
        'integer'=>  SQL_INT,
        'bigint'=>   SQL_INT,
        'int'=>      SQL_INT,
        'smallint'=> SQL_INT,
        'uint'=>     SQL_UINT,
        'float'=>    SQL_FLOAT,
        'real'=>     SQL_FLOAT,
        'double'=>   SQL_FLOAT,
        'numeric'=>  SQL_FIXED,
        'decimal'=>  SQL_FIXED,
        'character'=>SQL_CHAR,
        'char'=>     SQL_CHAR,
        'varchar'=>  SQL_VARCHAR,
        'money'=>    SQL_MONEY,
        'date'=>     SQL_DATE,
        'text'=>     SQL_TEXT,
        'ipv4'=>     SQL_IPV4,
        'ipaddr'=>   SQL_IPV4,
        'set'=>      SQL_SET,
        'enum'=>     SQL_ENUM,
    );
// }}}

// {{{ error($message)
function error($message) {
    $message = 'Syntax error: '.$message.' on line '.
        ($this->lexer->lineno+1);
    return PEAR::raiseError($message);
}
// }}}

// {{{ isType()
function isType() {
    return (($this->token >= SQL_NUM) && ($this->token <= SQL_ENUM));
}
// }}}

// {{{ isVal()
function isVal() {
    return (($this->token >= TOK_REAL_VAL) && ($this->token <= TOK_INT_VAL));
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
            ($this->token != TOK_END_OF_INPUT)) {
        $option = $this->token;
        $haveValue = true;
        switch ($option) {
            case (SQL_CONSTRAINT):
                $this->getTok();
                if ($this->token = TOK_IDENT) {
                    $options[SQL_CONSTRAINT][SQL_NAME] = $this->lexer->tokText;
                    $nextConstraint = true;
                    $haveValue = false;
                } else {
                    return $this->error('Expected SQL_IDENT');
                }
                break;
            case (SQL_DEFAULT):
                $this->getTok();
                if ($this->isVal()) {
                    $value = $this->lexer->tokText;
                } else {
                    return $this->error('Expected default value');
                }
                break;
            case (SQL_PRIMARY):
                $this->getTok();
                if ($this->token == SQL_KEY) {
                    $value = true;
                } else {
                    return $this->error('Expected "key"');
                }
                break;
            case (SQL_NOT):
                $this->getTok();
                if ($this->token == SQL_NULL) {
                    $value = true;
                } else {
                    return $this->error('Expected "null"');
                }
                break;
            case (SQL_CHECK): case (SQL_VARYING): case (SQL_UNIQUE):
                $this->getTok();
                if ($this->token != '(') {
                    return $this->error('Expected (');
                }
                $this->getTok();
                if ($this->isVal()) {
                    $value = $this->lexer->tokText;
                } else {
                    return $this->error('Expected value');
                }
                $this->getTok();
                if ($this->token != ')') {
                    return $this->error('Expected )');
                }
                break;
            case (SQL_NULL): case (SQL_AUTOINCREMENT):
                    $haveValue = false;
                break;
            default:
                return $this->error('Unexpected token '
                                    .$this->lexer->tokText);
        }
        if ($haveValue) {
            if ($nextConstraint) {
                $options[SQL_CONSTRAINT][$option] = $value;
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
        return $this->error('Expected (');
    }

    $fields = array();
    $i = 0;
    while (1) {

        // parse field identifier
        $this->getTok();
        if ($this->token == ')') {
            return $fields;
        }

        if ($this->token == TOK_IDENT) {
            $fields[$i][SQL_NAME] = $this->lexer->tokText;
        } else {
            return $this->error('Expected identifier');
        }

        // parse field type
        $this->getTok();
        if ($this->isType($this->token)) {
            $fields[$i][SQL_TYPE] = $this->token;
        } else {
            return $this->error('Expected a valid type');
        }

        // parse type parameters
        $this->getTok();
        if (($fields[$i][SQL_TYPE] >= SQL_FLOAT) &&
            ($fields[$i][SQL_TYPE] <= SQL_VARCHAR)) {
            if ($this->token == '(') {
                $this->getTok();
                if ($this->token == TOK_INT_VAL) {
                    $fields[$i][SQL_SIZE] = $this->lexer->tokText;
                } else {
                    return $this->error('Expected an integer');
                }

                $this->getTok();

                if ($this->token == ',') {
                    $this->getTok();
                    if ($this->token == TOK_INT_VAL) {
                        $fields[$i][SQL_DECIMALS] = $this->lexer->tokText;
                    } else {
                        return $this->error('Expected integer value');
                    }
                    $this->getTok();
                }

                if ($this->token != ')') {
                    return $this->error('Expected )');
                }
                $this->getTok();
            }
        } elseif (($fields[$i][SQL_TYPE] == SQL_ENUM) ||
                  ($fields[$i][SQL_TYPE] == SQL_SET)) {
            if ($this->token != '(') {
                return $this->error('Expected (');
            }
            while ($this->token != ')') {
                $this->getTok();
                if ($this->isVal()) {
                    $fields[$i][SQL_DOMAIN][] = $this->lexer->tokText;
                    $this->getTok();
                    if (($this->token != ',') && ($this->token != ')')) {
                        return $this->error('Expected , or )');
                    }
                } else {
                    return $this->error('Expected a value in domain');
                }
            }
            $this->getTok();
        }

        $options =& $this->parseFieldOptions();
        if (PEAR::isError($options)) {
            return $options;
        }

        if (sizeof($options)) {
            $fields[$i][SQL_OPTIONS] = $options;
        }

        if ($this->token == ')') {
            return $fields;
        } elseif ($this->token == TOK_END_OF_INPUT) {
            return $this->error('Expected )');
        }

        ++$i;
    }
}
// }}}

// {{{ parse($string)
function parse($string)
{
    $this->lexer = new Lexer();
    $this->lexer->string = $string;
    $this->lexer->symtab =& $this->symtab;
    $tree = array();
    $state = 0;

    // query
    $this->getTok();
    switch ($this->token) {
        case (TOK_END_OF_INPUT):
            return $tree;
        case (SQL_CREATE):
            $this->getTok();
            switch ($this->token) {
                case (SQL_TABLE):
                    $tree[SQL_COMMAND] = SQL_CREATE_TABLE;

                    $this->getTok();
                    if ($this->token == TOK_IDENT) {
                        $tree[SQL_NAME] = $this->lexer->tokText;
                        $fields =& $this->parseFieldList();
                        if (PEAR::isError($fields)) {
                            return $fields;
                        }
                        $tree[SQL_FIELDS] = $fields;
                    } else {
                        return $this->error('Expected identifier');
                    }
                    break;
            }
            break;
    }
    return $tree;
}
// }}}

}
?>
