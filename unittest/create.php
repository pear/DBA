<?php
$tests = array(

array(
'sql'=>
"CREATE TABLE albums (
    name varchar(60),
    directory varchar(60),
    rating enum (1,2,3,4,5,6,7,8,9,10) NOT NULL,
    category set('sexy','\'family time\'',\"outdoors\",'generic','very weird') NULL,
    description text NULL,
    id int default 200 PRIMARY KEY
)",
'expect'=>
array(
    'command'=>'create_table',
    'table_name'=>'albums',
    'column_defs'=>array(
        'name'=>array('type'=>'varchar', 'length'=>60),
        'directory'=>array(
            'type'=>'varchar',
            'length'=>60
        ),
        'rating'=>array(
            'type'=>'enum',
            'domain'=>array (1,2,3,4,5,6,7,8,9,10),
            'constraints'=>array(
                array('type'=>'not_null', 'value'=>true),
            ),
        ),
        'category'=>array(
            'type'=>'set',
            'domain'=>array("sexy","'family time'","outdoors","generic","very weird"),
        ),
        'description'=>array(
            'type'=>'text'
        ),
        'id'=>array(
            'type'=>'int',
            'constraints'=>array (
                array('type'=>'default_value', 'value'=>200),
                array('type'=>'primary_key', 'value'=>true),
            ),
        ),
    ),
    'column_names'=>array('name','directory','rating','category','description','id')
)),

array(
'sql'=>
"CREATE TABLE photos (
  filename varchar(60) not NULL,
  name varchar(60) default \"no name\",
  album int,
  price float (4,2),
  description text default 'hello',
  id int default 0 primary key not null,
)",
'expect'=>
array(
    'command'=>'create_table',
    'table_name'=>'photos',
    'column_defs'=>array(
        'filename'=>array(
            'type'=>'varchar',
            'length'=>60,
            'constraints'=>array(
                array('type'=>'not_null', 'value'=>true)
            )
        ),
        'name'=>array(
            'type'=>'varchar',
            'length'=>60,
            'constraints'=>array(
                array('type'=>'default_value', 'value'=>'no name')
            )
        ),
        'album'=>array(
            'type'=>'int'
        ),
        'price'=>array(
            'type'=>'float',
            'length'=>4,
            'decimals'=>2
        ),
        'description'=>array(
            'type'=>'text',
            'constraints'=>array(
                array('type'=>'default_value', 'value'=>'hello')
            )
        ),
        'id'=>array(
            'type'=>'int',
            'constraints'=>array(
                array('type'=>'default_value', 'value'=>0),
                array('type'=>'primary_key', 'value'=>true),
                array('type'=>'not_null', 'value'=>true)
            )
        )
    ),
    'column_names'=>array('filename','name','album','price','description','id')
)),

array(
'sql'=>
"CREATE TABLE films (
             code      CHARACTER(5) CONSTRAINT firstkey PRIMARY KEY,
             title     CHARACTER VARYING(40) NOT NULL,
             did       DECIMAL(3) NOT NULL,
             date_prod DATE,
             kind      CHAR(10),
             len       INTERVAL HOUR TO MINUTE
)",
'expect'=>
array(
    'command'=>'create_table',
    'table_name'=>'films',
    'column_defs'=>array(
        'code'=>array(
            'type'=>'character',
            'length'=>5,
            'constraints'=>array(
                'firstkey'=>array('type'=>'primary_key', 'value'=>true)
            )
        ),
        'title'=>array(
            'type'=>'character',
            'constraints'=>array(
                array('type'=>'varying', 'value'=>40),
            )
        )
    )
)
));
?>
