<?php
	$hatTableStruct = array (
	                   'type' => array ('type' => 'enum',
	                                    'domain' => array ('fedora',
	                                                       'stocking cap',
	                                                       'top hat',
	                                                       'bowler',
														   'beanie'),
	                                    'default' => 'fedora'),

	                   'quantity' => array ('type' => 'integer',
	                                        'default' => 0),

	                   'brand' => array ('type' => 'varchar',
	                                     'default' => 'No Name',
	                                     'size' => 45),

	                   'sizes' => array ('type' => 'set',
	                                     'domain' => array ('small',
	                                                        'medium',
	                                                        'large',
	                                                        'xlarge'),
	                                     'notnull' => 'true'),

	                   'lastshipment' => array ('type' => 'TIMESTAMP',
	                                            'format' => 'D M j, Y'),

	                   'hat_id' => array ('type' => 'integer',
	                                      'autoincrement' => True,
	                                      'primarykey' => True)
	                  );

	$new_hats[0] = array ('type' => 'bowler',
	                      'quantity' => 20,
	                      'brand' => "Jill's Hats",
	                      'sizes' => array ('small', 'large'),
	                      'lastshipment' => 'May 29, 1978');

	$new_hats[1] = array ('type' => 'stocking cap',
	                      'sizes' => array ('xlarge', 'large'),
	                      'brand' => "Brent's Hats",
	                      'lastshipment' => time());

	$new_hats[2] = array ('type' => 'fedora',
	                      'quantity' => 800,
	                      'brand' => "Shilanda's Hats",
	                      'sizes' => array ('small', 'medium', 'large'),
	                      'lastshipment' => 'June 30, 2001');

	$new_hats[3] = array ('type' => 'top hat',
	                      'quantity' => 10,
	                      'brand' => "Laurie's Hats",
	                      'sizes' => array ('small', 'medium', 'large'),
	                      'lastshipment' => 'April 22, 2000');

	$new_hats[4] = array ('type' => 'top hat',
	                      'quantity' => 60,
	                      'brand' => "Travis' Hats",
	                      'sizes' => array ('small'),
	                      'lastshipment' => 'January 1, 2000');

	$new_hats[5] = array ('type' => 'beanie',
	                      'quantity' => 600,
	                      'brand' => "Elizabeth's Hats",
	                      'sizes' => array ('small', 'large'),
	                      'lastshipment' => 'May 18, 2002');

?>
