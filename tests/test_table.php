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

$table =& new DBA_Table();

$result = $table->create('hats', $hatTableStruct);
if (PEAR::isError($result)) {
    echo $result->getMessage()."\n";
    exit;
}

// open and close table multiple times while inserting
$result = $table->open('hats', 'w');
if (PEAR::isError($result)) {
    echo $result->getMessage()."\n";
    exit;
}

for ($i=0; $i < 2; ++$i) {
    foreach ($new_hats as $hat) {
        $table->insert($hat);
    }
}
//$table->close();

$table->open ('hats', 'r');

$query = '(type != bowler) and (type != fedora)';
$results = $table->select($query);
echo "Query: $query\n\n";
echo $table->formatTextResults($results, array('brand', 'quantity', 'type'));

$query = 'quantity <= 50';
$results = $table->select($query);
echo "Query: $query\n\n";
echo DBA_Table::formatTextResults($results, array('brand', 'quantity'));

$query = 'quantity >= 50';
$results = $table->select($query);
echo "Query: $query\n\n";
echo DBA_Table::formatTextResults($results, array('brand', 'quantity'));

$sortFields = 'quantity, hat_id';
$results = $table->sort($sortFields, 'a', $table->getRows());
echo "Sorting by quantity, hat_id, ascending order\n";
echo DBA_Table::formatTextResults($results, array('brand', 'quantity', 'hat_id'));

$sortField = 'quantity';
$results = $table->sort($sortField, 'd', $table->select('*'));
echo "Sorting by $sortField, descending order\n";
echo DBA_Table::formatTextResults($results, array('brand', 'quantity'));

$sortField = 'quantity';
$results = $table->unique(
           $table->project('brand, quantity',
           $table->sort('quantity', 'd',
           $table->select($query))));
echo "Sorting by: $sortField, descending order\n";
echo "Query: $query\n\n";
echo DBA_Table::formatTextResults($results, array('brand', 'quantity'), 'mysql');

//$table->close();

?>
