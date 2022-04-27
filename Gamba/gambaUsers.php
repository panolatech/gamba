<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	
	use App\Models\Groups;
	use App\Models\Users;
	
	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaLocations;
	use App\Gamba\gambaLogin;
	
	class gambaUsers {
		
		public function user_id() {
			$logged_in_as = Session::get('logged_in_as');
			if($logged_in_as == "true") {
				$user_id = Session::get('uid'); 
			} else {
				$user_id = Auth::user()->id;
			}
			return $user_id;
		}
		
		/**
		 * List of all users
		 * @return unknown
		 */
		public static function user_list($view = NULL) {
			$query = Users::select('id', 'email', 'name', 'permission', 'created_at', 'last_login', 'last_activity', 'block', 'login', 'camp', 'locations', 'first_login_token');
			$query = $query->where('id', '!=', '1');
			if($view == "blocked") {
				$query = $query->where('block', 1);
			}
			if($view == "notloggedin") {
				$query = $query->where('login', 0);
			}
			if($view == "admins") {
				$query = $query->where('permission', 1);
			}
			if($view == "office") {
				$query = $query->where('permission', 2);
			}
			if($view == "cw") {
				$query = $query->where('permission', 3);
			}
			if($view == "reorders") {
				$query = $query->where('permission', 4);
			}
			$query = $query->orderBy('name')->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$array['users'][$id]['email'] = $row['email'];
					$array['users'][$id]['name'] = $row['name'];
					$array['users'][$id]['group'] = $row['permission'];
					$array['users'][$id]['created_at'] = $row['created_at'];
					$array['users'][$id]['last_login'] = $row['last_login'];
					$array['users'][$id]['last_activity'] = $row['last_activity'];
					$array['users'][$id]['first_login_token'] = $row['first_login_token'];
					$array['users'][$id]['block'] = $row['block'];
					$array['users'][$id]['login'] = $login = $row['login'];
					$array['users'][$id]['login_id'] = $row['pass'];
					$array['users'][$id]['camp'] = $row['camp'];
					$array['users'][$id]['locations'] = json_decode($row->locations, true);
				}
			}
