<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	
	use App\Models\Users;
	use App\Models\FirstTime;
	
	use App\Gamba\gambaLogs;
	
	class gambaLogin {

		// Handled by Laravel
	 	public static function authenticate($array) { 
			$row = Users::select('uid', 'email', 'name', 'permission', 'block', 'locations');
			$row = $row->where('email', '=', "{$array['frmuser']}");
			$row = $row->whereRaw("pass = SHA1('{$array['frmpass']}')");
			$row = $row->first();
	 		if($row['email'] != "") {
				
				session_set_cookie_params(18000);
				if(cookie_login == "false") { session_name("glsms"); } else { $array['persistent'] = "true"; }
				session_start();
				$block = $row['block'];
				if($block == 1) {
					gambaLogs::data_log("Blocked User | User Name: $user", "logins.log");
					header("Location: {$url}/?login=2");
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
				$_SESSION["GL-SM_LOCATIONS"] = $locations = json_decode($row['locations'], true);
				$_SESSION["GL-SM_CAMPS"] = $locations_with_camps = self::assign_camps($locations);
				self::last_login($row['uid']);
				self::cookies($array['persistent'], $row['uid']);
				$browser = get_browser(null, true); $platform = $browser['platform']; $version = $browser['parent'];
				if($array['r'] != "") {
					gambaLogs::data_log("Login Session Restore | User Name: $user ($uid) | Name: $name | Email: $email | Permissions: $permission | Browser: $version | Platform: $platform", "logins.log");
					header("Location: " . base64_decode($array['r']));
				} else {
					
					gambaLogs::data_log("Login | User Name: $user ($uid) | Name: $name | Email: $email | Permissions: $permission | Browser: $version | Platform: $platform", "logins.log");
					header("Location: {$url}/home?loggedin=1");
				}
			} else {
				gambaLogs::data_log("Failed Login | User Name: $user", "logins.log");
				header("Location: {$url}/?login=1");
			}
	 	}

	 	// Handled by Laravel
	 	public static function cookies($persistent, $uid) {
	 		$ip = getenv('HTTP_CLIENT_IP')?:
	 		getenv('HTTP_X_FORWARDED_FOR')?:
	 		getenv('HTTP_X_FORWARDED')?:
	 		getenv('HTTP_FORWARDED_FOR')?:
	 		getenv('HTTP_FORWARDED')?:
	 		getenv('REMOTE_ADDR');
			if($persistent == "true") {
		 		$array['salt'] = $salt = openssl_random_pseudo_bytes(32, $crypto_strong);
		 		$array['token'] = $token = base64_encode($salt);
				$array['pre_encrypt'] = $token ." ". $ip . " ". nToken;
				$token = hash('sha256', $token . $ip . nToken);
			
				setcookie("remember_me","true",time() + 7776000, "/", domain);
				setcookie("session_id",$_SESSION["GL-SM_SESS_ID"],time() + 7776000, "/", domain);
				setcookie("token",$token,time() + 7776000, "/", domain);
				
				$user = Users::find($uid);
				$user->token = $token;
				$user->ip_address = $ip;
				$user->save();
			} 
	 		return $array;
	 	}
	 	
	 	// Handled by Laravel
	 	public static function cookie_set() {
	 		if(isset($_COOKIE['remember_me'])) {
	 			return "true";
	 		}
	 	}
	 	
	 	// Handled by Laravel
	 	private static function set_cookie_session() {
	 		$ip = getenv('HTTP_CLIENT_IP')?:
	 		getenv('HTTP_X_FORWARDED_FOR')?:
	 		getenv('HTTP_X_FORWARDED')?:
	 		getenv('HTTP_FORWARDED_FOR')?:
	 		getenv('HTTP_FORWARDED')?:
	 		getenv('REMOTE_ADDR');
 			$session_id = $_COOKIE['session_id'];
 			$token = $_COOKIE['token'];
 			$user = Users::select('uid', 'email', 'name', 'permission', 'block', 'locations');
 			$user = $user->where('session_id', '=', $session_id);
 			$user = $user->where('token', '=', $token);
 			$user = $user->where('ip_address', '=', $ip);
 			$row = $user->first();
 			if($user->count() > 0) {
				session_start();
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
				$_SESSION["GL-SM_LOCATIONS"] = $locations = json_decode($row['locations'], true);
				$_SESSION["GL-SM_CAMPS"] = $locations_with_camps = self::assign_camps($locations);
 			}
	 		
	 	}
	 	
	 	// Handled by Larvel
	 	public static function session_security() {
	 		/* Session Security */
	 		if(cookie_login == "true") {
	 			self::set_cookie_session();
	 		} else {
				session_set_cookie_params(18000);
				if(cookie_login == "false") { session_name("glsms"); }
		 		session_start();
	 		}
	 		if(!isset($_SESSION['GL-SM_SESS_ID'])) {
	 			header("Location: {$url}/?expired=1&r=" . base64_encode($_SERVER['REQUEST_URI']));
	 			exit;
	 		}
	 		$update = Users::find($_SESSION['GL-SM_UID']);
	 		$update->last_activity = date("Y-m-d H:i:s");
	 		$update->save();
	 	}
	 	
	 	// Handled by Laravel
	 	public static function session_index() {
	 		if(cookie_login == "true") {
	 			self::set_cookie_session();
	 		} else {
	 			if(cookie_login == "false") { session_name("glsms"); }
		 		session_start();
	 		}
	 		if(isset($_SESSION['GL-SM_SESS_ID'])) {
				header("Location: {$url}/home");
	 		}
	 	}
	 	
	 	private static function last_login($uid) {
			$todays_date = date("Y-m-d H:i:s");
			$user = Users::find($uid);
			$user->last_login = $todays_date;
			$user->login = '1';
			$user->session_id = $_SESSION['GL-SM_SESS_ID'];
			$user->save();
	 	}

	 	// Moved to UserSessionController
	 	public static function login_as($array) {
// 			session_name("glsms"); 
// 			session_start(); 
// 	 		if($_SESSION["GL-SM_UID"] != "") {
		 		$id = $array['id'];
		 		$pass = $array['pass'];
		 		$query = Users::select('uid', 'email', 'name', 'permission', 'locations', 'block')->where('uid', $uid)->where('pass', $pass)->first();
		 		if($query->count() == 1) {
		 			$row = $query;
		 			// Destroy session so that you can create new session
// 					session_name("glsms"); 
					session_start(); 
					$_SESSION = array();
					session_destroy();
					// Start new user session
		 			session_set_cookie_params(18000);
// 		 			session_name("glsms");
		 			session_start();
		 			
		 			$_SESSION["GL-SM_SESS_ID"] = md5(uniqid(mt_rand(), true));
		 			$_SESSION["GL-SM_UID"] = $uid = $row['uid'];
		 			$_SESSION["GL-SM_NAME"] = $name =  $row['name'];
		 			$_SESSION["GL-SM_EMAIL"] = $email = $row['email'];
		 			$_SESSION["GL-SM_UPERMS"] = $permission = $row['permission'];
		 			$_SESSION["GL-SM_LOCATIONS"] = $locations = json_decode($row['locations'], true);
		 			$_SESSION["GL-SM_CAMPS"] = $locations_with_camps = self::assign_camps($locations);
		 			
		 			header("Location: {$url}/home?loggedin=1");
		 		} else {
		 			header("Location: {$url}/users");
		 		}
// 	 		}
	 	}
	 	private static function assign_camps($locations) {
	 		foreach($locations as $key => $values) {
	 			$array[] = $key;
	 		}
	 		return $array;
	 	}
	 	
	 	public static function ft_pass_change($first_time_token, $password) {
	 	}
	 	
	 	private static function pass_encrypt($password) {
	 		return $password;
	 	}

	 	// Handled by Laravel
	 	public static function first_time_session($array) { 
	 		$id = $array['id'];
	 		$row = Users::select('uid', 'email', 'name', 'permission', 'camp')->where('pass', $id)->first();
	 		if($row->count() == 1) {
	 			session_name("glsms_temp");
	 			session_start();
	 			$_SESSION["GLTEMP-SM_SESS_ID"] = md5(uniqid(mt_rand(), true));
	 			$_SESSION["GLTEMP-SM_UID"] = $row['uid'];
	 			$_SESSION["GLTEMP-SM_NAME"] = $row['name'];
	 			$_SESSION["GLTEMP-SM_EMAIL"] = $row['email'];
	 			$_SESSION["GLTEMP-SM_UPERMS"] = $row['permission'];
	 			$_SESSION["GLTEMP-SM_CAMP"] = $row['camp'];
	 		}
	 	}

	 	// First Time Login
	 	public static function first_time($first_time_token) {
	 		
	 		// moved to auth/firsttime.blade.php
	 	}
	 	
	 	// Handled by Laravel
	 	public static function forgot_password_reset($array) {
	 		$email = $_REQUEST['email'];
	 		$row = Users::select('uid')->where('email', $email)->first();
	 		if($row->count() > 0) {
	 			list($user_name, $domain) = explode("@", $email);
	 			$length = 8;
	 			$upass = substr(ereg_replace("[^A-Z0-9]", "", crypt(time())) .
	 					ereg_replace("[^A-Z0-9]", "", crypt(time())) .
	 					ereg_replace("[^A-Z0-9]", "", crypt(time())), 0, $length);
	 			$update = Users::where('$email', $email)->update([
	 				'pass' => $upass,
	 				'login' => 0
	 			]);
	 			$row = Users::select('uid', 'name')->where('email', $email)->first();
		 			$uid = $row['uid'];
		 			$name = $row['name'];
	 			$msg .= "
	 			$name
	 			Your password has been reset to allow access to the Galileo Learning
	 			GAMBA - Supply Management System.
	 			$app_url
	 			To complete the login process click on the link below or copy and paste
	 			it into your web browser.
	 			{$url}/login/first_time?action=first_time&id=$upass";
	 			$subject = "Galileo Learning GAMBA - Supply Management System Account";
	 			$headers = 'From: warehouse@galileo-learning.com' . "\r\n" .
	 					'Reply-To: warehouse@galileo-learning.com' . "\r\n" .
	 					'X-Mailer: PHP/' . phpversion();
	 			mail($email, $subject, $msg, $headers);
	 			header("Location: {$url}/");
	 		} else {
	 			header("Location: {$url}/login/forgot_pass?action=forgot_pass&err=1");
	 		}
	 	}

	 	// Handled by Laravel
	 	public static function forgot_password($array) {
	 		$content_array['page_title'] = "Gamba Forgot Password";
	 		$content_array['content'] = <<<EOT
	 			<form method="post" action="{$url}/login/forgot_password_reset" name="forgot" class="form-horizontal form-signin form-signin-lg">
EOT;
			$content_array['content'] .= csrf_field();
	 		if($array['err'] == 1) { $content_array['content'] .= '<div class="alert-box alert radius">The e-mail you address you provided was either incorrectly entered or does not exist. Please try again.</div>'; }
	 		$content_array['content'] .= <<<EOT
					<p class="directions">Type in your e-mail in the text field below, hit the submit button and follow the instructions in the e-mail you will receive shortly.</p>
 					<div class="row">
	 					<div class="small-12 medium-5 large-5 columns">
 							<label for="email" class="">Your E-mail Address</label>
	 					</div>
 						<div class="small-12 medium-7 large-7 columns">
	 						<input type="text" name="email" id="email" class="form-control" required />
	 					</div>
 					</div>
 					<div class="row">
 						<div class="small-12 medium-5 large-5 columns"></div>
 						<div class="small-12 medium-7 large-7 columns">
	 						<input type="submit" name="submit" value="Reset Password" class="button small radius success" />
	 					</div>
 					</div>
	 				<input type="hidden" name="action" value="forgot_password_reset" />
				</form>
EOT;
	 		return $content_array;
	 	}
	 }
