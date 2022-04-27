<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

use App\Gamba\gambaLogin;
use App\Gamba\gambaLogs;

use App\Models\FirstTime;

class LoginController extends Controller
{


	// First Time Login Screen
	public function first_time($first_time_token) {
		Session::forget('uid');
		Session::forget('email');
		Session::forget('name');
		Session::forget('group');
		Session::forget('locations');
		Session::forget('camp');
		Session::flush();
		Session::save();
		//echo $array['id']; exit; die();
//     	echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
		$query = FirstTime::select('id');
		$query = $query->where('first_login_token', $first_time_token);
		$sql = $query->toSql();
		$row = $query->first();
		$content['user_id'] = $row['id'];
		$content['csrf_field'] = csrf_field();
		$content['first_time_token'] = $first_time_token;
// 	 	echo $sql; exit; die();
// 	 	echo "<pre>";print_r($row); echo "</pre>"; exit; die();
	
		return view('auth.firsttime', ['array' => $content]);
	}
    
    // First Time Login Password Changec
    public function first_time_password_change($first_time_token, Request $array) {
    	Session::forget('uid');
    	Session::forget('email');
    	Session::forget('name');
    	Session::forget('group');
    	Session::forget('locations');
    	Session::forget('camp');
    	Session::flush();
    	Session::save();
    	//$result = gambaLogin::ft_pass_change($first_time_token, $array['upass']);
// 	 	echo $first_time_token; exit; die();
		$row = FirstTime::select('id')->where('first_login_token', $first_time_token)->first();
		$id = $row['id'];
// 	 	echo $first_time_token; echo $id; exit; die();
	 	if($first_time_token != "" && $array['upass'] != "" && $id != "") {
// 	 		echo $first_time_token;
// 	 		echo $password; exit; die();
			$password = bcrypt($array['upass']);
			$updated_at = date("Y-m-d H:i:s");
			$update = FirstTime::where('id', $id)->update([
				'password' => $password,
				'updated_at' => $updated_at,
				'first_login_token' => '',
		 		'login' => '1'
				]);
// 			echo $first_time_token; exit; die();

			return redirect("/login?passchange=success");
	 	} else {
    		return redirect("/login?passchange=fail");
	 	}
    }
    
    
    
    // Forgot Password Change - Handled by Laravel
    public function forgot_password_reset(Request $array) {
    	
    }
    
    // Forgot Password Screen - Handled by Laravel
    public function forgot_password(Request $array) {
    	
    }
	// Login Authentication
	// Now handled in UserSessionController
    public function authenticate(Request $array) {
    	$row = Users::select('uid', 'email', 'name', 'permission', 'block', 'locations');
		$row = $row->where('email', '=', "{$array['frmuser']}");
		$row = $row->whereRaw("pass = SHA1('{$array['frmpass']}')");
		$row = $row->first();
 		if($row['email'] != "") {
			
			session_set_cookie_params(18000);
			session_start();
			$block = $row['block'];
			// If User Blocked
			if($row['block'] == 1) {
				gambaLogs::data_log("Blocked User | User Name: $user", "logins.log");
				return redirect("/?login=2");
			}
			$_SESSION["GL-SM_SESS_ID"] = md5(uniqid(mt_rand(), true));
			$_SESSION["GL-SM_UID"] = $uid = $row['uid'];
			$_SESSION["GL-SM_NAME"] = $name =  $row['name'];
			$email = $row['email'];
			if($email == "admin") {
				$_SESSION["GL-SM_EMAIL"] = admin_email;
			} else {
				$_SESSION["GL-SM_EMAIL"] = $email;
			}
			$_SESSION["GL-SM_UPERMS"] = $permission = $row['permission'];
			$_SESSION["GL-SM_LOCATIONS"] = $locations = json_decode($row->locations, true);
			$_SESSION["GL-SM_CAMPS"] = $locations_with_camps = self::assign_camps($locations);
			self::last_login($row['uid']);
			self::cookies($array['persistent'], $row['uid']);
			$browser = get_browser(null, true); $platform = $browser['platform']; $version = $browser['parent'];
			if($array['r'] != "") {
				gambaLogs::data_log("Login Session Restore | User Name: $user ($uid) | Name: $name | Email: $email | Permissions: $permission | Browser: $version | Platform: $platform", "logins.log");
				return redirect(base64_decode($array['r']));
			} else {
				
				gambaLogs::data_log("Login | User Name: $user ($uid) | Name: $name | Email: $email | Permissions: $permission | Browser: $version | Platform: $platform", "logins.log");
				return redirect("/home?loggedin=1");
			}
		} else {
			gambaLogs::data_log("Failed Login | User Name: $user", "logins.log");
			return redirect("/?login=1");
		}
    }
    
    // First Time Login Start Session
	// Now handled in UserSessionController
    public function first_time_start(Request $array) {
    	
    }
}
