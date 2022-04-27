<?php

namespace App\Http\Controllers;

use App\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use App\Models\Users;

use App\Gamba\gambaUsers;

class UserSessionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
	public function index() {
    	$user_id = Auth::user()->id;
    	if($user_id == "") {
    		//echo "No user id"; exit; die();
    		return redirect('/logout');
    		exit;
    	}
    	$user = gambaUsers::user_info($user_id);
		// If User Has Not logged in for first time
    	if($user['login'] == 0) {
    		//echo "User Has Not logged in for first time"; exit; die();
    		return redirect('/logout');
    		exit;
    	}
    	// If User Has Been Blocked
    	elseif($user['block'] == 1) {
    		//echo "User Has Been Blocked"; exit; die();
    		return redirect('/logout');
    		exit;
    	} 
    	else {
    		// Office User
    		if($user['group'] == 2) {
    			//echo "Office User"; exit; die();
    			return redirect('/session-office');
    			exit;
    		} 
    		// Curriculum Writer
    		elseif($user['group'] == 3) {
    			//echo "Curriculum Writer"; exit; die();
    			return redirect('/session-cw');
    			exit;
    		}
    		// Reorder User
    		elseif($user['group'] == 4) {
    			//echo "Reorder User"; exit; die();
    			return redirect('/session-reorder');
    			exit;
    		// Admin
    		} else {
    			//echo "Admin"; exit; die();
    			return redirect('/session-admin');
    			exit;
    		}
    	}
    }
    // JQuery Session Check
    public function jquery_session_check() {
    	$user_id = Auth::user()->id;
    	$uid = Session::get('uid');
    	$logged_in_as = Session::get('logged_in_as');
    	if($uid != $user_id && $logged_in_as != "true") {
    		echo "true";
    	}
    }
    // User Session - Admin
    public function session_start_admin() {
    	//echo "Admin"; exit; die();
    	$user_id = Auth::user()->id;
    	//     	echo $user_id; exit; die();
    	$user = gambaUsers::user_info($user_id);
    	
    	Session::put('uid', $user['id']);
    	Session::put('email', $user['email']);
    	Session::put('name', $user['name']);
    	Session::put('group', $user['group']);
    	Session::put('group_name', 'Administrator');
    	$locations = json_decode($user->locations, true);
    	Session::put('locations', $user['locations']);
    	Session::put('camp', $user['camp']);
    	Session::save();
    	
    	$users = Users::find($user_id);
    	$users->last_login = date("Y-m-d H:i:s");
    	$users->save();
    	
    	$uid = Session::get('uid');
    	if($uid != $user_id) {
    		return redirect('/gatekeeper');
    	} else {
    		return redirect('/home?login=true');
    	}
    }
    // User Session - Office User
    public function session_start_office() {
    	$user_id = Auth::user()->id;
    	//     	echo $user_id; exit; die();
    	$user = gambaUsers::user_info($user_id);
    	
    	Session::put('uid', $user['id']);
    	Session::put('email', $user['email']);
    	Session::put('name', $user['name']);
    	Session::put('group', $user['group']);
    	Session::put('group_name', 'Office User');
    	$locations = json_decode($user->locations, true);
    	Session::put('locations', $user['locations']);
    	Session::put('camp', $user['camp']);
    	Session::save();
    	
    	$users = Users::find($user_id);
    	$users->last_login = date("Y-m-d H:i:s");
    	$users->save();
    	
    	$uid = Session::get('uid');
    	if($uid != $user_id) {
    		return redirect('/gatekeeper');
    	} else {
    		return redirect('/home?login=true');
    	}
    	
    }
    // User Session - Curriculum Writer
    public function session_start_cw() {
    	$user_id = Auth::user()->id;
    	//     	echo $user_id; exit; die();
    	$user = gambaUsers::user_info($user_id);
    	
    	Session::put('uid', $user['id']);
    	Session::put('email', $user['email']);
    	Session::put('name', $user['name']);
    	Session::put('group', $user['group']);
    	Session::put('group_name', 'Curriculum Writer');
    	$locations = json_decode($user->locations, true);
    	Session::put('locations', $user['locations']);
    	Session::put('camp', $user['camp']);
    	Session::save();
    	
    	$users = Users::find($user_id);
    	$users->last_login = date("Y-m-d H:i:s");
    	$users->save();
    	
    	$uid = Session::get('uid');
    	if($uid != $user_id) {
    		return redirect('/gatekeeper');
    	} else {
    		return redirect('/supplies?action=supplyrequests');
    	}
    	
    }
    // User Session - Reorder User
    public function session_start_reorder() {
    	$user_id = Auth::user()->id;
    	//     	echo $user_id; exit; die();
    	$user = gambaUsers::user_info($user_id);
    	
    	Session::put('uid', $user['id']);
    	Session::put('email', $user['email']);
    	Session::put('name', $user['name']);
    	Session::put('group', $user['group']);
    	Session::put('group_name', 'Reorder User');
    	$locations = json_decode($user->locations, true);
    	Session::put('locations', $user['locations']);
    	Session::put('camp', $user['camp']);
    	Session::save();
    	
    	$users = Users::find($user_id);
    	$users->last_login = date("Y-m-d H:i:s");
    	$users->save();
    	
    	$uid = Session::get('uid');
    	if($uid != $user_id) {
    		return redirect('/gatekeeper');
    	} else {
    		return redirect('/resupply');
    	}
    	
    }
    
    public function reauthorize() {
    	$user_id = Auth::user()->id;
    	$user = gambaUsers::user_info($user_id);
    	  
	    Session::put('uid', $user_id);
    	Session::put('email', $user['email']);
    	Session::put('name', $user['name']);
    	Session::put('group', $user['group']);
    	$locations = json_decode($user->locations, true);
    	Session::put('locations', $user['locations']);
    	Session::put('camp', $user['camp']);
    	Session::save();
    	
    	$users = Users::find($user_id);
    	$users->last_login = date("Y-m-d H:i:s");
    	$users->save();
    	
        return back();
    }
    
    public function login_as(Request $array) {
    	$user_id = Auth::user()->id;
    	$user = gambaUsers::user_info($array['login_as_user_id']);
    
    	Session::put('uid', $user['id']);
    	Session::put('email', $user['email']);
    	Session::put('name', $user['name']);
    	Session::put('group', $user['group']);
    	$locations = json_decode($user->locations, true);
    	Session::put('locations', $user['locations']);
    	Session::put('camp', $user['camp']);
    	Session::put('restore_user_id', $user_id);
    	Session::put('logged_in_as', 'true');
    	Session::save();
    	
    	// Office User
    	if($user['group'] == 2) {
    		//echo "Office User"; exit; die();
    		Session::put('group_name', 'Office User');
    		return redirect('/supplies?action=supplyrequests');
    		exit;
    	} 
    	// Curriculum Writer
    	if($user['group'] == 3) {
    		//echo "Curriculum Writer"; exit; die();
    		Session::put('group_name', 'Curriculum Writer');
    		return redirect('/supplies?action=supplyrequests');
    		exit;
    	}
    	// Reorder User
    	if($user['group'] == 4) {
    		//echo "Reorder User"; exit; die();
    		Session::put('group_name', 'Reorder User');
    		return redirect('/resupply');
    		exit;
    	}
    }
    public function logout_as(Request $array) {
    	$restore_user_id = Session::get('restore_user_id', $user_id);
    	$user = gambaUsers::user_info($restore_user_id);
    
    	Session::put('uid', $user['id']);
    	Session::put('email', $user['email']);
    	Session::put('name', $user['name']);
    	Session::put('group', $user['group']);
    	Session::put('group_name', 'Administrator');
    	$locations = json_decode($user->locations, true);
    	Session::put('locations', $user['locations']);
    	Session::put('camp', $user['camp']);
    	Session::forget('restore_user_id');
    	Session::forget('logged_in_as');
    	Session::save();
    	
    	return redirect('/users');
    }
    public function logout() {
    	Session::forget('uid');
    	Session::forget('email');
    	Session::forget('name');
    	Session::forget('group');
    	Session::forget('locations');
    	Session::forget('camp');
    	Session::forget('restore_user_id');
    	Session::forget('logged_in_as');
    	Session::flush();
    	Session::save();
    	return redirect('/logout');
    }
}
