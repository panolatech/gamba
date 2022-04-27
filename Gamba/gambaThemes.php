<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	
	use App\Models\Activities;
	use App\Models\Config;
	use App\Models\ThemeLink;
	use App\Models\Themes;
	
	use App\Gamba\gambaActivities;
	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaGrades;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaUsers;
	
	/**
	 * Class containing functions for Displaying, Adding and Updating the Themes, Activities
	 * @author bolles
	 *
	 */
	class gambaThemes {
		
		public static function camp_themes_array() {
			// Note: set active to false to disable
			$camp_array = array(
					"cg" => array(
							"nav" => "Camp G",
							"name" => "Camp Galileo Themes",
							"active" => "true",
							"camp_type" => 1,
							"theme_type" => array("A" => "Art", "S" => "Science"),
							"linked_to_col" => "true",
							"theme_type_col" => "true",
							"grade_select" => "true",
							"cg_staff" => 0,
							"edit" => "true",
							"add" => "true"
					),
					"gsq_major" => array(
							"nav" => "GSQ",
							"name" => "Galileo Summer Quest",
							"active" => "true",
							"grade_select" => "true",
							"camp_type" => 2,
							"cg_staff" => 0,
							"edit" => "true",
							"add" => "true"
					),
					"cgb" => array(
							"nav" => "Camp G Basic",
							"name" => "Camp Galileo Basic Supplies",
							"active" => "true",
							"camp_type" => 17,
							"theme_type" => array("A" => "Art", "S" => "Science"),
							"linked_to_col" => "true",
							"theme_type_col" => "true",
							"grade_select" => "true",
							"theme_camp" => 1,
							"cg_staff" => 0,
							"edit" => "false",
							"add" => "false"
					),
					"gsq_minor" => array(
							"nav" => "GSQ Minors",
							"name" => "Galileo Summer Quest Minors",
							"active" => "false",
							"camp_type" => 2,
							"minor" => 1,
							"cg_staff" => 0,
							"edit" => "true",
							"add" => "true"
					),
					"sat_staff" => array(
							"nav" => "All Staff",
							"name" => "All Staff Position Training",
							"active" => "true",
							"camp_type" => 12,
							"cg_staff" => 0,
							"edit" => "true",
							"add" => "true"
					),
					"cg_other" => array(
							"nav" => "CG Other",
							"name" => "Camp Galileo Other",
							"active" => "true",
							"camp_type" => array(4,5,6,7,9,11,12,13,14,15),
							"grade_select" => "true",
							"grade_select_camps" => array(6),
							"camp_col" => "true",
							"cg_staff" => 0,
							"edit" => "true",
							"add" => "true"
					),
					"gsq_other" => array(
							"nav" => "GSQ Other",
							"name" => "Galileo Summer Quest Other",
							"active" => "true",
							"camp_type" => 10,
							"cg_staff" => 0,
							"edit" => "true",
							"add" => "true"
					),
					"staff" => array(
							"nav" => "CG Curr",
							"name" => "Camp Galileo Curriculum Training",
							"active" => "true",
							"theme_type" => array("A" => "Art-Training", "S" => "Science-Training", "O" => "Outdoor-Training"),
							"camp_type" => 1,
							"theme_type_col" => "true",
							"training_type" => array(3 => "Curriculum Training", 12 => "General Training"), // DEPRECATED: No idea what this was for. Going to keep the data in case it is needed in the future. Training Type had a field name of camp_type in GAMBA 1.0. Camp Type in the themes database for one of the entries is 1, so I am not sure what this was used for.
							"cg_staff" => 1,
							"edit" => "true",
							"add" => "true"
					),
					"cg_position" => array(
							"nav" => "CG Position",
							"name" => "Camp Galileo Position Training",
							"active" => "true",
							"grade_select" => "true",
							"camp_type" => 11,
							"cg_staff" => 0,
							"edit" => "true",
							"add" => "true"
					),
					"gsq_staff" => array(
							"nav" => "GSQ Curr",
							"name" => "Galileo Summer Quest Staff Curriculum Training",
							"active" => "true",
							"grade_select" => "true",
							"camp_type" => 2,
							"cg_staff" => 1,
							"edit" => "true",
							"add" => "true"
					)
			);
			return $camp_array;
		}
		
		public static function campg_theme_numbering_array() {
			$array = array(
					1 => "Theme Number 1",
					2 => "Theme Number 2",
					3 => "Theme Number 3",
					4 => "Theme Number 4",
					5 => "Theme Number 5",
					6 => "Theme Number 6"
			);
			return $array;
		}
		
		public static function gsq_theme_numbering_array() {
			$array = array(
					1 => "Theme Number 1",
					2 => "Theme Number 2",
					3 => "Theme Number 3",
					4 => "Theme Number 4",
					5 => "Theme Number 5",
					6 => "Theme Number 6",
					7 => "Theme Number 7",
					8 => "Theme Number 8",
					9 => "Theme Number 9",
					10 => "Theme Number 10",
					11 => "Theme Number 11",
					12 => "Theme Number 12",
					13 => "Theme Number 13",
					14 => "Theme Number 14",
					15 => "Theme Number 15",
					16 => "Theme Number 16",
					17 => "Theme Number 17",
					18 => "Theme Number 18",
					19 => "Theme Number 19",
					20 => "Theme Number 20",
			);
			return $array;
		}
		
		public static function theme_number_activities($theme_id) {
// 			$sql = "SELECT 1 FROM ".tbpre."activities WHERE theme_id = $theme_id";
			$number = Activities::select('id')->where('theme_id', $theme_id)->get();
			return $number->count();
		}
		
		public static function themes_camps_all($term) {
			$camps = gambaCampCategories::camps_list();
			$theme_types = self::theme_types();
			$query = Themes::select('id', 'name', 'link_id', 'camp_type', 'minor', 'theme_type', 'theme_options', 'budget', 'costs', 'costing_summary')->where('term', $term)->orderBy('name')->get();
			
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$name = $row['name'];
					$link_id = $link_id = $row['link_id'];
					$camp_type = $row['camp_type'];
					$minor = $row['minor'];
					$theme_type = $row['theme_type'];
					$budget = json_decode($row->budget, true);
					$theme_type_name = $theme_types[$camp_type][$theme_type];
					$theme_options = json_decode($row->theme_options, true);
					$costing_summary = json_decode($row->costing_summary, true);
					$costs = json_decode($row->costs, true);
					$theme_number_activities = self::theme_number_activities($id);
					if(is_array($theme_options['category_themes'])) {
						$this_camp = $theme_options['this_camp'];
						$array[$camp_type][$id]['name'] = $name;
						$array[$camp_type][$id]['link_id'] = $link_id;
						$array[$camp_type][$id]['minor'] = $minor;
						$array[$camp_type][$id]['this_camp'] = $this_camp;
						$array[$camp_type][$id]['theme_type'] = $theme_type;
						$array[$camp_type][$id]['theme_type_name'] = $theme_type_name;
						$array[$camp_type][$id]['theme_camp'] = $camp_type;
						$array[$camp_type][$id]['theme_edit'] = "true";
						$array[$camp_type][$id]['camp'] = $camp_type;
						$array[$camp_type][$id]['camp_name'] = $camps[$camp_type]['name'];
						$array[$camp_type][$id]['theme_options'] = $theme_options;
						$array[$camp_type][$id]['number_activities'] = $theme_number_activities;
						$array[$camp_type][$id]['budget'] = $budget;
						$array[$camp_type][$id]['costs'] = $costs;
						$array[$camp_type][$id]['costing_summary'] = $costing_summary;
						foreach($theme_options['category_themes'] as $key => $camp) {
							$array[$camp][$id]['name'] = $name;
							$array[$camp][$id]['link_id'] = $link_id;
							$array[$camp][$id]['minor'] = $minor;
							$array[$camp][$id]['this_camp'] = $this_camp;
							$array[$camp][$id]['theme_type'] = $theme_type;
							$array[$camp][$id]['theme_type_name'] = $theme_type_name;
							$array[$camp][$id]['theme_camp'] = $camp_type;
							$array[$camp][$id]['theme_edit'] = "false";
							$array[$camp][$id]['camp'] = $camp;
							$array[$camp][$id]['camp_name'] = $camps[$camp]['name'];
							$array[$camp][$id]['theme_options'] = $theme_options;
							$array[$camp][$id]['number_activities'] = $theme_number_activities;
							$array[$camp][$id]['budget'] = $budget;
							$array[$camp][$id]['costs'] = $costs;
							$array[$camp][$id]['costing_summary'] = $costing_summary;
						}
					} else {
						$array[$camp_type][$id]['name'] = $name;
						$array[$camp_type][$id]['link_id'] = $link_id;
						$array[$camp_type][$id]['minor'] = $minor;
						$array[$camp_type][$id]['this_camp'] = "true";
						$array[$camp_type][$id]['theme_type'] = $theme_type;
						$array[$camp_type][$id]['theme_type_name'] = $theme_type_name;
						$array[$camp_type][$id]['theme_camp'] = $camp_type;
						$array[$camp_type][$id]['theme_edit'] = "true";
						$array[$camp_type][$id]['camp'] = $camp_type;
						$array[$camp_type][$id]['camp_name'] = $camps[$camp_type]['name'];
						$array[$camp_type][$id]['theme_options'] = $theme_options;
						$array[$camp_type][$id]['number_activities'] = $theme_number_activities;
						$array[$camp_type][$id]['budget'] = $budget;
						$array[$camp_type][$id]['costs'] = $costs;
						$array[$camp_type][$id]['costing_summary'] = $costing_summary;
					}
				}
			}
			return $array;
		}
		
		public static function themes_by_camp($camp, $term) {
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($camp == "") { $camp = 1; }
			
			$themes_camps_all = self::themes_camps_all($term);
			
			foreach($themes_camps_all[$camp] as $theme_id => $values) {
				$id = $theme_id;
				$array[$id]['name'] = $values['name'];
				$array[$id]['link_id'] = $link_id = $values['link_id'];
				$array[$id]['camp'] = $camp = $values['camp'];
				$array[$id]['camp_name'] = $values['camp_name'];
				$array[$id]['theme_options'] = $values['theme_options'];
				$array[$id]['minor'] = $values['minor'];
				$array[$id]['theme_type'] = $values['theme_type'];
				$array[$id]['theme_type_name'] = $values['theme_type_name'];
				$array[$id]['theme_camp'] = $values['theme_camp'];
				$array[$id]['theme_edit'] = $values['theme_edit'];
				$array[$id]['budget'] = $values['budget'];
				$array[$id]['costs'] = $values['costs'];
				$array[$id]['costing_summary'] = $values['costing_summary'];
				$array[$id]['number_activities'] = $values['number_activities'];
				$theme_link = self::theme_link($theme_id, $link_id);
				$array[$id]['link_theme_name'] = $theme_link['link_theme_name'];
				$array[$id]['link_theme_id'] = $theme_link['link_theme_id'];
				$activities = gambaActivities::activities_by_theme($id, $values['theme_camp']);
				$array[$id]['activities'] = $activities['activities'];
			}
			return $array;
		}
		
		
		public static function theme_year_nav($camp, $term) {
			$url = url('/');
			$terms = gambaTerm::terms();
			$current_term = gambaTerm::year_by_status('C');
			if($term == "") { $term = $current_term; }
			$content .= '<dl class="sub-nav"><dt>Select Term:</dt>';
			foreach($terms as $key => $value) {
				$active = ""; if($term == $key) { $active = ' class="active"'; }
				$content .= <<<EOT
				<dd{$active}><a href="{$url}/settings/themes?camp={$camp}&term={$key}" title="{$value['year_status']}">{$key}</a></dd>
EOT;
			}
			$content .= '</dl>';
			return $content;
		}
		
		public static function theme_camp_nav($camp, $term) {
			$url = url('/');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($camp == "") { $camp = 1; }
			$camps = gambaCampCategories::camps_list();
			
			if(is_array($camps)) {
				$content .= '<dl class="sub-nav"><dt>Select Category:</dt>';
				foreach($camps as $key => $value) {
				$active = ""; if($camp == $key) { $active = ' class="active"'; }
					$content .= <<<EOT
					<dd{$active}><a href="{$url}/settings/themes?camp={$key}&term={$term}">{$value['alt_name']}</a></dd>
EOT;
				}
				$content .= '</dl>';
			}
			return $content;
		}
		
		public static function quick_themes_by_camp($camp, $term) {
			$query = Themes::select('id', 'name')->where('camp_type', $camp)->where('term', $term)->orderBy('name')->get();
			
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$array[$id] = $row['name'];
				}
			}
			return $array;
		}
		
		public static function themes_by_term($term) {
			$query = Themes::select('id', 'name', 'theme_options')->where('term', $term)->get();
			
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$array[$id]['name'] = $row['name'];
					$array[$id]['theme_options'] = json_decode($row->theme_options, true);
				}
			}
			return $array;
		}
		
		public static function data_update_theme($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$name = htmlspecialchars($array['theme_name']);
			$term = $array['term'];
			$camp = $array['camp'];
			$theme_id = $array['theme_id'];
			$theme_type = $array['theme_type'];
			if($theme_type != "") {
				$array['theme_options']['theme_number'] = $array['theme_options']['theme_number'] . $theme_type;
			}
			$link_to = $array['link_to'];
			$currently_linked = $array['currently_linked'];
			
			if($currently_linked != 1 && $link_to > 0) {
// 				echo "<p>Link to</p>";
				self::theme_link_add($array);
			}
			if(empty($array['theme_options']['category_themes'])) {
				$array['theme_options']['category_themes'] = array();
			}
			
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();

			$update = Themes::find($theme_id);
				$update->name = $name;
				$update->theme_type = $theme_type;
				$update->theme_options = json_encode($array['theme_options']);
				$update->save();

		}	
		
		public static function data_delete_theme($array) {
			$delete = Themes::find($array['theme_id'])->delete();
		}
		
		public static function data_add_theme($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$name = htmlspecialchars($array['theme_name']);
			$term = $array['term'];
			$camp = $array['camp'];
			$theme_type = $array['theme_type'];
			if($theme_type != "") {
				$array['theme_options']['theme_number'] = $array['theme_options']['theme_number'] . $theme_type;
			}
			$theme_options = json_encode($array['theme_options']);
			$theme_id = Themes::insertGetId([
				'name' => $name, 
				'term' => $term, 
				'camp_type' => $camp, 
				'theme_type' => $theme_type, 
				'theme_options' => $theme_options
			]);
			$return['sql'] = \DB::last_query();
			$result = base64_encode(json_encode($return));
			return $result;
		}
		
		public static function theme_link_add($array) {
			$term = $array['term'];
			$camp = $array['camp'];
			$theme_id = $array['theme_id'];
			$theme_id_linked = $array['link_to'];
			$link_id = ThemeLink::insertGetId(['term' => $term, 'camp_type' => $camp]);
			$update = Themes::find($theme_id)->update(['link_id' => $link_id]);
			$update = Themes::find($theme_id_linked)->update(['link_id' => $link_id]);
			
		}
		
		public static function data_unlink_theme($array) {
			$url = url('/');
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$link_id = $array['link_id'];
			$delete = ThemeLink::find($link_id)->delete();
			$update = Themes::where('link_id', $link_id)->update(['link_id' => 0]);
		}
		
		public static function theme_link($theme, $link_id) {
			$row = Themes::select('name')->where('link_id', $link_id)->where('link_id', '!=', '0')->where('id', '!=', $theme)->first();
// 			echo "<pre>"; print_r($row); echo "</pre>"; exit; die();
			if(isset($row['name'])) {
				$array['link_theme_name'] = $row['name'];
				$array['link_theme_id'] = $link_id;
				
			}
			return $array;
		}
		
		public static function theme_by_id($id) {
			$row = Themes::find($id);
			
				$array['name'] = $row['name'];
				$array['term'] = $row['term'];
				$array['camp_type'] = $row['camp_type'];
				$array['theme_type'] = $row['theme_type'];
				$array['cg_staff'] = $row['cg_staff'];
				$array['link_id'] = $row['link_id'];
				$array['minor'] = $row['minor'];
				$array['id'] = $row['id'];
				$array['quantity_id'] = $row['quantity_id'];
				$array['number_activities'] = self::theme_number_activities($id);
				$array['theme_options'] = json_decode($row['theme_options'], true);
				$array['budget'] = json_decode($row['budget'], true);
				$array['costs'] = json_decode($row['costs'], true);
				$array['costing_summary'] = json_decode($row['costing_summary'], true);
			return $array;
		}
		
		public static function themes_by_year_camp($term, $camp) {
			
			$query = Themes::select('id', 'name', 'theme_type', 'cg_staff', 'link_id', 'minor', 'quantity_id')->where('term', $term)->where('camp_type', $camp)->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					
				}
			}
			return $array;
		}
		
		public static function themes_without_linkid($term, $id) {
			if($id != "") {
				$query = Themes::select('id', 'name')->where('term', '=', $term)->where('link_id', '=', '0')->where('camp_type', '=', '1')->where('id', '!=', $id)->get();
				foreach($query as $key => $row) {
					$id = $row['id'];
					$array[$id]['name'] = $row['name'];
				}
			}
			return $array;
		}
		
		public static function themes_by_linkid($term, $camp) {
			$query = Themes::select('id', 'name', 'theme_type', 'cg_staff', 'quantity_id', 'link_id', 'theme_options')->where('term', $term)->where('camp_type', $camp)->get();
			
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$linkid = $row['link_id'];
					$id = $row['id'];
					$array['themes'][$linkid][$id]['name'] = $row['name'];
					$array['themes'][$linkid][$id]['theme_type'] = $row['theme_type'];
					$array['themes'][$linkid][$id]['cg_staff'] = $row['cg_staff'];
					$array['themes'][$linkid][$id]['quantity_id'] = $row['quantity_id'];
					$theme_options = json_decode($row->theme_options, true);
					$array['themes'][$linkid]['this_camp'] = $theme_options['this_camp'];
				}
			}
			return $array;
		}
		
		public static function theme_types() {
			$row = Config::select('value')->where('field', 'theme_types')->first();
			$array = json_decode($row->value, true);
			
			return $array;
		}
		
		public static function data_update_theme_types($array) {
			if($array['add']['camp'] != "" && $array['add']['id'] != "" && $array['add']['name'] != "") {
				$camp = $array['add']['camp'];
				$id = $array['add']['id'];
				$name =  $array['add']['name'];
				$return['add'] = 1;
				$return['add_msg'] = $array['add']['name'] . " has been added.";
				//echo "<pre>"; print_r($array['update']); echo "</pre>"; exit; die();
			}
// 			echo "<pre>"; print_r($array['update']); echo "</pre>"; exit; die();
			$json_data = json_encode($array['update']);
			$update = Config::where('field', 'theme_types')->update(['value' => $json_data]);
			$return['sql'] = \DB::last_query();
			$result = base64_encode(json_encode($return));
			return $result;
		}

		// FORMS

		public static function theme_types_view($array, $return) {
			$url = url('/');
			$user_group = Session::get('group');
			$content_array['page_title'] = "Themes Types";
			$content_array['content'] .= gambaDirections::getDirections('theme_types_view');
			$camps = gambaCampCategories::camps_list();
			$theme_types = self::theme_types();
			if($return['add'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box($return['add_msg'], 'success');
			}
			if($array['updated'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box("Theme Types have been updated!", 'success');
			}
			$content_array['content'] .= <<<EOT
		<form method="post" name="theme_types" action="{$url}/settings/update_theme_types" class="form">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
			<table class="table table-striped table-bordered table-hover table-condensed table-small table-responsive" id="themes">
				<thead>
					<tr>
						<th>Camp Category</th>
						<th>Type ID</th>
						<th>Theme Type Name</th>
					</tr>
				</thead>
				<tbody>
EOT;
			foreach($theme_types as $camp => $values) {
				foreach($values as $id => $name) {
					$content_array['content'] .= <<<EOT
					<tr>
						<td>{$camps[$camp]['name']}</td>
						<td>{$id}</td>
						<td><input type="text" name="update[{$camp}][{$id}]" value="{$name}" /></td>
					</tr>
EOT;
				}
			}
			$content_array['content'] .= <<<EOT
					<tr>
						<td><select name="add[camp]">
							<option value="">----------------</option>
EOT;
			foreach($camps as $id => $values) {
				if($values['camp_values']['theme_type'] == "true") {
					$content_array['content'] .= '<option value="'.$id.'">'.$values['name'].'</option>'."\n";
				}
			}
			$content_array['content'] .= <<<EOT
							</select></td>
						<td><input type="text" name="add[id]" /></td>
						<td><input type="text" name="add[name]" /></td>
					</tr>
				</tbody>
			</table>
			<p><button type="submit" class="button small radius">Update</button></p>
			<input type="hidden" name="action" value="update_theme_types" />
		</form>
EOT;
// 			if($user_group == 3) {
// 				FixedWidthScreen::template($content_array);
// 				return $content_array;
// 			} else {
// 				FixedWidthTwoColumn::template($content_array);
				return $content_array;
// 			}
// 			gambaDebug::preformatted_arrays($theme_types, "theme_types", "Theme Types");
		}
		
		
		public static function view_themes_activities($camp, $term) {
			$url = url('/');
			$user_group = Session::get('group');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($camp == "") { $camp = 1; }
			$camps = gambaCampCategories::camps_list();
			$themes = self::themes_by_camp($camp, $term);
			$theme_types = self::theme_types();
			$grades = gambaGrades::grade_list();
			$content_array['page_title'] = "Themes and Activities: ".$camps[$camp]['name']. " ".$term;
			$content_array['content'] .= gambaDirections::getDirections('theme_edit');
			$theme_year_nav = self::theme_year_nav($camp, $term);
			$theme_camp_nav = self::theme_camp_nav($camp, $term);
			$campg_theme_numbering_array = self::campg_theme_numbering_array();
			$content_array['content'] .= <<<EOT
			
					{$theme_year_nav}
					
					{$theme_camp_nav}
					
		<script type="text/javascript">
			// call the tablesorter plugin
// 			$(function(){ 
// 			    $("table").tablesorter({
// 					widgets: [ 'stickyHeaders' ],
// 					widgetOptions: { stickyHeaders_offset : 50, },
// 				}); 
// 			 }); 
		</script>
EOT;
			if($themes['camp_settings']['theme_camp'] != "" && $themes['camp_settings']['theme_camp'] != $themes['camp_settings']['camp_type']) {
				$content_array['content'] .= "<div class='panel directions'><strong>Note:</strong> This Camp is using the themes and activities from ".$camps[$themes['camp_settings']['theme_camp']]['name'].".</div>";
			}
			if($themes['camp_settings']['add'] == "false") { $add_disabled = ' disabled'; }
			$content_array['content'] .= <<<EOT
			<p><a data-toggle="modal" href="{$url}/settings/theme_add?action=theme_add&term={$term}&camp={$camp}" class="button small  thin radius success{$add_disabled}">Add Theme</a></p>
EOT;
			if(is_array($themes)) {
				$content_array['content'] .= <<<EOT
				<table class="table table-small table-striped" id="themes">
					<thead>
						<tr>
							<th></th>
							<th></th>
							<th>Theme</th>
							<th>No. Activities/Supplies</th>
EOT;
				if($camps[$camp]['camp_values']['linked_to'] == "true") {
					$content_array['content'] .= <<<EOT
							<th>Linked To</th>
EOT;
				} 
				if($camps[$camp]['camp_values']['theme_type'] == "true") {
					$content_array['content'] .= <<<EOT
							 <th>Theme Type</th>
EOT;
				}
				$content_array['content'] .= <<<EOT
							<th class="center">Also Used In</th>
							<th class="center">Used In This Camp Category</th>
						</tr>
					</thead>
					<tbody>
EOT;
				foreach($themes as $theme_id => $theme_val) {
// 					if($themes['camp_settings']['theme_camp'] == "" || ($themes['camp_settings']['theme_camp'] != $theme_id && $theme_val['theme_options']['other_camps'] == "true")) {
						if(is_int($theme_id)) {
							// Row Successfully Changed
							$row_success = ""; if($return->updated->$theme_id == 1 || $return->add_id == $theme_id) { $row_success = ' success'; }
							// Disable Theme
							$theme_edit_disable = ""; if($theme_val['theme_edit'] == "false" && $theme_val['camp'] != $theme_val['theme_camp']) { $theme_edit_disable = ' disabled'; }
							// Disable Activity
							$activity_add_disable = ""; if($theme_val['theme_edit'] == "false" && $theme_val['camp'] != $theme_val['theme_camp']) { $activity_add_disable = ' disabled'; }
							// Theme Number
							$theme_number = ""; if($theme_val['theme_options']['theme_number'] > 0) { $theme_number = " <span data-tooltip aria-haspopup=\"true\" class=\"has-tip [tip-top tip-bottom tip-left tip-right] [radius round]\" title=\"{$campg_theme_numbering_array[$theme_val['theme_options']['theme_number']]}\">[{$theme_val['theme_options']['theme_number']}]</span>"; }
							// Delete Theme
							$theme_delete = ""; if(empty($theme_val['activities'])) {
								$theme_delete = " <a href=\"{$url}/settings/theme_delete?action=delete_theme&camp={$camp}&term={$term}&theme_id={$theme_id}\" class=\"button tiny radius alert\" onClick=\"javascript:return confirm('Are you sure you want to delete this theme?');\">Delete</a>";
							}
							$content_array['content'] .= <<<EOT
						<tr class="row-theme{$row_success}">
							<td><a href="{$url}/settings/theme_edit?action=theme_edit&term={$term}&id={$theme_id}&camp={$camp}" class="button small thin radius{$theme_edit_disable}">Edit Theme</a></td>
							<td><a name="theme{$theme_id}"></a><a data-toggle="modal" href="{$url}/settings/activity_add?action=activity_add&term={$term}&camp={$camp}&theme_id={$theme_id}" class="button small thin radius success{$activity_add_disable}">Add Activity</a></td>
							<td class="theme-name">{$theme_val['name']}{$theme_number}{$theme_delete}</td>
							<td>{$theme_val['number_activities']}</td>
							<td>		
EOT;
							if($camps[$camp]['camp_values']['linked_to'] == "true") {
								$content_array['content'] .= <<<EOT
							<a href="{$url}/settings/unlink_theme?action=unlink_theme&term={$term}&camp={$camp}&link_id={$theme_val['link_id']}&theme_id={$theme_id}" onclick="javascript:return confirm('Are you sure you want to unlink this theme?');">{$theme_val['link_theme_name']}</a>
EOT;
							}
							$content_array['content'] .= <<<EOT
							</td>
							<td class="center">
EOT;
							if($camps[$camp]['camp_values']['theme_type'] == "true") {
								$content_array['content'] .= <<<EOT
							{$theme_val['theme_type_name']}
EOT;
							}
							$content_array['content'] .= <<<EOT
							</td>
							<td class="center">
EOT;
							if(is_array($theme_val['theme_options']['category_themes'])) { 
								foreach($theme_val['theme_options']['category_themes'] as $key => $value) {
									$content_array['content'] .= $camps[$value]['name'] . ", ";
								}
							} 
							$content_array['content'] .= <<<EOT
							</td>
							<td class="center">
EOT;
							if($theme_val['theme_options']['this_camp'] == "true" || $theme_val['theme_options']['this_camp'] == "") { $content_array['content'] .= "Yes"; } else { $content_array['content'] .= "No"; } 
							$content_array['content'] .= <<<EOT
							</td>
						</tr>
EOT;
						}
					foreach($theme_val['activities'] as $activity_id => $activity_val) {
						if(is_int($activity_id)) {
								$activity_edit_disable = ""; if($theme_val['theme_edit'] == "false" && $theme_val['camp'] != $theme_val['theme_camp']) { $activity_edit_disable = ' disabled'; }
								if($activity_val['theme_type'] == "A") { $activity_theme_type = "Art"; }
								elseif($activity_val['theme_type'] == "S") { $activity_theme_type = "Science"; }
								else { $activity_theme_type = ""; }
								$content_array['content'] .= <<<EOT
						<tr>
							<td><a name="activity{$activity_id}"></a></td>
							<td><a href="{$url}/settings/activity_edit?action=activity_edit&term={$term}&camp={$camp}&theme_id={$theme_id}&activity_id={$activity_id}" class="button small thin radius{$activity_edit_disable}">Edit Activity</a></td>
							<td><strong>Activity:</strong> 
EOT;
							if($activity_val['grade_name'] != "") { 
								$content_array['content'] .= $activity_val['grade_name'] . " - "; 
							}  
							// Delete Activity
							$activity_delete = ""; if($activity_val['num_supplies'] == 0) {
								$activity_delete = " <a href=\"{$url}/settings/delete_activity?action=delete_activity&camp={$camp}&term={$term}&theme_id={$theme_id}&id={$activity_id}\" class=\"button tiny radius alert\" onClick=\"javascript:return confirm('Are you sure you want to delete this activity?');\">Delete</a>";
							}
							$content_array['content'] .= "{$activity_val['activity_name']}{$activity_delete}"; 
							if($activity_val['description'] != "") { 
								$content_array['content'] .= '<br /><strong>Description:</strong> '.$activity_val['description'];
							} 
							$content_array['content'] .= <<<EOT
							</td>
							<td class="center">{$activity_val['num_supplies']}</td>
EOT;
							$content_array['content'] .= '<td></td><td class="center">'; 
							if($camps[$camp]['camp_values']['theme_type'] == "true") { $content_array['content'] .= $activity_theme_type; } 
							$content_array['content'] .= <<<EOT
							</td>
							<td></td>
							<td></td>
						</tr>
EOT;
						}
					}
					$content_array['content'] .= <<<EOT
					
					</tbody>
EOT;
				}
				$content_array['content'] .= <<<EOT

				</table>
EOT;
			} else {
				$content_array['content'] .= <<<EOT
						<div class="alert-box info radius">There are no themes for this Camp and Year.</div>
EOT;
			}
// 			if($user_group == 3) {
// 				FullScreen::template($content_array);
// 			} else {
// 				FullScreenTwoColumn::template($content_array);
// 			}

			return $content_array;
		}
		
		public static function data_form_all_theme($array, $return) {
			$url = url('/');
			$user_group = Session::get('group');
			$camp = $array['camp'];
			$theme_id = $array['id'];
			$term = $array['term'];
			$action = $array['action'];

			$camps = gambaCampCategories::camps_list();
			$themes = self::themes_by_camp($camp, $term);
			$theme_types = self::theme_types();
			$grades = gambaGrades::grade_list();
			
			if($action == "theme_edit") {
				$theme_val = Themes::find($theme_id)->toArray();
				$content_array['page_title'] = "Edit Theme: ". $theme_val['name'];
				$theme_val['theme_options'] = json_decode($theme_val['theme_options'], true);
				$theme_val['budget'] = json_decode($theme_val['budget'], true);
				$form_action = "update_theme";
				$form_button = "Save Changes";
			}
			if($action == "theme_add") {
				$content_array['page_title'] = "Add Theme for ".$camps[$camp]['name'];
				$form_action = "add_theme";
				$form_button = "Add Theme";
			}
			$content_array['back_nav'] .= <<<EOT
			<p class="right"><a href="{$url}/settings/themes?term={$term}&camp={$camp}#theme{$array['id']}" class="button small radius success">&lt;= Back to Themes</a></p>
EOT;
			if($array['update'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box("Your Theme Data is Successfully Updated.", 'success');
			}
			$content_array['content'] .= <<<EOT
					<form method="post" action="{$url}/settings/{$form_action}" name="theme{$theme_id}" class="form-horizontal">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
						<div class="row">
							<div class="small-12 medium-2 large-2 columns">
								<label for="theme_name">Theme Name</label>
							</div>
							<div class="small-12 medium-6 large-6 end columns">
								<input type="text" name="theme_name" id="theme_name" value="{$theme_val['name']}" />
							</div>
						</div>
EOT;
			// Theme Type			
			if($camps[$camp]['camp_values']['theme_type'] == "true") {
				$content_array['content'] .= <<<EOT
						<div class="row">
							<div class="small-12 medium-2 large-3 columns">
								<label for="theme_type">Theme Type</label>
							</div>
							<div class="small-12 medium-6 large-6 end columns">
								<select name="theme_type" id="theme_type">
									<option value="">-------------------</option>
EOT;
				foreach($theme_types[$camp] as $key => $value) {
					$content_array['content'] .= '<option value="'.$key.'"'; 
					if($key == $theme_val['theme_type']) { $content_array['content'] .= " selected"; } 
					$content_array['content'] .= '>'. $value .'</option>';
				}
				$content_array['content'] .= <<<EOT
								</select>
							</div>
						</div>
EOT;
			} 
			// Theme Number
			if($camps[$camp]['camp_values']['cost_analysis'] == "true") {
				$content_array['content'] .= <<<EOT
						<div class="row">
							<div class="small-12 medium-2 large-3 columns">
								<label for="theme_number">Theme Number</label>
							</div>
							<div class="small-12 medium-6 large-6 columns">
								<select name="theme_options[theme_number]" id="theme_number">
									<option value="0">-------------------</option>
EOT;
				
				if($camps[$camp]['camp_values']['cost_analysis_summary'] == "campg") {
					$theme_numbering_array = self::campg_theme_numbering_array();
				}
				if($camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
					$theme_numbering_array = self::gsq_theme_numbering_array();
				}
				foreach($theme_numbering_array as $key => $value) { 
					$content_array['content'] .= '<option value="'.$key.'"'; 
					if($key == $theme_val['theme_options']['theme_number']) { $content_array['content'] .= " selected"; } 
					$content_array['content'] .= '>'.$value.'</option>';
				} 
				$content_array['content'] .= <<<EOT
								</select>
							</div>
							<div class="columns small-12 medium-2 large-2 end">
								<span class="round label"><span data-tooltip aria-haspopup="true" class="has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="By Setting a Theme Number you can link different Camp G Themes from Season to Season.">?</span></span>
							</div>
						</div>
EOT;
			}
			if($camps[$camp]['camp_values']['linked_to'] == "true") {
				if($theme_val['link_id'] == 0) {
					$themes_without_linkid = self::themes_without_linkid($term, $theme_id);
// 					echo "<pre>"; print_r($themes_without_linkid); echo "</pre>";
					$content_array['content'] .= <<<EOT
						<div class="row">
							<div class="small-12 medium-3 large-3 columns">
								<label for="link_to">Link Theme To</label>
							</div>
							<div class="small-12 medium-6 large-6 end columns">
								<select name="link_to" id="link_to">
									<option value="0">-------------------</option>
EOT;
					foreach($themes_without_linkid as $key => $value) { 
						$content_array['content'] .= '<option value="'.$key.'"'; 
						if($key == $theme_val['link_id']) { $content_array['content'] .= " selected"; } 
						$content_array['content'] .= '>'.$value['name'].'</option>';
					} 
					$content_array['content'] .= <<<EOT
								</select>
							</div>
						</div>
EOT;
				} 
				if($theme_val['link_id'] > 0) {
					$content_array['content'] .=  '<input type="hidden" name="currently_linked" value="1" />';
				}
			} 
			if($theme_val['theme_options']['this_camp'] == "true" || $theme_val['theme_options']['this_camp'] == "") { $this_camp_true = " checked"; }
			if($theme_val['theme_options']['this_camp'] == "false") { $this_camp_false = " checked"; }
			$content_array['content'] .= <<<EOT
								
						<div class="row">
							<div class="small-12 medium-3 large-3 columns switch small round">
								<input type="radio" name="theme_options[this_camp]" value="true"{$this_camp_true} id="this_camp_true" />
								<label for="this_camp_true" class="radio-true">Yes</label>
								<input type="radio" name="theme_options[this_camp]" value="false"{$this_camp_false} id="this_camp_false" />
								<label for="this_camp_false" class="radio-false">No</label>
							</div>
							<div class="small-12 medium-6 large-6 end columns">
								<label>This Camp Category</label>
								<span class="help-block">Set to &quot;No&quot; to exclude from this Camp Category in Material Requests.</span>
							</div>
						</div>
					
						<div class="row">
							<div class="small-12 medium-3 large-3 columns">
								<label>Category Themes</label>
							</div>
							<div class="small-12 medium-9 large-9 columns switch small round">
								<ul class="small-block-grid-2">
EOT;
			foreach($camps as $id => $camp_values) {
				if($id != $array['camp']) {
					$category_themes_checked = ""; 
					if(in_array($id, $theme_val['theme_options']['category_themes'])) { $category_themes_checked = " checked"; }
					$content_array['content'] .= <<<EOT
									<li><input type="checkbox" name="theme_options[category_themes][]" id="category_theme{$id}" value="{$id}"{$category_themes_checked} />
										<label for="category_theme{$id}"></label>
										{$camp_values['alt_name']}</li>
EOT;
				}
			} 
			$content_array['content'] .= <<<EOT
								</ul>
								<span class="help-block">Select the camp category to share the theme and it's activities in Material Requests.</span>
							</div>
						</div>
EOT;
			/* if($camps[$camp]['camp_values']['cost_analysis'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "campg") {
				$content_array['content'] .= <<<EOT
						<div class="row">
							<div class="small-12 medium-2 large-2 columns">
								<input type="text" name="theme_options[theme_budget_activities]" value="{$theme_val['theme_options']['theme_budget_activities']}" id="theme_budget" />
							</div>
							<div class="small-12 medium-4 large-4 columns">
								<label for="theme_budget">Budget # of Activities</label>
							</div>
							
							<div class="small-12 medium-2 large-2 columns">
								<input type="text" name="theme_options[theme_budget_per_camper]" value="{$theme_val['theme_options']['theme_budget_per_camper']}" id="theme_budget2" />
							</div>
							<div class="small-12 medium-4 large-4 end columns">
								<label for="theme_budget2">Budget Cost Per Camper</label>
							</div>
										
							<div class="small-12 medium-12 large-12 columns">
								<span class="help-block">Set the budget for activies under this theme for Material List Cost Analysis. Do not include &#36; dollar signs.</span>
							</div>
						</div>
EOT;
			}
			if($camps[$camp]['camp_values']['cost_analysis'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
				$content_array['content'] .= <<<EOT
						<div class="row">
							<div class="small-12 medium-2 large-2 columns">
								<input type="text" name="theme_options[theme_budget_rotations_yr1]" value="{$theme_val['theme_options']['theme_budget_rotations_yr1']}" id="theme_budget_rotations_yr1" />
							</div>
							<div class="small-12 medium-4 large-4 columns">
								<label for="theme_budget_rotations_yr1">Total Rotations Year 1</label>
							</div>
							
							<div class="small-12 medium-2 large-2 columns">
								<input type="text" name="theme_options[theme_budget_per_camper_yr1]" value="{$theme_val['theme_options']['theme_budget_per_camper_yr1']}" id="theme_budget_per_camper_yr1" />
							</div>
							<div class="small-12 medium-4 large-4 columns">
								<label for="theme_budget_per_camper_yr1">Budget Cost Per Camper Year 1</label>
							</div>
						</div>
						<div class="row">
							<div class="small-12 medium-2 large-2 columns">
								<input type="text" name="theme_options[theme_budget_rotations_yr2]" value="{$theme_val['theme_options']['theme_budget_rotations_yr2']}" id="theme_budget_rotations_yr2" />
							</div>
							<div class="small-12 medium-4 large-4 columns">
								<label for="theme_budget_rotations_yr2">Total Rotations Year 2</label>
							</div>
							
							<div class="small-12 medium-2 large-2 columns">
										<input type="text" name="theme_options[theme_budget_per_camper_yr2]" value="{$theme_val['theme_options']['theme_budget_per_camper_yr2']}" id="theme_budget_per_camper_yr2" />
							</div>
							<div class="small-12 medium-4 large-4 end columns">
								<label for="theme_budget_per_camper_yr2">Budget Cost Per Camper Year 2</label>
							</div>
												
							<div class="small-12 medium-12 large-12 columns">
								<span class="help-block">Set the budget for activies under this theme for Material List Cost Analysis. Do not include &#36; dollar signs.</span>
							</div>
						</div>
EOT;
			} */
			$content_array['content'] .= <<<EOT

        				<p><button type="submit" class="button small">{$form_button}</button></p>
        						
        				<input type="hidden" name="action" value="{$form_action}" />
        				<input type="hidden" name="theme_id" value="{$theme_id}" />
        				<input type="hidden" name="term" value="{$term}" />
        				<input type="hidden" name="camp" value="{$camp}" />
        			</form>
EOT;
// 			if($user_group == 3) {
// 				FixedWidthScreen::template($content_array);
// 			} else {
// 				FixedWidthTwoColumn::template($content_array);
// 			}
			//$content_array['content'] .= "<pre>" . print_r($theme_val, true) . "</pre>"; 
			return $content_array;
			
// 			echo "<pre>"; print_r($camps[$camp]); echo "</pre>";
		}
		
	}
