<?php
return [
	
	'log_path' => '/var/www/gamba/lp18-5.2d/public/logs/',
	'site_path' => '/var/www/gamba/lp18-5.2d/public/',
		
	'gamba_name' => 'GAMBA 2018 Dev',
	
	// Set Debug for arrays at bottom
	'debug' => '1',  // 1 is On, 0 is Off
	
	// Used in Calculation - Corrects Item Types in requested Materials versus the Parts Database
	'itemtype_override' => 'true',  // true is On
	
	// Use Basic Supplies Packing Recalculation in Purchase and Sales Orders 
	'basic_calc_status' => '1', // 1 is On, 0 is Off
			
	// Admin E-mail
	'admin_email' => 'john@panolatech.com'
];