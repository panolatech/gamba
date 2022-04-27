<?php
	

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


// Login
// 		Route::get('/', function () {
// 			if(Auth::check()) {
// 				$uid = Session::get('uid');
// 				$uid = Session::get('uid');
// 				if($uid == "") {
// 					//return redirect('/session');
// 					return redirect('/session-auth');
// 				} else {
// 					return redirect('/login');
// 				}
// 			} else {
// 				return redirect('/login');
// 			}
// 		});
	Route::get('/', function () {
		return redirect('/login');
	});
	Route::group(['middleware' => 'web'], function () {
		
		Route::auth();
		// Gate Keeper
		Route::get('/gatekeeper', 'UserSessionController@index')->middleware('auth');
		
		// User Session - Admin
		Route::get('/session-admin', 'UserSessionController@session_start_admin')->middleware('auth');
		// User Session - Office User
		Route::get('/session-office', 'UserSessionController@session_start_office')->middleware('auth');
		// User Session - Curriculum Writer
		Route::get('/session-cw', 'UserSessionController@session_start_cw')->middleware('auth');
		// User Session - Reorder User
		Route::get('/session-reorder', 'UserSessionController@session_start_reorder')->middleware('auth');
		// JQuery Session Check
		Route::get('/check-session', 'UserSessionController@jquery_session_check')->middleware('auth');
		// User Session Reauthenticate
		Route::get('/session-auth', 'UserSessionController@reauthorize')->middleware('auth');
		
		// User Logout
		Route::get('/user-logout', 'UserSessionController@logout')->middleware('auth');

		Route::get('/login/first_time/{first_time_token}', 'LoginController@first_time');
		
		Route::get('/first_time/{first_time_token}', 'LoginController@first_time');
		
		Route::post('/ft_pass_change/{first_time_token}', 'LoginController@first_time_password_change');
		
		// Home Page
		Route::get('/home', 'HomeController@index')->middleware('auth');
		// Home Page
		//Route::get('/', 'UserSessionController@index')->middleware('auth');
		// Enrollment
		include('Routes/enrollment.php');
		// Settings
		include('Routes/settings.php');
		// Costs
		include('Routes/costs.php');
		// Supplies
		include('Routes/supplies.php');
		// Resupply
		include('Routes/resupply.php');
		// Executables
		include('Routes/execute.php');
		// Parts
		include('Routes/parts.php');
		// Packing
		include('Routes/packing.php');
		// Purchase
		include('Routes/purchase.php');
		// Sales
		include('Routes/sales.php');
		// Sales
		include('Routes/users.php');
		// Queue Jobs
		include('Routes/jobs.php');
		// Log Files
		include('Routes/logs.php');
		
		// Data
		Route::get('data-backup', function() {
			return view('app.data.backup');
		});
		
	});
		
	// Registration Redirect
	Route::get('/register', function () {
		return redirect('/login');
	});
	
	