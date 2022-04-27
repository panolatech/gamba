<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\Camps;
	use App\Models\Supplies;

	use App\Gamba\gambaDebug;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaGrades;
	use App\Gamba\gambaLocations;
	use App\Gamba\gambaCosts;
	use App\Gamba\gambaNavigation;
	use App\Gamba\gambaUsers;

	class gambaCampCategories {
		/**
		 *
		 * @return array by id: abbr, name, alt_name
		 */
		public static function camps_list($show = NULL) {
			$camps = Camps::select('id', 'abbr', 'name', 'alt_name', 'camp_values', 'data_inputs', 'costing_summary', 'cost_options');
			$camps = $camps->orderBy('id');
			$camps = $camps->get();
			if($camps->count() > 0) {
				foreach($camps as $key => $row) {
					$id = $row['id'];
					$camp_values = json_decode($row->camp_values, true);
					if(($show == NULL && $camp_values['active'] != "false") || $show == "all" || ($show == "active" && $camp_values['cost_analysis'] == "true" && $camp_values['active'] != "false") || ($show == "inactive" && $camp_values['cost_analysis'] != "true" && $camp_values['active'] != "false")) {
						$array[$id]['abbr'] = $row['abbr'];
						$array[$id]['name'] = $row['name'];
						$array[$id]['alt_name'] = $row['alt_name'];
						$array[$id]['camp_values'] = $camp_values;
						$data_inputs = json_decode($row->data_inputs, true);
						$data_change = $data_inputs['data_change'];
						if($data_change == 1) {
							$array[$id]['data_inputs'] = $data_inputs['data'];
							$array[$id]['location_input'] = $data_inputs['location_input'];
						} else {
							$array[$id]['data_inputs'] = $data_inputs;
						}
						$array[$id]['costing_summary'] = json_decode($row->costing_summary, true);
						$array[$id]['cost_options'] = json_decode($row->cost_options, true);
					}
				}
			}
			return $array;

			// {"1":{"name":"Number of Trainers","enabled":"true"},"2":{"name":"Number of Staffers","enabled":"true"},"3":{"name":"Training Rotations","enabled":"true"}}
		}

		public static function camp_info($id) {
			$row = Camps::find($id);
				$array['id'] = $row['id'];;
				$array['abbr'] = $row['abbr'];
				$array['name'] = $row['name'];
				$array['alt_name'] = $row['alt_name'];
				$array['camp_values'] = json_decode($row['camp_values'], true);
				$data_inputs = json_decode($row['data_inputs'], true);
				$data_change = $data_inputs['data_change'];
				if($data_change == 1) {
					$array['data_inputs'] = $data_inputs['data'];
					$array['location_input'] = $data_inputs['location_input'];
				} else {
					$array['data_inputs'] = $data_inputs;
				}
				$array['costing_summary'] = json_decode($row['costing_summary'], true);
				$array['cost_options'] = json_decode($row['cost_options'], true);
			return $array;
		}

		public static function camp_supplies($camp, $term) {
			$row = Supplies::select(\DB::raw('COUNT(*) AS supplies'))->where('term', $term)->where('camp_id', $camp)->first();
			$return['supplies'] = $row['supplies'];
			return $return;
		}

		public static function data_camp_update($array) {
// 			echo "<pre>"; print_r($array['camp_values']); echo "</pre>"; //exit; die();
			$camps = Camps::find($array['camp_id']);
    			$camp_values = json_decode($camps->camp_values, true);
    			$camp_values['quantity_types_shared'] = "";
    			$camp_values['request_locations'] = "";
    			$new_camp_values = array_merge($camp_values, $array['camp_values']);
//     			echo "<pre>"; print_r($new_camp_values); echo "</pre>"; exit; die();
    			$camps->name = htmlspecialchars($array['name']);
    			$camps->alt_name = htmlspecialchars($array['altname']);
    			$camps->abbr = htmlspecialchars($array['abbr']);
    			$camps->camp_values = json_encode($new_camp_values);
    			$camps->save();

			$return['updated'] = $array['camp_id'];
			$return['row_updated'] = 1;
			$return['name'] = $array['name'];
			return base64_encode(json_encode($return));

		}

		public static function data_camp_add($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$name = htmlspecialchars($array['name']);
			$alt_name = htmlspecialchars($array['altname']);
			$abbr = $array['abbr'];
			$camp_values = $array['camp_values'];
			$camp_values_json = json_encode($camp_values);

			$return['add_id'] = Camps::insertGetId([
					'name' => $name,
					'alt_name' => $alt_name,
					'abbr' => $abbr,
					'camp_values' => $camp_values_json
			]);
			$return['sql_add'] = \DB::last_query();
			$return['name'] = $name;
			return base64_encode(json_encode($return));

		}

		public static function camps_nav($camp) {
			$url = url('/');
			if($camp == "") { $camp = 1; }
			$camps = self::camps_list();
// 			echo "<pre>"; print_r($camps_array); echo "</pre>";

			if(is_array($camps)) {
				$content .= <<<EOT
				<dl class="sub-nav">
					<dt><strong>Camps:</strong></dt>
EOT;
				foreach($camps as $key => $value) {
					if(is_int($key)) {
						$content .= '<dd';
						if($camp == $key) { $content .= ' class="active"';  $return_camp = $camp; }
						$content .= "><a href=\"{$url}/settings/camp_category_input?camp={$key}\" title=\"{$value['name']}\">{$value['alt_name']}</a></dd>";
					}
				}
				$content .= '</dl>';
			}
			return $content;
		}

		public static function camp_category_input() {
			$camps = Camps::select('id', 'name', 'data_inputs')->orderBy('id')->get();
			$array['sql'] = \DB::last_query();
			if($camps->count() > 0) {
				foreach($camps as $key => $row) {
					$id = $row['id'];
					$array['camps'][$id]['name'] = $row['name'];
					$data_inputs = json_decode($row->data_inputs, true);
					$data_change = $data_inputs['data_change'];
					if($data_change == 1) {
						$array['camps'][$id]['data_inputs'] = $data_inputs['data'];
						$array['camps'][$id]['location_input'] = $data_inputs['location_input'];
					} else {
						$array['camps'][$id]['data_inputs'] = $data_inputs;
					}
				}
			}
			return $array;
		}

		public static function data_update_camp_category_input($array) {
// 			echo "<pre>"; print_r($array); echo "<pre>"; //exit; die();
			$camp = $array['camp'];
			$i = $array['i'];
// 			$data_inputs = $array['data_inputs'];
			$data_inputs['location_input'] = $array['location_input'];
			$data_inputs['data_change'] = $array['data_change'];
			$data_inputs['data'] = $array['data_inputs'];
			if($array['add_input'] != "") {
				$data_inputs['data'][$i]['name'] = $array['add_input'];
				$data_inputs['data'][$i]['enabled'] = "true";
			}
// 			echo "<pre>"; print_r($data_inputs); echo "<pre>"; exit; die();
			$data_input = json_encode($data_inputs);
			$camps = Camps::find($camp);
			$camps->data_inputs = $data_input;
			$camps->save();
			return $camp;
		}

		public static function view_camp_category_input_edit($array, $return) {
			$url = url('/');
			if($array['camp'] == "") { $camp = 1; } else { $camp = $array['camp']; }
			$camps = self::camps_list("all");
			$content_array['page_title'] = "Material Request Data Input: ".$camps[$camp]['name'];

			if($array['updated'] == "success") {
				$content_array['content'] .= gambaDebug::alert_box('Data Inputs successfully updated.', 'success');
			}
			$content_array['content'] .= self::camps_nav($camp);
			$content_array['content'] .= gambaDirections::getDirections('camp_category_input_edit');
			$camp_category_input = self::camp_category_input();
			if($camp_category_input['camps'][$camp]['location_input'] == "true") { $location_input_enabled = " checked"; }
			$content_array['content'] .= <<<EOT

		<form class="form" method="post" action="{$url}/settings/camp_category_input_update">
			<div class="row">
				<div class="small-12 medium-6 large-6 columns">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
			<table class="table table-bordered table-hover table-condensed table-small">
				<thead>
					<tr>
						<th>Data Input Name</th>
						<th>Enabled</th>
					</tr>
				</thead>
				<tbody>
EOT;
			$i = 1;
			foreach($camp_category_input['camps'][$camp]['data_inputs'] as $key => $value) {
				if($value['enabled'] == "true") { $enabled[$key] = " checked"; }
				$content_array['content'] .= <<<EOT
					<tr>
						<td><input type="text" name="data_inputs[{$key}][name]" value="{$value['name']}" class="form-control input-sm" /></td>
						<td class="switch small round">
							<input type="checkbox" name="data_inputs[{$key}][enabled]" value="true"{$enabled[$key]} id="enabled_{$key}" />
							<label for="enabled_{$key}">Enabled</label>
						</td>
					</tr>
EOT;
				$i++;
			}
			$content_array['content'] .= <<<EOT
					<tr>
						<td><input type="text" name="add_input" value="{$return['add_input']}" placeholder="Add New Data Input" class="form-control input-sm" /></td>
						<td></td>
					</tr>
				</tbody>
			</table>
			<input type="hidden" name="data_change" value="1" />
			<input type="hidden" name="action" value="camp_category_input_update" />
			<input type="hidden" name="i" value="{$i}" />
			<input type="hidden" name="camp" value="{$camp}" />
			<p><button type="submit" class="button success radius">Update Data Inputs</button></p>
		</form>
EOT;

			$content_array['content'] .= gambaDebug::preformatted_arrays($camp_category_input, "camp_category_input", "Camp Category Inputs");
// 			$content_array['content'] .= "<pre>" . print_r($camp_category_input['camps'][$camp], true) . "</pre>";
			return $content_array;
		}

		// FORMS
		// Moved to /resources/views/app/settings/campcategories.blade.php
		public static function view_camps($array) {
			$content_array['page_title'] = "Camp Material Categories";
			$url = url('/');
			$camps = self::camps_list("all");
			$content_array['content'] .= gambaDirections::getDirections('camps_edit');
			$camps_with_grades = gambaGrades::camps_with_grades();
			$camps_with_locations = gambaLocations::camps_with_locations();
			$summary_report_list = gambaCosts::summary_report_list();
			if($return['row_updated'] != "") {
				$content_array['content'] .= gambaDebug::alert_box($return['name'].' successfully updated.', 'success');
			}
			if($return['add_id'] > 0) {
				$content_array['content'] .= gambaDebug::alert_box('Data successfully added.', 'success');
			}
			$content_array['content'] .= <<<EOT
		<table class="table table-bordered table-hover table-condensed table-small">
			<thead>
				<tr>
					<th></th>
					<th></th>
					<th colspan="3" class="center">Name</th>
					<th colspan="6" class="center">Material Requests</th>
				</tr>
				<tr>
					<th><a href="{$url}/settings/camp_add?action=camp_add" class="button small radius success">Add</a></th>
					<th>ID</th>
					<th>Full</th>
					<th>Short</th>
					<th>Abbreviation</th>
					<th>Locations</th>
					<th>Costing<br />Tool</th>
					<th>Day Column</th>
					<th>Standard/<br />Non-Standard Calc</th>
					<th>Theme Types</th>
					<th>Quantity Types</th>
					<th>Grades</th>
				</tr>
			</thead>
			<tbody>
EOT;
			foreach($camps as $key => $values) {
				$supply_request_locations = $values['camp_values']['request_locations'];
				$row_status = "";
				if($return['updated'] == $key || $return['add_id'] == $key) { $row_status .= 'success '; }
				if($values['camp_values']['active'] == "false") { $active_status = "color: #CCC; text-shadow: 1px 1px #000;"; $disable_button = " secondary"; } else { $disable_button = $active_status = "";  }
				$content_array['content'] .= <<<EOT
				<tr class="{$row_status}">
					<td><a data-toggle="modal" href="{$url}/settings/camp_edit?action=camp_edit&id={$key}" class="button small radius{$disable_button}">Edit</a></td>
					<td>{$key}</td>
					<td style="{$active_status}">{$values['name']}</td>
					<td>{$values['alt_name']}</td>
					<td>{$values['abbr']}</td>
					<td>
EOT;
				foreach($supply_request_locations as $id => $location) {
				$content_array['content'] .= <<<EOT
						{$camps_with_locations[$location]},
EOT;
				}
				if($values['camp_values']['day_col'] == "true") { $day_col = "Yes"; } else { $day_col = "No"; }
				if($values['camp_values']['standard'] == "true") { $standard = "Yes"; } else { $standard = "No"; }
				if($values['camp_values']['theme_type'] == "true") { $theme_type = "Yes"; } else { $theme_type = "No"; }
				if($values['camp_values']['quantity_types'] == "true") { $quantity_types = "Yes"; } else { $quantity_types = "No"; }
				if($values['camp_values']['grade_select'] == "true") {
					$grade_select = "Yes: " . $camps[$values['camp_values']['grade_select_camps']]['name'];
				} else {
					$grade_select = "No";
				}
				if($values['camp_values']['cost_analysis'] == "true") { $cost_tool = "Yes"; } else { $cost_tool = "No"; }
				$content_array['content'] .= <<<EOT
					</td>
					<td class="center">{$cost_tool}</td>
					<td class="center">{$day_col}</td>
					<td class="center">{$standard}</td>
					<td class="center">{$theme_type}</td>
					<td class="center">{$quantity_types}</td>
					<td class="center">{$grade_select}</td>
				</tr>
EOT;
			}
			$content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
			return $content_array;
		}

		// Moved to /resources/views/app/settings/campcategoriesedit.blade.php
		public static function form_data_all_camp($array) {
			$action = $array['action'];
			$url = url('/');
			$camps_with_grades = gambaGrades::camps_with_grades();
			$camps_with_locations = gambaLocations::camps_with_locations();
			$summary_report_list = gambaCosts::summary_report_list();

			$camps = self::camps_list("all");
			if($action == "camp_edit") {
				$row = Camps::select('id', 'abbr', 'name', 'alt_name', 'camp_values', 'data_inputs')->where('id', $array['id'])->first();
				$id = $row['id'];
				$camp_values = json_decode($row->camp_values, true);
				//if(($show == "" && $camp_values['active'] != "false") || $show == "all") {
					$abbr = $row['abbr'];
					$camp_name = $row['name'];
					$alt_name = $row['alt_name'];
					$array[$id]['camp_values'] = $camp_values;
					$data_inputs = json_decode($row->data_inputs, true);
					$data_change = $data_inputs['data_change'];
					if($data_change == 1) {
						$array[$id]['data_inputs'] = $data_inputs['data'];
						$array[$id]['location_input'] = $data_inputs['location_input'];
					} else {
						$array[$id]['data_inputs'] = $data_inputs;
					}
				//}
				$content_array['page_title'] = "Edit Camp Category $camp_name";
				$form_action = "data_update_camp";
				$form_button = "Save changes";
			}
			if($action == "camp_add") {
				$content_array['page_title'] = "Add Camp Category";
				$form_action = "data_add_camp";
				$form_button = "Add Camp";
			}
			if($camp_values['active'] == "true" || $camp_values['active'] == "") {
				$active_true = " checked";
			}
			if($camp_values['active'] == "false") {
				$active_false = " checked";
			}

			$content_array['content'] .= <<<EOT
				<form name="edit_camp" class="form-horizontal" action="{$url}/settings/{$form_action}" id="form-camp">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
					<div class="row">
						<div class="small-12 medium-2 large-2 columns">
							<label>Camp Active</label>
						</div>
						<div class="small-12 medium-6 large-6 end columns switch small round">
							<input type="radio" name="camp_values[active]" id="active_true" value="true"{$active_true} />
							<label for="active_true" class="radio-true">Enabled</label>
							<input type="radio" name="camp_values[active]" id="active_false" value="false"{$active_false} />
							<label for="active_false" class="radio-false">Disabled</label>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-2 large-2 columns">
							<label for="name">Long Name</label>
						</div>
						<div class="small-12 medium-6 large-6 columns">
							<input type="text" name="name" id="name" value="{$camp_name}" required />
						</div>
						<div class="small-12 medium-2 large-4 columns">
							(required)
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-2 large-2 columns">
							<label for="altname">Short Name</label>
						</div>
						<div class="small-12 medium-6 large-6 columns">
							<input type="text" name="altname" id="altname" value="{$alt_name}" required />
						</div>
						<div class="small-12 medium-2 large-4 columns">
							(required)
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-2 large-2 columns">
							<label for="abbr">Abbreviation</label>
						</div>
						<div class="small-12 medium-3 large-3 columns">
							<input type="text" name="abbr" id="abbr" value="{$abbr}" required />
						</div>
						<div class="small-12 medium-2 large-4 end columns">
							(required)
						</div>
					</div>
					<hr />

					<div class="row">
						<div class="small-12 medium-3 large-3 columns">
							<label>Camp Locations for Material Requests</label>
						</div>
						<div class="small-12 medium-9 large-9 columns switch small round">
							<ul class="small-block-grid-3">
EOT;
			foreach($camps_with_locations as $camp_id => $name) {
				if(in_array($camp_id, $camp_values['request_locations'])) { $cb_checked = " checked"; } else { $cb_checked = ""; }
				$content_array['content'] .= <<<EOT
				<li><input type="checkbox" name="camp_values[request_locations][]" id="request_locations{$camp_id}" value="{$camp_id}"{$cb_checked} /> <label for="request_locations{$camp_id}"></label>&nbsp; {$name}</li>
EOT;
			}
			if($camp_values['dli_location'] == "true") { $dli_location_true = " checked"; }
			if($camp_values['dli_location'] == "false" || $camp_values['dli_location'] == "") { $dli_location_false = " checked"; }
			if($camp_values['day_col'] == "true") { $day_col_true = " checked"; }
			if($camp_values['day_col'] == "false" || $camp_values['day_col'] == "") { $day_col_false = " checked"; }
			if($camp_values['standard'] == "true") { $standard_true = " checked"; }
			if($camp_values['standard'] == "false" || $camp_values['standard'] == "") { $standard_false = " checked"; }
			if($camp_values['resupply'] == "true" || $camp_values['resupply'] == "") { $resupply_true = " checked"; }
			if($camp_values['resupply'] == "false" || $camp_values['resupply'] == "") { $resupply_false = " checked"; }
			if($camp_values['theme_type'] == "true") { $theme_type_true = " checked"; }
			if($camp_values['theme_type'] == "false" || $camp_values['theme_type'] == "") { $theme_type_false = " checked"; }
			if($camp_values['linked_to'] == "true") { $linked_to_true = " checked"; }
			if($camp_values['linked_to'] == "false" || $camp_values['linked_to'] == "") { $linked_to_false = " checked"; }
			if($camp_values['grade_select'] == "true") { $grade_select_true = " checked"; }
			if($camp_values['grade_select'] == "false" || $camp_values['grade_select'] == "") { $grade_select_false = " checked"; }
			$content_array['content'] .= <<<EOT
							</ul>
							<span class="help-block">Select locations by camp category to appear in material requests. This helps in sharing enrollment data for packing calculations.</span>
						</div>
					</div>
					<hr />

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="camp_values[dli_location]" value="true"{$dli_location_true} id="dli_location_true" />
							<label for="dli_location_true" class="radio-true">Yes</label>
							<input type="radio" name="camp_values[dli_location]" value="false"{$dli_location_false} id="dli_location_false" />
							<label for="dli_location_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>DLI Locations</label>
							<span class="help-block">Used in Calculation of Office Material Lists. Select Yes to allow calculation for locations indicated as DLI.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="camp_values[day_col]" value="true"{$day_col_true} id="day_col_true" />
							<label for="day_col_true" class="radio-true">Yes</label>
							<input type="radio" name="camp_values[day_col]" value="false"{$day_col_false} id="day_col_false" />
							<label for="day_col_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Day Column</label>
							<span class="help-block">Select yes to appear in Supply Requests.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="camp_values[standard]" value="true"{$standard_true} id="standard_true" />
							<label for="standard_true" class="radio-true">Yes</label>
							<input type="radio" name="camp_values[standard]" value="false"{$standard_false} id="standard_false" />
							<label for="standard_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Standard/Non-Standard</label>
							<span class="help-block">Separate Out from the Supply Requests.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="camp_values[resupply]" value="true"{$resupply_true} id="resupply_true" />
							<label for="resupply_true" class="radio-true">Yes</label>
							<input type="radio" name="camp_values[resupply]" value="false"{$resupply_false} id="resupply_false" />
							<label for="resupply_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Resupply</label>
							<span class="help-block">Set to No to exclude from Resupply Material List.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="camp_values[theme_type]" value="true"{$theme_type_true} id="theme_type_true" />
							<label for="theme_type_true" class="radio-true">Yes</label>
							<input type="radio" name="camp_values[theme_type]" value="false"{$theme_type_false} id="theme_type_false" />
							<label for="theme_type_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Theme Types</label>
							<span class="help-block">Set in Themes and Activities. Separate Out from the Supply Requests.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="camp_values[linked_to]" value="true"{$linked_to_true} id="linked_to_true" />
							<label for="linked_to_true" class="radio-true">Yes</label>
							<input type="radio" name="camp_values[linked_to]" value="false"{$linked_to_false} id="linked_to_false" />
							<label for="linked_to_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Theme Links</label>
							<span class="help-block">Set in Themes and Activities. Used in Enrollment to link Themes that will share the same numbers.</span>
						</div>
					</div>
					<hr />
					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="camp_values[grade_select]" value="true"{$grade_select_true} id="grades_select_true" />
							<label for="grades_select_true" class="radio-true">Yes</label>
							<input type="radio" name="camp_values[grade_select]" value="false"{$grade_select_false} id="grade_select_false" />
							<label for="grade_select_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Grade Select</label>
							<span class="help-block">Set in Themes and Activities. Used in Enrollment to link Themes that will share the same numbers.</span>
						</div>
					</div>
					<script type="text/javascript">
 						$(document).ready(function(){
 							if($('#grades_select_true').prop('checked')){
						        $('.grades_from_category').show();
							} else {
 								$('.grades_from_category').hide();
							}
							$('#form-camp').change(function() {
								$('.grades_from_category').hide();
								if($('#grades_select_true').prop('checked')){
							        $('.grades_from_category').show();
 							    }
 							});
						});
					</script>

					<div class="row grades_from_category">
						<div class="small-12 medium-3 large-3 columns">
							<label for="camp_type">Grades From Other Category</label>
						</div>
						<div class="small-12 medium-6 large-6 end columns">
							<select name="camp_values[grade_select_camps]" id="theme_type">
								<option value="">-------------------</option>
EOT;
			foreach($camps_with_grades as $camp_id => $grade_values) {
				$grade_select_camps = ""; if($camp_id == $camp_values['grade_select_camps']) { $grade_select_camps .= " selected"; }
				$content_array['content'] .= <<<EOT
										<option value="{$camp_id}"{$grade_select_camps}>{$grade_values}</option>
EOT;
			}
			if($camp_values['quantity_types'] == "true") { $quantity_types_true =  " checked"; }
			if($camp_values['quantity_types'] == "false" || $camp_values['quantity_types'] == "") { $quantity_types_false = " checked"; }
			$content_array['content'] .= <<<EOT
							</select>
							<span class="help-block">Optional: Set in Themes and Activities. Used in Enrollment and Material Requests.</span>
						</div>
					</div>
					<hr />
					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="camp_values[quantity_types]" value="true"{$quantity_types_true} id="quantity_types_true" />
							<label for="quantity_types_true" class="radio-true">Yes</label>
							<input type="radio" name="camp_values[quantity_types]" value="false"{$quantity_types_false} id="quantity_types_false" />
							<label for="quantity_types_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Quantity Types</label>
							<span class="help-block">Select yes to populate this camp.</span>
						</div>
					</div>
 					<script type="text/javascript">
 							$(document).ready(function(){
 								if($('#quantity_types_true').prop('checked')){
							        $('.category_themes').show();
								} else {
 									$('.category_themes').hide();
								}
								$('#form-camp').change(function() {
									$('.category_themes').hide();
									if($('#quantity_types_true').prop('checked')){
								        $('.category_themes').show();
 								    }
 								});
 							});
					</script>

					<div class="row category_themes">
						<div class="small-12 medium-3 large-3 columns">
							<label>Category Themes</label>
						</div>
						<div class="small-12 medium-9 large-9 columns switch small round">
							<span class="help-block">Select the camp category to share the quantity types in Material Requests.</span>
							<ul class="small-block-grid-2">
EOT;
			foreach($camps as $camp_id => $camp_category_values) {
				$quantity_types_shared = ""; if(in_array($camp_id, $camp_values['quantity_types_shared'])) { $quantity_types_shared .= " checked"; }
				$content_array['content'] .= <<<EOT
								<li><input type="checkbox" name="camp_values[quantity_types_shared][]" id="category_qt{$camp_id}" value="{$camp_id}"{$quantity_types_shared} /> <label for="category_qt{$camp_id}"></label> &nbsp;{$camp_category_values['alt_name']}</li>
EOT;
			}
			if($camp_values['cost_analysis'] == "true") { $cost_analysis_true = " checked"; }
			if($camp_values['cost_analysis'] != "true" || $camp_values['cost_analysis'] == "") { $cost_analysis_false = " checked"; }
			$content_array['content'] .= <<<EOT
							</ul>
						</div>
					</div>
					<p>&nbsp;</p>
					<p><button type="submit" class="button small radius">{$form_button}</button></p>
					<input type="hidden" name="action" value="{$form_action}" />
					<input type="hidden" name="camp_id" value="{$id}" />
					<input type="hidden" name="camp_values[cost_analysis_summary]" value="{$camp_values['cost_analysis_summary']}" />
					<input type="hidden" name="camp_values[cost_analysis]" value="{$camp_values['cost_analysis']}" />
				</form>
EOT;
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($camps, "camps_array", "Camps");
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($camps_with_grades, "camps_with_grades", "Camps with Grades");
			return $content_array;
// 			echo "<pre>"; print_r($camps); echo "</pre>";
		}

	}
