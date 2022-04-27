<?php

	Route::get('users', 'UsersController@index')->middleware('auth');

	Route::get('users/user_edit', 'UsersController@formUser')->middleware('auth');

	Route::get('users/user_add', 'UsersController@formUser')->middleware('auth');

	Route::post('users/add_user', 'UsersController@insertUser')->middleware('auth');

	Route::post('users/update_user', 'UsersController@updateUser')->middleware('auth');

	Route::get('users/delete_user', 'UsersController@deleteUser')->middleware('auth');

	Route::get('users/update_password', 'UsersController@updatePassword')->middleware('auth');

	Route::get('users/block_user', 'UsersController@blockUser')->middleware('auth');
	

	Route::get('users/login_as', 'UserSessionController@login_as')->middleware('auth');

	Route::get('users/logout_as', 'UserSessionController@logout_as')->middleware('auth');
	

	Route::get('users/passwordmodal/{prev_url}', 'UsersController@password_modal')->middleware('auth');
	
	Route::post('users/password_update', 'UsersController@password_modal_update')->middleware('auth');
	

	Route::get('users/newuserpasswordmodal/{uid}', 'UsersController@new_user_password_modal')->middleware('auth');
	
	Route::post('users/new_user_password_update/{uid}', 'UsersController@new_user_password_modal_update')->middleware('auth');
	