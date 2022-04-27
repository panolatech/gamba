<?php

	Route::get('parts/part_add', 'PartsController@formPartAdd')->middleware('auth');
	Route::get('parts/part_edit/{part}', 'PartsController@formPartEdit')->middleware('auth');
	Route::get('parts/part_number_edit/{part}', 'PartsController@formPartNumberEdit')->middleware('auth');
	Route::post('parts/part_number_process', 'PartsController@processPartNumber')->middleware('auth');
	Route::get('parts/backto', 'PartsController@backTo')->middleware('auth');
	Route::get('parts/parts_log', 'PartsController@partsLog')->middleware('auth');
	Route::get('parts/products_log', 'PartsController@productsLog')->middleware('auth');


	Route::post('parts/update_part', 'PartsController@updatePart')->middleware('auth');
	Route::post('parts/add_part', 'PartsController@addPart')->middleware('auth');
	Route::get('parts/part_delete', 'PartsController@deletePart')->middleware('auth');

	Route::get('parts/fbcosts/{page}', 'PartsController@viewPartCosts')->middleware('auth');
	Route::get('parts/fbcosts', 'PartsController@viewPartCosts')->middleware('auth');
	Route::get('parts/fbcostsupdate', 'PartsController@viewJoinPartCosts')->middleware('auth');

	Route::get('parts/{view}/{page}', 'PartsController@index')->middleware('auth');
	Route::get('parts/{view}', 'PartsController@index')->middleware('auth');
	Route::get('parts', 'PartsController@index')->middleware('auth');