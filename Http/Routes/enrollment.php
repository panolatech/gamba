<?php

	// Home Page
	Route::get('enrollment', 'EnrollmentController@index')->middleware('auth');

	// Sheet Locations
	Route::get('enrollment/{term}/sheet_locations', 'EnrollmentController@sheetLocations')->middleware('auth');

	// Reset Sheet Locations
	Route::get('enrollment/{term}/reset_enrollment_sheet', 'EnrollmentController@resetSheetLocations')->middleware('auth');



	// Camp G Campers
	Route::get('enrollment/{term}/cg_campers', 'EnrollmentCampGController@showCGCampers')->middleware('auth');

	// Camp G Campers - Edit
	Route::get('enrollment/{term}/cg_campers_edit/{grade}', 'EnrollmentCampGController@editCGCampers')->middleware('auth');

	// Camp G Campers - Upload and Edit
	Route::post('enrollment/{term}/cg_campers_edit/{grade}/upload', 'EnrollmentCampGController@editCGCampers')->middleware('auth');

	// Camp G Campers - Update
	Route::post('enrollment/{term}/cg_campers_update/{grade}', 'EnrollmentCampGController@updateCGCampers')->middleware('auth');

	// Camp G Campers - Calculate
	Route::get('enrollment/calculate_from_cg_enrollment', 'EnrollmentCampGController@showCGCampers')->middleware('auth');

	// Camp G Campers - Duplicate Locations
	Route::get('enrollment/duplicate_location', 'EnrollmentCampGController@duplicateLocation')->middleware('auth');

	// Camp G Campers - Remove Duplicate Locations
	Route::get('enrollment/remove_duplicate_location', 'EnrollmentCampGController@removeDuplicateLocation')->middleware('auth');

	// Camp G CSV - View
	Route::get('enrollment/{term}/cg_campers_csv_view', 'EnrollmentCampGController@showCSV')->middleware('auth');

	// Camp G CSV - Download
	Route::get('enrollment/{term}/cg_campers_csv_download/{grade}', 'EnrollmentCampGController@downloadCSV')->middleware('auth');

	// Camp G Campers - Update
	Route::get('enrollment/{term}/cg_campers_update/{grade}', 'EnrollmentCampGController@updateCGCampers')->middleware('auth');

	// Camp G Campers - Calculate
	Route::get('enrollment/{term}/calculate/{grade}/{camp}', 'EnrollmentCampGController@calculateCGCampers')->middleware('auth');



	// GSQ Campers
	Route::get('enrollment/{term}/gsq_campers', 'EnrollmentGSQController@showCampers')->middleware('auth');

	// GSQ Campers - Edit
	Route::get('enrollment/{term}/gsq_campers_edit', 'EnrollmentGSQController@editCampers')->middleware('auth');

	// GSQ Campers - Upload and Edit
	Route::post('enrollment/{term}/gsq_campers_edit/upload', 'EnrollmentGSQController@editCampers')->middleware('auth');

	// GSQ Campers - Update
	Route::post('enrollment/{term}/gsq_campers_update', 'EnrollmentGSQController@updateCampers')->middleware('auth');

	// GSQ CSV - View
	Route::get('enrollment/{term}/gsq_campers_csv_view', 'EnrollmentGSQController@showCSV')->middleware('auth');

	// GSQ CSV - Upload
	Route::get('enrollment/gsq_campers_csv_upload', 'EnrollmentGSQController@uploadCSV')->middleware('auth');

	// GSQ CSV - Download
	Route::get('enrollment/gsq_campers_csv_download', 'EnrollmentGSQController@downloadCSV')->middleware('auth');


	// Extension - Camp G
	Route::get('enrollment/{term}/cg_ext', 'EnrollmentExtensionController@showCampGCampers')->middleware('auth');

	// Extension - GSQ
	Route::get('enrollment/{term}/gsq_ext', 'EnrollmentExtensionController@showGSQCampers')->middleware('auth');

	// Extension - Update
	Route::post('enrollment/ext_update', 'EnrollmentExtensionController@update');
