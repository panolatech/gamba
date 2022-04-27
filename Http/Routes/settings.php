<?php

// Settings
Route::get('settings', 'SettingsController@index')->middleware('auth');

Route::get('settings/config', 'SettingsController@showConfig')->middleware('auth');

Route::post('settings/update_config', 'SettingsController@updateConfig')->middleware('auth');

Route::get('settings/packing_calc', 'SettingsCalculateController@calculatePacking')->middleware('auth');

Route::get('settings/basic_calc', 'SettingsCalculateController@calculateBasic')->middleware('auth');

Route::get('settings/calculate_basic_packing', 'SettingsCalculateController@calculateBasicPacking')->middleware('auth');

Route::get('settings/calculate_all', 'SettingsCalculateController@calculateAll')->middleware('auth');

Route::get('settings/calc_quantity_short', 'SettingsCalculateController@calcQtyShort')->middleware('auth');
Route::get('settings/test_calc_quantity_short', 'SettingsCalculateController@testCalcQtyShort')->middleware('auth');

Route::get('settings/calc_packing_totals', 'SettingsCalculateController@calcPackingTotals')->middleware('auth');

Route::get('settings/log_files', 'SettingsCalculateController@logFiles')->middleware('auth');
Route::get('settings/jobs', 'SettingsCalculateController@jobs')->middleware('auth');
Route::get('settings/failed_jobs', 'SettingsCalculateController@jobsFailed')->middleware('auth');

// Camp Categories
Route::get('settings/camps', 'SettingsCampsController@index')->middleware('auth');
Route::get('settings/camp_add', 'SettingsCampsController@createCamp')->middleware('auth');
Route::get('settings/camp_edit', 'SettingsCampsController@createCamp')->middleware('auth');
Route::get('settings/data_update_camp', 'SettingsCampsController@dataUpdateCamp')->middleware('auth');
Route::get('settings/data_add_camp', 'SettingsCampsController@dataAddCamp')->middleware('auth');
Route::post('settings/camp_category_input_update', 'SettingsCampsController@categoryInputUpdateCamp')->middleware('auth');
Route::get('settings/camp_category_input', 'SettingsCampsController@categoryInputCamp')->middleware('auth');

// Directions
Route::get('settings/directions', 'SettingsDirectionsController@index')->middleware('auth');
Route::get('settings/update_directions', 'SettingsDirectionsController@updateDirections')->middleware('auth');
Route::get('settings/update_direction', 'SettingsDirectionsController@updateDirection')->middleware('auth');

// Fishbowl Sync

Route::post('settings/fishbowl_schedule', 'SettingsFishbowlController@schedule')->middleware('auth');

Route::get('settings/fbpart_search', 'SettingsFishbowlController@partSearch')->middleware('auth');

Route::get('settings/customers', 'SettingsFishbowlController@customers')->middleware('auth');

Route::get('settings/vendors', 'SettingsFishbowlController@vendors')->middleware('auth');

// Inventory Numbers
Route::get('settings/csvimport', 'SettingsCSVController@csvImport')->middleware('auth');
Route::get('settings/csv_qtyonhanddata_import', 'SettingsCSVController@csvQtyOnHandImport')->middleware('auth');
Route::get('settings/csv_qtyshippeddata_import', 'SettingsCSVController@csvQtyShippedImport')->middleware('auth');
Route::get('settings/csv_onorder_import', 'SettingsCSVController@csvOnOrderImport')->middleware('auth');
Route::post('settings/csv_upload', 'SettingsCSVController@csvUpload')->middleware('auth');
Route::get('settings/quantity_short', 'CalculateController@quantity_short')->middleware('auth');

// Fishbowl Sync
Route::get('settings/fishbowl', 'SettingsFishbowlController@showSync')->middleware('auth');
Route::get('settings/sync_all', 'SyncController@all')->middleware('auth');
Route::get('settings/fishbowl/sync_all', 'SettingsFishbowlController@outputSyncAll')->middleware('auth');
Route::get('settings/sync_parts', 'SyncController@parts')->middleware('auth');
Route::get('settings/fishbowl/sync_parts', 'SettingsFishbowlController@outputSyncParts')->middleware('auth');
Route::get('settings/sync_uoms', 'SyncController@uoms')->middleware('auth');
Route::get('settings/fishbowl/sync_uoms', 'SettingsFishbowlController@outputSyncUoMs')->middleware('auth');
Route::get('settings/sync_vendors', 'SyncController@vendors')->middleware('auth');
Route::get('settings/fishbowl/sync_vendors', 'SettingsFishbowlController@outputSyncVendors')->middleware('auth');
Route::get('settings/sync_customers', 'SyncController@customers')->middleware('auth');
Route::get('settings/fishbowl/sync_customers', 'SettingsFishbowlController@outputSyncCustomers')->middleware('auth');
Route::get('settings/sync_vendorparts', 'SyncController@vendorparts')->middleware('auth');
Route::get('settings/fishbowl/sync_vendorparts', 'SettingsFishbowlController@outputSyncVendorParts')->middleware('auth');

