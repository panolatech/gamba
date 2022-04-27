<?php
	
	Route::get('sales', 'SalesController@index')->middleware('auth');
	
	Route::get('sales/packinglists', 'SalesController@showPackingLists')->middleware('auth');
	
	Route::get('sales/camp_locations', 'SalesController@showCampLocations')->middleware('auth');
	
	Route::get('sales/camp_locationparts', 'SalesController@showCampLocationParts')->middleware('auth');
	
	Route::get('sales/salesorder', 'SalesController@showSalesOrder')->middleware('auth');
	
	Route::get('sales/salesorderdelete', 'SalesController@deleteSalesOrder')->middleware('auth');
	
	Route::post('sales/salesordercreate', 'SalesController@insertSalesOrder')->middleware('auth');
	
	Route::post('sales/salesorderupdate', 'SalesController@updateSalesOrder')->middleware('auth');
	
	Route::get('sales/so_mark_pushed', 'SalesController@markPushed')->middleware('auth');
	
	Route::get('sales/create_supplemental', 'SalesController@create_supplemental')->middleware('auth');
	
	Route::get('sales/getlocations', 'SalesController@get_locations')->middleware('auth');
	
	Route::get('sales/getthemes', 'SalesController@get_themes')->middleware('auth');
	
	Route::get('sales/getgrades', 'SalesController@get_grades')->middleware('auth');
	
	Route::get('sales/getquantitytypes', 'SalesController@get_quantitytypes')->middleware('auth');
	
	Route::post('sales/getsaleslist', 'SalesController@get_saleslist')->middleware('auth');
	
	Route::post('sales/createsupplementalorder', 'SalesController@create_supplemental_order')->middleware('auth');