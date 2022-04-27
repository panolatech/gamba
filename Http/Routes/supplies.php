<?php

Route::get('supplies/supplyrequests', 'SuppliesController@index')->middleware('auth');

Route::get('supplies/conceptitems', 'PartsController@suppliesconceptitems')->middleware('auth');
Route::get('supplies/conceptitems/{page}', 'PartsController@suppliesconceptitems')->middleware('auth');
Route::get('supplies/conceptitems/edit/{part}', 'PartsController@suppliesconceptitemsedit')->middleware('auth');
Route::post('supplies/conceptitems/part_update', 'PartsController@suppliesconceptitemsupdate')->middleware('auth');
Route::get('supplies/conceptitems/delete/{part}', 'PartsController@suppliesconceptitemsdelete')->middleware('auth');

Route::get('supplies/masterinventorylist', 'PartsController@suppliesmasterinventorylist')->middleware('auth');
Route::get('supplies/parts', 'PartsController@suppliesparts')->middleware('auth');
Route::get('supplies/parts/{alpha}', 'PartsController@suppliesparts')->middleware('auth');
Route::get('supplies/parts/view/{part}', 'PartsController@suppliespartsview')->middleware('auth');

Route::get('supplies/supplylistview', 'SuppliesController@showSupplyList')->middleware('auth');

Route::get('supplies/supplylistedit', 'SuppliesController@editSupplyList')->middleware('auth');

Route::get('supplies/createsupplylist', 'SuppliesController@createSupplyList')->middleware('auth');

Route::get('supplies/supplylistcopy', 'SuppliesController@copySupplyList')->middleware('auth');

Route::get('supplies/js/add_form_field', 'SuppliesController@add_form_field')->middleware('auth');



Route::get('supplies/createlist', 'SuppliesController@storeList')->middleware('auth');

Route::get('supplies/delete_supply', 'SuppliesController@deleteSupply')->middleware('auth');

Route::post('supplies/move_materials', 'SuppliesController@moveMaterial')->middleware('auth');

Route::get('supplies/supplylistdelete', 'SuppliesController@deleteList')->middleware('auth');

Route::get('supplies/listinsert', 'SuppliesController@insertList')->middleware('auth');

Route::get('supplies/listcreateinsert', 'SuppliesController@listCreateInsert')->middleware('auth');

Route::post('supplies/supplies_add', 'SuppliesController@storeSupplies')->middleware('auth');

Route::post('supplies/supplies_update', 'SuppliesController@updateSupplies')->middleware('auth');

Route::post('supplies/update_data_inputs', 'SuppliesController@updateDataInputs')->middleware('auth');

Route::get('supplies/admin_lock', 'SuppliesController@adminLock')->middleware('auth');
Route::get('supplies/admin_lock_debug', 'SuppliesController@adminLockDebug')->middleware('auth');

Route::get('supplies/admin_unlock', 'SuppliesController@adminUnlock')->middleware('auth');


Route::get('supplies', 'SuppliesController@index')->middleware('auth');


Route::get('supplies/downloadcsv', 'DownloadController@materiallistcsv')->middleware('auth');


Route::get('supplies/usedmaterials', 'UsedMaterialsController@index')->middleware('auth');
Route::get('supplies/usedmaterials/supplylists/{term}/{part_num}', 'UsedMaterialsController@supplylists')->middleware('auth');
Route::get('supplies/usedmaterials/download/{term}', 'DownloadController@usedmaterialscsv')->middleware('auth');