// Grades
Route::get('settings/grades', 'SettingsGradesController@index')->middleware('auth');
Route::get('settings/grade_add', 'SettingsGradesController@form')->middleware('auth');
Route::get('settings/grade_edit', 'SettingsGradesController@form')->middleware('auth');
Route::post('settings/update_grade', 'SettingsGradesController@update')->middleware('auth');
Route::post('settings/add_grade', 'SettingsGradesController@store')->middleware('auth');

// Camp Locations
Route::get('settings/locations', 'SettingsLocationsController@index')->middleware('auth');
Route::get('settings/location_add', 'SettingsLocationsController@form')->middleware('auth');
Route::get('settings/location_edit', 'SettingsLocationsController@form')->middleware('auth');
Route::post('settings/add_location', 'SettingsLocationsController@store')->middleware('auth');
Route::post('settings/update_location', 'SettingsLocationsController@update')->middleware('auth');

// Packing Lists
Route::get('settings/packing_lists', 'SettingsPackingController@index')->middleware('auth');
Route::get('settings/packing_list_add', 'SettingsPackingController@formPackingList')->middleware('auth');
Route::get('settings/packing_list_edit', 'SettingsPackingController@formPackingList')->middleware('auth');
Route::post('settings/update_packing', 'SettingsPackingController@updatePackingList')->middleware('auth');
Route::post('settings/add_packing', 'SettingsPackingController@storePackingList')->middleware('auth');

// Quantity Types
Route::get('settings/quantity_types', 'SettingsQuantityTypesController@index')->middleware('auth');
Route::get('settings/quantity_types/test', 'SettingsQuantityTypesController@index_test')->middleware('auth');
Route::post('settings/quantity_types/ordering', 'SettingsQuantityTypesController@ordering')->middleware('auth');
Route::get('settings/quantity_type_add', 'SettingsQuantityTypesController@formQtyType')->middleware('auth');
Route::get('settings/quantity_type_edit', 'SettingsQuantityTypesController@formQtyType')->middleware('auth');
Route::post('settings/data_add_quantitytype', 'SettingsQuantityTypesController@storeQtyType')->middleware('auth');
Route::post('settings/data_update_quantitytype', 'SettingsQuantityTypesController@updateQtyType')->middleware('auth');
Route::get('settings/data_ordering_quantitytypes', 'SettingsQuantityTypesController@orderQtyType')->middleware('auth');

// Seasons
Route::get('settings/seasons', 'SettingsSeasonsController@index')->middleware('auth');
Route::post('settings/seasons/update', 'SettingsSeasonsController@update')->middleware('auth');
Route::get('settings/seasons/delete', 'SettingsSeasonsController@delete')->middleware('auth');

// Terms - Soon to be disabled
Route::get('settings/terms', 'SettingsTermsController@index')->middleware('auth');
Route::post('settings/update_years', 'SettingsTermsController@updateYears')->middleware('auth');
Route::get('settings/delete_year', 'SettingsTermsController@deleteYear')->middleware('auth');

// Themes
Route::get('settings/themes', 'SettingsThemesController@index')->middleware('auth');
Route::get('settings/theme_add', 'SettingsThemesController@formTheme')->middleware('auth');
Route::get('settings/theme_edit', 'SettingsThemesController@formTheme')->middleware('auth');
Route::get('settings/theme_types', 'SettingsThemesController@themeTypes')->middleware('auth');
Route::post('settings/update_theme', 'SettingsThemesController@updateTheme')->middleware('auth');
Route::get('settings/theme_delete', 'SettingsThemesController@theme_delete')->middleware('auth');
Route::post('settings/add_theme', 'SettingsThemesController@storeTheme')->middleware('auth');
Route::get('settings/unlink_theme', 'SettingsThemesController@unlinkTheme')->middleware('auth');
Route::post('settings/update_theme_types', 'SettingsThemesController@updateThemeTypes')->middleware('auth');

// Activities
Route::get('settings/activity_add', 'SettingsThemesController@formActivity')->middleware('auth');
Route::get('settings/activity_edit', 'SettingsThemesController@formActivity')->middleware('auth');
Route::post('settings/update_activity', 'SettingsThemesController@updateActivity')->middleware('auth');
Route::post('settings/add_activity', 'SettingsThemesController@storeActivity')->middleware('auth');
Route::get('settings/delete_activity', 'SettingsThemesController@delete_activity')->middleware('auth');
