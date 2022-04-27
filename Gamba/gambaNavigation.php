<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	
	use App\Gamba\gambaLocations;
	use App\Gamba\gambaUsers;
	
	class gambaNavigation {
		
		public static function action_is_active($action, $set_action) {
			if($action == $set_action) {
				echo ' class="active"';
			}
		}
		
		public static function header_nav_is_active($files, $class) {
			$path = $_SERVER["REQUEST_URI"];
			$file =  explode("?", basename($path));
			$file = $file[0];
			$files = explode(",", $files);
			echo ' class="'.$class;
// 			echo "<pre>"; print_r($files); echo "</pre>";
			foreach($files as $key => $file_name) {
				if($file_name == $file) {
					echo ' active'; 
				}
			}
			echo '"';
		}
		
		public static function navigation_static() {
			$content .= <<<EOT
			
<div class="contain-to-grid contain-to-grid-top sticky">
	<nav class="top-bar" data-topbar role="navigation">		
		<ul class="title-area">
			<li class="name">
				<h1><a class="navbar-brand" href="/2016/">Gamba 2016</a></h1>
			</li>
		</ul>
			
		<section class="top-bar-section">
			<!-- Right Nav Section -->
			<ul class="right">
			
				<li class="has-dropdown"><a href="#">{$_SESSION["GL-SM_NAME"]}</a>
					<ul class="dropdown">
						<li><a href="#" data-reveal-id="change_password_modal" data-remote="modal_php?action=password_change">Password</a></li>
						<li><a href="logout_php">Logout</a></li>
EOT;
			if($_SESSION['GL-SM_LOCATIONS']) { $location = gambaLocations::location_by_id($_SESSION['GL-SM_LOCATIONS']); 
				$content .= <<<EOT
				
						<li><a href="#">{$location['name']}</a></li>
EOT;
			}
			$content .= <<<EOT
			
					</ul>
				</li>
			</ul>
					
					
			<!-- Left Nav Section -->
			<ul class="left ">
				<li><a href="home_php">Home</a></li>
EOT;
			if($_SESSION['GL-SM_UPERMS'] <= 1 || $_SESSION['GL-SM_UPERMS'] == 3) {
				$content .= <<<EOT
				
				<li class="has-dropdown"><a href="#">Admin</a>
					<ul class="dropdown">
EOT;
				if($_SESSION['GL-SM_UPERMS'] <= 1) {
					$content .= <<<EOT
					
						<li><a href="settings_php">Settings</a></li>
						<li><a href="parts_php">Parts</a></li>
						<li><a href="users_php">Users</a></li>
						<li><a href="costs_php?action=calculation_setup">Material Cost Quantity Type Setup</a></li>
EOT;
				}
				if($_SESSION["GL-SM_UID"] == 1) { 
					$content .= <<<EOT
					
						<li><a href="data_php?action=backup">Backup Data</a></li>
						<li><a href="settings-fishbowl_php?action=fbpart_search">Fishbowl Part Search</a></li>
						<li><a href="testing_php?action=test_function">Test Function</a></li>
EOT;
					/* <li><a href="settings-fishbowl_php?action=export_fb_list">FB Export List</a></li>
						<li><a href="settings-fishbowl_php?action=export_inventory_quantities">FB Export Inventory Quantities</a></li>
						<li><a href="settings-fishbowl_php?action=export_custom_field_lists">FB Export Custom Field Lists</a></li>
						<li><a href="settings-fishbowl_php?action=export_parts">FB Export Parts</a></li>
						<li><a href="settings-fishbowl_php?action=getsos">Get Sales Orders</a></li>
						<li><a href="settings-fishbowl_php?action=partquery">FB Part Query</a></li>
						<li><a href="settings-fishbowl_php?action=productquery">FB Product Query</a></li>
						<li><a href="settings-fishbowl_php?action=inventory_quantity">FB Inventory Quantity</a></li>
						<li><a href="settings-fishbowl_php?action=sales_by_count">FB Sales By Count</a></li>
						<li><a href="testing_php?action=view_test_get_sos">Get Sales Orders</a></li> */ 
				}
				if($_SESSION['GL-SM_UPERMS'] == 3) {
				$content .= <<<EOT
				
						<li><a href="parts_php?view=approved">Parts</a></li>
						<li><a href="settings-themes_php">Themes and Activities</a></li>
EOT;
				}
				$content .= <<<EOT
				
					</ul>
				</li>
EOT;
			}
			if($_SESSION['GL-SM_UPERMS'] <= 2) {
				$content .= <<<EOT
				
				<li class="has-dropdown"><a href="#">People</a>
					<ul class="dropdown">
						<li><a href="enroll_php">Campers</a></li>
						<li><a href="staff_php">Staff</a></li>
					</ul>
				</li>
EOT;
			}
			$content .= <<<EOT
			
				<li class="has-dropdown"><a href="#">Materials</a>
					<ul class="dropdown">
EOT;
			if($_SESSION['GL-SM_UPERMS'] <= 3) {
				$content .= <<<EOT
				
						<li><a href="supplies_php?action=supplyrequests">CW Material Lists</a></li>
EOT;
			}
			if($_SESSION['GL-SM_UPERMS'] <= 1) {
				$content .= <<<EOT
				
						<li><a href="costs_php?action=summaries">Material Cost Summaries</a></li>
EOT;
			}
			$content .= <<<EOT
			
						<li><a href="supplies_php?action=parts">Master Inventory List</a></li>
					</ul>
				</li>
EOT;
			if($_SESSION['GL-SM_UPERMS'] <= 2) {
				$content .= <<<EOT
				
				<li><a href="packing_php">Packing</a></li>
EOT;
			}
			if($_SESSION['GL-SM_UPERMS'] <= 1) {
				$content .= <<<EOT
				
				<li class="has-dropdown"><a href="#">Purchase Orders</a>
					<ul class="dropdown">
						<li><a href="purchase_php?action=mastersupply">Master Supply List</a></li>
						<li><a href="purchase_php?action=pos">Purchase Orders</a></li>
EOT;
				if($_SESSION["GL-SM_UID"] == 1 || $_SESSION["GL-SM_UID"] == 245) {
					$content .= <<<EOT
					
						<li><a href="purchase_php?action=orphans">Orphaned Packing Totals</a></li>
EOT;
				}
				$content .= <<<EOT
				
					</ul>
				</li>
				<li class="has-dropdown"><a href="#">Sales Orders</a>
					<ul class="dropdown">
						<li><a href="sales_php">Sales Orders</a></li>
						<li><a href="sales_php?action=packinglists">Packing Lists</a></li>
					</ul>
				</li>
EOT;
			}
			if($_SESSION['GL-SM_UPERMS'] <= 1 || $_SESSION['GL-SM_UPERMS'] == 4) {
				$content .= <<<EOT
				
				<li><a href="resupply_php">Resupply</a></li>
EOT;
			}
			$content .= <<<EOT
			
			</ul>
		</section>
	</nav>
</div>
EOT;
			return $content;
		}
		
		public static function bootstrap_navigation_static() {
			if(isset($_SESSION['GL-SM_UPERMS'])) {
				$site_name = site_name;
				$content = <<<EOT
		<div class="navbar navbar-fixed-top navbar-inverse">
			<a class="navbar-brand" href="#">{$site_name}</a>
			<ul class="nav navbar-nav ">
				<li><a href="home_php">Home</a></li>
EOT;
				if($_SESSION['GL-SM_UPERMS'] <= 1 || $_SESSION['GL-SM_UPERMS'] == 3) {
					$content .= <<<EOT
				<li><a class="dropdown-toggle" data-toggle="dropdown" href="#">Admin <span class="caret"></span></a>
					<ul class="dropdown-menu">
EOT;
					if($_SESSION['GL-SM_UPERMS'] <= 1) {
						$content .= <<<EOT
						<li><a href="settings_php">Settings</a></li>
						<li><a href="parts_php">Parts</a></li>
						<li><a href="users_php">Users</a></li>
						<li><a href="costs_php?action=calculation_setup">Material Cost Quantity Type Setup</a></li>
EOT;
					} 
					if($_SESSION["GL-SM_UID"] == 1) { 
						$content .= <<<EOT
						<li><a href="data_php?action=backup">Backup Data</a></li>
						<li><a href="settings-fishbowl_php?action=fbpart_search">Fishbowl Part Search</a></li>
						<li><a href="testing_php?action=test_function">Test Function</a></li>
EOT;
									
					}
					if($_SESSION['GL-SM_UPERMS'] == 3) {
						$content .= <<<EOT
						<li><a href="settings-themes_php">Themes and Activities</a></li>
						<li><a href="parts_php?view=approved">Parts</a></li>
EOT;
					}
					$content .= <<<EOT
					</ul>
				</li>
EOT;
				}
				if($_SESSION['GL-SM_UPERMS'] <= 2) {
					$content .= <<<EOT
				<li>
					<a class="dropdown-toggle" data-toggle="dropdown" href="#">People <span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li><a href="enroll_php">Campers</a></li>
						<li><a href="staff_php">Staff</a></li>
					</ul>
				</li>
EOT;
				}
				$content .= <<<EOT
				<li>
					<a class="dropdown-toggle" data-toggle="dropdown" href="#">Materials <span class="caret"></span></a>
					<ul class="dropdown-menu">
EOT;
				if($_SESSION['GL-SM_UPERMS'] <= 3) {
					$content .= <<<EOT
						<li><a href="supplies_php?action=supplyrequests">CW Material Lists</a></li>
EOT;
				}
				if($_SESSION['GL-SM_UPERMS'] <= 1 && site == 2) {
					$content .= <<<EOT
						<li><a href="costs_php?action=summaries">Material Cost Summaries</a></li>
EOT;
				}
				$content .= <<<EOT
						<li><a href="supplies_php?action=parts">Master Inventory List</a></li>
					</ul>
				</li>
EOT;
				if($_SESSION['GL-SM_UPERMS'] <= 2) {
					$content .= <<<EOT
				<li><a href="packing_php">Packing</a></li>
EOT;
				}
				if($_SESSION['GL-SM_UPERMS'] <= 1) {
					$content .= <<<EOT
				<li><a class="dropdown-toggle" data-toggle="dropdown" href="#">Purchase Orders <span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li><a href="purchase_php?action=mastersupply">Master Supply List</a></li>
						<li><a href="purchase_php?action=pos">Purchase Orders</a></li>
EOT;
				if($_SESSION["GL-SM_UID"] == 1 || $_SESSION["GL-SM_UID"] == 245) {
					$content .= <<<EOT
						<li><a href="purchase_php?action=orphans">Orphaned Packing Totals</a></li>
EOT;
				}
				$content .= <<<EOT
					</ul>
				</li>
				<li><a class="dropdown-toggle" data-toggle="dropdown" href="#">Sales Orders <span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li><a href="sales_php">Sales Orders</a></li>
						<li><a href="sales_php?action=packinglists">Packing Lists</a></li>
					</ul>
				</li>
EOT;
				}
				if($_SESSION['GL-SM_UPERMS'] <= 1 || $_SESSION['GL-SM_UPERMS'] == 4) {
					$content .= <<<EOT
				<li><a href="resupply_php">Resupply</a></li>
EOT;
				}
				$content .= <<<EOT
			</ul>
			<ul class="nav navbar-nav navbar-right">
				<li><a class="dropdown-toggle" data-toggle="dropdown">{$_SESSION["GL-SM_NAME"]} <span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li><a href="#" data-toggle="modal" data-target="#change_password_modal" data-remote="modal_php?action=password_change">Password</a></li>
						<li><a href="logout_php">Logout</a></li>
EOT;
				if($_SESSION['GL-SM_LOCATIONS']) { 
					$location = gambaLocations::location_by_id($_SESSION['GL-SM_LOCATIONS']); 
				}
				$content .= <<<EOT
						<li><a href="#">{$location['name']}</a></li>
					</ul>
				</li>
			</ul>
		</div>
		<!-- Change Password Modal -->
		<div id="change_password_modal" class="reveal-modal" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
			 			<h2 class="modalTitle">Change Password</h2>
					 <a class="close-reveal-modal" aria-label="Close">&#215;</a>
</div>
EOT;
			}
			return $content;
		}
		
		public static function pagination($url, $array, $page = 1, $items_per_page = 25) {
			if($page == "") { $page = 1; }
			$num_items = count($array); $pages = ceil($num_items / $items_per_page) - 1;
			if($page == 1) {
				$prev_disabled = ' class="disabled"'; 
			} else {
				$prev_page = $page - 1;
			}
			if($num_items > $items_per_page) {
				$content = <<<EOT
					<nav>
					  <ul class="pagination center">
					    <li{$prev_disabled}>
					      <a href="{$url}&page={$prev_page}" aria-label="Previous">
					        <span aria-hidden="true">&laquo;</span>
					      </a>
					    </li>
EOT;
				for($i = 1; $i <= $pages; $i++) {
					$page_class = ""; if($page == $i) { $page_class = ' class="active"'; }
					$content .= <<<EOT
						<li{$page_class}><a href="{$url}&page={$i}">{$i}</a></li>
EOT;
			}
				if($page == $pages) { 
					$next_disabled = ' class="disabled"'; 
				} else {
					$next_page = $page + 1;
				}
				$content .= <<<EOT
					    <li{$next_disabled}>
					      <a href="{$url}&page={$next_page}" aria-label="Next">
					        <span aria-hidden="true">&raquo;</span>
					      </a>
					    </li>
					  </ul>
					</nav>
EOT;
			}
			return $content;
		}
		
		public static function array_slice_pagination($array, $page, $items_per_page = 25) {
			if($page == "" || $page == 1) { 
				$offset = 0; 
			} else { 
				$offset = ($page * $items_per_page) - 1;
			}
			$array = array_slice($array, $offset, $items_per_page);
			return $array;
		}

		public static function settings_nav() {
			$content .= <<<EOT
			<ul class="side-nav">
				<li><a href="settings_php">Dashboard</a></li>
				<li><a href="parts_php">Parts</a></li>
				<li><a href="settings-fishbowl_php?action=customers">Fishbowl Customers</a></li>
				<li><a href="settings-fishbowl_php?action=vendors">Fishbowl Vendors</a></li>
				<li><a href="settings-camps_php?action=camps">Camp Material Categories</a></li>
				<li><a href="settings-camps_php?action=camp_category_input">Material Request Data Input</a></li>
				<li><a href="settings-locations_php?action=locations">Locations</a></li>
				<li><a href="settings-quantitytypes_php?action=quantity_types">Quantity Types</a></li>
				<li><a href="settings-packing_php?action=packing_lists">Packing Lists</a></li>
				<li><a href="settings-staff_php?action=staff">Staff</a></li>
				<li><a href="settings-themes_php?action=themes">Themes and Activities</a></li>
				<li><a href="settings-themes_php?action=theme_types">Themes Types</a></li>
				<li><a href="settings-terms_php?action=terms" title="Formerly Terms/Years">Seasons</a></li>
				<li><a href="settings-grades_php?action=grades">Grades</a></li>
				<li><a href="settings_php?action=config">Configuration</a></li>
				<li><a href="settings-calculate_php?action=packing_calc">Packing Calculation</a></li>
				<li><a href="settings-calculate_php?action=basic_calc">Basic Supplies Packing Calculation</a></li>
				<li><a href="settings-fishbowl_php?action=fishbowl">Fishbowl Sync</a></li>
				<li><a href="settings_php?action=log_files">Log Files</a></li>
				<li><a href="settings-fishbowl_php?action=csvimport">Inventory Numbers - Tab Delimited Import</a></li>
			</ul>
EOT;
			return $content;
		}
	}
