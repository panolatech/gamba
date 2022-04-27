<?php

	// Backups
	Route::get('backup/tables', 'BackupController@backup_tables')->middleware('auth');

	// Calculate
	Route::get('calculate/cg_all_grades_total', 'CalculateController@cg_all_grades_total')->middleware('auth');
	Route::get('calculate/calculate_all', 'CalculateController@calculate_all')->middleware('auth');
	Route::get('calculate/basic_calc', 'CalculateController@basic_calc')->middleware('auth');
	Route::get('calculate/from_cg_enrollment', 'CalculateController@from_cg_enrollment')->middleware('auth');
	Route::get('calculate/cg_office_data', 'CalculateController@cg_office_data')->middleware('auth');
	Route::get('calculate/gsq_office_data', 'CalculateController@gsq_office_data')->middleware('auth');
	Route::get('calculate/packing_totals_calc_all', 'CalculateController@packing_totals_calc_all')->middleware('auth');
	Route::get('calculate/quantity_short', 'CalculateController@quantity_short')->middleware('auth');
	Route::get('calculate/costs_calculate', 'CalculateController@costs_calculate')->middleware('auth');

	// Export
	Route::get('export/purchase_orders', 'ExportController@purchase_orders')->middleware('auth');
	Route::get('export/sales_orders', 'ExportController@sales_orders')->middleware('auth');
	Route::get('export/parts', 'ExportController@parts')->middleware('auth');
	Route::get('export/products', 'ExportController@products')->middleware('auth');

	// Fishbowl Sync
	Route::get('sync/vendors', 'SyncController@vendors')->middleware('auth');
	Route::get('sync/all', 'SyncController@all')->middleware('auth');
	Route::get('sync/customers', 'SyncController@customers')->middleware('auth');
	Route::get('sync/inventory', 'SyncController@inventory')->middleware('auth');
	Route::get('sync/parts', 'SyncController@parts')->middleware('auth');
	Route::get('sync/rest', 'SyncController@rest')->middleware('auth');
	Route::get('sync/uoms', 'SyncController@uoms')->middleware('auth');

	// Parts and Products
	Route::get('parts/get_parts', 'JobsController@exportParts')->middleware('auth');
	Route::get('parts/get_products', 'JobsController@exportProducts')->middleware('auth');