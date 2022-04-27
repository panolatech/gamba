<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;
	
	use App\Models\Config;

	use App\Gamba\gambaDebug;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaNavigation;
	use App\Gamba\gambaStatus;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaUsers;
	
	class gambaAdmin {
		/**
		 * Access the content of the 'config' table
		 */
		private static function config() {
			$config = Config::select('id', 'description', 'field', 'value', 'editable')->orderBy('id')->get();
			if($config->count() > 0) {
				foreach($config as $key => $row) {
					$id = $row['id'];
					$array[$id]['description'] = $row['description'];
					$array[$id]['field'] = $row['field'];
					$array[$id]['value'] = $row['value'];
					$array[$id]['editable'] = $row['editable'];
				}
			}
			return $array;
		}

		public static function config_val($config_field) {
			$row = Config::select('value')->where('field', $config_field)->first();
			return $row['value'];
		}
		
		/**
		 * Inputs the results of the form and Updates the configuration table
		 * @param array $array
		 */
		public static function data_update_config($array) {
			$return['function'] = "config_update";
			if($array['add_field'] != "" && $array['add_description'] != "" && $array['add_value'] != "") {
				$return['add_id'] = Config::insertGetId([
					'description' => $array['add_description'], 
					'field' => $array['add_field'], 
					'value' => $array['add_value'],
					'editable' => $array['add_editable']
				]);
			} else {
				if($array['add_field'] == "" && $array['add_description'] == "" && $array['add_value'] == "") {
					$return['add_error'] = 0;
				} else {
					$return['add_description'] = $array['add_description'];
					$return['add_field'] = $array['add_field'];
					$return['add_value'] = $array['add_value'];
					$return['add_editable'] = $array['add_editable'];
					$return['add_error'] = 1;
				}
			}
			foreach($array['description_edit'] as $key => $value_data) {
				$return['data'] = "foreach";
				if(($array['description_edit'][$key] != $array['description_orig'][$key]) || ($array['value_edit'][$key] != $array['value_orig'][$key])) {
					
					if($array['description_edit'][$key] != $array['description_orig'][$key]) {
						$config_desc = Config::where('id', $key)->update([
								'description' => $array['description_edit'][$key]
							]);
						$return['updated'][$key] = 1;
						$return['row_updated'] = 1;
					}
					if($array['value_edit'][$key] != $array['value_orig'][$key]) {
						$config_value = Config::where('id', $key)->update([
								'value' => $array['value_edit'][$key]
							]);
						$return['updated'][$key] = 1;
						$return['row_updated'] = 1;
					}
				}
			}
			return base64_encode(json_encode($return));
		}

		

		public static function dashboard_year_nav($camp, $term) {
			$terms = gambaTerm::terms();
			$current_term = gambaTerm::year_by_status('C');
			if($term == "") { $term = $current_term; }
			$url = url('/');
			$content .= <<<EOT
			<button data-dropdown="dropyearnav" aria-controls="dropyearnav" aria-expanded="false" class="button dropdown small radius">Select Term {$term}</button><br />
			<ul id="dropyearnav" data-dropdown-content class="f-dropdown" aria-hidden="true">
EOT;
			foreach($terms as $term => $value) {
				$content .= <<<EOT
				<li><a href="{$url}/settings?term={$term}" title="{$value['year_status']}">{$term}</a></li>
EOT;
			}
			$content .= <<<EOT
			</ul>
EOT;
			return $content;
		}
		
		
		public static function dashboard($camp, $term) {
			$url = url('/');
			$current_term = gambaTerm::year_by_status('C');
			if($term == "") { $term = $current_term; }
			$gamba_name = config('gamba.gamba_name');
			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$content_array['page_title'] = "Admin Dashboard for " . $gamba_name;
			$dashboard_year_nav = self::dashboard_year_nav($camp, $term);
			$dashboard_getDirections = gambaDirections::getDirections('dashboard');
			$camps_with_themes = gambaStatus::camps_with_themes($term);
			$num_themes = gambaStatus::num_themes($term);
			$num_activities = gambaStatus::num_activities($term);
			$num_supplies = gambaStatus::num_supplies($term);
			$campg_themes_linked = gambaStatus::campg_themes_linked($term);
			$num_parts = gambaStatus::num_parts();
			$num_products = gambaStatus::num_products();
			$num_customers = gambaStatus::num_customers();
			$basic_calc_status = config('gamba.basic_calc_status');
			if($basic_calc_status == 1) { $basic_calc_status = "Enabled"; } else { $basic_calc_status = "Disabled"; }
			$debug = config('gamba.debug');
			if($debug == 1) { $debug = "Enabled"; } else { $debug = "Disabled"; }
			$num_vendors = gambaStatus::num_vendors();
			$database = database;
			$tbpre = tbpre;
			$log_path = config('gamba.log_path');
			$admin_email = config('gamba.admin_email');
			$content_array['content'] .= <<<EOT
			<div class="row">
				<div class="small-12 medium-3 large-3 columns">
					{$dashboard_year_nav}
				</div>
				<div class="small-12 medium-9 large-9 columns">
					{$dashboard_getDirections}
				</div>
			</div>	
		<table role="grid">
			<thead>
				<tr>
					<th>Item</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Camps With Themes this Term</td>
					<td>{$camps_with_themes}</td>
				</tr>
				<tr>
					<td>Number of Themes this Term</td>
					<td>{$num_themes}</td>
				</tr>
				<tr>
					<td>Number of Activities this Term</td>
					<td>{$num_activities}</td>
				</tr>
				<tr>
					<td>Number of CW Supply Requests this Term</td>
					<td>{$num_supplies}</td>
				</tr>
				<tr>
					<td>Camp G Themes Linked</td>
					<td>{$campg_themes_linked}</td>
				</tr>
				<tr>
					<td>Number of GAMBA/Fishbowl Parts</td>
					<td>{$num_parts}</td>
				</tr>
				<tr>
					<td>Number of Fishbowl Products</td>
					<td>{$num_products}</td>
				</tr>
				<tr>
					<td>Number of Fishbowl Customers</td>
					<td>{$num_customers}</td>
				</tr>
				<tr>
					<td>Basic Calculation Results</td>
					<td>{$basic_calc_status}</td>
				</tr>
				<tr>
					<td>Site Admin Debug</td>
					<td>{$debug}</td>
				</tr>
				<tr>
					<td>Number of Fishbowl Vendors</td>
					<td>{$num_vendors}</td>
				</tr>
				<tr>
					<td>Log Path</td>
					<td>{$log_path}</td>
				</tr>
				<tr>
					<td>Admin Email</td>
					<td>{$admin_email}</td>
				</tr>
			</tbody>
		</table>
EOT;
			return $content_array;
		}
		// FORMS
		


		/**
		 * Displays the edit form for the configuration
		 */
		public static function form_data_all_config($return) {
			$url = url('/');
			
			$content_array['page_title'] = "Configuration";
			$config = self::config();
			if($return['add_error'] == 1) {
				$content_array['content'] .= Debug::alert_box('Please check your entry and try again.', 'warning');
			}
			if($return['row_updated'] == 1) {
				$content_array['content'] .= Debug::alert_box('Data successfully updated.', 'success');
			}
			if($return['add_id'] > 0) {
				$content_array['content'] .= Debug::alert_box('Data successfully added.', 'success');
			}
			$content_array['content'] .= gambaDirections::getDirections('configuration');
			$content_array['content'] .= <<<EOT
			<form method="post" action="{$url}/settings/update_config" name="update_config" class="form">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
				<table class="table-striped table-bordered table-hover table-condensed table-small">
					<thead>
						<tr>
							<th>Field</th>
							<th>Description</th>
							<th>Values</th>
							<th>Editable</th>
						</tr>
					</thead>
					<tbody>
EOT;
					foreach($config as $key => $value) {
						if($value['editable'] == "true") {
							if($return->updated->$key == 1 || $return->add_id == $key) { $success_display = ' class="success"'; } else { $success_display = ""; }
						$content_array['content'] .= <<<EOT
						<tr{$success_display}>
							<td>{$value['field']}</td>
							<td>
EOT;
						if($value['editable'] == "true") { 
							$content_array['content'] .= <<<EOT
							<input type="text" name="description_edit[{$key}]" value="{$value['description']}" />
							<input type="hidden" name="description_orig[{$key}]" value="{$value['description']}" />
EOT;
							} else { 
							$content_array['content'] .= $value['description']; 
							}
							$content_array['content'] .= "</td>\n<td>";
							if($value['editable'] == "true") { 
								$content_array['content'] .= <<<EOT
								<input type="text" name="value_edit[{$key}]" value="{$value['value']}" class="min-width: 100px; max-width: 100px;" />
								<input type="hidden" name="value_orig[{$key}]" value="{$value['value']}" />
EOT;
							} else { 
								$content_array['content'] .= $value['value']; 
							} 
							$content_array['content'] .= <<<EOT
							</td>
							<td colspan="2"></td>
						</tr>
EOT;
						}
					}
					if($return->add_error == 1) { $danger_display = ' class="danger"'; } else { $danger_display = ""; }
					$content_array['content'] .= <<<EOT
						<tr{$danger_display}>
							<td><input type="text" name="add_field" value="{$return->add_field}" placeholder="Add Field" /></td>
							<td><input type="text" name="add_description" value="{$return->add_description}" placeholder="Add Description" style="width:500px;" /></td>
							<td><input type="text" name="add_value" value="{$return->add_value}" placeholder="Add Value" /></td>
							<td class="switch small round">
								<input type="radio" name="add_editable" id="add_editable1" value="true" checked />
								<label for="add_editable1" class="radio-true">Yes</label>
							</td>
							<td class="switch small round">
								<input type="radio" name="add_editable" id="add_editable2" value="false" />
								<label for="add_editable2" class="radio-false">No</label>
							</td>
						</tr>
					</tbody>
				</table>
				<input type="hidden" name="action" value="update_config" />
				<button type="submit" class="button small radius">Update</button>
			</form>
EOT;
			$content_array['content'] .= gambaDebug::preformatted_arrays($config, 'Configurations', 'configuration');
			return $content_array;
		}
		
	}
