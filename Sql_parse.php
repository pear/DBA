<?php
require_once 'PEAR.php';
require_once 'Sql_lex.php';

define('SQL_COMMAND',0);
define('SQL_NAME',1);
define('SQL_TYPE',2);
define('SQL_FIELDS',3);
define('SQL_SIZE', 4);
define('SQL_DOMAIN', 5);
define('SQL_OPTIONS', 6);

define('SQL_CREATE_TABLE',10);
define('SQL_CREATE_INDEX',11);
define('SQL_CREATE_SEQUENCE',12);
define('SQL_DROP_TABLE',13);
define('SQL_DROP_INDEX',14);
define('SQL_DROP_SEQUENCE',15);

class Parser
{
    var $lexer;
    var $token;

function error($message) {
    $message = 'Syntax error: '.$message.' on line '.
        ($this->lexer->lineno+1);
    return PEAR::raiseError($message);
}

function isType() {
    return (($this->token >= SQL_NUM) && ($this->token <= SQL_ENUM));
}

function isVal() {
    return (($this->token >= SQL_REAL_VAL) && ($this->token <= SQL_INT_VAL));
}

function parseFieldOption()
{
}

function &parseFieldList()
{
    if ($this->lexer->lex() != '(') {
        return $this->error('Expected (');
    }

    $fields = array();
    $i = 0;
    while (1) {
        // parse field identifier
        $this->token = $this->lexer->lex();
        if ($this->token == ')') {
            return $fields;
        }
        if ($this->token == SQL_IDENT) {
            $fields[$i][SQL_NAME] = $this->lexer->tokText;
        } else {
            return $this->error('Expected SQL_IDENT');
        }

        // parse field type
        $this->token = $this->lexer->lex();
        if ($this->isType($this->token)) {
            $fields[$i][SQL_TYPE] = $this->token;
        } else {
            return $this->error('Expected a valid type');
        }

        // parse type parameters
        $this->token = $this->lexer->lex();
        if (($fields[$i][SQL_TYPE] >= SQL_INT) &&
            ($fields[$i][SQL_TYPE] <= SQL_VARCHAR)) {
            if ($this->token == '(') {
                $this->token = $this->lexer->lex();
                if ($this->token == SQL_INT_VAL) {
                    $fields[$i][SQL_SIZE] = $this->lexer->tokText;
                } else {
                    return $this->error('Expected an integer');
                }

                $this->token = $this->lexer->lex();
                if ($this->token != ')') {
                    return $this->error('Expected )');
                }
                $this->token = $this->lexer->lex();
            }
        } elseif (($fields[$i][SQL_TYPE] == SQL_ENUM) ||
                  ($fields[$i][SQL_TYPE] == SQL_SET)) {
            if ($this->token != '(') {
                return $this->error('Expected (');
            }
            while ($this->token != ')') {
                $this->token = $this->lexer->lex();
                if ($this->isVal()) {
                    $fields[$i][SQL_DOMAIN][] = $this->lexer->tokText;
                    $this->token = $this->lexer->lex();
                    if (($this->token != ',') && ($this->token != ')')) {
                        return $this->error('Expected , or )');
                    }
                } else {
                    return $this->error('Expected a value in domain');
                }
            }
            $this->token = $this->lexer->lex();
        }

        // parse field options
        $nextConstraint = false;
        while (($this->token != ',') && ($this->token != ')') &&
               ($this->token != SQL_END_OF_INPUT)) {
            $option = $this->token;
            $haveValue = true;
            switch ($option) {
                case (SQL_CONSTRAINT):
                    $this->token = $this->lexer->lex();
                    if ($this->token = SQL_IDENT) {
                       $this->fields[$i][SQL_CONSTRAINT][SQL_NAME]
                            = $this->lexer->tokText;
                        $nextConstraint = true;
                        $haveValue = false;
                    } else {
                        return $this->error('Expected SQL_IDENT');
                    }
                    break;
                case (SQL_DEFAULT):
                    $this->token = $this->lexer->lex();
                    if ($this->isVal()) {
                        $value = $this->lexer->tokText;
                    } else {
                        return $this->error('Expected default value');
                    }
                    break;
                case (SQL_PRIMARY):
                    $this->token = $this->lexer->lex();
                    if ($this->token == SQL_KEY) {
                        $value = true;
                    } else {
                        return $this->error('Expected "key"');
                    }
                    break;
                case (SQL_NOT):
                    $this->token = $this->lexer->lex();
                    if ($this->token == SQL_NULL) {
                        $value = true;
                    } else {
                        return $this->error('Expected "null"');
                    }
                    break;
                case (SQL_CHECK): case (SQL_VARYING): case (SQL_UNIQUE):
                    $this->token = $this->lexer->lex();
                    if ($this->token != '(') {
                        return $this->error('Expected (');
                    }
                    $this->token = $this->lexer->lex();
                    if ($this->isVal()) {
                        $value = $this->lexer->tokText;
                    } else {
                        return $this->error('Expected value');
                    }
                    $this->token = $this->lexer->lex();
                    if ($this->token != ')') {
                        return $this->error('Expected )');
                    }
                    break;
                case (SQL_NULL): case (SQL_AUTO_INCREMENT):
                    break;
                default:
                    return $this->error('Unexpected token '
                                        .$this->lexer->tokText);
            }
            if ($haveValue) {
                if ($nextConstraint) {
                    $fields[$i][SQL_CONSTRAINT][$option] = $value;
                    $nextConstraint = false;
                } else {
                    $fields[$i][$option] = $value;
                }
            }
            $this->token = $this->lexer->lex();
        }

        if ($this->token == ')') { 
            return $fields;
        } elseif ($this->token == SQL_END_OF_INPUT) {
            return $this->error('Expected )');
        }
        ++$i;
    }
}
    
function parse($string)
{
    $this->lexer = new Lexer();
    $this->lexer->string = $string;
    $tree = array();
    $state = 0;

    // query
    $this->token = $this->lexer->lex();
    switch ($this->token) {
        case (SQL_END_OF_INPUT):
            return $tree;
        case (SQL_CREATE):
            switch ($this->lexer->lex()) {
                case (SQL_TABLE):
                    $tree[SQL_COMMAND] = SQL_CREATE_TABLE;

                    if ($this->lexer->lex() == SQL_IDENT) {
                        $tree[SQL_NAME] = $this->lexer->tokText;
                        $fields =& $this->parseFieldList();
                        if (PEAR::isError($fields)) {
                            return $fields;
                        }
                        $tree[SQL_FIELDS] = $fields;
                    } else {
                        return $this->error('Expected SQL_IDENT');
                    }
                    break;
            }
            break;
    }
    return $tree;
}

}

?>
