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
require_once 'PEAR.php';
require_once 'DB/DBA/DBA_Relational.php';
require_once 'empSchema.php';

// set the working directory and driver
$db=new DBA_Relational('./', 'simple');

// generate and populate the tables
foreach ($empSchema as $tableName=>$tableSchema) {
    $data = "{$tableName}_data";

    echo "Creating table: $tableName\n";

    $result = $db->createTable($tableName, $tableSchema);

    if (PEAR::isError($result)) {
        echo $result->getMessage()."\n";
    } else {
        foreach ($empData[$tableName] as $row) {
            $result = $db->insert($tableName, $row);
            if (PEAR::isError($result)) {
            echo $result->getMessage()."\n";
            }
        }

        $results = $db->select($tableName, '*');

        echo "Query: \$db->select($tableName, '*');\n";
        echo $db->formatResults($db->finalize($tableName, $results));
        echo "------------------------------------------------\n\n";
    }
}

// closes all open tables - testing auto-open feature
$db->close();

$queries = array(
                '$db->select("nothere", "pigs == fly")',
                '$db->select("emp", "salary >= 1500")',
                '$db->sort("empname", "a", $db->select("emp", "(job != analyst) and (job != intern)"))',
                '$db->sort("empname", "d", $db->select("emp", "(job != analyst) and (job != intern)"))',
                '$db->join("emp", "dept", "emp.deptno == dept.deptno")',
                '$db->join("location", $db->join("dept", "deptloc", "dept.deptno == deptloc.deptno"), "location.locno == B.locno")',
                '$db->sort("manager", "a", $db->join("location", $db->join("dept", "deptloc", "dept.deptno == deptloc.deptno"), "location.locno == B.locno"))'
                );

foreach ($queries as $query) {
    echo "Query: $query\n";
    eval ('$results = '.$query.';');

    if (PEAR::isError($results)) { 
        echo " Query failed.\n";
        echo $results->getMessage()."\n";
    } else {
        echo $db->formatResults ($results);
    }

    echo "------------------------------------------------\n\n";
}
	
	$db->close();
?>
