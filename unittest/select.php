<?php
$tests =
array(
    array(
        'sql'=>'SELECT COUNT(DISTINCT country) FROM publishers',
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
