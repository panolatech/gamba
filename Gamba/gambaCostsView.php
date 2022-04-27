<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\SupplyLists;
	use App\Models\Config;


	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaCosts;
	use App\Gamba\gambaCostsNav;
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaGrades;
	use App\Gamba\gambaParts;
	use App\Gamba\gambaQuantityTypes;
	use App\Gamba\gambaSupplies;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaThemes;
	use App\Gamba\gambaUsers;

	class gambaCostsView {

		public static function theme_data_enrollment() {
			$array = array(
				1 => "true",	// Camp G
				2 => "true",	// GSQ
				3 => "false",	// Camp G Staff
				4 => "true",	// Camp G Extension Materials
				5 => "true",	// Camp Galileo Extended Care
				6 => "false",	// Camp Galileo Outdoors
				7 => "false",	// Office
				9 => "false", 	// Partner Sites
				10 => "true",	// Galileo Summer Quest Extended Care
				13 => "false",	// Galileo Summer Quest Staff Curriculum Training
				14 => "false",	// Camp Director Start-up List
				15 => "false",	// Kit Materials
				16 => "false",	// Chabot
				17 => "true",	// Camp Galileo Basic Supplies
				18 => "false",	// T-Shirts and Retention Items
				19 => "false",	// Tech
			);
			return $array;
		}

		public static function view_themes_setup($array) {
			$url = url('/');

			if($array['camp'] == "") { $camp = 1; } else { $camp = $array['camp']; }
			if($array['term'] == "") { $term = gambaTerm::year_by_status('C'); } else { $term = $array['term']; }
			$camps = gambaCampCategories::camps_list();
			$summary_report_list = gambaCosts::summary_report_list();
			$themes = gambaThemes::themes_by_camp($camp, $term);
			$theme_year_nav = gambaCostsNav::theme_year_nav($camp, $term);
			$theme_camp_nav = gambaCostsNav::theme_camp_nav($camp, $term);
			$campg_theme_numbering_array = gambaThemes::campg_theme_numbering_array();
			if($camps[$camp]['camp_values']['cost_enrollment_data'] == "true") {
				$disable_enrollment = " disabled";
			}
			$content_array['page_title'] = "Theme Setup - Material Cost Summaries";
			$content_array['content'] .= <<<EOT
				{$theme_year_nav}

				{$theme_camp_nav}

				<div class="panel radius directions">
					<p><strong>Directions:</strong> On this page you can set the Total Rotations and the Budget Cost Per Camper. Set the budget for activies under this theme for Material List Cost Analysis. Do not include &#36; dollar signs for Costs.</p>
				</div>
				<p>This camp category is using the {$summary_report_list[$camps[$camp]['camp_values']['cost_analysis_summary']]} for Material Cost Summaries.</p>
EOT;
			if($array['success'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box("Your Theme Data is Successfully Updated.", 'success');
			}


				if(is_array($themes)) {
					$content_array['content'] .= <<<EOT
			<form method="post" action="{$url}/costs/themes_update" name="update">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
				<p><input type="submit" name="submit" value="Update Theme Data" class="button small radius" /></p>
				<table class="table table-small table-striped" id="themes">
					<thead>
						<tr>
							<th>Theme</th>
EOT;
					// Camp G Format Chosen in Camp Categories
					if($camps[$camp]['camp_values']['cost_analysis_summary'] == "campg") {
					$content_array['content'] .= <<<EOT
							<th>Budget # of Activities</th>
							<th>Budget Cost Per Camper</th>
							<th>Year 1 Enrollment</th>
EOT;
					}
					// GSQ Format Chosen in Camp Categories
					if($camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
					$content_array['content'] .= <<<EOT
							<th>Year 1 Total Rotations</th>
							<th>Year 1 Budget Cost Per Camper</th>
							<th>Year 1 Enrollment</th>
							<th>Year 2 Total Rotations</th>
							<th>Year 2 Budget Cost Per Camper</th>
							<th>Year 2 Enrollment</th>
EOT;
					}
					$content_array['content'] .= <<<EOT
						</tr>
					</thead>
					<tbody>
EOT;
					foreach($themes as $theme_id => $theme_val) {
						if($theme_val['theme_options']['this_camp'] == "true" || ($theme_val['theme_options']['this_camp'] == "false" && in_array($camp, $theme_val['theme_options']['category_themes']))) {
							if(is_int($theme_id)) {
								$row_success = ""; if($return->updated->$theme_id == 1 || $return->add_id == $theme_id) { $row_success = ' success'; }
								$theme_edit_disable = ""; if($theme_val['theme_edit'] == "false" && $theme_val['camp'] != $theme_val['theme_camp']) { $theme_edit_disable = ' disabled'; }
								$activity_add_disable = ""; if($theme_val['theme_edit'] == "false" && $theme_val['camp'] != $theme_val['theme_camp']) { $activity_add_disable = ' disabled'; }
								// Theme Number
								$theme_number = ""; if($theme_val['theme_options']['theme_number'] > 0) {
									$theme_number_as_int = intval($theme_val['theme_options']['theme_number']);
									$theme_number = <<<EOT
									<span data-tooltip aria-haspopup="true" classo"has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="{$campg_theme_numbering_array[$theme_number_as_int]}">[{$theme_val['theme_options']['theme_number']}]</span>
EOT;
								}
								$content_array['content'] .= <<<EOT
						<tr class="row-theme{$row_success}">
							<td class="theme-name">{$theme_val['name']}<input type="hidden" name="theme_values[{$theme_id}][name]" value="{$theme_val['name']}" />{$theme_number}</td>
EOT;
								// Camp G Format Chosen in Camp Categories
								if($camps[$camp]['camp_values']['cost_analysis_summary'] == "campg") {
// 									if($theme_val['budget']['theme_budget_activities'] == "") {
// 										$theme_val['budget']['theme_budget_activities'] = rand(10, 20);
// 									}
// 									if($theme_val['budget']['theme_budget_per_camper'] == "" || $theme_val['budget']['theme_budget_per_camper'] == 0) {
// 										$decimal = rand(0, 99);
// 										$theme_val['budget']['theme_budget_per_camper'] = number_format(rand(2, 5) . "." . $decimal, 2);
// 									}
// 									if($theme_val['budget']['theme_budget_yr1_enrollment'] == "") {
// 										$theme_val['budget']['theme_budget_yr1_enrollment'] = rand(500, 1000);
// 									}
// 									if($theme_val['budget']['theme_budget_yr2_enrollment'] == "") {
// 										$theme_val['budget']['theme_budget_yr2_enrollment'] = rand(500, 1000);
// 									}
									$content_array['content'] .= <<<EOT
							<td><input type="text" name="theme_values[{$theme_id}][budget][theme_budget_activities]" value="{$theme_val['budget']['theme_budget_activities']}" /></td>
							<td><input type="text" name="theme_values[{$theme_id}][budget][theme_budget_per_camper]" value="{$theme_val['budget']['theme_budget_per_camper']}" /></td>
							<td><input type="text" name="theme_values[{$theme_id}][budget][theme_budget_yr1_enrollment]" value="{$theme_val['budget']['theme_budget_yr1_enrollment']}"{$disable_enrollment} /></td>
EOT;
								}

								// GSQ Format Chosen in Camp Categories
								if($camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
// 									if($theme_val['budget']['theme_budget_rotations_yr1'] == "") {
// 										$theme_val['budget']['theme_budget_rotations_yr1'] = rand(10, 20);
// 									}
// 									if($theme_val['budget']['theme_budget_rotations_yr2'] == "") {
// 										$theme_val['budget']['theme_budget_rotations_yr2'] = rand(10, 20);
// 									}
// 									if($theme_val['budget']['theme_budget_per_camper_yr1'] == "" || $theme_val['budget']['theme_budget_per_camper_yr1'] == 0) {
// 										$decimal = rand(0, 99);
// 										$theme_val['budget']['theme_budget_per_camper_yr1'] = number_format(rand(2, 5) . "." . $decimal, 2);
// 									}
// 									if($theme_val['budget']['theme_budget_per_camper_yr2'] == "" || $theme_val['budget']['theme_budget_per_camper_yr2'] == 0) {
// 										$decimal = rand(0, 99);
// 										$theme_val['budget']['theme_budget_per_camper_yr2'] = number_format(rand(2, 5) . "." . $decimal, 2);
// 									}
// 									if($theme_val['budget']['theme_budget_yr1_enrollment'] == "") {
// 										$theme_val['budget']['theme_budget_yr1_enrollment'] = rand(500, 1000);
// 									}
// 									if($theme_val['budget']['theme_budget_yr2_enrollment'] == "") {
// 										$theme_val['budget']['theme_budget_yr2_enrollment'] = rand(500, 1000);
// 									}

								$content_array['content'] .= <<<EOT
							<td><input type="text" name="theme_values[{$theme_id}][budget][theme_budget_rotations_yr1]" value="{$theme_val['budget']['theme_budget_rotations_yr1']}" /></td>
							<td><input type="text" name="theme_values[{$theme_id}][budget][theme_budget_per_camper_yr1]" value="{$theme_val['budget']['theme_budget_per_camper_yr1']}" /></td>
							<td><input type="text" name="theme_values[{$theme_id}][budget][theme_budget_yr1_enrollment]" value="{$theme_val['budget']['theme_budget_yr1_enrollment']}"{$disable_enrollment} /></td>
							<td><input type="text" name="theme_values[{$theme_id}][budget][theme_budget_rotations_yr2]" value="{$theme_val['budget']['theme_budget_rotations_yr2']}" /></td>
							<td><input type="text" name="theme_values[{$theme_id}][budget][theme_budget_per_camper_yr2]" value="{$theme_val['budget']['theme_budget_per_camper_yr2']}" /></td>
							<td><input type="text" name="theme_values[{$theme_id}][budget][theme_budget_yr2_enrollment]" value="{$theme_val['budget']['theme_budget_yr2_enrollment']}"{$disable_enrollment} /></td>
EOT;
								}

								$content_array['content'] .= <<<EOT
						</tr>
EOT;
							}
						}
				}
				$content_array['content'] .= <<<EOT

					</tbody>
				</table>
				<input type="hidden" name="action" value="themes_update" />
				<input type="hidden" name="camp" value="{$camp}" />
				<input type="hidden" name="term" value="{$term}" />
				<p><input type="submit" name="submit" value="Update Theme Data" class="button small radius" /></p>
			</form>
EOT;
			} else {
				$content_array['content'] .= '<div class="alert alert-info">There are no themes for this Camp and Year.</div>';
			}
			
// 			$content_array['content'] .= "<pre>" . print_r($themes, true) . "</pre>";
// 			$content_array['content'] .= "<pre>" . print_r($camps, true) . "</pre>";
			return $content_array;
		}

		public static function view_quantity_type_setup($array) {
			$url = url('/');
			// NOTE: Go back and show Camp G Enrollment in place of inputs

			if($camp == "") { $camp = 1; }
			$camp = $array['camp'];
			$current_term = gambaTerm::year_by_status('C');
			$term = $array['term'];
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$quantity_types = gambaQuantityTypes::camp_quantity_types();
			$camps = gambaCampCategories::camps_list();
			$check_quantity_type_values = gambaCosts::check_quantity_type_values($term, $camp);
			$date = date("m/d g:ia");
			if($check_quantity_type_values == "true") {
				$copy_previous = <<<EOT
				<a href="{$url}/costs/copy_previous_quantity_types?camp={$camp}&term={$term}" class="button secondary radius small">Copy Previous Year Data</a>
EOT;
			}
			$content_array['page_title'] = "Quantity Type Setup - Material Cost Summaries";
			if($camp == "") { $camp = 1; }
// 			$content_array['content'] = Directions::getDirections('calculation_setup');
			$content_array['content'] .= <<<EOT
		<div class="row">
			<div class="large-12 medium-12 small-12 columns">
EOT;
			$content_array['content'] .= gambaCostsNav::quantity_types_year_nav($camp, $term);
			$content_array['content'] .= gambaCostsNav::quantity_types_camp_nav($camp, $term);
			if($array['update'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box("Your Quantity Types Have Been Successfully Updated - {$date}", 'success');
			}
			$content_array['content'] .= <<<EOT
			</div>
		</div>
		<div class="directions">
			<p><strong>Directions:</strong> The inputs and switches below control the calculation for each quantity type. Turning on a Non-Consumable or Consumable will allow division by Rotations, Theme Weeks, and Campers. Turning on or off under each input will allow division by these inputted values.</p>
		</div>
		<div class="row">
			<div class="large-12 medium-12 small-12 columns">
				<form method="post" action="{$url}/costs/quantity_types_update" name="update">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
					<p><button type="submit" name="submit" class="button primary radius small">Update Quantity Types</button> {$copy_previous}</p>
					<table class="table table-small table-double-row">
						<thead>
							<tr>
								<td colspan="2"><strong>{$quantity_types[$camp]['camp']}</strong></td>
								<th></th>
								<th></th>
								<th>Rotations</th>
								<th>Theme Weeks</th>
								<th>Campers</th>
EOT;
			if($camps[$camp]['camp_values']['quantity_type_avg'] == "true") {
				$content_array['content'] .= <<<EOT
								<th>Average Select</th>
EOT;
			}
			$content_array['content'] .= <<<EOT
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><strong>ID</strong></td>
								<td><strong>Quantity Type</strong></td>
								<td><strong>Non-Consumable</strong></td>
								<td><strong>Consumable</strong></td>
								<td><input type="text" name="camp_cost_options[rotations]" value="{$quantity_types[$camp]['camp_cost_options'][$term]['rotations']}" /></td>
								<td><input type="text" name="camp_cost_options[theme_weeks]" value="{$quantity_types[$camp]['camp_cost_options'][$term]['theme_weeks']}" /></td>
								<td><input type="text" name="camp_cost_options[campers]" value="{$quantity_types[$camp]['camp_cost_options'][$term]['campers']}" /></td>
EOT;
			if($camps[$camp]['camp_values']['quantity_type_avg'] == "true") {
				$content_array['content'] .= <<<EOT
								<td></td>
EOT;
			}
			$content_array['content'] .= <<<EOT
							</tr>
						</tbody>
						<tbody>
EOT;
			foreach($quantity_types[$camp]['quantity_types'] as $key => $values) {
				if($values['qt_options']['terms'][$current_term] == "true") {
					if($values['cost_options'][$term]['NC']['enabled'] == "true") { $nc_enabled = " checked"; }
					else { $nc_enabled = ""; }
					if($values['cost_options'][$term]['C']['enabled'] == "true") { $c_enabled = " checked"; }
					else { $c_enabled = ""; }
					if($values['cost_options'][$term]['rotations_enabled'] == "true") { $rotations_enabled = " checked"; }
					else { $rotations_enabled = ""; }
					if($values['cost_options'][$term]['theme_weeks_enabled'] == "true") { $theme_weeks_enabled = " checked"; }
					else { $theme_weeks_enabled = ""; }
					if($values['cost_options'][$term]['campers_enabled'] == "true") { $campers_enabled = " checked"; }
					else { $campers_enabled = ""; }
					$content_array['content'] .= <<<EOT
							<tr valign="top">
								<td>{$key}</td>
								<td><strong>{$values['name']}</strong></td>
								<td>
									<div class="switch small radius">
										<input id="NCEnabled{$key}" type="checkbox" name="update[{$key}][cost_options][NC][enabled]" value="true"{$nc_enabled} />
										<label for="NCEnabled{$key}" title="Non-Consumable Enabled" class="radio-true"></label>
									</div>
								</td>
								<td>
									<div class="switch small radius">
										<input id="CEnabled{$key}" type="checkbox" name="update[{$key}][cost_options][C][enabled]" value="true"{$c_enabled} />
										<label for="CEnabled{$key}" title="Consumable Enabled" class="radio-true"></label>
									</div>
								</td>
								<td class="center">
									<div class="switch small round">
										<input id="RotationsEnabled{$key}" type="checkbox" name="update[{$key}][cost_options][rotations_enabled]" value="true"{$rotations_enabled} />
										<label for="RotationsEnabled{$key}" title="Rotations Enabled" class="radio-true"></label>
									</div>
								</td>
								<td class="center">
									<div class="switch small round">
										<input id="ThemeWeeksEnabled{$key}" type="checkbox" name="update[{$key}][cost_options][theme_weeks_enabled]" value="true"{$theme_weeks_enabled} />
										<label for="ThemeWeeksEnabled{$key}" title="Theme Weeks Enabled" class="radio-true"></label>
									</div>
								</td>
								<td class="center">
									<div class="switch small round">
										<input id="CampersEnabled{$key}" type="checkbox" name="update[{$key}][cost_options][campers_enabled]" value="true"{$campers_enabled} />
										<label for="CampersEnabled{$key}" title="Campers Enabled" class="radio-true"></label>
									</div>
								</td>
EOT;
					if($camps[$camp]['camp_values']['quantity_type_avg'] == "true") {
						if($values['cost_options'][$term]['quantity_type_link'] == "average") { $qt_avg = " selected"; } else { $qt_avg = ""; }
						if($values['cost_options'][$term]['quantity_type_link'] == "exclude") { $qt_exclude = " selected"; } else { $qt_exclude = ""; }
						$content_array['content'] .= <<<EOT
								<td>
									<select name="update[{$key}][cost_options][quantity_type_link]">
										<option value="">--------------</option>
										<option value="average"{$qt_avg}>Average Value</option>
										<option value="exclude"{$qt_exclude}>Exclude</option>
									</select>
								</td>
EOT;
					}
					$content_array['content'] .= <<<EOT
							</tr>
EOT;
				}
			}
			$content_array['content'] .= <<<EOT
						</tbody>
					</table>
					<div class="row">
					<p><button type="submit" name="submit" class="button primary radius small">Update Quantity Types</button></p>
					</div>
					<input type="hidden" name="camp" value="{$camp}" />
					<input type="hidden" name="term" value="{$term}" />
					<input type="hidden" name="action" value="quantity_types_update" />
				</form>
			</div>
		</div>
EOT;
			$content_array['content'] .= gambaDebug::preformatted_arrays($quantity_types[$camp], "quantity_types{$camp}", "Quantity Types", $_REQUEST['debug_override']);

			return $content_array;
// 			echo "<pre>"; print_r($quantity_types[$camp]); echo "</pre>";
// 			echo "<pre>"; print_r($camps[$camp]); echo "</pre>";
		}

		public static function report_campg($array) {
			$url = url('/');
			$camp = $array['camp'];
			$term = $array['term'];
			//$term = gambaTerm::year_by_status('C');
			$last_term = $term - 1;
			$next_term = $term + 1;

			$camps = gambaCampCategories::camps_list();
			$themes = gambaThemes::themes_camps_all($array['term']);
			$camp_cost_summary = $camps[$camp]['camp_values']['cost_summary'];


			$content_array['page_title'] = "Material Cost Summaries";
			$content_array['content'] = <<<EOT
			<div class="directions">
					<strong>Directions</strong> To make use of this reporting tool some standard data must be set up for Themes and Quantity Types. Please click on <a href="{$url}/costs/setup">Setup</a> to make changes to data values.
			</div>
EOT;
			$array['action'] = "summaries_camps";
			$content_array['content'] .= gambaCostsNav::summaries_year_nav($array);
			$content_array['content'] .= gambaCostsNav::summaries_camp_nav($array);
			$content_array['content'] .= <<<EOT
			<h3>{$camps[$camp]['name']}</h3>
			<p><a href="{$url}/costs/summaries_noncurriculum?term={$array['term']}">Back to Non-Curriculum</a></p>
			<div class="row">
EOT;
			if($camps[$camp]['costing_summary'][$next_term]['total_average'] != "") {
				$total_average_next = "$".number_format($camps[$camp]['costing_summary'][$next_term]['total_average'], 2);
				$content_array['content'] .= '<div class="columns large-3 medium-3 small-12"><p><strong>'.$next_term.' Average:</strong> '."{$total_average_next}</p></div>";
			}
			$total_average = "$".number_format($camps[$camp]['costing_summary'][$term]['total_average'], 2);
			$content_array['content'] .= '<div class="columns large-3 medium-3 small-12"><p><strong>'.$term.' Average:</strong> '."{$total_average}</p></div>";
			$total_average_last = "$".number_format($camps[$camp]['costing_summary'][$last_term]['total_average'], 2);
			$content_array['content'] .= '<div class="columns large-3 medium-3 small-12 end"><p><strong>'.$last_term.' Average:</strong> '."{$total_average_last}</p></div>";
			$content_array['content'] .= "</div>";
			$content_array['content'] .= <<<EOT
			<table class="table table-striped table-bordered table-hover table-condensed table-small">
				<thead>
					<tr>
						<th>{$camps[$camp]['name']}</th>
						<th>Budget # of<br />Activities</th>
						<th>Actual # of<br />Activities</th>
						<th>Budget Cost<br />Per Camper</th>
						<th>Avg Actual Cost<br />Per Camper</th>
						<th>Total Cost Year 1</th>
					</tr>
				</thead>
				<tbody>
EOT;
			$weighted_cost_per_camper = 0;
			$weighted_activities = 0;
			foreach($themes[$camp] as $theme_id => $values) {
				//if($values['this_camp'] == "true") {
					$weighted_cost_per_camper += $values['budget']['theme_cost_year1'];
					$cost_per_camper = $values['budget']['theme_cost_year1'];
					$cost_per_camper = "$".number_format($cost_per_camper, 2);
					$weighted_activities += $values['number_activities'];
					$theme_weighted_cost_per_camper = "$".number_format($values['budget']['theme_weighted_cost_per_camper'], 2);
					$weighted_cost = $cost_per_camper / $values['budget']['theme_budget_yr1_enrollment'];
					$theme_budget_per_camper = "$".number_format($values['budget']['theme_budget_per_camper'], 2);
					$theme_cost_per_camper = "$".number_format($values['costs']['theme_cost_per_camper'], 2);
					$total_cost_year1 = "$".number_format($values['costing_summary']['total_cost_year1'], 2);
					$content_array['content'] .= <<<EOT
					<tr>
						<td><a href="{$url}/costs/activities_camps?term={$term}&camp={$camp}&theme={$theme_id}">{$values['name']}</a></td>
						<td>{$values['budget']['theme_budget_activities']}</td>
						<td>{$values['number_activities']}</td>
						<td>{$theme_budget_per_camper}</td>
						<td>{$theme_cost_per_camper}</td>
						<td>{$total_cost_year1}</td>
					</tr>
EOT;
				//}
			}
			$total_average = "$".number_format($camps[$camp]['costing_summary'][$term]['total_average'], 2);
			$total_cost = "$".number_format($camps[$camp]['costing_summary'][$term]['total_cost'], 2);
			$updated_on = date("n/j g:ia", strtotime($camps[$camp]['costing_summary'][$term]['updated_on']));
			$content_array['content'] .= <<<EOT
				</tbody>
				<tfoot>
					<tr>
						<th>Total</th>
						<th></th>
						<th></th>
						<th></th>
						<th>{$total_average} </th>
						<th>{$total_cost}</th>
					</tr>
				</tfoot>
			</table>
			<p>Updated on {$updated_on}</p>
EOT;
			$content_array['content'] .= gambaDebug::preformatted_arrays($themes, 'themes', 'Themes');
			return $content_array;
// 			echo "<pre>"; print_r($themes[$camp]); echo "</pre>";
// 			echo "<pre>"; print_r($camps[$camp]); echo "</pre>";
		}

		public static function report_non_yearly_avgs($term) {
			$url = url('/');
			$last_term = $term - 1;
			$next_term = $term + 1;
			$camps = gambaCampCategories::camps_list();
			// Current Term
			$current_term_cost_per_camper = 0;
			// Next Term
			$next_term_cost_per_camper = 0;
			// Last Term
			$last_term_cost_per_camper = 0;
			foreach($camps as $camp_id => $values) {
				if($values['camp_values']['cost_analysis'] == "true" && $values['camp_values']['cost_non_curriculum'] == "true") {

					$current_term_cost_per_camper += $values['costing_summary'][$term]['total_average'];
					$next_term_cost_per_camper += $values['costing_summary'][$next_term]['total_average'];
					$last_term_cost_per_camper += $values['costing_summary'][$last_term]['total_average'];
				}
			}
			$array['total_averages'][$term] = $current_term_cost_per_camper;
			$array['total_averages'][$next_term] = $next_term_cost_per_camper;
			$array['total_averages'][$last_term] = $last_term_cost_per_camper;
			return $array;
		}

		public static function report_noncurriculum($array) {
			$url = url('/');
			$camp = $array['camp'];
			$term = $array['term'];
			//$term = gambaTerm::year_by_status('C');
			$last_term = $term - 1;
			$next_term = $term + 1;

			$camp_cost_summary = $camps[$camp]['camp_values']['cost_summary'];
			$camps = gambaCampCategories::camps_list();
			$themes = gambaThemes::themes_camps_all($array['term']);
			if($array['reveal'] == "hidden") { $reveal = ""; $reveal_text = "Reveal"; } else { $reveal = "hidden"; $reveal_text = "Hide"; }


			$content_array['page_title'] = "Material Cost Summaries";
			$content_array['content'] = <<<EOT
			<div class="directions">
					<strong>Directions</strong> To make use of this reporting tool some standard data must be set up for Themes and Quantity Types. Please click on <a href="{$url}/costs/setup">Setup</a> to make changes to data values. If you have Zero values for Avg Actual Cost Per Camper and Total Cost Year 1 you need to input Enrollment in the Cost Summary Settings or Camper Enrollment.
			</div>
EOT;
			$array['action'] = "summaries_noncurriculum";
			$content_array['content'] .= gambaCostsNav::summaries_year_nav($array);
			$content_array['content'] .= gambaCostsNav::summaries_camp_nav($array);
			$content_array['content'] .= <<<EOT
			<h3>Non-Curriculum</h3>

			<div class="row">
EOT;
			$yearly_avgs = self::report_non_yearly_avgs($term);
			if($yearly_avgs['total_averages'][$next_term] > 0) {
				$next_term_avg = "$". number_format($yearly_avgs['total_averages'][$next_term], 2);
				$content_array['content'] .= '<div class="columns large-3 medium-3 small-12"><p><strong>'.$next_term.' Average:</strong> '."{$next_term_avg}</p></div>";
			}
			$current_term_avg = "$". number_format($yearly_avgs['total_averages'][$term], 2);
			$content_array['content'] .= '<div class="columns large-3 medium-3 small-12"><p><strong>'.$term.' Average:</strong> '."{$current_term_avg}</p></div>";
			$last_term_avg = "$". number_format($yearly_avgs['total_averages'][$last_term], 2);
			$content_array['content'] .= '<div class="columns large-3 medium-3 small-12 end"><p><strong>'.$last_term.' Average:</strong> '."{$last_term_avg}</p></div>";
			$content_array['content'] .= "</div>";
			$content_array['content'] .= <<<EOT
			<table class="table table-striped table-bordered table-hover table-condensed table-small">
				<thead>
					<tr>
						<th>Non-Curriculum</th>
						<th>Budget # of<br />Activities</th>
						<th>Actual # of<br />Activities</th>
						<th>Budget Cost<br />Per Camper</th>
						<th>Avg Actual Cost<br />Per Camper</th>
						<th>Total Cost<br />Year 1</th>
					</tr>
				</thead>
				<tbody>
EOT;
			$col_total_enrollment = 0;
			$col_total_cost = 0;
			foreach($camps as $camp_id => $values) {
				if($values['camp_values']['cost_analysis'] == "true" && $values['camp_values']['cost_non_curriculum'] == "true") {
					$camp_array[$camp_id] = $values;
					$theme_budget_activities = 0;
					$actual_activities = 0;
					$theme_budget_per_camper = 0;
					$cost_per_camper = 0;
					$total_cost = 0;
					$total_enrollment = 0;
					foreach($themes[$camp_id] as $theme_id => $theme_values) {
						$theme_budget_activities += $theme_values['budget']['theme_budget_activities'];
						$actual_activities += $theme_values['number_activities'];
						$theme_budget_per_camper += $theme_values['budget']['theme_budget_per_camper'];
						$col_total_cost += $cost_per_camper += $theme_values['budget']['cost_per_camper'];
						$col_total_enrollment += $theme_values['budget']['theme_budget_yr1_enrollment'];
						$total_enrollment += $theme_values ['budget']['theme_budget_yr1_enrollment'];
					}
// 					$total_cost += $cost_per_camper * $total_enrollment;
					$theme_budget_per_camper = "$".number_format($theme_budget_per_camper, 2);
					$avg_per_camper = "$".number_format($values['costing_summary'][$term]['total_average'], 2);
					$col_total_cost += $values['costing_summary'][$term]['total_cost'];
					$total_cost = "$".number_format($values['costing_summary'][$term]['total_cost'], 2);

					$content_array['content'] .= <<<EOT
					<tr>
						<td><a href="{$url}/costs/summaries_camps?camp={$camp_id}&term={$term}">{$values['name']}</a></td>
						<td>{$theme_budget_activities}</td>
						<td>{$actual_activities}</td>
						<td>{$theme_budget_per_camper}</td>
						<td><span data-tooltip aria-haspopup="true" class="has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="{$values['costing_summary'][$term]['total_average_calc']}">{$avg_per_camper}</span></td>
						<td><span data-tooltip aria-haspopup="true" class="has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="$total_cost = {$values['costing_summary'][$term]['total_enrollment']} * $avg_per_camper">{$total_cost}</span></td>
					</tr>
EOT;
				}
			}
			$col_total_cost = "$".number_format($col_total_cost, 2);
			$costing_updated_on = Config::find('costing_summary_updated');
			$costing_updated_on_array = json_decode($costing_updated_on->value, true);
			$updated_on = date("n/j g:ia", strtotime($costing_updated_on_array[$term]['updated_on']));
			$content_array['content'] .= <<<EOT
				</tbody>
				<tfoot>
					<tr>
						<th>Total</th>
						<th></th>
						<th></th>
						<th>Weighted Avg:</th>
						<th>{$current_term_avg}</th>
						<th>{$col_total_cost}</th>
					</tr>
				</tfoot>
			</table>
			<p>Updated on {$updated_on}</p>
EOT;
			return $content_array;
// 			echo "<pre>"; print_r($camp_array); echo "</pre>";
// 			echo "<pre>"; print_r($camps[5]); echo "</pre>";
// 			echo "<pre>"; print_r($camps[10]); echo "</pre>";
// 			echo "<pre>"; print_r($yearly_avgs); echo "</pre>";
// 			echo "<pre>"; print_r($themes[4]); echo "</pre>";
// 			echo "<pre>"; print_r($themes[5]); echo "</pre>";
// 			echo "<pre>"; print_r($grades); echo "</pre>";
		}

		public static function report_campg_by_grade($array) {
			$url = url('/');
			$camp = $array['camp'];
			$term = $array['term'];
			$grade = $array['grade'];
			//$term = gambaTerm::year_by_status('C');
			$last_term = $term - 1;
			$next_term = $term + 1;
			$grades = gambaGrades::grade_list();

			$themes = gambaThemes::themes_camps_all($array['term']);
			$camps = gambaCosts::camp_categories();
			$camp_cost_summary = $camps[$camp]['camp_values']['cost_summary'];


			$content_array['page_title'] = "Material Cost Summaries";
			$content_array['content'] = <<<EOT
			<div class="directions">
					<strong>Directions</strong> To make use of this reporting tool some standard data must be set up for Themes and Quantity Types. Please click on <a href="{$url}/costs/setup">Setup</a> to make changes to data values.
			</div>
EOT;
			$array['action'] = "summaries_campg";
			$content_array['content'] .= gambaCostsNav::summaries_year_nav($array);
			$content_array['content'] .= gambaCostsNav::summaries_camp_nav($array);
			$content_array['content'] .= <<<EOT
			<h3>{$grades[$camp]['camp_name']} {$grades[$camp]['grades'][$grade]['level']}</h3>
			<div class="row">
EOT;
			if(is_array($camp_cost_summary[$next_term])) {
			$total_avg = "$".number_format($camps[$camp]['costing_summary'][$term][$grade]['total_average'], 2);
				$content_array['content'] .= '<div class="columns large-3 medium-3 small-12"><p><strong>'.$next_term.' Average:</strong> '."{$camp_cost_summary[$next_term]['cost_summary_total']}</p></div>";
			}
			$total_avg = "$".number_format($camps[$camp]['costing_summary'][$term][$grade]['total_average'], 2);
			$content_array['content'] .= '<div class="columns large-3 medium-3 small-12"><p><strong>'.$term.' Average:</strong> '."{$total_avg}</p></div>";
			$total_avg_last = "$".number_format($camps[$camp]['costing_summary'][$last_term][$grade]['total_average'], 2);
			$content_array['content'] .= '<div class="columns large-3 medium-3 small-12 end"><p><strong>'.$last_term.' Average:</strong> '."{$total_avg_last}</p></div>";
			$content_array['content'] .= "</div>";
			$content_array['content'] .= <<<EOT
			<table class="table table-striped table-bordered table-hover table-condensed table-small">
				<thead>
					<tr>
						<th>{$grades[$camp]['camp_name']}</th>
						<th>Budget # of<br />Activities</th>
						<th>Actual # of<br />Activities</th>
						<th>Budget Cost<br />Per Camper</th>
						<th>Avg Actual Cost<br />Per Camper {$term}</th>
						<th>Year 1 Enrollments</th>
						<th>Total Cost<br />Year 1</th>
					</tr>
				</thead>
				<tbody>
EOT;
			$weighted_cost_per_camper = 0;
			$weighted_activities = 0;
			$col_total_enrollment = 0;
			$col_total_cost = 0;
			$budget_total = 0;
			$a = 0;
			foreach($themes[$camp] as $theme_id => $values) {
				if($values['this_camp'] == "true") {
// 					$weighted_cost_per_camper += $values['budget']['theme_cost_year1'][$grade];
// 					$cost_per_camper = $values['budget']['theme_cost_year1'][$grade];
// 					$weighted_activities += $values['number_activities'];
// 					$theme_weighted_cost_per_camper = "$".number_format($values['budget']['theme_weighted_cost_per_camper'], 2);
// 					$col_total_cost += $total_theme_cost = $cost_per_camper * $theme_budget_yr1_enrollment;

					$col_total_enrollment += $theme_budget_yr1_enrollment = gambaCosts::get_year1_enrollment($term, $camp, $theme_id, $grade, $values['link_id']);

					$budget_total += $values['budget']['theme_budget_per_camper'];
					$theme_budget_per_camper = "$".number_format($values['budget']['theme_budget_per_camper'], 2);
					$a++;

					$cost_per_camper = "$".number_format($values['costs']['theme_cost_per_camper'][$grade], 2);

					$total_theme_cost = "$".number_format($values['costing_summary']['total_cost_year1'][$grade], 2);

					$content_array['content'] .= <<<EOT
					<tr>
						<td><a href="{$url}/costs/activities?view=by_grade&term={$term}&camp={$camp}&grade={$grade}&theme={$theme_id}">{$values['name']}</a></td>
						<td>{$values['budget']['theme_budget_activities']}</td>
						<td>{$values['number_activities']}</td>
						<td>{$theme_budget_per_camper}</td>
						<td>{$cost_per_camper}</td>
						<td>{$theme_budget_yr1_enrollment}</td>
						<td><span data-toolt<span data-tooltip aria-haspopup="true" class="has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="$total_theme_cost = $theme_budget_yr1_enrollment * $cost_per_camper">{$total_theme_cost}</span></td>
					</tr>
EOT;
				}
			}
			$total_avg = $col_total_cost / $col_total_enrollment;
			$total_avg = "$".number_format($camps[$camp]['costing_summary'][$term][$grade]['total_average'], 2);
			$total_cost = "$".number_format($camps[$camp]['costing_summary'][$term][$grade]['total_cost'], 2);
			$budget_avg = $budget_total / $a;
			$budget_avg = "$".number_format($budget_avg, 2);
			$updated_on = date("n/j g:ia", strtotime($camps[$camp]['costing_summary'][$term][$grade]['updated_on']));
			$content_array['content'] .= <<<EOT

				</tbody>
				<tfoot>
					<tr>
						<th>Total</th>
						<th></th>
						<th></th>
						<th>Total Avg</th>
						<th>{$total_avg}</th>
						<th></th>
						<th>{$total_cost}</th>
					</tr>
					<tr>
						<th></th>
						<th></th>
						<th></th>
						<th>Budget Avg</th>
						<th>{$budget_avg}</th>
						<th></th>
						<th></th>
					</tr>
				</tfoot>
			</table>
			<p>Updated on {$updated_on}</p>
EOT;
			//$content_array['content'] .= gambaDebug::preformatted_arrays($themes, 'themes', 'Themes');
			return $content_array;
// 			echo "<pre>"; print_r($camp_array); echo "</pre>";
// 			echo "<pre>"; print_r($camps[$array['camp']]); echo "</pre>";
// 			echo "<pre>"; print_r($themes[$array['camp']]); echo "</pre>";
// 			echo "<pre>"; print_r($grades); echo "</pre>";
		}

		public static function report_all_yearly_avgs($term) {
			$url = url('/');
			$last_term = $term - 1;
			$next_term = $term + 1;
			$camps = gambaCampCategories::camps_list();
			$themes = gambaThemes::themes_camps_all($array['term']);
			$grades = gambaGrades::grade_list();
			// Current Term
			$current_term_cost_per_camper = 0;
			// Next Term
			$next_term_cost_per_camper = 0;
			// Last Term
			$last_term_cost_per_camper = 0;

			// Camp G By Grade
			foreach($camps as $key => $value) {
				if($value['camp_values']['cost_analysis'] == "true" && $value['camp_values']['cost_grade_display'] == "true") {
					foreach($grades[$key]['grades'] as $grade_id => $grade_values) {
						if($grade_values['enrollment'] == 1) {
							// Current Term

							$current_term_cost_per_camper += $value['costing_summary'][$term][$grade_id]['total_average'];
							// Next Term
							$next_term_cost_per_camper += $value['costing_summary'][$next_term][$grade_id]['total_average'];
							// Last Term
							$last_term_cost_per_camper += $value['costing_summary'][$last_term][$grade_id]['total_average'];
						}
					}
				}
			}
			// GSQ
			foreach($camps as $key => $value) {
				if($value['camp_values']['cost_analysis'] == "true" && $value['camp_values']['cost_grade_display'] != "true" && $value['camp_values']['cost_non_curriculum'] != "true") {
					// Current Term
					$current_term_cost_per_camper += $value['costing_summary'][$term]['total_average'];
					// Next Term
					$next_term_cost_per_camper += $value['costing_summary'][$next_term]['total_average'];
					// Last Term
					$last_term_cost_per_camper += $value['costing_summary'][$last_term]['total_average'];
				}
			}
			// Non Curriculum
			$non_total_cost = 0;
			$non_avg_total_cost = 0;
			foreach($camps as $camp_id => $values) {
				if($values['camp_values']['cost_analysis'] == "true" && $values['camp_values']['cost_non_curriculum'] == "true") {
					// Current Term
					$col_total_avg_cost_per_camper += $value['costing_summary'][$term]['total_average'];
					// Next Term
					$next_term_cost_per_camper += $value['costing_summary'][$next_term]['total_average'];
					// Last Term
					$last_term_cost_per_camper += $value['costing_summary'][$last_term]['total_average'];
				}
			}
			$array['total_averages'][$term] = $col_total_avg_cost_per_camper;
			$array['total_averages'][$next_term] = $next_term_cost_per_camper;
			$array['total_averages'][$last_term] = $last_term_cost_per_camper;
			return $array;
		}

		public static function report_all($array) {
			$url = url('/');
			$camp = $array['camp'];
			$term = $array['term'];
			if($array['term'] == "") { $term = gambaTerm::year_by_status('C'); }
			$last_term = $term - 1;
			$next_term = $term + 1;

			$camp_cost_summary = $camps[$camp]['camp_values']['cost_summary'];
			$camps = gambaCampCategories::camps_list();
			$themes = gambaThemes::themes_camps_all($array['term']);
			$grades = gambaGrades::grade_list();


			$content_array['page_title'] = "Material Cost Summaries";
			$content_array['content'] = <<<EOT
			<div class="directions">
					<strong>Directions</strong> To make use of this reporting tool some standard data must be set up for Themes and Quantity Types. Please click on <a href="{$url}/costs/setup">Setup</a> to make changes to data values. If you have Zero values for Avg Actual Cost Per Camper and Total Cost Year 1 you need to input Enrollment in the Cost Summary Settings or Camper Enrollment.
			</div>
EOT;
			$array['action'] = "summaries";
			$content_array['content'] .= gambaCostsNav::summaries_year_nav($array);
			$content_array['content'] .= gambaCostsNav::summaries_camp_nav($array);
			$content_array['content'] .= <<<EOT
			<h3>All (Summary)</h3>
			<div class="row">
EOT;

			$yearly_avgs = self::report_all_yearly_avgs($term);
			if($yearly_avgs['total_averages'][$next_term] > 0) {
				$next_term_avg = "$". number_format($yearly_avgs['total_averages'][$next_term], 2);
				$content_array['content'] .= '<div class="columns large-3 medium-3 small-12"><p><strong>'.$next_term.' Average:</strong> '."{$next_term_avg}</p></div>";
			}
			$current_term_avg = "$". number_format($yearly_avgs['total_averages'][$term], 2);
			$content_array['content'] .= '<div class="columns large-3 medium-3 small-12"><p><strong>'.$term.' Average:</strong> '."{$current_term_avg}</p></div>";
			$last_term_avg = "$". number_format($yearly_avgs['total_averages'][$last_term], 2);
			$content_array['content'] .= '<div class="columns large-3 medium-3 small-12 end"><p><strong>'.$last_term.' Average:</strong> '."{$last_term_avg}</p></div>";
			$content_array['content'] .= "</div>";

			$content_array['content'] .= <<<EOT
			<table class="table table-striped table-bordered table-hover table-condensed table-small">
				<thead>
					<tr>
						<th></th>
						<th>Total Cost</th>
						<th>Avg Actual Cost<br />Per Camper</th>
					</tr>
				</thead>
				<tbody>
EOT;
			$col_total_avg_cost_per_camper = 0;
			$col_total_cost = 0;
			// Camp G By Grade
			foreach($camps as $key => $value) {
				if($value['camp_values']['cost_analysis'] == "true" && $value['camp_values']['cost_grade_display'] == "true") {
					foreach($grades[$key]['grades'] as $grade_id => $grade_values) {
						if($grade_values['enrollment'] == 1) {
							$col_total_cost += $total_cost = $value['costing_summary'][$term][$grade_id]['total_cost'];
							$total_cost = "$" . number_format($total_cost, 2);
							$col_total_avg_cost_per_camper += $cost_per_camper = $value['costing_summary'][$term][$grade_id]['total_average'];
							$cost_per_camper = "$" . number_format($cost_per_camper, 2);
							$content_array['content'] .= <<<EOT
					<tr>
						<td><a href="{$url}/costs/summaries_campg?camp={$key}&grade={$grade_id}&term={$term}">{$value['alt_name']} {$grade_values['level']}</a></td>
						<td>{$total_cost}</td>
						<td>{$cost_per_camper}</td>
					</tr>
EOT;
						}
					}
				}
			}
			// GSQ
			foreach($camps as $key => $value) {
				if($value['camp_values']['cost_analysis'] == "true" && $value['camp_values']['cost_grade_display'] != "true" && $value['camp_values']['cost_non_curriculum'] != "true") {
					$total_cost = $value['costing_summary'][$term]['total_cost'];
					if($key == 2) { $total_cost = $total_cost / 2; }
					$col_total_cost += $total_cost;
					$total_cost = "$" . number_format($total_cost, 2);
					$col_total_avg_cost_per_camper += $cost_per_camper = $value['costing_summary'][$term]['total_average'];
					$cost_per_camper = "$" . number_format($cost_per_camper, 2);
					$content_array['content'] .= <<<EOT
					<tr>
						<td><a href="{$url}/costs/summaries_gsq?camp={$key}&term={$term}">{$value['alt_name']}</a></td>
						<td>{$total_cost}</td>
						<td>{$cost_per_camper}</td>
					</tr>
EOT;
				}
			}
			// Non Curriculum
			$non_total_cost = 0;
			$non_avg_total_cost = 0;
			foreach($camps as $camp_id => $values) {
				if($values['camp_values']['cost_analysis'] == "true" && $values['camp_values']['cost_non_curriculum'] == "true") {
					$camp_array[$camp_id] = $values;

					$non_avg_total_cost += $values['costing_summary'][$term]['total_average'];
					$non_total_cost += $values['costing_summary'][$term]['total_cost'];
				}
			}
			$col_total_avg_cost_per_camper += $non_avg_total_cost;
			$col_total_cost += $non_total_cost;

			$non_total_cost = "$".number_format($non_total_cost, 2);
			$non_avg_total_cost = "$".number_format($non_avg_total_cost, 2);
			$content_array['content'] .= <<<EOT
					<tr>
						<td><a href="{$url}/costs/summaries_noncurriculum?term={$term}">Non-Curriculum</a></td>
						<td>{$non_total_cost}</td>
						<td>{$non_avg_total_cost}</td>
					</tr>
EOT;
			$col_total_cost = "$".number_format($col_total_cost, 2);
			$col_total_avg_cost_per_camper = "$".number_format($col_total_avg_cost_per_camper, 2);
			$costing_updated_on = Config::find('costing_summary_updated');
			$costing_updated_on_array = json_decode($costing_updated_on->value, true);
			$updated_on = date("n/j g:ia", strtotime($costing_updated_on_array[$term]['updated_on']));
			// Removed <th>{$col_total_avg_cost_per_camper}</th> from 3rd column
			$content_array['content'] .= <<<EOT
				</tbody>
				<tfoot>
					<tr>
						<th>Total</th>
						<th>{$col_total_cost}</th>
						<th></th>
					</tr>
				</tfoot>
			</table>
			<p>Updated on {$updated_on}</p>
EOT;
			return $content_array;


// 			echo "<pre>"; print_r($camp_array); echo "</pre>";
// 			echo "<pre>"; print_r($camps); echo "</pre>";
		}

		public static function activities($array) {
			$url = url('/');
			$camp = $array['camp'];
			$term = $array['term'];

			if($array['camp'] == 2) { $array['grade'] = 11; }
			$supplies = self::supplyactivities($array['term'], $array['theme'], $array['grade']);
			$themes = gambaThemes::theme_by_id($array['theme']);
			$grades = gambaGrades::grade_list();
			$camps = gambaCampCategories::camps_list();

			$content_array['page_title'] = "Activities For {$themes['name']} {$grades[$array['camp']]['grades'][$array['grade']]['level']}";
			$content_array['content'] .= <<<EOT
			<p><a href="{$url}/costs/summaries_campg?camp={$array['camp']}&grade={$array['grade']}&term={$array['term']}">Back to {$camps[$array['camp']]['name']} {$grades[$array['camp']]['camp_name']}</a></p>
			<table class="table table-striped table-bordered table-hover table-condensed table-small">
				<thead>
					<tr>
						<th>{$grades[$array['camp']]['camp_name']}</th>
						<th>Activity</th>
						<th>Budget Cost per camper</th>
						<th>Avg Actual Cost per camper</th>
						<th>Actual Cost per Class</th>
					</tr>
				</thead>
				<tbody>
EOT;
			$cost_per_camper = "$". $themes['budget'][$array['term']]['theme_budget_per_camper'];
			$i = 0;
			$total_cost = 0;
			$budget_cost_per_camper = $themes['budget']['theme_budget_per_camper'] / $themes['number_activities'];
			$budget_cost_per_camper = "$". number_format($budget_cost_per_camper, 2);
			foreach($supplies['activities'] as $key => $values) {
				$activity_cost_year1 = "$". number_format($values['costing_summary']['activity_cost_per_camper'], 2);
				$total_cost += $values['costing_summary']['activity_cost_per_camper'];
				// Actual Cost Per Camper' and multiplying by the number of kids per class
				if($array['parts'] == $key) {
					$activity_link = "<a href=\"{$url}/costs/activities?view=by_grade&term={$array['term']}&camp={$array['camp']}&grade={$array['grade']}&theme={$array['theme']}\">â–¼ {$values['name']}</a>";
					$activity_highlight = 'activity-highlight ';
				} else {
					$activity_link = "<a href=\"{$url}/costs/activities?view=by_grade&term={$array['term']}&camp={$array['camp']}&grade={$array['grade']}&theme={$array['theme']}&parts={$key}\">â–² {$values['name']}</a>";
					$activity_highlight = "";
				}
				if($values['costing_summary']['activity_cost_per_class'] < .01) {
					$activity_cost_per_class = '&lt; $0.01';
				} else {
					$activity_cost_per_class = "$".number_format($values['costing_summary']['activity_cost_per_class'], 2);
				}
				$content_array['content'] .= <<<EOT
					<tr class="{$activity_highlight}">
						<td>{$themes['name']}</td>
						<td>{$activity_link}</td>
						<td class="text-center">{$budget_cost_per_camper}</td>
						<td class="text-center">{$activity_cost_year1}</td>
						<td class="text-center">{$activity_cost_per_class}</td>
					</tr>
EOT;
				$i++;
				if($array['parts'] == $key) {
					foreach($values['costing_summary']['supplylist'] as $supply_id => $supply_values) {
						// Avg Actual Cost per camper
						if($supply_values['cost_per_camper'] < .01) {
							$part_cost = '&lt; $0.01';
						} else {
							$part_cost = "$".number_format($supply_values['cost_per_camper'], 2);
						}
						// Actual Cost per Class
						if($supply_values['actual_cost_per_class'] < .01) {
							$actual_cost_per_class = '&lt; $0.01';
						} else {
							$actual_cost_per_class = "$".number_format($supply_values['actual_cost_per_class'], 2);
						}
						if($supply_values['exclude'] == 1) { $row_exclude = 'row-exclude '; $part_exclude = '<br />[REQUEST EXCLUDED FROM PACKING]'; } else { $row_exclude = ""; $part_exclude = ""; }
						$part_info = gambaParts::part_info($supply_values['part']);
						$content_array['content'] .= <<<EOT
					<tr class="{$activity_highlight}{$row_exclude}">
						<td></td>
						<td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$supply_values['part']} {$part_info['description']}</td>
						<td class="text-center"><span data-tooltip aria-haspopup="true" class="has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="{$supply_values['calc']}">{$part_cost}</span></td>
						<td title="{$supply_values['actual_cost_per_class_calc']}" class="text-center">{$actual_cost_per_class}</td>
					</tr>
EOT;
					}
				}
			}
			$total_avg_cost = $total_cost / $i;
			$total_avg_cost = "$". number_format($total_avg_cost, 2);
			$total_cost = "$". number_format($total_cost, 2);

			$content_array['content'] .= <<<EOT
				</tbody>
				<tfoot>
					<tr>
						<th></th>
						<th></th>
						<th class="text-right">Total Cost</th>
						<th class="text-center">{$total_cost}</th>
						<th></th>
					</tr>
					<tr>
						<th></th>
						<th></th>
						<th class="text-right">Total Avg</th>
						<th class="text-center">{$total_avg_cost}</th>
						<th></th>
					</tr>
				</tfoot>
			</table>
EOT;
			return $content_array;
// 			$json = '{"parts":{"part":"C9016","description":"NEBULA NAT PARKS SCIENCE PER CAMP","calc":" NEBULA NAT PARKS SCIENCE PER CAMP - C: 0.01 = (Qty: 1 * KQD: 1) * Cost: 0.01   ","cost_per_camper":0.01,"actual_cost_per_class":0.16,"actual_cost_per_class_calc":"0.16 = 0.01 * 16"},"part_cost_per_camper":0.01}';
// 			$json_array = json_decode($json, true);
// 			echo "<pre>"; print_r($json_array); echo "</pre>";
// 			echo "<pre>"; print_r($supplies); echo "</pre>";
		}

		public static function supplyactivities($term, $theme, $grade) {
			$url = url('/');
			$query = SupplyLists::select(
					'supplylists.id',
					'supplylists.activity_id',
					'supplylists.costing_summary',
					'activities.activity_name'
				);
				$query = $query->leftjoin('activities', 'activities.id', '=', 'supplylists.activity_id');
				$query = $query->where('activities.term', $term);
				$query = $query->where('activities.theme_id', $theme);
			if($grade != "") {
				$query = $query->where('activities.grade_id', $grade);
			}
			$query = $query->orderBy('activities.activity_name');
			$array['sql'] = $query->toSql();
			$query = $query->get();
			foreach($query as $key => $value) {
				$array['activities'][$value['activity_id']]['name'] = $value['activity_name'];
				$array['activities'][$value['activity_id']]['supplylist_id'] = $value['id'];
				$array['activities'][$value['activity_id']]['costing_summary'] = json_decode($value->costing_summary, true);
			}
			return $array;
		}

		public static function activities_gsq($array) {
			$url = url('/');
			$camp = $array['camp'];
			$term = $array['term'];

			if($array['camp'] == 2) { $array['grade'] = 11; }
			$supplies = self::supplyactivities($array['term'], $array['theme'], $array['grade']);
			$themes = gambaThemes::theme_by_id($array['theme']);
			$grades = gambaGrades::grade_list();
			$camps = gambaCampCategories::camps_list();
			$content_array['page_title'] = "Activities For {$themes['name']} {$grades[$array['camp']]['grades'][$array['grade']]['level']}";
			$content_array['content'] .= <<<EOT
			<p><a href="{$url}/costs/summaries_gsq?camp={$array['camp']}&term={$array['term']}">Back to {$camps[$array['camp']]['name']}</a></p>
			<table class="table table-striped table-bordered table-hover table-condensed table-small">
				<thead>
					<tr>
						<th>{$grades[$array['camp']]['camp_name']}</th>
						<th>Activity</th>
						<th>Budget cost per camper</th>
						<th>Avg Actual cost per camper</th>
						<th>Actual Cost per Class</th>
					</tr>
				</thead>
				<tbody>
EOT;
			$cost_per_camper = "$". $themes['budget'][$array['term']]['theme_budget_per_camper_yr1'];
			$i = 0;
			$total_cost = 0;
			$budget_cost_per_camper = $themes['budget']['theme_budget_per_camper_yr1'] / $themes['number_activities'];
			$budget_cost_per_camper = "$". number_format($budget_cost_per_camper, 2);
			foreach($supplies['activities'] as $key => $values) {
				$activity_cost_year1 = "$". number_format($values['costing_summary']['activity_cost_per_camper'], 2);
				// Actual Cost Per Camper' and multiplying by the number of kids per class
				if($array['parts'] == $key) {
					$activity_link = "<a href=\"{$url}/costs/activities_gsq?term={$array['term']}&camp={$array['camp']}&grade={$array['grade']}&theme={$array['theme']}\">â–¼ {$values['name']}</a>";
					$activity_highlight = 'activity-highlight ';
				} else {
					$activity_link = "<a href=\"{$url}/costs/activities_gsq?term={$array['term']}&camp={$array['camp']}&grade={$array['grade']}&theme={$array['theme']}&parts={$key}\">â–² {$values['name']}</a>";
					$activity_highlight = "";
				}
				if($values['costing_summary']['activity_cost_per_class'] < .01) {
					$activity_cost_per_class = '&lt; $0.01';
				} else {
					$activity_cost_per_class = "$".number_format($values['costing_summary']['activity_cost_per_class'], 2);
				}
				$content_array['content'] .= <<<EOT
					<tr class="{$activity_highlight}">
						<td>{$themes['name']}</td>
						<td>{$activity_link}</td>
						<td class="center">{$budget_cost_per_camper}</td>
						<td class="center">{$activity_cost_year1}</td>
						<td class="center">{$activity_cost_per_class}</td>
					</tr>
EOT;
				$total_cost += $values['costing_summary']['activity_cost_per_camper'];
				$i++;
				if($array['parts'] == $key) {
					foreach($values['costing_summary']['supplylist'] as $supply_id => $supply_values) {
						if($supply_values['cost_per_camper'] < .01) {
							$part_cost = '&lt; $0.01';
						} else {
							$part_cost = "$".number_format($supply_values['cost_per_camper'], 2);
						}
						// Actual Cost per Class
						if($supply_values['actual_cost_per_class'] < .01) {
							$actual_cost_per_class = '&lt; $0.01';
						} else {
							$actual_cost_per_class = "$".number_format($supply_values['actual_cost_per_class'], 2);
						}
						if($supply_values['exclude'] == 1) { $row_exclude = 'row-exclude '; $part_exclude = '<br />[REQUEST EXCLUDED FROM PACKING]'; } else { $row_exclude = ""; $part_exclude = ""; }
						$part_info = gambaParts::part_info($supply_values['part']);
						$content_array['content'] .= <<<EOT
					<tr class="{$activity_highlight}{$row_exclude}">
						<td></td>
						<td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$supply_values['part']} {$part_info['description']}</td>
						<td class="center"><span data-tooltip aria-haspopup="true" class="has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="{$supply_values['calc']}">{$part_cost}</span></td>
						<td title="{$supply_values['actual_cost_per_class_calc']}" class="text-center">{$actual_cost_per_class}</td>
					</tr>
EOT;
					}
				}
			}
			$total_avg_cost = $total_cost / $i;
			$total_avg_cost = "$". number_format($total_avg_cost, 2);
			$total_cost = "$". number_format($total_cost, 2);

			$content_array['content'] .= <<<EOT
				</tbody>
				<tfoot>
					<tr>
						<th></th>
						<th></th>
						<th class="text-right">Total Cost</th>
						<th class="text-center">{$total_cost}</th>
						<th></th>
					</tr>
					<tr>
						<th></th>
						<th></th>
						<th class="text-right">Total Avg</th>
						<th class="text-center">{$total_avg_cost}</th>
						<th></th>
					</tr>
				</tfoot>
			</table>
EOT;
			return $content_array;
// 			echo "<pre>"; print_r($grades[$array['camp']]); echo "</pre>";
// 			echo "<pre>"; print_r($themes); echo "</pre>";
// 			echo "<pre>"; print_r($supplies); echo "</pre>";
		}

		public static function activities_camps($array) {
			$url = url('/');
			$camp = $array['camp'];
			$term = $array['term'];

			if($array['camp'] == 2) { $array['grade'] = 11; }
			$supplies = self::supplyactivities($array['term'], $array['theme'], $array['grade']);
			$themes = gambaThemes::theme_by_id($array['theme']);
			$grades = gambaGrades::grade_list();
			$camps = gambaCampCategories::camps_list();
			$content_array['page_title'] = "Activities For {$themes['name']} {$grades[$array['camp']]['grades'][$array['grade']]['level']}";
			$content_array['content'] .= <<<EOT
			<p><a href="{$url}/costs/summaries_camps?camp={$array['camp']}&term={$array['term']}">Back to {$camps[$array['camp']]['name']}</a></p>
			<table class="table table-striped table-bordered table-hover table-condensed table-small">
				<thead>
					<tr>
						<th>{$grades[$array['camp']]['camp_name']}</th>
						<th>Activity</th>
						<th>Budget cost per camper</th>
						<th>Avg Actual cost per camper</th>
						<th>Actual Cost per Class</th>
					</tr>
				</thead>
				<tbody>
EOT;
			$cost_per_camper = "$". $themes['budget'][$array['term']]['theme_budget_per_camper_yr1'];
			$i = 0;
			$total_cost = 0;
			$budget_cost_per_camper = "$". number_format($themes['budget']['theme_budget_per_camper'], 2);
			foreach($supplies['activities'] as $key => $values) {
				$activity_cost_year1 = "$". number_format($values['costing_summary']['activity_cost_per_camper'], 2);
				// Actual Cost Per Camper' and multiplying by the number of kids per class
				if($array['parts'] == $key) {
					$activity_link = "<a href=\"{$url}/costs/activities_camps?term={$array['term']}&camp={$array['camp']}&grade={$array['grade']}&theme={$array['theme']}\">â–¼ {$values['name']}</a>";
					$activity_highlight = 'activity-highlight ';
				} else {
					$activity_link = "<a href=\"{$url}/costs/activities_camps?term={$array['term']}&camp={$array['camp']}&grade={$array['grade']}&theme={$array['theme']}&parts={$key}\">â–² {$values['name']}</a>";
					$activity_highlight = "";
				}
				if($values['costing_summary']['activity_cost_per_class'] < .01) {
					$activity_cost_per_class = '&lt; $0.01';
				} else {
					$activity_cost_per_class = "$".number_format($values['costing_summary']['activity_cost_per_class'], 2);
				}
				$content_array['content'] .= <<<EOT
					<tr class="{$activity_highlight}">
						<td>{$themes['name']}</td>
						<td>{$activity_link}</td>
						<td class="text-center">{$budget_cost_per_camper}</td>
						<td class="text-center">{$activity_cost_year1}</td>
						<td class="text-center">{$activity_cost_per_class}</td>
					</tr>
EOT;
				$total_cost += $values['costing_summary']['activity_cost_per_camper'];
				$i++;
				if($array['parts'] == $key) {
					foreach($values['costing_summary']['supplylist'] as $supply_id => $supply_values) {
						if($supply_values['cost_per_camper'] < .01) {
							$part_cost = '&lt; $0.01';
						} else {
							$part_cost = "$".number_format($supply_values['cost_per_camper'], 2);
						}
						// Actual Cost per Class
						if($supply_values['actual_cost_per_class'] < .01) {
							$actual_cost_per_class = '&lt; $0.01';
						} else {
							$actual_cost_per_class = "$".number_format($supply_values['actual_cost_per_class'], 2);
						}
						if($values['exclude'] == 1) { $row_exclude = 'row-exclude '; $part_exclude = '<br />[REQUEST EXCLUDED FROM PACKING]'; } else { $row_exclude = ""; $part_exclude = ""; }
						$part_info = gambaParts::part_info($supply_values['part']);
						$content_array['content'] .= <<<EOT
					<tr class="{$activity_highlight}{$row_exclude}">
						<td></td>
						<td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$supply_values['part']} {$part_info['description']}</td>
						<td class="text-center"><span data-tooltip aria-haspopup="true" class="has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="{$supply_values['calc']}">{$part_cost}</span></td>
						<td title="{$supply_values['actual_cost_per_class_calc']}" class="text-center">{$actual_cost_per_class}</td>
					</tr>
EOT;
					}
				}
			}
			$total_average = $total_cost / $i;
			$total_average = "$". number_format($total_average, 2);
			$total_cost = "$". number_format($total_cost, 2);

			$content_array['content'] .= <<<EOT
				</tbody>
EOT;
			$content_array['content'] .= <<<EOT
				<tfoot>
					<tr>
						<th></th>
						<th></th>
						<th class="text-right">Total Cost</th>
						<th class="text-center">{$total_cost}</th>
						<th></th>
					</tr>
					<tr>
						<th></th>
						<th></th>
						<th class="text-right">Total Avg</th>
						<th class="text-center">{$total_average}</th>
						<th></th>
					</tr>
				</tfoot>
EOT;
			$content_array['content'] .= <<<EOT
			</table>
EOT;
			return $content_array;
// 			echo "<pre>"; print_r($grades[$array['camp']]); echo "</pre>";
// 			echo "<pre>"; print_r($camps[$camp]); echo "</pre>";
// 			echo "<pre>"; print_r($supplies); echo "</pre>";
		}

		public static function report_gsq($array) {
			$url = url('/');
			$camp = $array['camp'];
			$term = $array['term'];
			//$term = gambaTerm::year_by_status('C');
			$last_term = $term - 1;
			$next_term = $term + 1;
			$camps = gambaCosts::camp_categories();
			$themes = gambaThemes::themes_camps_all($term);



			$content_array['page_title'] = "Material Cost Summaries";
			$content_array['content'] = <<<EOT
			<div class="directions">
					<strong>Directions</strong> To make use of this reporting tool some standard data must be set up for Themes and Quantity Types. Please click on <a href="{$url}/costs/setup">Setup</a> to make changes to data values.
			</div>
EOT;
			$array['action'] = "summaries_gsq";
			$content_array['content'] .= gambaCostsNav::summaries_year_nav($array);
			$content_array['content'] .= gambaCostsNav::summaries_camp_nav($array);
			$camp_cost_summary = $camps[$camp]['costing_summary'];
			$content_array['content'] .= "<div class=\"row\">";
			if(is_array($camp_cost_summary[$next_term])) {
				$total_avg_next = "$".number_format($camps[$camp]['costing_summary'][$next_term]['total_average'], 2);
				$content_array['content'] .= '<div class="columns large-3 medium-3 small-12"><p><strong>'.$next_term.' Average:</strong> '."{$total_avg_next}</p></div>";
			}
			$total_avg = "$".number_format($camps[$camp]['costing_summary'][$term]['total_average'], 2);
			$content_array['content'] .= '<div class="columns large-3 medium-3 small-12"><p><strong>'.$term.' Average:</strong> '."{$total_avg}</p></div>";
			$total_avg_last = "$".number_format($camps[$camp]['costing_summary'][$last_term]['total_average'], 2);
			$content_array['content'] .= '<div class="columns large-3 medium-3 small-12 end"><p><strong>'.$last_term.' Average:</strong> '."{$total_avg_last}</p></div>";
			$content_array['content'] .= "</div>";
			$content_array['content'] .= <<<EOT
			<table class="table table-striped table-bordered table-hover table-condensed table-small">
				<thead>
					<tr>
						<th>{$camps[$camp]['name']}</th>
						<th>Budget Cost Per<br />Camper Year 1</th>
						<th>Actual Cost Per<br />Camper Year 1</th>
						<!-- <th>Enrollment<br />Camper Year 1</th> -->
						<th>Budget Cost Per<br />Camper Year 2</th>
						<th>Actual Cost Per<br />Camper Year 2</th>
						<!-- <th>Enrollment<br />Camper Year 2</th> -->
						<th>Total Cost Year<br />1 & 2</th>
					</tr>
				</thead>
				<tbody>
EOT;
			$total_enrollment = 0;
			$total_cost = 0;
			$budget_total = 0;
			$a = 0;
			foreach($themes[$camp] as $theme_id => $values) {
				if($values['this_camp'] == "true") {
					// Budget
					$budget_total += $values['budget']['theme_budget_per_camper_yr1'];
					$theme_budget_per_camper_yr1 = "$".number_format($values['budget']['theme_budget_per_camper_yr1'], 2);
					$a++;
					$theme_budget_per_camper_yr2 = "$".number_format($values['budget']['theme_budget_per_camper_yr2'], 2);
					$total_enrollment += $theme_budget_yr1_enrollment = $values['budget']['theme_budget_yr1_enrollment'];
					$total_enrollment += $theme_budget_yr2_enrollment = $values['budget']['theme_budget_yr2_enrollment'];
					// Costs
					$theme_cost_per_camper = "$".number_format($values['costs']['theme_cost_per_camper'], 2);
					$theme_cost_per_camper_yr2 = "$".number_format($values['costs']['theme_cost_per_camper_yr2'], 2);
					// Costing Summary
					$total_cost_year1 = "$".number_format($values['costing_summary']['total_cost_year1'], 2);
					$total_cost += $values['costing_summary']['total_cost_year1'];
					$total_cost_year2 = "$".number_format($values['costing_summary']['total_cost_year2'], 2);
					$total_cost += $values['costing_summary']['total_cost_year2'];
					$total_cost_combined = "$".number_format($values['costing_summary']['total_cost_combined'], 2);
					$content_array['content'] .= <<<EOT
					<tr>
						<td><a href="{$url}/costs/activities_gsq?view=by_grade&term={$term}&camp={$camp}&grade={$grade}&theme={$theme_id}">{$values['name']}</a></td>
						<td>{$theme_budget_per_camper_yr1}</td>
						<td><span data-tooltip aria-haspopup="true" class="has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="{$values['costs']['theme_cost_per_camper_yr1_calc']}">{$theme_cost_per_camper}</span></td>
						<!-- <td>{$theme_budget_yr1_enrollment}</td> -->
						<td>{$theme_budget_per_camper_yr2}</td>
						<td><span data-tooltip aria-haspopup="true" class="has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="{$values['costs']['theme_cost_per_camper_yr2_calc']}">{$theme_cost_per_camper_yr2}</span></td>
						<!-- <td>{$theme_budget_yr2_enrollment}</td> -->
						<td><span data-tooltip aria-haspopup="true" class="has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="{$values['costing_summary']['total_cost_combined_calc']}">{$total_cost_combined}</span></td>
					</tr>
EOT;
				}
			}

			$total_avg = "$".number_format($camps[$camp]['costing_summary'][$term]['total_average'], 2);
			// 3/7/17 Divide $all_total_cost by 2
			$all_total_cost = $camps[$camp]['costing_summary'][$term]['total_cost'] / 2;
			$all_total_cost = "$".number_format($all_total_cost, 2);
			$total_cost = "$".number_format($total_cost, 2);
			$avg_total_calc = "$total_avg = $total_cost / $total_enrollment";
			$budget_avg = $budget_total / $a;
			$budget_avg = "$".number_format($budget_avg, 2);
			$updated_on = date("n/j g:ia", strtotime($camps[$camp]['costing_summary'][$term]['updated_on']));
			$content_array['content'] .= <<<EOT
				</tbody>
				<tfoot>
					<tr>
						<th></th>
						<th></th>
						<th></th>
						<th>Total Avg:</th>
						<th><span data-tooltip aria-haspopup="true" class="has-tip [tip-top tip-bottom tip-left tip-right] [radius round]" title="{$avg_total_calc}">{$total_avg}</span></th>
						<th title="Averaged and divided by 2">{$all_total_cost}</th>
					</tr>
						<th></th>
						<th></th>
						<th></th>
						<th>Budget Avg:</th>
						<th>{$budget_avg}</th>
						<th></th>
					</tr>
				</tfoot>
			</table>
			<p>Updated on {$updated_on}</p>
EOT;
			//$content_array['content'] .= gambaDebug::preformatted_arrays($camps[$camp], 'themes', 'Themes');
			return $content_array;
// 			echo "<pre>"; print_r($grades[$array['camp']]); echo "</pre>";
// 			echo "<pre>"; print_r($themes[$camp]); echo "</pre>";
// 			echo "<pre>"; print_r($camps[$camp]); echo "</pre>";
// 			echo "<pre>"; print_r($supplies); echo "</pre>";
		}

		public static function camp_list($array) {
			$url = url('/');
			$current_term = gambaTerm::year_by_status('C');
			$past_term = $current_term - 1;
			$next_term = $current_term + 1;
			$terms = gambaTerm::terms();
			$amt_cost_percentages = gambaSupplies::amt_cost_per();
			$theme_data_enrollment = self::theme_data_enrollment();
			if($array['status'] == "") { $show = "active"; } else { $show = $array['status']; }
			$camps = gambaCampCategories::camps_list($show);
			$summary_report_list = gambaCosts::summary_report_list();
			$content_array['page_title'] = "Camp Category Setup";
			$camp_list_nav = gambaCostsNav::camps_list_nav($array);
			if($array['update'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box("Camp Categories Have Been Updated", "success");
			}
			$content_array['content'] .= <<<EOT
			{$camp_list_nav}
				<form method="post" action="{$url}/costs/update_camps" name="camps" id="form-camp">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
					<p><button type="submit" class="button small radius">Update Camp Categories</button></p>
EOT;
			foreach($amt_cost_percentages as $camp => $terms) {
				foreach($terms as $term => $acp) {
					$content_array['content'] .= <<<EOT
					<input type="hidden" name="amt_cost_percentages[{$camp}][{$term}]" value="{$acp}" />
EOT;
				}
			}
			foreach($camps as $key => $values) {
				$cost_analysis_true = ""; if($values['camp_values']['cost_analysis'] == "true") { $cost_analysis_true = " checked"; }
				$content_array['content'] .= <<<EOT


				<!-- {$values['name']} -->
				<hr />
				<label>{$values['name']}: {$key}</label>
					<div class="row">
						<div class="small-12 medium-3 large-3 columns text-right">
							<label>Active Status</label>
						</div>
						<div class="small-12 medium-1 large-1 columns">
							<div class="switch switch-vertical radius">
								<input id="cost_analysis{$key}" type="checkbox" name="values[{$key}][camp_values][cost_analysis]" value="true" {$cost_analysis_true} />
								<label for="cost_analysis{$key}">
									<span class="switch-on">ON</span>
									<span class="switch-off">OFF</span>

								</label>
							</div>
						</div>
						<div class="small-12 medium-5 large-5 columns end">
							<span class="help-block">Enable Material List Cost Analysis for this camp category. <em>Applies across all Terms/Seasons</em>.</span>
						</div>
					</div>

 					<script type="text/javascript">
 							$(document).ready(function(){
 								if($('#cost_analysis{$key}').prop('checked')){
							        $('.cost_analysis_summary{$key}').show();
								} else {
 									$('.cost_analysis_summary{$key}').hide();
								}
								$('#form-camp').change(function() {
									$('.cost_analysis_summary{$key}').hide();
									if($('#cost_analysis{$key}').prop('checked')){
								        $('.cost_analysis_summary{$key}').show();
 								    }
 								});
 							});
					</script>

					<div class="row cost_analysis_summary{$key}">
						<div class="row">
							<div class="small-12 medium-3 large-3 columns text-right">
								<label for="cost_analysis_summary">Select Summary Report</label>
							</div>
							<div class="small-12 medium-6 large-6 columns end">
								<select name="values[{$key}][camp_values][cost_analysis_summary]" id="cost_analysis_summary{$key}">
									<option value="">-------------------</option>
EOT;
				foreach($summary_report_list as $rpt_id => $rpt_name) {
					$cost_analysis_summary_selected = "";
					if($values['camp_values']['cost_analysis_summary'] == $rpt_id) { $cost_analysis_summary_selected = " selected"; }
					$content_array['content'] .= <<<EOT
									<option value="{$rpt_id}"{$cost_analysis_summary_selected}>{$rpt_name}</option>
EOT;
				}
				$content_array['content'] .= <<<EOT
								</select>
								<span class="help-block">Select the report format that you want to appear in the Material List Cost Analysis Summaries. <em>Applies across all Terms/Seasons</em>.</span>
							</div>
						</div>
EOT;
				if($theme_data_enrollment[$key] == "true") {
					$cost_enrollment_data = "";
					if($values['camp_values']['cost_enrollment_data'] == "true") {
						$cost_enrollment_data = " checked";
					}
					$content_array['content'] .= <<<EOT
						<div class="row">
							<div class="small-12 medium-3 large-3 columns text-right">
								<label>Enrollment</label>
							</div>
							<div class="small-12 medium-1 large-1 columns">
								<div class="switch switch-vertical radius">
									<input id="enrollment_data{$key}" type="checkbox" name="values[{$key}][camp_values][cost_enrollment_data]" value="true" {$cost_enrollment_data} />
									<label for="enrollment_data{$key}">
										<span class="switch-on">ON</span>
										<span class="switch-off">OFF</span>

									</label>
								</div>
							</div>
							<div class="small-12 medium-5 large-5 columns end">
								<span class="help-block">Select to use <a href="{$url}/enrollment">Camper Enrollment</a> data instead of manual entry. <em>Applies across all Terms/Seasons</em>.</span>
							</div>
						</div>
EOT;
				}
				$cost_grade_display = "";
				if($values['camp_values']['cost_grade_display'] == "true") {
					$cost_grade_display = " checked";
				}
				$content_array['content'] .= <<<EOT
						<div class="row">
							<div class="small-12 medium-3 large-3 columns text-right">
								<label>Display by Grade</label>
							</div>
							<div class="small-12 medium-1 large-1 columns">
								<div class="switch switch-vertical radius">
									<input id="cost_grade_display{$key}" type="checkbox" name="values[{$key}][camp_values][cost_grade_display]" value="true" {$cost_grade_display} />
									<label for="cost_grade_display{$key}">
										<span class="switch-on">ON</span>
										<span class="switch-off">OFF</span>

									</label>
								</div>
							</div>
							<div class="small-12 medium-5 large-5 columns end">
								<span class="help-block">Select to divide up the themes by Grade. <em>Applies across all Terms/Seasons</em>.<br />
								Note: Really only applicable at this point for Camp G. </span>
							</div>
						</div>
EOT;
				$quantity_type_avg = "";
				if($values['camp_values']['quantity_type_avg'] == "true") {
					$quantity_type_avg = " checked";
				}
				$content_array['content'] .= <<<EOT
						<div class="row">
							<div class="small-12 medium-3 large-3 columns text-right">
								<label>Quantity Type Average</label>
							</div>
							<div class="small-12 medium-1 large-1 columns">
								<div class="switch switch-vertical radius">
									<input id="quantity_type_avg{$key}" type="checkbox" name="values[{$key}][camp_values][quantity_type_avg]" value="true" {$quantity_type_avg} />
									<label for="quantity_type_avg{$key}">
										<span class="switch-on">ON</span>
										<span class="switch-off">OFF</span>

									</label>
								</div>
							</div>
							<div class="small-12 medium-5 large-5 columns end">
								<span class="help-block">Check to turn on and off the selection of quantity types to use one as the average value and exclude the other quantity type from calculation. Quantity types can be selected at <a href="{$url}/costs/quantity_type_setup?camp={$key}">Quantity Types Setup</a>.<br />
								Note: Use on Large/Small and DLI/Non-DLI Camp Quantity Types. </span>
							</div>
						</div>
EOT;
				$cost_non_curriculum = "";
				if($values['camp_values']['cost_non_curriculum'] == "true") {
					$cost_non_curriculum = " checked";
				}
				$content_array['content'] .= <<<EOT
						<div class="row">
							<div class="small-12 medium-3 large-3 columns text-right">
								<label>Group Non-Curriculum</label>
							</div>
							<div class="small-12 medium-1 large-1 columns">
								<div class="switch switch-vertical radius">
									<input id="cost_non_curriculum{$key}" type="checkbox" name="values[{$key}][camp_values][cost_non_curriculum]" value="true" {$cost_non_curriculum} />
									<label for="cost_non_curriculum{$key}">
										<span class="switch-on">ON</span>
										<span class="switch-off">OFF</span>

									</label>
								</div>
							</div>
							<div class="small-12 medium-5 large-5 columns end">
								<span class="help-block">Group with other Camp Categories that are checked off and display using the Camp G Summary Report Format. <em>Applies across all Terms/Seasons</em>.</span>
							</div>
						</div>
						<div class="row">
							<div class="small-12 medium-3 large-3 columns text-right">
								<label>Amortization By Year</label>
							</div>
EOT;
				$i = 1;
				foreach($terms as $term => $term_values) {
					if($term == $current_term || $term == $next_term || $term == $past_term) {
						if($i == 3) { $end_column = " end"; }
						$content_array['content'] .= <<<EOT
							<div class="small-3 medium-1 large-1 columns text-right">
								<label>{$term}</label>
							</div>
							<div class="small-3 medium-1 large-1 columns{$end_column}">
								<input type="text" name="amt_cost_percentages[{$key}][{$term}]" value="{$amt_cost_percentages[$key][$term]}" />
							</div>
EOT;
						$i++;
					}
				}
				$content_array['content'] .= <<<EOT
							<div class="small-12 medium-5 large-6 large-offset-3 columns end">
								<span class="help-block">You can change the inputed amount for the current, past and the next terms/seasons. Enter in the following format. If you want to do 75% enter as .75.</span>
							</div>
						</div>
					</div>
EOT;
			}
			$content_array['content'] .= <<<EOT

					<p><button type="submit" class="button small radius">Update Camp Categories</button></p>
					<input type="hidden" name="action" value="update_camps" />
				</form>
EOT;

			//$content_array['content'] .= "<pre>" . print_r($camps, true) . "</pre>";
			return $content_array;
		}

		/**
		 * Calculate Material Costs
		 * @param unknown $array
		 */
		public static function calculate_material_costs($array) {
			$url = url('/');

			$terms = gambaTerm::terms();
			$costing_summary_updated = Config::find('costing_summary_updated');
			$costing_summary_updated_array = json_decode($costing_summary_updated->value, true);
			$current_term = gambaTerm::year_by_status('C');
			$date = date("Ymdhis");
			$content_array['page_title'] = "Calculate Material Costs";

			if($array['calculate'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box("{$array['term']} term is now calculating.", "success");
			}
			$content_array['content'] .= <<<EOT
			<form method="post" action="{$url}/costs/calculate" name="calculate">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
				<div class="row">
					<div class="columns large-2 medium-2 small-6">
						<label>Select Term:</label>
					</div>
					<div class="columns large-3 medium-3 small-6">
						<select name="term">
EOT;
			foreach($terms as $term => $values) {
				$term_select = ""; if($array['term'] == $term || ($array['term'] == "" && $term == $current_term)) {
					$term_select = " selected";
				}
				if($costing_summary_updated_array[$term]['updated_on'] != "" || $costing_summary_updated_array[$term]['updated_on'] != NULL) {
					$updated_on = " (Last Run: ". date("n/j g:ia", strtotime($costing_summary_updated_array[$term]['updated_on'])) .")";
				} else {
					$updated_on = "";
				}
				$content_array['content'] .= <<<EOT
							<option value="{$term}"{$term_select}>{$term}{$updated_on}</option>
EOT;
			}
			$content_array['content'] .= <<<EOT
						</select>
					</div>
					<div class="columns large-2 medium-2 small-6">
						<label>Select Camp:</label>
					</div>
					<div class="columns large-3 medium-3 small-6">
						<select name="camp_selected">
EOT;
			if($array['camp_selected'] == "") { $all_camps = " selected"; }
			$content_array['content'] .= <<<EOT
							<option value=""{$all_camps}>All Camp Categories</option>
EOT;
			$camps = gambaCampCategories::camps_list();
			foreach($camps as $camp => $values) {
				if($values['camp_values']['cost_analysis'] == "true") {
					$camp_select = ""; if($array['camp_selected'] == $camp) {
						$camp_select = " selected";
					}
					$content_array['content'] .= <<<EOT
							<option value="{$camp}"{$camp_select}>{$values['name']}</option>
EOT;
				}
			}
			$content_array['content'] .= <<<EOT
						</select>
					</div>
					<div class="columns large-2 medium-2">
						<p><input type="submit" name="submit" value="Calculate" class="button primary radius small" /></p>
					</div>
				</div>
				<div class="row">
					<div class="columns large-12 medium-12 small-12">
						<a href='{$url}/logs/costs.log?{$date}' target='logfile' class="button primary radius small">Costs Log</a>
EOT;
			foreach($camps as $camp => $values) {
				if($values['camp_values']['cost_analysis'] == "true") {
					$camp_select = ""; if($array['camp_selected'] == $camp) {
						$camp_select = " selected";
					}
					$content_array['content'] .= <<<EOT
						<a href='{$url}/logs/costs-{$camp}.log?{$date}' target='logfile' class="button primary radius small">{$values['alt_name']} Log</a>
EOT;
				}
			}
			$content_array['content'] .= <<<EOT
					</div>
				</div>
EOT;
			$content_array['content'] .= <<<EOT
				<input type="hidden" name="action" value="calculate" />

			</form>
EOT;
			if($array['view_logs'] == 1) {
				// Located in Routes/logs.php and LogsController@enroll_calc_log
				$content_array['content'] .= <<<EOT
			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/enroll_calc_log?logfile=costs.log&limit=3500&{$date}', function(data) {
							var logfile = nl2br(data);
							//var logfile = data;
							$("#cost_logfile").html("<p>" + logfile + "</p>");
							setTimeout(functionToLoadFile, 500);
						});
					};
					function nl2br (str) {
					  var breakTag = '<br />';
					  return (str + '').replace(/([^>\\r\\n]?)(\\r\\n|\\n\\r|\\r|\\n)/g, '$1'+ breakTag +'$2');
					};
					setTimeout(functionToLoadFile, 10);
				});
			</script>
			<div id="cost_logfile" style="height: 1500px;"></div>
EOT;
			} else {
				$content_array['content'] .= <<<EOT
			<p><a href="{$url}/costs/calculate_material_costs?view_logs=1" class="button primary radius small">View Log Files</a></p>
EOT;
			}
			return $content_array;
// 			echo "<pre>"; print_r($terms); echo "</pre>";
		}

	}