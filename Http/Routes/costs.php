<?php
	
	Route::get('costs', 'CostsController@summaries')->middleware('auth');
	
	Route::get('costs/summaries', 'CostsController@summaries')->middleware('auth');
	
	
	//Route::get('costs/calculation_setup', 'CostsController@calculationSetup')->middleware('auth');
	
	//Route::post('costs/calculation_update', 'CostsController@updateCalculation')->middleware('auth');
	
	Route::post('costs/calculate', 'CostsController@calculate')->middleware('auth');
	
	
	Route::get('costs/quantity_type_setup', 'CostsController@quantity_type_setup')->middleware('auth');
	
	Route::post('costs/quantity_types_update', 'CostsController@quantity_types_update')->middleware('auth');
	
	Route::get('costs/copy_previous_quantity_types', 'CostsController@copy_previous_quantity_types')->middleware('auth');
	
	Route::get('costs/themes_setup', 'CostsController@themes_setup')->middleware('auth');
	
	Route::post('costs/themes_update', 'CostsController@themes_update')->middleware('auth');
	
	Route::get('costs/setup', 'CostsController@camp_list')->middleware('auth');
	
	Route::get('costs/camp_list', 'CostsController@camp_list')->middleware('auth');
	
	Route::post('costs/update_camps', 'CostsController@update_camps')->middleware('auth');

	Route::get('costs/summaries_campg', 'CostsController@summaries_campg')->middleware('auth');

	Route::get('costs/summaries_camps', 'CostsController@summaries_camps')->middleware('auth');

	Route::get('costs/summaries_gsq', 'CostsController@summaries_gsq')->middleware('auth');

	Route::get('costs/summaries_noncurriculum', 'CostsController@summaries_noncurriculum')->middleware('auth');

	Route::get('costs/activities', 'CostsController@activities')->middleware('auth');

	Route::get('costs/activities_gsq', 'CostsController@activities_gsq')->middleware('auth');

	Route::get('costs/activities_camps', 'CostsController@activities_camps')->middleware('auth');

	Route::get('costs/calculate_material_costs', 'CostsController@calculate_material_costs')->middleware('auth');
	