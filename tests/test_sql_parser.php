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
// | Author: Brent Cook <busterb@mail.utexas.edu>                         |
// +----------------------------------------------------------------------+
//
// $Id$
//

// test functionality of the sql parser

require_once 'PEAR.php';
require_once '../Sql_parse.php';

$queries = array(
"CREATE TABLE albums (
  name varchar(60),
  directory varchar(60),
  rating enum (1,2,3,4,5,6,7,8,9,10) NOT NULL,
  category set('sexy','\'family time\'',\"outdoors\",'generic','very weird') NULL,
  description text NULL,
  id int default 200 PRIMARY KEY
)",
"CREATE TABLE photos (
  filename varchar(60) not NULL,
  name varchar(60) default \"no name\",
  album int,
  price float (4,2),
  description text default 'hello',
  id int default 0 primary key auto_increment not null,
)",
"create table brent (
    filename varchar(10),
    description varchar(20),
)",
"create table nodefinitions",
"create dogfood",
"create table dunce (name varchar",
"create table dunce (name varchar(2,3))",
"create table dunce (enum)",
"create table dunce (enum(23))",
"CREATE TABLE films ( 
             code      CHARACTER(5) CONSTRAINT firstkey PRIMARY KEY, 
             title     CHARACTER VARYING(40) NOT NULL, 
             did       DECIMAL(3) NOT NULL, 
             date_prod DATE, 
             kind      CHAR(10), 
             len       INTERVAL HOUR TO MINUTE
             CONSTRAINT production UNIQUE(date_prod)
)",
"CREATE TABLE distributors ( 
             did      DECIMAL(3) PRIMARY KEY DEFAULT NEXTVAL('serial'), 
             name     VARCHAR(40) NOT NULL CHECK (name <> '') 
             CONSTRAINT con1 CHECK (did > 100 AND name > '') 
)",
"CREATE TABLE distributors ( 
            did      DECIMAL(3) PRIMARY KEY, 
            name     VARCHAR(40) 
)",
"insert into dogmeat ('horse', 'hair') values (2, 4)",
"inSERT into dogmeat (horse, hair) values (2, 4)",
"CREATE TABLE msgs ( user_id integer, msg_id integer, msg_text varchar, msg_title varchar(30), msg_date time)",
"update dogmeat set horse=2 dog='forty' where moose <> 'howdydoo'",
"update dogmeat set horse=2 dog='forty' where moose != 'howdydoo'",
"update dogmeat set horse=2 , dog='forty' where moose <> 'howdydoo'",
"update table1 set col=1 where not col = 2",
"update table2 set col=1 where col > 2 and col <> 4",
"update table2 set col=1 where col > 2 and col <> 4 or dog=\"Hello\"",
"update table3 set col=1 where col > 2 and (col < 30)",
);

$lexer = new Lexer();

$parser = new Sql_Parser();

foreach ($queries as $query) {
//    $query = implode(';', $queries);
    echo "SQL:\n$query\n\n";

    $results = $parser->parse($query);

    if (PEAR::isError($results)) {
        echo $results->getMessage()."\n";
    } else {
//        echo "Table Name: $name\n\nSchema:\n";
        echo "Results:\n";
        print_r($results);
    }
/*
    $lexer = new Lexer($query);
    $token = $lexer->lex();
    while ($token != SQL_END_OF_INPUT) {
        echo "'$token'\n";
        $token = $lexer->lex();
    }
*/
    echo "\n***********************************\n\n";
}

?>
