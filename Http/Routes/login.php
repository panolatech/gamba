<?php

	Route::post('login', function () {
		return redirect('home');
	});
	// Login Authentication
	Route::post('login', 'LoginController@authenticate');
	
	// First Time Login Start Session
	Route::post('first_time_start', 'LoginController@first_time_start');
	
	// First Time Login Password Change
	Route::post('ft_pass_change', 'LoginController@first_time_password_change');
	
	// Forgot Password Change
	Route::post('forgot_password_reset', 'LoginController@forgot_password_reset');
	
	
	// First Time Login Screen
	Route::get('first_time', 'LoginController@first_time');
	
	// Forgot Password Screen
	Route::get('forgot_password', 'LoginController@forgot_password');
	