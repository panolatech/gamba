<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Users;

use App\Gamba\gambaUsers;
use App\Gamba\gambaLogin;

class UsersController extends Controller
{
	
    public function index(Request $array) {
    	$content = gambaUsers::user_list_view($array);
    	return view('app.users.home', ['content' => $content]);
    }
	
    public function formUser(Request $array) {
    	$content = gambaUsers::user_edit_form($array);
    	return view('app.users.home', ['content' => $content]);
    }
	
    public function insertUser(Request $array) {
    	$url = url('/');
    	$result = gambaUsers::user_add($array);
		return redirect("{$url}/users?r={$result['return']}");
    }
	
    public function updateUser(Request $array) {
    	$url = url('/');
    	$result = gambaUsers::user_update($array);
		return redirect("{$url}/users?r={$result['return']}");
    }
	
    public function deleteUser(Request $array) {
    	$url = url('/');
    	$result = gambaUsers::user_delete($array);
		return redirect("{$url}/users?r={$result['return']}");
    }
	
    public function updatePassword(Request $array) {
    	$content = gambaUsers::update_password($array);
    	return redirect("{$array['url']}");
    }
	
    public function blockUser(Request $array) {
    	$url = url('/');
    	$result = gambaUsers::user_block($array);
		return redirect("{$url}/users?r={$result['return']}");
    }
	
    // Now Handled in UserSessionController.php
    public function loginAs(Request $array) {
    	$content = gambaLogin::login_as($array);
    }
    
    public function password_modal($prev_url) {
    	//$user_id = Auth::user()->id;
    	$content['prev_url'] = base64_decode($prev_url);
    	$content['page_title'] = "Change Password";
    	return view('app.users.passwordmodal', ['array' => $content]);
    }

    public function password_modal_update(Request $array) {
    	$user_id = Auth::user()->id;
    	$logged_in_as = Session::get('logged_in_as');
    	if($logged_in_as == "true") {
    		$user_id = Session::get('uid');
    	} 
    	if($array['upass'] != "" && $user_id != "") {
    		$update = Users::find($user_id);
	    		$update->password = bcrypt($array['upass']);
	    		$update->updated_at = date("Y-m-d H:i:s");
	    		$update->save();
    	}
//     	echo $array['prev_url'];
//     		echo "{$array['upass']} | $user_id"; exit; die();
    	return redirect($array['prev_url'] . "#passwordchanged");
    }
    
    public function new_user_password_modal($uid) {
    	//$user_id = Auth::user()->id;
    	$content['uid'] = $uid;
    	$content['page_title'] = "Change Password";
    	return view('app.users.newuserpasswordmodal', ['array' => $content]);
    }

    public function new_user_password_modal_update($uid, Request $array) {
    	$url = url('/');
    	if($array['upass'] != "") {
    		$update = Users::find($uid);
	    		$update->password = bcrypt($array['upass']);
	    		$update->login = 1;
	    		$update->updated_at = date("Y-m-d H:i:s");
	    		$update->save();
    	}
    	
    	return redirect("{$url}/users?password=changed");
    }
}
