<?php

$empSchema = array (
'emp'      => array('id'       => array('type' => 'integer',
                                        'autoincrement' => True,
                                        'default' => 0),
                    'empname'  => array('type' => 'varchar',
                                        'default' => 'No Name',
                                        'size' => 45),
                    'job'      => array('type' => 'enum',
                                        'domain' => array('intern',
                                                            'clerk',
                                                            'salesman',
                                                            'manager',
                                                            'analyst'),
                                        'default' => 'intern'),
                    'manager'  => array('type' => 'integer',
                                        'default' => 0),
                    'hiredate' => array('type' => 'TIMESTAMP',
                                        'format' => 'D M j, Y'),
                    'salary'   => array('type' => 'integer',
                                        'primarykey' => True),
                    'comm'     => array('type' => 'integer'),
                    'deptno'   => array('type' => 'integer')
             ),

'dept'     => array('deptno'    => array('type' => 'integer'),
                    'deptname'  => array('type' => 'varchar'),
                    'manager'   => array('type' => 'integer')
             ),

'location' => array('locno'     => array('type' => 'integer'),
                    'locname'   => array('type' => 'varchar')
             ),

'deptloc'  => array('deptno'    => array ('type' => 'integer'),
                    'locno'     => array ('type' => 'integer')
             ),
);

// some test data
$emp_data = array(
                array ('id' => '7369', 'empname' => 'Smith', 'job' => 'clerk',
                    'manager' => 7782, 'hiredate' => 'May 29, 1978',
                    'salary' => 800, 'comm' => 0, 'deptno' => 20),
                array (7499, 'Allen',  'salesman',  7782, 'FEB 20, 1981',                              1600, 300, 30),
                array (7521, 'Ward',   'salesman',  7782, 'FEB 22, 1981',
                    1250, 500, 30),
                array (7782, 'Clark',  'manager',   7788, 'JUN 9,1981', 2450,
                    0, 10),
                array (7788, 'Scott',  'analyst',   NULL, 'DEC 9, 1982', 3000, 
                    0, 20));

$dept_data = array (
                array (10, 'ACCOUNTING', 7782),
                array (20, 'RESEARCH', 7788),
                array (30, 'SALES',  7369),
                array (40, 'OPERATIONS',  7782));

$location_data = array (
                array (1, 'New York'),
                array (2, 'Austin'),
                array (3, 'Chicago'));

$deptloc_data = array (
                array (10, 1),
                array (20, 2),
                array (30, 3),
                array (40, 3),
                array (10, 2));
?>
