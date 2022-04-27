<?php

	Route::get('purchase/pos', 'PurchaseController@index')->middleware('auth');

	Route::get('purchase/poview', 'PurchaseController@showPurchaseOrder')->middleware('auth');

	Route::get('purchase/poitemadd', 'PurchaseController@formPurchaseOrderItem')->middleware('auth');

	Route::get('purchase/poitemedit', 'PurchaseController@formPurchaseOrderItem')->middleware('auth');

	Route::get('purchase/pologfile', 'PurchaseController@showPurchaseOrderLogs')->middleware('auth');

	Route::get('purchase/orphans', 'PurchaseController@showPackingTotalOrphans')->middleware('auth');

	Route::get('purchase/mastersupply', 'PurchaseController@showMasterSupply')->middleware('auth');
	
	Route::get('purchase/mastersupplylist', 'PurchaseController@masterSupplyList')->middleware('auth');
	
	
	Route::post('purchase/master_supply_update', 'PurchaseController@updateMasterSupply')->middleware('auth');

	Route::post('purchase/pocreate', 'PurchaseController@insertPurchaseOrder')->middleware('auth');

	Route::get('purchase/podelete', 'PurchaseController@deletePurchaseOrder')->middleware('auth');

	Route::get('purchase/poitemdelete', 'PurchaseController@deletePurchaseOrderItem')->middleware('auth');

	Route::post('purchase/posave', 'PurchaseController@updatePurchaseOrder')->middleware('auth');

	Route::post('purchase/poiteminsert', 'PurchaseController@insertPurchaseOrderItem')->middleware('auth');

	Route::post('purchase/poitemupdate', 'PurchaseController@updatePurchaseOrderItem')->middleware('auth');

	Route::get('purchase/poitemdelete', 'PurchaseController@deletePurchaseOrderItem')->middleware('auth');

	Route::post('purchase/popushtofb', 'PurchaseController@pushPurchaseOrder')->middleware('auth');

	Route::get('purchase/delete_all_orphans', 'PurchaseController@deleteAllOrphans')->middleware('auth');

	Route::get('purchase/delete_orphan', 'PurchaseController@deleteOrphans')->middleware('auth');
	

	Route::get('purchase', 'PurchaseController@index')->middleware('auth');