// 			dd($query);
			return $array;
		}
		
		public static function session() {
			if(Auth::check()) {
				$user_id = self::user_id();
				$user = self::user_info($user_id);
				// Make sure it is all cleared
				Session::forget('uid');
				Session::forget('email');
				Session::forget('name');
				Session::forget('group');
				Session::forget('locations');
				Session::forget('camp');
				 
				Session::put('uid', $user_id);
				Session::put('email', $user['email']);
				Session::put('name', $user['name']);
				Session::put('group', $user['group']);
				$locations = json_decode($user['locations'], true);
				Session::put('locations', $locations);
				Session::put('camp', $user['camp']);
			} else {
				//return redirect('/login');
			}
		}
		
		/**
		 * User Permission Groups
		 * @return Ambigous <string, mixed>
		 */
		public static function groups() {
			$query = Groups::get();
			//$array['sql'] = \DB::last_query();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$array['groups'][$id]['name'] = $row['name'];
					$array['groups'][$id]['group_values'] = json_decode($row->group_values, true);
				}
			}
			return $array;
		}
		
		/**
		 * Update a user
		 * @param unknown $array
		 */
		public static function user_update($array) {
			$user['user_id'] = $id = $array['id'];
			$user['name'] = $name = htmlspecialchars($array['name']);
			$user['email'] = $email = $array['email'];
			$user['group'] = $group = $array['group'];
			$user['locations'] = $locations = json_encode($array['locations']);
// 			echo "<pre>"; print_r($user); echo "</pre>"; exit; die();

			$update = Users::find($id);
				$update->name = $name;
				$update->email = $email;
				$update->permission = $group;
				$update->locations = $locations;
				$update->save();
			//$return['sql'] = \DB::last_query();
			$return['updated'] = 1;
			$return['name'] = $array['name'];
			$return = base64_encode(json_encode($return));
			return $return;
		}
		
		/** 
		 * Add a user
		 * @param unknown $array
		 */
		public static function user_add($array) {
    		$url = url('/');
			$id = $array['id'];
			$name = htmlspecialchars($array['name']);
			$email = $array['email'];
			$group = $array['group'];
			$locations = json_encode($array['locations']);
			$length = 34;
			$first_login_token = substr(ereg_replace("[^A-Z0-9]", "", crypt(time())) .
					ereg_replace("[^A-Z0-9]", "", crypt(time())) .
					ereg_replace("[^A-Z0-9]", "", crypt(time())), 0, $length);
			$date = date("Y-m-d H:i:s");
			if($name != "" && $email != "") {
				$return['add_row'] = Users::insertGetId([
					'name' => $name,
					'email' => $email,
					'permission' => $group,
					'first_login_token' => $first_login_token,
					'created_at' => $date,
					'updated_at' => $date,
					'locations' => $locations
				]);
				$row = Users::find($return['add_row']);
				//$return['sql'] = \DB::last_query();
				$msg .= <<<EOT
{$name},

You have been given access to GAMBA, Galileo’s database for camp supplies.  
To login to your account please click on this link:
{$url}/first_time/{$first_login_token}
This is a one-time link and can not be used to access the site again.

Your username is: {$email}

Once you login, you’ll be asked to create a password.  Please bookmark the 
site on your browser so that you can access it at any time.  Thanks for your 
work in making Galileo’s programs great for kids! 

-The Galileo Warehouse Team
EOT;
				$subject = "Galileo Learning GAMBA 2017 - Supply Management System Account - $created_at";
				$headers = 'From: warehouse@galileo-learning.com' . "\r\n" .
					'Reply-To: warehouse@galileo-learning.com' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
				mail($email, $subject, $msg, $headers, "-fwarehouse@galileo-learning.com");
			}
			$return['added'] = 1;
			$return['name'] = $array['name'];
			$return['email'] = $array['email'];
			return base64_encode(json_encode($return));
		}
		
		/** 
		 * Add a user
		 * @param unknown $array
		 */
		public static function user_block($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$id = $array['id'];
			$block = $array['block'];
			if($block == 1) { $block = 0; } else { $block = 1; }
			$update = Users::find($id)->update(['block' => $block]);
			//$return['sql'] = DB::last_query();
			$user = self::user_info($id);
			$return['blocked'] = 1;
			$return['name'] = $user['name'];
			return base64_encode(json_encode($return));
		}
		
		/**
		 * Delete a user
		 * @param unknown $array
		 */
		public static function user_delete($array) {
			$id = $array['id'];
			$user = self::user_info($id);
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$delete = Users::find($id)->delete();
			//$return['sql'] = \DB::last_query();
			$return['deleted'] = 1;
			$return['name'] = $user['name'];
			return base64_encode(json_encode($return));
		}
		
		/**
		 * Information on a single user
		 * @param unknown $id
		 * @return unknown
		 */
		public static function user_info($id) {
			$row = Users::find($id);
			$array['id'] = $row['id'];
			if($row['email'] == "admin") {
				$array['email'] = admin_email;
			} else {
				$array['email'] = $row['email'];
			}
			$array['name'] = $row['name'];
			$array['group'] = $row['permission'];
			$array['created'] = $row['created_at'];
			$array['last_login'] = $row['last_login'];
			$array['locations'] = $row['locations'];
			$array['last_activity'] = $row['last_activity'];
			$array['block'] = $row['block'];
			$array['login'] = $row['login'];
			$array['camp'] = $row['camp'];
			return $array;
		}
		
		public static function update_password($array) {
			$password = $array['upass'];
			$id = $array['id'];
			$update = Users::find($id);
				$update->password = bcrypt($password);
				$update->save();
		}
		
		/**
		 * Form Content for the Change Password Modal
		 * @param unknown $id
		 */
		public static function password_change_modal($id) {
			$url = url('/');
			$user_id = Session::get('id');
			$content = <<<EOT
	 <script type="text/javascript" src="js/pwstrength.js"></script>
	 <script type="text/javascript">
        jQuery(document).ready(function () {
            "use strict";
            var options = {};
            options.ui = {
                container: "#pwd-container",
                showVerdictsInsideProgressBar: true,
                viewports: {
                    progress: ".pwstrength_viewport_progress"
                }
            };
            options.common = {
                debug: true,
                onLoad: function () {
                    $('#messages').text('Start typing password');
                }
            };
            $(':password').pwstrength(options);
        });
    </script>
					
				<form method="post" action="{$url}/users/update_password" name="add_user" class="form-horizontal">
EOT;
			$content .= csrf_field();
			$content .= <<<EOT
					<div id="pwd-container">
						<p class="directions">Please choose a password that is between 6-12 characters in length that is not your username. Strong passwords are those that are a combination of numbers and letters, with some non-alpha-numeric characters, yet something that you can remember. Passwords are also case sensitive.</p>
	 					<div class="row">
	 						<div class="small-12 medium-5 large-5 columns">
	 							<label for="upass" class="">Type Password</label>
	 						</div>
	 						<div class="small-12 medium-7 large-7 columns">
	 							<input type="password" name="upass" id="upass" class="form-control" required />
	 						</div>
	 					</div>
	 					<div class="row">
	 						<div class="small-12 medium-5 large-5 columns">
	 							<label class="">Password Strength Meter</label>
	 						</div>
	 						<div class="small-12 medium-7 large-7 columns">
	 							<div class="pwstrength_viewport_progress"></div>
	 						</div>
	 					</div>
	 				</div>
					<input type="hidden" name="action" value="update_password" />
					<input type="hidden" name="url" value="{$_SERVER['REQUEST_URI']}" />
					<input type="hidden" name="id" value="{$user_id}" />
					<p><input type="submit" name="submit" id="" class="button small" value="Update" /></p>
				</form>
							
EOT;
			return $content;
		}
		
		private static function locations_select($user_locations = NULL) {
			$user_locations = json_decode($user_locations, true);
			$camps = gambaCampCategories::camps_list();
			$locations = gambaLocations::locations_with_camps();
					$content = <<<EOT
					<div class="row locations_select">
						<div class="small-12 medium-4 large-4 columns">
							<label class="">Camp Locations</label>
						</div>
						<div class="small-12 medium-8 large-8 columns">Select the locations for reorder users.</div>
EOT;
			foreach($locations['camps'] as $camp => $values) {
					$content .= <<<EOT
						<div class="small-12 medium-12 large-12 columns">
							<label>$camp. {$camps[$camp]['name']}</label>
						</div>
						<div class="small-12 medium-12 large-12 columns">
							<div class="row">
EOT;
				foreach($values['locations'] as $id => $vars) {
					if(in_array($id, $user_locations[$camp])) {
						$selected = " checked";
					} else {
						$selected = "";
					}
					$content .= <<<EOT
								<div class="large-4 medium-6 small-12 columns">
									<div class="small-1 medium-1 large-1 columns">
										<input type="checkbox" name="locations[$camp][]" value="$id"{$selected} /> 
									</div>
									<div class="small-11 medium-11 large-11 columns">	
										<label>$id {$vars['name']}</label>
									</div>
								</div>
EOT;
				
				}
				$content .= <<<EOT
							</div>
						</div>
EOT;
			}
			$content .= <<<EOT
					</div>
EOT;
			return $content;
		}
		
		/**
		 * Displays Users; Add, Edit and Delete Functionality
		 */
		public static function user_list_view($array) {
			$return = $array['r'];
			$url = url('/');
			$users = self::user_list($array['view']);
			$groups = self::groups();
// 			$locations = gambaLocations::locations_by_camp();
			$camps = gambaCampCategories::camps_list();
			$content_array['page_title'] = "Users";
			$content_array['content'] .= <<<EOT
			<div class="directions">
					<strong>Directions:</strong>
					Below is the list of all users. To change information or delete a user click on Edit. Clicking on any column header will sort either up or down.
			</div>
EOT;
			if($return['deleted'] == 1) {
				$content_array['content'] .=<<<EOT
		<div data-alert class="alert-box success radius">
			{$return['name']} successfully deleted.
			<a href="#" class="close">&times;</a>
		</div>
EOT;
			}
			if($return['updated'] == 1) {
				$content_array['content'] .= <<<EOT
		<div data-alert class="alert-box success radius">
			{$return['name']} has been successfully updated.
			<a href="#" class="close">&times;</a>
		</div>
EOT;
			}
			if($return['blocked'] == 1) {
				$content_array['content'] .= <<<EOT
		<div data-alert class="alert-box success radius">
			{$return['name']} successfully blocked.
			<a href="#" class="close">&times;</a>
		</div>
EOT;
			}
			if($return['added'] == 1) {
				$content_array['content'] .= <<<EOT
		<div data-alert class="alert-box success radius">
			A user account has been created for {$return['name']} and an email sent to {$return['email']}.
			<a href="#" class="close">&times;</a>
		</div>
EOT;
			}
// 			echo "<pre>"; print_r($return); echo "</pre>"; 
			$content_array['content'] .= <<<EOT
		<script type="text/javascript">
		// call the tablesorter plugin
			$(document).ready(function(){ 
			        $("table").tablesorter({
						sortList:[
							[1,0]
						],
						headers:{
							0:{sorter:false},
							9:{sorter:false}
						}
					}); 
			        
			 }); 
		</script>
					<script type="text/javascript">
						$(document).ready(function(){
							$("[data-toggle=popover]").popover();
						});	
					</script>
		<p><a href="{$url}/logs/logins.log" target="new" class="button small radius">View Log File of User Logins</a></p>
		<ul class="pagination">
			<li><a href="{$url}/users">All</a></li>
			<li><a href="{$url}/users?view=admins">Admins</a></li>
			<li><a href="{$url}/users?view=office">Office Users</a></li>
			<li><a href="{$url}/users?view=cw">Curriculum Writers</a></li>
			<li><a href="{$url}/users?view=reorders">Reorder Users</a></li>
			<li><a href="{$url}/users?view=blocked">Blocked</a></li>
			<li><a href="{$url}/users?view=notloggedin">Not Logged In</a></li>
		</ul>
<div id="userPasswordModal" class="reveal-modal" data-reveal aria-labelledby="userPasswordModalTitle" aria-hidden="true" role="dialog">
</div>
		<table class="table table-bordered table-hover table-condensed table-small tablesorter">
			<thead>
				<tr>
					<th><a href="{$url}/users/user_add?action=user_add" class="button small radius">Add</a></th>
					<th class="{sorter: 'text'}">Name</th>
					<th>E-mail Address</th>
					<th>Group</th>
					<th>Login First Time</th>
					<th>Blocked</th>
					<th>Created</th>
					<th>Last Login</th>
					<th>Last Activity</th>
					<th>Login As</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
EOT;
			foreach($users['users'] as $key => $values) {
				$group = $values['group'];
				$location = $values['camp'];
				$location_by_id = gambaLocations::location_by_id($location);
				$camp = $location_by_id['camp'];
				$row_alert = ""; if($values['login'] == 0 || $values['block'] == 1) { $row_alert = ' class="danger"'; }
				if($values['login'] == 1) { 
					$login_first_time = '<span style="color:green;">Yes</span>';
				} else { 
					$login_first_time = <<<EOT
				<button data-dropdown="no_login_drop" aria-controls="no_login_drop" aria-expanded="false" class="button small alert radius">No/Login</button>
				<div id="no_login_drop" data-dropdown-content class="f-dropdown content" aria-hidden="true" tabindex="-1">
  					<p>To Test the First Time Login Link, <a href="{$url}/login/first_time/{$values['first_login_token']}" target="new">Click Here</a> or Copy the following link to email to user:<br /><textarea>{$url}/login/first_time/{$values['first_login_token']}</textarea></p>
				</div>
EOT;
				}
				if($values['block'] == 1) { $user_blocked = '<span style="color:red;">Blocked</span>'; } else { $user_blocked = '<span style="color:green;">No</span>'; }
				$created_at = date("m/d/Y g:i a", strtotime($values['created_at']));
				$last_login = ""; if($values['last_login']) { $last_login = date("m/d/Y g:i a", strtotime($values['last_login'])); }
				$last_activity = ""; if($values['last_activity']) { $last_activity = date("m/d/Y g:i a", strtotime($values['last_activity'])); }
				$content_array['content'] .= <<<EOT
				<tr{$row_alert}>
					<td><a href="{$url}/users/user_edit?action=user_edit&id={$key}&view={$array['view']}" class="button small radius">Edit</a></td>
					<td title="UID: {$key} | Group: {$values['group']} | Login: {$values['login']}">{$values['name']}</td>
					<td>{$values['email']}</td>
					<td>{$groups['groups'][$group]['name']}</td>
					<td class="center">{$login_first_time}</td>
					<td class="center"><a href="{$url}/users/block_user?id={$key}&block={$values['block']}">{$user_blocked}</a></td>
					<td>{$created_at}</td>
					<td>{$last_login}</td>
					<td>{$last_activity}</td>
					<td>
EOT;
					if($values['group'] > 1 && $values['login'] == 1) { 
						$content_array['content'] .= <<<EOT
					<a href="{$url}/users/login_as?login_as_user_id={$key}" class="button small success radius">Login</a>
EOT;
					} 
					if($values['group'] > 1 && $values['login'] == 0) {
						$content_array['content'] .= <<<EOT
					<button data-reveal-ajax="{$url}/users/newuserpasswordmodal/{$key}" class="button small success radius" data-reveal-id="userPasswordModal">Activate</button>
EOT;
					}
					$content_array['content'] .= <<<EOT
					</td>
					<td><a href="{$url}/users/delete_user?id={$key}" class="button small alert radius" onclick="return confirm('Are you sure you want to delete {$values['name']}');">Delete</a></td>
				</tr>
EOT;
			}
			$content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
			return $content_array;
		}
		
		public static function user_edit_form($array) {
			$url = url('/');
			$groups = self::groups();
			$camps = gambaCampCategories::camps_list();
			if($array['action'] == "user_edit") {
				$row = Users::find($array['id']);
				$form_action = "update_user";
				$form_button = "Update";
				$content_array['page_title'] = "Edit User: {$row['name']}";
			}
			if($array['action'] == "user_add") {
				$form_action = "add_user";
				$form_button = "Add";
				$content_array['page_title'] = "Add User";
			}
			$content_array['content'] .= <<<EOT
				<div class="row">
					<div class="small-12 medium-12 large-12 columns">
						<p class="right"><a href="{$url}/users?view={$array['view']}">&#8920;== Return to Users List</a></p>
					</div>
				</div>
		
				<form method="post" action="{$url}/users/{$form_action}" name="user" class="form-horizontal">
EOT;

			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
					
						
							<div class="row">
								<div class="small-12 medium-4 large-4 columns">
									<label for="name" class="">Name</label>
								</div>
								<div class="small-12 medium-8 large-8 columns">
									<input type="text" name="name" value="{$row['name']}" id="name" class="form-control" required />
								</div>
							</div>
							<div class="row">
								<div class="small-12 medium-4 large-4 columns">
									<label for="email" class="">E-mail Address</label>
								</div>
								<div class="small-12 medium-8 large-8 columns">
									<input type="email" name="email" value="{$row['email']}" id="email" class="form-control" required />
								</div>
							</div>
							<div class="row">
								<div class="small-12 medium-4 large-4 columns">
									<label for="group" class="">Group/Permissions</label>
								</div>
								<div class="small-12 medium-8 large-8 columns">
									<select name="group" id="group">
EOT;
			foreach($groups['groups'] as $id => $group_values) {
				$group_select = ""; if($row['permission'] == $id) { $group_select = " selected"; }
				$content_array['content'] .= <<<EOT
										<option value="{$id}"{$group_select}>{$group_values['name']}</option>
EOT;
			}
			$content_array['content'] .= <<<EOT
								</select></div>
							</div>
							<button type="submit" class="button small radius">{$form_button} User</button>
EOT;
			$content_array['content'] .= self::locations_select($row['locations']);
			$content_array['content'] .= <<<EOT
							
						
							<button type="submit" class="button small radius">{$form_button} User</button>
						
					<input type="hidden" name="action" value="{$form_action}" />
					<input type="hidden" name="id" value="{$array['id']}" />
				</form>
			
EOT;
			$content_array['content'] .= gambaDebug::preformatted_arrays($row, "user_list", "Array of Users");
			return $content_array;
// 			gambaDebug::preformatted_arrays($locations, "locations", "Array of Locations");
		}
		
	}
