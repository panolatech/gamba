<?php

	Route::get('packing', 'PackingController@index')->middleware('auth');

	Route::get('packing/lists/{term}/{key}', 'PackingController@showPackingSupplies')->middleware('auth');
	
	Route::get('download/packing_lists', 'PackingController@downloadPackingList')->middleware('auth');
	