<?php
$tests =
array(
    array(
        'sql'=>"
CREATE TABLE albums (
    name varchar(60),
    directory varchar(60),
    rating enum (1,2,3,4,5,6,7,8,9,10) NOT NULL,
    category set('sexy','\'family time\'',\"outdoors\",'generic','very weird') NULL,
    description text NULL,
    id int default 200 PRIMARY KEY
)",
        'expect'=>array(
            'command'=>'select',
            'set_function'=>array(
                'name'=>'count',
                'distinct'=>true,
                'arg'=>'country'),
            'table_names'=>array('publishers')
        )
    ),
    array(
        'sql'=>'select * from dog where cat <> 4',
        'expect'=>array(
            'command'=>'select',
            'column_names'=>array('*'),
            'table_names'=>array('dog'),
            'where_clause'=>array(
                'arg_1'=>array(
                    'value'=>'cat',
                    'type'=>'ident'),
                'op'=>'<>',
                'arg_2'=>array(
                    'value'=>'4',
                    'type'=>'int_val')
            )
        )
    ),
    array(
        'sql'=>'select legs, hairy from dog',
        'expect'=>array(
            'command'=>'select',
            'column_names'=>array('legs', 'hairy'),
            'table_names'=>array('dog'),
        )
    ),
    array(
        'sql'=>'select max(length) from dog',
        'expect'=>array(
            'command'=>'select',
            'set_function'=>array(
                'name'=>'max',
                'arg'=>'length'
            ),
            'table_names'=>array('dog')
        ),
    ),
);
?>
