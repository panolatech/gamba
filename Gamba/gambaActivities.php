<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;
	
	use App\Gamba\gambaGrades;
	use App\Gamba\gambaThemes;
	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaUsers;
	
	use App\Models\Activities;
	use App\Models\Supplies;
	use App\Models\Themes;
	
	class gambaActivities {
		public static function activities_by_theme($theme_id, $camp) {
			$grades = gambaGrades::grade_list();
			$activities = Activities::select('activities.id', 'activities.activity_name', 'activities.description', 'activities.grade_id', 'activities.theme_id', 'activities.term', 'activities.theme_type', 'activities.camp', 'supplylists.costing_summary');
			$activities = $activities->leftjoin('supplylists', 'supplylists.activity_id', '=', 'activities.id');
			$activities = $activities->where('activities.theme_id', '=', $theme_id);
			$activities = $activities->orderBy('activities.grade_id')->orderBy('activities.activity_name');
			$activities = $activities->get();
			$num_activities = $activities->count();
			if($num_activities > 0) {
				foreach($activities as $key => $row) {
					$id = $row['id'];
					$array['activities'][$id]['activity_name'] = $row['activity_name'];
					$array['activities'][$id]['description'] = $row['description'];
					$array['activities'][$id]['grade_id'] = $grade_id = $row['grade_id'];
					$array['activities'][$id]['grade_name'] = $grades[$camp]['grades'][$grade_id]['level'];
					$array['activities'][$id]['theme_id'] = $row['theme_id'];
					$array['activities'][$id]['term'] = $row['term'];
					$array['activities'][$id]['theme_type'] = $row['theme_type'];
					$array['activities'][$id]['camp'] = $row['camp'];
					$array['activities'][$id]['costing_summary'] = json_decode($row->costing_summary, true);
					$array['activities'][$id]['num_supplies'] = gambaSupplies::number_supplies_by_activity($id);
				}
			}
			$array['activities_sql'] = $sql;
			return $array;
		}
		
		public static function activity_info($activity_id) {
			$activity = Activities::select('activities.activity_name', 'activities.description', 'activities.grade_id', 'grades.level', 'activities.theme_id', 'themes.name', 'activities.term', 'themes.theme_type', 'themes.camp_type', 'camps.name AS camp_name', 'themes.theme_options', 'camps.abbr', 'themes.budget', 'supplylists.costing_summary');
			$activity = $activity->leftjoin('themes', 'themes.id', '=', 'activities.theme_id');
			$activity = $activity->leftjoin('grades', 'grades.id', '=', 'activities.grade_id');
			$activity = $activity->leftjoin('camps', 'camps.id', '=', 'themes.camp_type');
			$activity = $activity->leftjoin('supplylists', 'supplylists.activity_id', '=', 'activities.id');
			$activity = $activity->where('activities.id', '=', $activity_id);
			$row = $activity->first();
			$array['name'] = $row['activity_name'];
			$array['description'] = $row['description'];
			$array['grade_id'] = $row['grade_id'];
			$array['grade_name'] = $row['level'];
			$array['theme_id'] = $row['theme_id'];
			$array['theme_name'] = $row['name'];
			$array['theme_type'] = $row['theme_type'];
			$array['theme_options'] = json_decode($row->theme_options, true);
			$array['camp'] = $row['camp_type'];
			$array['camp_name'] = $row['camp_name'];
			$array['abbr'] = $row['abbr'];
			$array['term'] = $row['term'];
			$array['budget'] = json_decode($row->budget, true);
			$array['costing_summary'] = json_decode($row->costing_summary, true);
			return $array;
		}
		
		public static function activity_list_by_term($term) {
			
			$activities = Activities::select('activities.id', 'activities.activity_name', 'activities.description', 'activities.grade_id', 'activities.theme_id', 'activities.term', 'activities.theme_type', 'themes.camp_type', 'supplylists.costing_summary');
			$activities = $activities->leftjoin('themes', 'themes.id', '=', 'activities.theme_id');
			$activities = $activities->leftjoin('supplylists', 'supplylists.activity_id', '=', 'activities.id');
			$activities = $activities->where('activities.term', '=', $term);
			$activities = $activities->where('themes.camp_type', '!=', '');
			$activities = $activities->orderBy('activities.grade_id')->orderBy('activities.theme_id')->orderBy('activities.activity_name');
			$activities = $activities->get();
			$num_activities = $activities->count();
			if($num_activities > 0) {
				foreach($activities as $key => $row) {
					$id = $row['id'];
					$array[$id]['name'] = $row['activity_name'];
					$array[$id]['description'] = $row['description'];
					$array[$id]['grade_id'] = $row['grade_id'];
					$array[$id]['theme_id'] = $row['theme_id'];
					$array[$id]['term'] = $row['term'];
					$array[$id]['theme_type'] = $row['theme_type'];
					$array[$id]['camp'] = $row['camp_type'];
					$array[$id]['costing_summary'] = json_decode($row->costing_summary, true);
				}
			}
			return $array;
		}
		
		public static function themes_activities_by_term($term) {
			$themes_camps_all = gambaThemes::themes_camps_all($term);
			foreach($themes_camps_all as $camp => $themes) {
				foreach($themes as $theme_id => $theme_values) {
					$array[$camp][$theme_id]['name'] = $theme_values['name'];
					$array[$camp][$theme_id]['link_id'] = $theme_values['link_id'];
					$array[$camp][$theme_id]['minor'] = $theme_values['minor'];
					$array[$camp][$theme_id]['theme_type'] = $theme_values['theme_type'];
					$array[$camp][$theme_id]['theme_type_name'] = $theme_values['theme_type_name'];
					$array[$camp][$theme_id]['theme_camp'] = $theme_values['theme_camp'];
					$array[$camp][$theme_id]['theme_edit'] = $theme_values['theme_edit'];
					$array[$camp][$theme_id]['camp'] = $theme_values['camp'];
					$array[$camp][$theme_id]['camp_name'] = $theme_values['camp_name'];
					$array[$camp][$theme_id]['theme_options'] = $theme_values['theme_options'];
					$activities = self::activities_by_theme($theme_id, $theme_values['theme_camp']);
					$array[$camp][$theme_id]['activities'] = $activities['activities'];
				}
			}
			return $array;
		}
		
		public static function data_add_activity($array) {
			$grade_id = $array['grade']; if($grade_id == "") { $grade_id = 0; }
			$return['id'] = Activities::insertGetId([
					'activity_name' => $array['activity_name'], 
					'description' => $array['description'], 
					'grade_id' => $grade_id,
					'theme_id' => $array['theme_id'],
					'term' => $array['term'],
					'theme_type' => $array['theme_type']]	
				);
			return $return;
		}
		
		public static function data_update_activity($array) {
			$grade_id = $array['grade']; if($grade_id == "") { $grade_id = 0; }
			$return['id'] = $array['id'];
			$activity = Activities::where('id', $array['id'])->update([
					'activity_name' => $array['activity_name'], 
					'description' => $array['description'], 
					'grade_id' => $grade_id,
					'theme_type' => $array['theme_type']
					
				]);
			return $return;
		}
		
		public static function data_delete_activity($array) {
			$query = Supplies::select('id')->where('activity_id', $array['id'])->get();
			$number_rows = $query->count();
			if($number_rows == 0) {
				$delete = Activities::find($array['id'])->delete();
			} else {
				return $number_rows;
			}
		}
		

		public static function data_form_all_activity($array, $return) {
			$camp = $array['camp'];
			$term = $array['term'];
			$url = url('/');
			$user_group = Session::get('group');
			$activity_id = $array['activity_id'];
			$theme_id = $array['theme_id'];
			$camps = gambaCampCategories::camps_list();
			$grades = gambaGrades::grade_list();
			$theme_val = Themes::select('name', 'camp_type', 'theme_type')->where('id', $theme_id)->first();
			if($array['action'] == "activity_edit") {
				$activity_val = Activities::select('activity_name', 'description', 'grade_id')->where('id', $activity_id)->first();
				$content_array['page_title'] = "Edit {$activity_val['activity_name']} for {$theme_val['name']}";
				$form_action = "update_activity";
				$form_button = "Save Changes";
			}
			if($array['action'] == "activity_add") {
				$content_array['page_title'] = "Add Activity for ". $theme_val['name'];
				$form_action = "add_activity";
				$form_button = "Add Activity";
			}
			$content_array['content'] .= <<<EOT
			<form method="post" action="{$url}/settings/{$form_action}" name="edit_activity">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
							
									
				<div class="row">
					<div class="small-12 medium-2 large-2 columns">
						<label for="activity_name">Activity Name</label>
					</div>
					<div class="small-12 medium-6 large-6 end columns">
						<input type="text" name="activity_name" id="activity_name" value="{$activity_val['activity_name']}" required />
					</div>
				</div>
										
				<div class="row">
					<div class="small-12 medium-2 large-2 columns">
						<label for="description">Description</label>
					</div>
					<div class="small-12 medium-6 large-6 end columns">
						<input type="text" name="description" id="description" value="{$activity_val['description']}" />
					</div>
				</div>
EOT;
			if($camps[$camp]['camp_values']['grade_select'] == "true") { 
				$content_array['content'] .= <<<EOT
									
				<div class="row">
					<div class="small-12 medium-2 large-2 columns">
						<label for="grade">Grade</label>
					</div>
					<div class="small-12 medium-6 large-6 end columns">
						<select name="grade" id="grade" required>
							<option value="">-------------------</option>
EOT;
				$camp_type = $theme_val['camp_type']; 
				foreach($grades[$camp]['grades'] as $key => $value) { 
					$content_array['content'] .= "<option value=\"".$key."\"";
					if($key == $activity_val['grade_id']) { $content_array['content'] .=  " selected"; }
					$content_array['content'] .= ">". $value['level'] . "</option>\n";
												} 
			$content_array['content'] .= <<<EOT
						</select>
					</div>
				</div>
EOT;
											} 
			$content_array['content'] .= <<<EOT
		
				<p><button type="submit" class="button small radius">{$form_button}</button></p>
									
				<input type="hidden" name="action" value="{$form_action}" />
				<input type="hidden" name="camp_type" value="{$theme_val['camp_type']}" />
				<input type="hidden" name="term" value="{$term}" />
				<input type="hidden" name="camp" value="{$camp}" />
				<input type="hidden" name="id" value="{$activity_id}" />
				<input type="hidden" name="theme_type" value="{$theme_val['theme_type']}" />
				<input type="hidden" name="theme_id" value="{$theme_id}" />
			</form>
EOT;
			
// 			if($user_group == 3) {
// 				FixedWidthScreen::template($content_array);
// 			} else {
// 				FixedWidthTwoColumn::template($content_array);
// 			}

			return $content_array;
// 			echo "<pre>"; print_r($activity_val); echo "</pre>";
		}
		
	}
