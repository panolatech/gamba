<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\Grades;

	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaUsers;

	use App\Models\ViewCampsWithGrades;

	class gambaGrades {

		public static function grade_list() {
			$camps = gambaCampCategories::camps_list();
			$query = Grades::select('id', 'level', 'camp_type', 'enrollment', 'altname', 'grade_options')->orderBy('camp_type')->orderBy('level')->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$camp = $row['camp_type'];
					$grade_array[$camp]['camp_name'] = $camp_name = $camps[$camp]['name'];
					$grade_array[$camp]['grades_add'] = "true";
					$grade_array[$camp]['grades'][$id]['camp'] = $camp;
					$grade_array[$camp]['grades'][$id]['camp_name'] = $camp_name;
					$grade_array[$camp]['grades'][$id]['level'] = $level = $row['level'];
					$grade_array[$camp]['grades'][$id]['enrollment'] = $enrollment = $row['enrollment'];
					$grade_array[$camp]['grades'][$id]['altname'] = $altname = $row['altname'];
					$grade_array[$camp]['grades'][$id]['grade_edit'] = "true";
					$grade_array[$camp]['grades'][$id]['grade_options'] = json_decode($row->grade_options, true);
				}
			}
			foreach($camps as $key => $values) {
				if($values['camp_values']['grade_select_camps'] != "") {
					$array[$key]['camp_name'] = $camps[$key]['name'];
					$array[$key]['grades_add'] = "false";
					foreach($grade_array[$values['camp_values']['grade_select_camps']]['grades'] as $grade_id => $grade_values) {
						$array[$key]['grades'][$grade_id] = $grade_values;
						$array[$key]['grades'][$grade_id]['camp'] = $key;
						$array[$key]['grades'][$grade_id]['camp_name'] = $camps[$key]['name'];
						$array[$key]['grades'][$grade_id]['grade_select_camp'] = $values['camp_values']['grade_select_camps'];
						$array[$key]['grades'][$grade_id]['grade_edit'] = "false";
					}
				} else {
					if(is_array($grade_array[$key]['grades'])) {
						$array[$key] = $grade_array[$key];
					}
				}
			}
			return $array;
		}

		public static function camps_nav($camp) {
    		$url = url('/');
			if($camp == "") { $camp = 1; }
			$camps_array = self::grade_list();

			if(is_array($camps_array)) {
				$content .= <<<EOT
				<dl class="sub-nav">
					<dt>Grade:</dt>
EOT;
				foreach($camps_array as $key => $value) {
					if(is_int($key)) {
						$content .= '<dd';
						if($camp == $key) { $content .= ' class="active"';  $return_camp = $camp; }
						$content .= '><a href="'.$url.'/settings/grades?camp='.$key.'">'. $value['camp_name']. '</a></dd>';
					}
				}
				$content .= <<<EOT
				</dl>
EOT;
			}
			return $content;
		}

		public static function camps_with_grades() {
			//$query = Grades::select('grades.camp_type')->select(\DB::raw('gmb_camps.alt_name as name'))->
			//leftjoin('camps', 'grades.camp_type', '=', 'camps.id')->orderBy('camps.name')->get();
			$query = ViewCampsWithGrades::get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['camp_type'];
					$array[$id] = $row['name'];
				}
			}
			return $array;
		}

		public static function data_update_grades($array) {
			$id = $array['id'];
			$level = htmlspecialchars($array['level']);
			$altname = htmlspecialchars($array['altname']);
			$enrollment = $array['enrollment']; if($enrollment == "") { $enrollment = 0; }
			$grade_options = json_encode($array['grade_options']);

			$update = Grades::find($id);
				$update->level = $level;
				$update->altname = $altname;
				$update->enrollment = $enrollment;
				$update->grade_options = $grade_options;
				$update->save();


			$return['updated'] = $id;
			$return['row_updated'] = 1;
			return base64_encode(json_encode($return));
		}

		public static function data_add_grades($array) {
			$id = $array['id'];
			$camp = $array['camp'];
			$level = htmlspecialchars($array['level']);
			$altname = htmlspecialchars($array['altname']);
			$enrollment = $array['enrollment']; if($enrollment == "") { $enrollment = 0; }

			$return['add_id'] = Grades::insertGetId(['level' => $level, 'altname' => $altname, 'enrollment' => $enrollment, 'camp_type' => $camp, 'grade_options' => $grade_options]);


			return base64_encode(json_encode($return));

		}
		// FORMS

		public static function view_grades($camp, $return) {
    		$url = url('/');
			$camps = gambaCampCategories::camps_list();
			if($camp == "") { $camp = 1; }

			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$content_array['page_title'] = "Grades: ".$camps[$camp]['name'];
			//$content_array['content'] .= gambaDirections::getDirections('grades_edit');
			$content_array['content'] .= self::camps_nav($camp);
			$grades = self::grade_list();
			if($return['row_updated'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box('Data successfully updated.', 'success');
			}
			if($return['add_id'] > 0) {
				$content_array['content'] .= gambaDebug::alert_box('Data successfully added.', 'success');
			}
			if($grades[$camp]['grades_add'] != "true") { $grades_add = ' disabled'; }
			$content_array['content'] .= <<<EOT
			<table class="table table-striped table-bordered table-hover table-condensed table-small">
				<thead>
					<tr>
						<th><a href="{$url}/settings/grade_add?action=grade_add&camp={$camp}" class="button small radius success"{$grades_add}>Add</a></th>
						<th>Grade Level</th>
						<th>Alternative Name</th>
						<th>Enrollment Management</th>
						<th>Packing Lists</th>
					</tr>
				</thead>
				<tbody>
EOT;
			foreach($grades[$camp]['grades'] as $key => $values) {
				$edit_disabled = ""; if($values['grade_edit'] != "true") { $edit_disabled .= ' disabled'; }
				$enrollment = ""; if($values['enrollment'] == 1) { $enrollment .= "Used In Enrollment"; }
				$excluded = ""; if($values['grade_options']['exclude_packing'] == "true") { $excluded .= "Excluded: True"; }
				$content_array['content'] .= <<<EOT
					<tr>
						<td><a  href="{$url}/settings/grade_edit?action=grade_edit&id={$key}&camp={$camp}" class="button small radius"{$edit_disabled}>Edit</a></td>
						<td>{$values['level']}</td>
						<td>{$values['altname']}</td>
						<td>{$enrollment}</td>
						<td>{$excluded}</td>
					</tr>
EOT;
			}
			$content_array['content'] .= <<<EOT

				</tbody>
			</table>
EOT;
			return $content_array;
		}

		public static function form_data_all_grades($array, $return) {
			$url = url('/');

			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$camps = gambaCampCategories::camps_list();
			$camp_name = $camps[$array['camp']]['name'];
			if($array['action'] == "grade_edit") {
				$row = Grades::find($array['id']);
				$id = $row['id'];
				$camp = $row['camp_type'];
				$grade_array[$camp]['grades_add'] = "true";
				$grade_array[$camp]['grades'][$id]['camp'] = $camp;
				$grade_array[$camp]['grades'][$id]['camp_name'] = $camp_name;
				$level = $row['level'];
				$enrollment = $row['enrollment'];
				$altname = $row['altname'];
				$grade_array[$camp]['grades'][$id]['grade_edit'] = "true";
				$grade_options = json_decode($row->grade_options, true);
				$content_array['page_title'] = "Edit Grade Level $level for $camp_name";
				$form_action = "update_grade";
				$form_button = "Save changes";
			}
			if($array['action'] == "grade_add") {
				$content_array['page_title'] = "Add Grade Level for $camp_name";
				$form_action = "add_grade";
				$form_button = "Add Grade Level";
			}
			if($enrollment == 1) { $enrollment_checked = " checked"; }
			if($grade_options['exclude_packing'] == "true") { $exclude_packing = " checked"; }
			$content_array['content'] .= <<<EOT
				<form name="form-grade" class="form" action="{$url}/settings/{$form_action}" method="post">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
					<div class="row">
						<div class="small-12 medium-3 large-3 columns">
							<label for="level">Grade Level</label>
						</div>
						<div class="small-12 medium-6 large-6 end columns">
							<input type="text" name="level" id="level" value="{$level}" required />
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns">
							<label for="altname">Alternative Name</label>
						</div>
						<div class="small-12 medium-6 large-6 end columns">
							<input type="text" name="altname" id="altname" value="{$altname}" />
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-2 large-2 columns switch small round">
							<input type="checkbox" name="enrollment" value="1"{$enrollment_checked} id="enrollment" />
							<label for="enrollment">Check</label>
						</div>
						<div class="small-12 medium-6 large-6 end columns">
							<label>Enrollment</label>
							<span class="help-block">Check box to have only this grade appear in the enrollment management pages</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-2 large-2 columns switch small round">
							<input type="checkbox" name="grade_options[exclude_packing]" value="true"{$exclude_packing} id="exclude_packing" />
							<label for="exclude_packing">Check</label>
						</div>
						<div class="small-12 medium-6 large-6 end columns">
							<label>Packing</label>
							<span class="help-block">Check box to exclude this grade from appearing in Packing Lists</span>
						</div>
					</div>

					<p><button type="submit" class="button small">{$form_button}</button></p>

					<input type="hidden" name="camp" value="{$array['camp']}" />
					<input type="hidden" name="id" value="{$array['id']}" />
				</form>
EOT;

			return $content_array;
// 			gambaDebug::preformatted_arrays($grades, 'grades', 'Grades');
		}
	}
