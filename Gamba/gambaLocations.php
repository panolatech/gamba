<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	
	use App\Models\Locations;
	
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaPacking;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaUsers;
	
	class gambaLocations {
		
		public static function locations_by_camp() {
			$query = Locations::select('id', 'location', 'abbreviation', 'camp', 'term_data', 'cut_off_day')->orderBy('camp')->orderBy('location')->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$camp = $row['camp'];
					$id = $row['id'];
					$array['locations'][$camp][$id]['name'] = $location_name = $row['location'];
					$array['locations'][$camp][$id]['abbr'] = $abbreviation = $row['abbreviation'];
					$array['locations'][$camp][$id]['terms'] = json_decode($row->term_data, true);
					$array['locations'][$camp][$id]['cut_off_day'] = $row['cut_off_day'];
				}
			}
			return $array;
		}
		
		public static function locations_with_camps($term = NULL) {
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$array['term'] = $term;
			$category = gambaCampCategories::camps_list();
			$camps = gambaPacking::packing_lists();
			$locations_by_camp = self::locations_by_camp();
			foreach($camps['camps'] as $camp => $values) {
				foreach($values['list_values']['camp_locations'] as $key => $camp_id) {
					foreach($locations_by_camp['locations'][$camp_id] as $location_id => $location_values) {
						if($location_values['terms'][$term]['active'] == "Yes") {
							$array['locations'][$location_id]['camps'][] = $camp;
							$array['camps'][$camp]['locations'][$location_id]['name'] = $location_values['name'];
							$array['camps'][$camp]['locations'][$location_id]['abbr'] = $location_values['abbr'];
							$array['camps'][$camp]['locations'][$location_id]['camp_id'] = $camp_id;
							$array['camps'][$camp]['locations'][$location_id]['camp_abbr'] = $category[$camp_id]['abbr'];
							$array['camps'][$camp]['locations'][$location_id]['terms'] = $location_values['terms'];
							$array['camps'][$camp]['locations'][$location_id]['cut_off_day'] = $location_values['cut_off_day'];
						}
					}
				}
// 				if(is_array($values['camp_values']['request_locations'])) {
// 					foreach($values['camp_values']['request_locations'] as $key => $camp_id) {
// 						foreach($locations_by_camp['locations'][$camp_id] as $location_id => $location_values) {
// 							if($location_values['terms'][$term]['active'] == "Yes") {
// 								$array['locations'][$location_id]['camps'][] = $camp;
// 								$array['camps'][$camp_id]['locations'][$location_id]['name'] = $location_values['name'];
// 								$array['camps'][$camp_id]['locations'][$location_id]['abbr'] = $location_values['abbr'];
// 							}
// 						}
// 					}
// 				}
			}
			$array['locations_by_camp'] = $locations_by_camp;
			return $array;
		}
		
		public static function locations_list() {
			$query = Locations::select('id', 'location', 'abbreviation', 'camp', 'term_data')->orderBy('location')->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$array[$id]['name'] = $row['location'];
					$array[$id]['abbr'] = $row['abbreviation'];
					$array[$id]['terms'] = json_decode($row->term_data, true);
				}
			}
			return $array;
		}

		public static function location_by_id($id) {
			if($id == "") { $id = 0; }

			$row = Locations::select('id', 'location', 'abbreviation', 'camp', 'term_data', 'cut_off_day')->where('id', $id)->first();
			$array['id'] = $row['id'];
			$array['name'] = $row['location'];
			$array['abbr'] = $row['abbreviation'];
			$array['camp'] = $row['camp'];
			$array['terms'] = json_decode($row->term_data, true);
			$array['cut_off_day'] = $row['cut_off_day'];
			$array['sql'] = \DB::last_query();
			return $array;
		}
		
		/**
		 * Returns an array of camp locations by the original id numbers
		 * @return array
		 */
		public static function list_old_location_ids() {
			$query = Locations::select('id', 'location', 'abbreviation', 'camp', 'term_data')->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$term_data = json_decode($row->term_data, true);
					foreach($term_data as $key => $value) {
						$old_id = $value['old_id'];
						$array[$old_id]['location_id'] = $id;
						$array[$old_id]['name'] = $row['location'];
						$array[$old_id]['abbr'] = $row['abbreviation'];
						$array[$old_id]['camp'] = $row['camp'];
					}
				}
			}
			return $array;
		}

		public static function camps_with_locations() {
			$query = Locations::select(\DB::raw('DISTINCT gmb_locations.camp, gmb_camps.alt_name as name'))->leftjoin('camps', 'locations.camp', '=', 'camps.id')->orderby('camps.name')->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['camp'];
					$array[$id] = $row['name'];
				}
			}
			return $array;
		}
		
		public static function update_location($array) {
			$id = $array['id'];
			$location = $array['location'];
			$abbr = $array['abbr'];
			$term_data = json_encode($array['terms']);
			
			$update = Locations::find($id);
				$update->location = $location;
				$update->abbreviation = $abbr;
				$update->term_data = $term_data;
				$update->save();
				
			$return['updated'] = $array['id'];
			$return['row_updated'] = 1;

			return base64_encode(json_encode($return));
		}
		
		public static function add_location($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$terms = gambaTerm::terms();
			$id = $array['id'];
			$camp = $array['camp'];
			$location = $array['location'];
			$abbr = $array['abbr'];
			$term_data = json_encode($array['terms']);
			$return['add_id'] = Locations::insertGetId(['location' => $location, 'camp' => $camp, 'abbreviation' => $abbr, 'term_data' => $term_data]);
			$return['sql_add'] = $sql;

			return base64_encode(json_encode($return));
		}

		public static function camps_nav($camp) {
			$url = url('/');
			if($camp == "") { $camp = 1; }
			$camps_array = self::camps_with_locations();
				
			if(is_array($camps_array)) {
				$content .= '<ul class="pagination">';
				foreach($camps_array as $key => $value) {
					if(is_int($key)) {
						$content .= '<li';
						if($camp == $key) { $content .= ' class="active"';  $return_camp = $camp; }
						$content .= '><a href="'.$url.'/settings/locations?camp='.$key.'">'. $value. '</a></li>';
					}
				}
				$content .= '</ul>';
			}
			return $content;
		}
		
		public static function view_locations($camp, $return) {
			$url = url('/');
			
			$content_array['side_nav'] = gambaNavigation::settings_nav();
			if($camp == "") { $camp = 1; }
			$content_array['content'] .= gambaDirections::getDirections('locations_edit');
			$current_term = gambaTerm::year_by_status('C');
			$content_array['content'] .= self::camps_nav($camp);
			$camps = gambaCampCategories::camps_list();
			$content_array['page_title'] = "Locations for ".$camps[$camp]['name'];
			$terms = gambaTerm::terms();
			$locations = self::locations_by_camp();
			if($return['row_updated'] != "") {
				$content_array['content'] .= gambaDebug::alert_box('Data successfully updated.', 'success');
			}
			if($return['add_id'] > 0) {
				$content_array['content'] .= gambaDebug::alert_box('Data successfully added.', 'success');
			}
			$content_array['content'] .= <<<EOT
		<script type="text/javascript">
			// call the tablesorter plugin
			$(function(){ 
			    $("table").tablesorter({
					widgets: [ 'stickyHeaders' ],
					widgetOptions: { stickyHeaders_offset : 50, },
				}); 
			 }); 
		</script>
		<table class="table table-bordered table-hover table-condensed table-small">
			<thead>
				<tr>
					<th><a href="{$url}/settings/location_add?action=location_add&id{$key}&camp={$camp}" class="button small radius success">Add</a></th>
					<th>ID</th>
					<th>Location</th>
					<th>Abbreviation</th>
EOT;
			foreach($terms as $year => $term_values) {
				$content_array['content'] .= <<<EOT
					<th>{$year}</th>
EOT;
			}
			$content_array['content'] .= <<<EOT
				</tr>
			</thead>
			<tbody>
EOT;
			foreach($locations['locations'][$camp] as $key => $values) {
				$update_success = ""; if($return['updated'] == $key || $return['add_id'] == $key) { $update_success = ' class="success"'; }
				$content_array['content'] .= <<<EOT
				<tr>
					<td><a href="{$url}/settings/location_edit?action=location_edit&id={$key}&camp={$camp}" class="button small radius">Edit</a></td>
					<td class="center">{$key}</td>
					<td{$update_success}>{$values['name']}</td>
					<td class="center">{$values['abbr']}</td>
EOT;
				foreach($terms as $year => $term_values) {
					$active = ""; if($values['terms'][$year]['active'] == "Yes") { $active = ' style="color:green; background-color: #ccdeb3;"'; }
					$content_array['content'] .= <<<EOT
					<td{$active}><strong>Enabled:</strong> {$values['terms'][$year]['active']} &nbsp;
EOT;
					if($camps[$camp]['camp_values']['dli_location'] == "true") { $content_array['content'] .= "<strong>DLI:</strong>"; 
						if($values['terms'][$year]['dstar'] == 1) { $content_array['content'] .= "Yes"; } else { $content_array['content'] .= "No"; } 
					} 
					$content_array['content'] .= <<<EOT
				<br />
					<strong>Sessions:</strong> {$values['terms'][$year]['tot_sessions']}  &nbsp;
					<strong>Classrooms:</strong> {$values['terms'][$year]['classrooms']} 
					</td>
EOT;
				}
				$content_array['content'] .= <<<EOT
				</tr>
EOT;
			}
			$content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
			return $content_array;
		}
		
		public static function form_data_all_location($array, $return) {
			$url = url('/');
			$camp = $array['camp'];
			$camps = gambaCampCategories::camps_list();
			$terms = gambaTerm::terms();
			
			$content_array['side_nav'] = gambaNavigation::settings_nav();
			if($array['action'] == "location_edit") {
				$row = Locations::find($array['id']);
					$camp = $row['camp'];
					$id = $row['id'];
					$location_name = $row['location'];
					$abbreviation = $row['abbreviation'];
					$term_data = json_decode($row->term_data, true);
				$content_array['page_title'] = "Edit Location $location_name for ".$camps[$camp]['name'];
				$form_action = "update_location";
				$form_button = "Save Changes";
				
			}
			if($array['action'] == "location_add") {
				$content_array['page_title'] = "Add Location for ".$camps[$camp]['name'];
				$form_action = "add_location";
				$form_button = "Add Location";
			}
			if($camps[$camp]['camp_values']['dli_location'] == "true") { $camp_caption = "<caption>Double LI locations are calculated in the Office Material List.</caption>"; }
			$content_array['content'] .= <<<EOT
				<form name="form-camp" class="form" action="{$url}/settings/{$form_action}" method="post">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT

			
					<label for="location">Name</label>
					<input type="text" name="location" id="location" value="{$location_name}" class="form-control" />
					
					<label for="abbr">Alternative Name</label>
					<input type="text" name="abbr" id="abbr" value="{$abbreviation}" class="form-control" />
					
					<table>
					{$camp_caption}
						<thead>
							<tr>
								<th>Term</th>
								<th>Enabled</th>
EOT;
			if($camps[$camp]['camp_values']['dli_location'] == "true") { $content_array['content'] .= "<th>DLI</th>"; }
			$content_array['content'] .= <<<EOT
								<th>Sessions</th>
								<th>Classrooms</th>
							</tr>
						</thead>
						<tbody>
EOT;
			foreach($terms as $year => $term_values) {
				if($term_data[$year]['active'] == "Yes") { $active_true = " checked"; } else { $active_true = ""; }
				if($term_data[$year]['active'] == "No") { $active_false = " checked"; } else { $active_false = ""; }
				$content_array['content'] .= <<<EOT
							<tr>
								<td>{$year}</td>
								<td class="switch small round">
									<input type="radio" name="terms[{$year}][active]" value="Yes"{$active_true} id="active_true_{$year}" />
									<label for="active_true_{$year}" class="radio-true">Yes</label>
									<input type="radio" name="terms[{$year}][active]" value="No"{$active_false} id="active_false_{$year}" />
									<label for="active_false_{$year}" class="radio-false">No</label>
								</td>
EOT;
				if($camps[$camp]['camp_values']['dli_location'] == "true") {
					if($term_data[$year]['dstar'] == "1") { $dstar_true = " checked"; } else { $dstar_true = ""; }
					if($term_data[$year]['dstar'] == "0") { $dstar_false = " checked"; } else { $dstar_false = ""; }
					$content_array['content'] .= <<<EOT
								<td class="switch small round">
									<input type="radio" name="terms[{$year}][dstar]" value="1"{$dstar_true} id="dstar_true_{$year}" />
									<label for="dstar_true_{$year}" class="radio-true">Yes</label>
									<input type="radio" name="terms[{$year}][dstar]" value="0"{$dstar_false} id="dstar_false_{$year}" />
									<label for="dstar_false_{$year}" class="radio-false">No</label>
								</td>
EOT;
				}
				if($term_data[$year]['tot_sessions'] == "") {
					$prev_term = $year - 1;
					$term_data[$year]['tot_sessions'] = $term_data[$prev_term]['tot_sessions'];
				}
				if($term_data[$year]['classrooms'] == "") {
					$prev_term = $year - 1;
					$term_data[$year]['classrooms'] = $term_data[$prev_term]['classrooms'];
				}
				$content_array['content'] .= <<<EOT
								<td><input type="text" name="terms[{$year}][tot_sessions]" value="{$term_data[$year]['tot_sessions']}" class="form-control" /></td>
								<td><input type="text" name="terms[{$year}][classrooms]" value="{$term_data[$year]['classrooms']}" class="form-control" /></td>
							</tr>
							<input type="hidden" name="terms[{$year}][old_id]" value="{$term_data[$year]['old_id']}" />
							<input type="hidden" name="terms[{$year}][sat_host]" value="{$term_data[$year]['sat_host']}" />
							<input type="hidden" name="terms[{$year}][sat_visit]" value="{$term_data[$year]['sat_visit']}" />
							<input type="hidden" name="terms[{$year}][sat_total]" value="{$term_data[$year]['sat_total']}" />
EOT;
			}
			$content_array['content'] .= <<<EOT
						</tbody>
					</table>
					
					<p><button type="submit" class="button small">{$form_button}</button></p>

					<input type="hidden" name="action" value="{$form_action}" />
					<input type="hidden" name="camp" value="{$camp}" />
					<input type="hidden" name="id" value="{$array['id']}" />
				</form>
EOT;
// 			dd($term_data);
// 			dd($locations);
			return $content_array;
// 			$list_old_location_ids = self::list_old_location_ids();
// 			gambaDebug::preformatted_arrays($list_old_location_ids, 'list_old_location_ids', 'Old Locations by ID');
// 			$locations_with_camps = self::locations_with_camps();
// 			gambaDebug::preformatted_arrays($locations_with_camps, 'locations_with_camps', 'Locations with Camps');
// 			gambaDebug::preformatted_arrays($camps, 'camps', 'Camps');
		}
	}
