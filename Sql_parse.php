<?php
require_once 'Sql_lex.php';

class Parser
{
    var $lexer;

function isType($token) {
    return (($token >= SQL_NUM) && ($token <= SQL_IPV4));
}

function parseFieldList()
{
    $fields = array();
    $token = $this->lexer->lex();
    while ($token == SQL_IDENT) {
        $fieldName = $this->lexer->text;
        $token = $this->lexer->lex();
        if ($this->isType($token)) {
            $fieldType = $token;
        } else {
            return $fields;
        }
        $fields[] = array('name'=>$fieldName, 'type'=>$fieldType);
        while (($token != ',') && ($token != ')') &&
               ($token != SQL_END_OF_INPUT)) {
            $token = $this->lexer->lex();
        }
        $token = $this->lexer->lex();
    }
    return $fields;
}
    
function parse($string)
{
    $this->lexer = new Lexer();
    $this->lexer->string = $string;
    $tree = array();

    $state = 0;

    // query
    $token = $this->lexer->lex();
    switch ($token) {
        case (SQL_END_OF_INPUT):
            return $tree;
        case (SQL_CREATE):
            switch ($this->lexer->lex()) {
                case (SQL_TABLE):
                    if ($this->lexer->lex() == SQL_IDENT) {
                        $name = $this->lexer->text;
                    }
                    if ($this->lexer->lex() == '(') {
                        $fields = $this->parseFieldList();
                        while (($this->lexer->text != ')') && 
                               ($token != SQL_END_OF_INPUT)) {
                            $token = $this->lexer->lex();
                        }
                        $tree['command'] = SQL_CREATE_TABLE;
                        $tree['name'] = $name;
                        $tree['fields'] = $fields;
                    } else {
                        return $tree;
                    }
                    break;
            }
            break;
    }
    return $tree;
}

}

$parser = new Parser();
$expression = "create table brent(dogfood int wala bing bang, cats char)";
print_r($parser->parse($expression));

?>
