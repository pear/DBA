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
require_once '../DBA_Sql.php';

$queries = array(
"CREATE TABLE albums (
  name varchar(60),
  directory varchar(60),
  rating enum (1,2,3,4,5,6,7,8,9,10) NOT NULL,
  category set(sexy,'\'family time\'',outdoors,generic,'very weird') NULL,
  description text NULL,
  id int auto_increment default 200 PRIMARY KEY
)",
"CREATE TABLE photos (
  filename varchar(60) not NULL,
  name varchar(60) default \"no name\",
  album int,
  price float (4,2),
  description text default 'hello',
  id int default 0 primary key autoincrement not null,
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
);

foreach ($queries as $query) {
    list($name, $schema) = Sql::parseCreate($query);
    echo "SQL:\n$query\n\n";
    if (PEAR::isError($results)) {
        echo $results->getMessage()."\n";
    } else {
        echo "Table Name: $name\n\nSchema:\n";
        print_r($schema);
    }
    echo "\n***********************************\n\n";
}

?>
