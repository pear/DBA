<?php
	$hatTableStruct = array (
	                   'type' => array (DBA_TYPE => DBA_ENUM,
	                                    DBA_DOMAIN => array ('fedora',
	                                                       'stocking cap',
	                                                       'top hat',
	                                                       'bowler',
														   'beanie'),
	                                    DBA_PRIMARYKEY => True,
	                                    DBA_DEFAULT => 'fedora'),

	                   'quantity' => array (DBA_TYPE => DBA_INTEGER,
	                                        DBA_DEFAULT => 0),

	                   'brand' => array (DBA_TYPE => DBA_VARCHAR,
	                                     DBA_DEFAULT => 'No Name',
	                                     DBA_SIZE => 45),

	                   'sizes' => array (DBA_TYPE => DBA_SET,
	                                     DBA_DOMAIN => array ('small',
	                                                        'medium',
	                                                        'large',
	                                                        'xlarge'),
	                                     DBA_NOTNULL => true),

	                   'lastshipment' => array (DBA_TYPE => DBA_TIMESTAMP),
	                   'hat_id' => array (DBA_TYPE => DBA_INTEGER,
	                                      DBA_AUTOINCREMENT => True,
	                                      DBA_PRIMARYKEY => True,
                                          DBA_DEFAULT => 100)
	                  );

	$hats = array(
        array ('type' => 'bowler',
	           'quantity' => 20,
	           'brand' => "Jill's Hats",
	           'sizes' => array ('small', 'large'),
	           'lastshipment' => 'May 29, 1978'),

	    array ('type' => 'stocking cap',
	           'sizes' => array ('xlarge', 'large'),
	           'brand' => "Brent's Hats",
   	           'lastshipment' => time()),

	    array ('type' => 'fedora',
	           'quantity' => 800,
	           'brand' => "Shilanda's Hats",
	           'sizes' => array ('small', 'medium', 'large'),
	           'lastshipment' => 'June 30, 2001'),

	    array ('type' => 'top hat',
	           'quantity' => 10,
	           'brand' => "Laurie's Hats",
	           'sizes' => array ('small', 'medium', 'large'),
	           'lastshipment' => 'April 22, 2000'),

	    array ('type' => 'top hat',
	           'quantity' => 60,
	           'brand' => "Travis' Hats",
	           'sizes' => array ('small'),
	           'lastshipment' => 'January 1, 2000'),

	    array ('type' => 'beanie',
	           'quantity' => 600,
	           'brand' => "Elizabeth's Hats",
	           'sizes' => array ('small', 'large'),
	           'lastshipment' => 'May 18, 2002'));

?>
