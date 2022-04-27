<?php

	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\Config;
	use App\Models\Seasons;

	use App\Gamba\gambaUsers;

	class gambaTerm {
		
	    public static function terms() {
	        $query = Seasons::select('year', 'status', 'json_array')->orderBy('year', 'desc')->get();
	        foreach($query as $key => $value) {
	            $year = $value['year'];
	            $return_array[$year]['year_status'] = $value['status'];
	            $json_array = json_decode($value['json_array'], true);
	            $return_array[$year]['gsq'] = $json_array['gsq'];
	            $return_array[$year]['access'] = $json_array['access'];
// 				$return_array[$year]['campg_themes_linked'] = $json_array['campg_themes_linked'];
	            $return_array[$year]['campg_packper'] = $json_array['campg_packper'];
	            $return_array[$year]['campg_enroll_rotations'] = $json_array['campg_enroll_rotations'];
	        }
	        // End Resorting
	        return $return_array;
		}

		public static function year_by_status($status) {
// 			$years = self::terms();
//			foreach($years as $year => $value) {
// 				if($value['year_status'] == $status) {
// 					return $year; exit;
// 				}
// 			}
		    $query = Seasons::select('year')->where('status', 'C')->first();
		    $year = $query['year'];
		    return $year;
		}

		public static function year_status() {
			$terms = Config::select('value')->where('field', 'year_status')->first();
			$array = json_decode($terms['value'], true);
			return $array;
		}

		// Moved to SettingsSeasonsController.php
		public static function year_update($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$json_array = $array['update'];
			if($array['add_year'] != "" || $array['add_gsq'] != "") {
				$year = $array['add_year'];
				$json_array[$year]['year_status'] = $array['add_year_status'];
				$json_array[$year]['gsq'] = $array['add_gsq'];
				$json_array[$year]['access'] = $array['add_access'];
// 				$json_array[$year]['campg_themes_linked'] = $array['add_campg_themes_linked'];
				$json_array[$year]['campg_packper'] = $array['add_campg_packper'];
				$json_array[$year]['campg_enroll_rotations'] = $array['add_campg_enroll_rotations'];
				$return['add_id'] = 1;
			}
// 			echo "<pre>"; print_r($json_array); echo "</pre>";
// 			exit; die();
			$json_encoded_array = json_encode($json_array);
// 			echo "<p>$json_encoded_array</p>";
// 			exit; die();
			$update = Config::where('field', 'year')->update(['value' => $json_encoded_array]);
			$return['updated'][$key] = 1;
			$return['row_updated'] = 1;
			return base64_encode(json_encode($return));
		}

		// Moved to SettingsSeasonsController.php
		public static function year_delete($array) {
			// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$row = Config::select('value')->where('field', 'year')->first();
			$year_array = json_decode($row->value, true);
			// 			echo "<pre>"; print_r($year_array); echo "</pre>"; exit; die();
			foreach($year_array as $key => $value) {
				if($array['year'] != $key) {
					$json_array[$key]['year_status'] = $value['year_status'];
					$json_array[$key]['gsq'] = $value['gsq'];
					$json_array[$key]['access'] = $value['access'];
				}
			}
			// 			echo "<pre>"; print_r($json_array); echo "</pre>"; //exit; die();
			$years = json_encode($json_array);
			$update = Config::where('field', 'year')->update(['value' => $years]);
			// 			echo($years); exit; die();
		}

		// FORM

		// Moved to SettingsSeasonsController.php
		public static function view_terms($return) {
			$url = url('/');
			$return = json_decode(base64_decode($return), true);
			$content_array['page_title'] = "Seasons";
			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$camps = gambaCampCategories::camps_list("all");
			$terms = self::terms();
			$year_status = self::year_status();
			// 			echo "<p>$terms</p>";
			// 			echo "<pre>"; print_r($terms); echo "</pre>";
			if($return['add_error'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box('Please check your entry and try again.', 'warning');
			}
			if($return['row_updated'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box('Term successfully updated.', 'success');
			}
			if($return['add_id'] > 0) {
				$content_array['content'] .= gambaDebug::alert_box('Term successfully added.', 'success');
			}
			$content_array['content'] .= gambaDirections::getDirections('terms_edit');
			$content_array['content'] .= <<<EOT

		<form method="post" name="terms-form" action="{$url}/settings/update_years" class="form">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
			<table>
				<thead>
					<tr>
						<th style="width: 80px !important;">Season</th>
						<th style="width: 200px !important;">Status</th>
						<th style="width: 80px !important;">GSQ Training Rotations</th>
						<th style="max-width: 140px !important;">Curriculum Writers Access</th>
						<!-- <th>Camp G Themes Linked</th> -->
						<th style="max-width: 140px !important;">Camp G Hide Pack %</th>
						<th style="max-width: 140px !important;">Camp G Per Theme Avg Weekly Enrollment and Rotations</th>
					</tr>
				</thead>
				<tbody>
EOT;
			// Term Updates
			foreach($terms as $year => $values) {
				$content_array['content'] .= <<<EOT
					<tr>
						<td>
							<input type="text" value="{$year}" id="disabledInput" disabled />
						</td>
						<td>
							<select name="update[{$year}][year_status]">
EOT;
				foreach($year_status as $key => $value) {
					$content_array['content'] .= "<option value='".$key."'";
					if($key == $values['year_status']) { $content_array['content'] .= ' selected'; }
					$content_array['content'] .= ">".$value."</option>";
				}
				$content_array['content'] .= <<<EOT
							</select>
						</td>
EOT;
				$term_access_true = ""; if($values['access'] == 'true') { $term_access_true = ' checked'; }
				$term_access_false = ""; if($values['access'] == 'false') { $term_access_false = ' checked'; }
				$content_array['content'] .= <<<EOT
						<td>
							<input type="text" name="update[{$year}][gsq]" value="{$values['gsq']}" placeholder="Number Rotations" />
						</td>
						<td class="switch small round center">
							<input type="radio" name="update[{$year}][access]" id="access_true_{$year}" value="true"{$term_access_true} />
							<label for="access_true_{$year}" class="radio-true">Yes</label>
							<input type="radio" name="update[{$year}][access]" id="access_false_{$year}" value="false"{$term_access_false} />
							<label for="access_false_{$year}" class="radio-false">No</label>
						</td>
EOT;

				/* $campg_themes_linked_checked = ""; if($values['campg_themes_linked'] == "true") { $campg_themes_linked_checked = " checked"; }
				$content_array['content'] .= <<<EOT
						<td class="switch small round center">
							<input type="checkbox" name="update[{$year}][campg_themes_linked]" value="true"{$campg_themes_linked_checked} id="CGThemesLinkedCheckboxSwitch{$year}" />
							<label for="CGThemesLinkedCheckboxSwitch{$year}"></label>
						</td>
EOT; */

				$campg_packper_checked = ""; if($values['campg_packper'] == "true") { $campg_packper_checked = " checked"; }
				$content_array['content'] .= <<<EOT
						<td class="switch small round center">
							<input type="checkbox" name="update[{$year}][campg_packper]" value="true"{$campg_packper_checked} id="CGPackPerCheckboxSwitch{$year}" />
							<label for="CGPackPerCheckboxSwitch{$year}"></label>
						</td>
EOT;
			//campg_enroll_rotations
				$campg_enroll_rotations_checked = ""; if($values['campg_enroll_rotations'] == "true") { $campg_enroll_rotations_checked = " checked"; }
				$content_array['content'] .= <<<EOT
						<td class="switch small round center">
							<input type="checkbox" name="update[{$year}][campg_enroll_rotations]" value="true"{$campg_enroll_rotations_checked} id="CGEnrollRotationsCheckboxSwitch{$year}" />
							<label for="CGEnrollRotationsCheckboxSwitch{$year}"></label>
						</td>
EOT;
				$content_array['content'] .= <<<EOT
					</tr>
EOT;
			}
			// Add Term
			$content_array['content'] .= '<tr'; if($return['add_error'] == 1) { $content_array['content'] .= ' class="danger"'; } $content_array['content'] .= ">\n";
			$content_array['content'] .= '<td><input type="text" name="add_year" value="'.$return['add_year'].'" placeholder="Add Year" /></td>';
			$content_array['content'] .= '<td><select name="add_year_status">';
			foreach($year_status as $key => $value) {
				$content_array['content'] .= "<option value='".$key."'>".$value."</option>";
			}
			$content_array['content'] .= <<<EOT
							</select></td>
						<td><input type="text" name="add_gsq" value="{$return['add_gsq']}" placeholder="Number Rotations" /></td>
						<td class="switch small round center">
							<input type="radio" name="add_access" id="add_access1" title="Yes" value="true" checked />
							<label for="add_access1" class="radio-true">Yes</label>
							<input type="radio" name="add_access" id="add_access2" title="No" value="false" />
							<label for="add_access2" class="radio-false">No</label>
						</td>
EOT;

				/* $content_array['content'] .= <<<EOT
						<td class="switch small round center">
							<input type="checkbox" name="add_campg_themes_linked" value="true" id="CGThemesLinkedCheckboxSwitch" />
							<label for="CGThemesLinkedCheckboxSwitch"></label>
						</td>
EOT; */
				$content_array['content'] .= <<<EOT
						<td class="switch small round center">
							<input type="checkbox" name="add_campg_packper" value="true" id="CGPackPerCheckboxSwitch" />
							<label for="CGPackPerCheckboxSwitch"></label>
						</td>
EOT;
				$content_array['content'] .= <<<EOT
						<td class="switch small round center">
							<input type="checkbox" name="add_campg_enroll_rotations" value="true" id="CGEnrollRotationsCheckboxSwitch" />
							<label for="CGEnrollRotationsCheckboxSwitch"></label>
						</td>
EOT;
				$content_array['content'] .= <<<EOT
					</tr>
				</tbody>
			</table>
			<input type="hidden" name="action" value="update_years" />
			<button type="submit" class="button small radius">Update</button>
		</form>
EOT;
			return $content_array;
// 			echo "<pre>"; print_r($terms); echo "</pre>"; exit; die();
// 			gambaDebug::preformatted_arrays($terms, "terms", "Terms");
		}
	}

