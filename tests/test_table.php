<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Brent Cook <busterb@mail.utexas.edu>                        |
// +----------------------------------------------------------------------+
//
// $Id$
//

// test functionality of the dba table layer

ini_set('include_path',ini_get('include_path').':../../');
require_once 'DB/DBA/DBA_Table.php';
require_once 'hatSchema.php';

function printQueryResults($results, $fields=null)
{
    if (is_array($results) && sizeof($results)) {

        if (is_null($fields))
            $fields = array_keys(current($results));

        // get the maximum length of each field
        foreach ($fields as $key=>$field) {
            $longest[$key] = strlen($field);
            foreach ($results as $result) {
                $resultLen = strlen($result[$field]);
                if ($resultLen > $longest[$key])
                    $longest[$key] = $resultLen;
            }
        }

        // print fields
        foreach ($fields as $key=>$field)
            echo str_pad($field, $longest[$key]).' ';
        echo "\n";
        
        // print dividers
        foreach ($longest as $length)
            echo str_repeat('-',$length).' ';
        echo "\n";

        // print rows
        foreach ($results as $result) {
            foreach ($fields as $key=>$field)
                echo str_pad($result[$field], $longest[$key]).' ';
            echo "\n";
        }

    } else {
        echo "\tEmpty!\n";
    }
    echo "\n";
    echo "------------------------------------------\n";
        
}

$table=new DBA_Table();

$result = $table->create ('hats', $hatTableStruct);
if (PEAR::isError($result)) {
    echo $result->getMessage()."\n";
    exit;
}

// open and close table multiple times while inserting
for ($i=0; $i < 2; ++$i) {
    $table->open ('hats', 'w');
    foreach ($new_hats as $hat) {
        $table->insertRow($hat);
    }
    $table->close();
}

$table->open ('hats', 'r');

$query = '(type != bowler) and (type != fedora)';
$results = $table->select($query);
echo "Query: $query\n\n";
printQueryResults ($results, array('brand', 'quantity', 'type'));

$query = 'quantity <= 50';
$results = $table->select($query);
echo "Query: $query\n\n";
printQueryResults ($results, array('brand', 'quantity'));

$query = 'quantity >= 50';
$results = $table->select($query);
echo "Query: $query\n\n";
printQueryResults ($results, array('brand', 'quantity'));

$sortFields = 'quantity, hat_id';
$results = $table->sort($sortFields);
echo "Sorting by quantity, hat_id, ascending order\n";
printQueryResults ($results, array('brand', 'quantity', 'hat_id'));

$sortField = 'quantity';
$results = array_reverse ($table->sort($sortField));
echo "Sorting by $sortField, descending order\n";
printQueryResults ($results, array('brand', 'quantity'));

$sortField = 'quantity';
$results = $table->unique(
           $table->project('brand, quantity',
           $table->sort('quantity', 'd',
           $table->select($query))));
echo "Sorting by: $sortField, descending order\n";
echo "Query: $query\n\n";
printQueryResults ($results, array('brand', 'quantity'));

$table->close();

?>
