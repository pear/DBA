<?php

$empSchema = array (
'emp'      => array('id'       => array(DBA_TYPE => DBA_INTEGER,
                                        DBA_AUTOINCREMENT => True,
                                        DBA_DEFAULT => 0),
                    'empname'  => array(DBA_TYPE => DBA_VARCHAR,
                                        DBA_DEFAULT => 'No Name',
                                        DBA_SIZE => 45),
                    'job'      => array(DBA_TYPE => DBA_ENUM,
                                        DBA_DOMAIN => array('intern',
                                                          'clerk',
                                                          'salesman',
                                                          'manager',
                                                          'analyst'),
                                        DBA_DEFAULT => 'intern'),
                    'manager'  => array(DBA_TYPE => DBA_INTEGER,
                                        DBA_DEFAULT => 0),
                    'hiredate' => array(DBA_TYPE => DBA_TIMESTAMP),
                    'salary'   => array(DBA_TYPE => DBA_INTEGER,
                                        DBA_PRIMARYKEY => True),
                    'comm'     => array(DBA_TYPE => DBA_INTEGER),
                    'deptno'   => array(DBA_TYPE => DBA_INTEGER)
             ),

'dept'     => array('deptno'   => array(DBA_TYPE => DBA_INTEGER),
                    'deptname' => array(DBA_TYPE => DBA_VARCHAR),
                    'manager'  => array(DBA_TYPE => DBA_INTEGER)
             ),

'location' => array('locno'    => array(DBA_TYPE => DBA_INTEGER),
                    'locname'  => array(DBA_TYPE => DBA_VARCHAR)
             ),

'deptloc'  => array('deptno'   => array(DBA_TYPE => DBA_INTEGER),
                    'locno'    => array(DBA_TYPE => DBA_INTEGER)
             ),
'account'  => array('id'       => array(DBA_TYPE => DBA_INTEGER,
                                        DBA_AUTOINCREMENT => True,
                                        DBA_DEFAULT => 0),
                    'name'     => array(DBA_TYPE => DBA_VARCHAR,
                                        DBA_SIZE => 45),
                    'notes'    => array(DBA_TYPE => DBA_TEXT),
                    'active'   => array(DBA_TYPE => DBA_BOOLEAN)
             ),
);

// some test data
$empData = array(
'emp' => array(array('id' => '7369', 'empname' => 'Smith', 'job' => 'clerk',
                     'manager' => 7782, 'hiredate' => 'May 29, 1978',
                     'salary' => 800, 'comm' => 0, 'deptno' => 20),
               array(7499, 'Allen', 'salesman', 7782, 'FEB 20, 1981',
                     1600, 300, 30),
               array(7521, 'Ward', 'salesman', 7782, 'FEB 22, 1981',
                     1250, 500, 30),
               array(7782, 'Clark', 'manager', 7788, 'JUN 9,1981', 2450,
                     0, 10),
               array(7788, 'Scott', 'analyst', NULL, 'DEC 9, 1982', 3000, 
                     0, 20)),

'dept' => array(array(10, 'ACCOUNTING', 7782),
                array(20, 'RESEARCH', 7788),
                array(30, 'SALES', 7369),
                array(40, 'OPERATIONS', 7782)),

'location' => array(array(1, 'New York'),
                    array(2, 'Austin'),
                    array(3, 'Chicago')),

'deptloc' => array(array(10, 1),
                   array(20, 2),
                   array(30, 3),
                   array(40, 3),
                   array(10, 2)),

'account' => array(array('name'=>'Ford', 'notes'=>'Wear black to meetings',
                         'active'=>true),
                   array('name'=>'Chevy', 'notes'=>'Springtime casual',
                         'active'=>'no')),
);
?>
