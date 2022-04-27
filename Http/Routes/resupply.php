<?php
	// Landing Page Resupply Orders
	// Break up into Different Lists
	
	// Reorders Open and Editable or Reorder User View
	Route::get(
		'resupply',
			'ResupplyController@index'
			)->middleware('auth');
	// Reorders to Push
	Route::get(
		'resupply/orders/pushtofishbowl',
			'ResupplyController@reorders_to_push'
			)->middleware('auth');
	// Reorders In Production
	Route::get(
		'resupply/orders/inproduction',
			'ResupplyController@reorders_in_production'
			)->middleware('auth');
	// Reorders Partially Shipped
	Route::get(
		'resupply/orders/partiallyshipped',
			'ResupplyController@reorders_partially_shipped'
			)->middleware('auth');
	// Reorders Fully Shipped
	Route::get(
		'resupply/orders/fullyshipped',
			'ResupplyController@reorders_fully_shipped'
			)->middleware('auth');
	

	// Reorder Edit Open and Editable
	Route::get(
		'resupply/edit/open/{id}',
			'ResupplyController@edit_open_editable'
			)->middleware('auth');
	// Update Resupply Order - Open and Editable
	Route::post(
		'resupply/resupplyorderupdate', 
			'ResupplyController@updateResupplyOrder'
			)->middleware('auth');
	// Reorder Edit to Push
	Route::get(
		'resupply/edit/pushtofishbowl/{id}',
			'ResupplyController@edit_push'
			)->middleware('auth');
	// Update Resupply Order - Push to Fishbowl
	Route::post(
		'resupply/resupplyorderchange-push', 
			'ResupplyController@change_push_to_fishbowl'
			)->middleware('auth');
	// Reorder Edit In Production
	Route::get(
		'resupply/edit/inproduction/{id}',
			'ResupplyController@edit_in_production'
			)->middleware('auth');
	// Update Resupply Order - In Production
	Route::post(
		'resupply/resupplyorderchange-production', 
			'ResupplyController@change_in_production'
			)->middleware('auth');
	// Reorder Edit Partially Shipped
	Route::get(
		'resupply/edit/partiallyshipped/{id}',
			'ResupplyController@edit_partially_shipped'
			)->middleware('auth');
	// Update Resupply Order - Partially Shipped
	Route::post(
		'resupply/resupplyorderchange-partial', 
			'ResupplyController@change_partially_shipped'
			)->middleware('auth');
	// Mark as Shipped
	Route::post(
		'resupply/markshipped', 
			'ResupplyController@markShipped'
			)->middleware('auth');
	// Reorder Edit Fully Shipped
	Route::get(
		'resupply/edit/fullyshipped/{id}',
			'ResupplyController@edit_fully_shipped'
			)->middleware('auth');
	// Update Resupply Order - Fully Shipped
	Route::post(
		'resupply/reopentoedit', 
			'ResupplyController@change_fully_shipped'
			)->middleware('auth');
	
	
	// Reorder View to Push
	Route::get(
		'resupply/view/pushtofishbowl/{id}',
			'ResupplyController@view_reorders_to_push'
			)->middleware('auth');
	// Reorder View In Production
	Route::get(
		'resupply/view/inproduction/{id}',
			'ResupplyController@view_reorders_in_production'
			)->middleware('auth');
	// Reorder View Partially Shipped
	Route::get(
		'resupply/view/partiallyshipped/{id}',
			'ResupplyController@view_reorders_partially_shipped'
			)->middleware('auth');
	// Reorder View Fully Shipped
	Route::get(
		'resupply/view/fullyshipped/{id}',
			'ResupplyController@view_reorders_fully_shipped'
			)->middleware('auth');
	
	
	// Material Lists
	Route::get(
		'resupply/materiallists', 
			'ResupplyController@material_lists'
			)->middleware('auth');
	// Material List Items, Select Location and Add Items
	Route::get(
		'resupply/materiallistitems/{request_id}/{activity_id}/{camp}', 
			'ResupplyController@material_list_items'
			)->middleware('auth');
	
	// Create Resupply Order
	Route::post(
		'resupply/resupplyordercreate', 
			'ResupplyController@insertResupplyOrder'
			)->middleware('auth');
	

	// Reporting
	Route::get(
		'resupply/resupply-reporting', 
			'ResupplyController@reportResupply'
			)->middleware('auth');
	
	
	// Delete Resupply Order
	Route::get(
		'resupply/resupplyorderdelete', 
			'ResupplyController@deleteResupplyOrder'
			)->middleware('auth');
	
	// Delete Reorder Item
	Route::get(
		'resupply/reorderitemdelete', 
			'ResupplyController@reorder_item_delete'
			)->middleware('auth');
	
	// Update Resupply Order Item Need By Date Format
	Route::get(
		'resupply/update_reorder_item_need_by_date', 
			'ResupplyController@update_reorder_item_need_by_date'
			)->middleware('auth');
	
	// Resupply Admin - Locations Cut Off Day
	Route::get(
		'resupply/cut_off_locations',
			'ResupplyController@cut_off_locations'
			)->middleware('auth');
	
	// Resupply Admin - Location Cut Off Day Update
	Route::post(
		'resupply/cut_off_location_update',
			'ResupplyController@cut_off_location_update'
			)->middleware('auth');
	
	// Resupply Admin - Location Modal
	Route::get(
		'resupply/location_modal/{location_id}',
			'ResupplyController@location_modal'
			)->middleware('auth');
	
	// Datepicker Cutoff
	Route::post(
		'resupply/datepicker_cutoff',
			'ResupplyController@datepicker_cutoff'
			)->middleware('auth');
	