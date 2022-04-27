<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\Config;
	use App\Models\Enrollment;
	use App\Models\EnrollmentExt;
	use App\Models\Locations;
	use App\Models\Supplies;
	use App\Models\SupplyLists;

	use App\Gamba\gambaLogs;
	//use App\Gamba\gambaPacking;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaGrades;
	use App\Gamba\gambaActivities;
	use App\Gamba\gambaParts;
	use App\Gamba\gambaInventory;
	use App\Gamba\gambaThemes;
	use App\Gamba\gambaSupplies;
	use App\Gamba\gambaLocations;
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaQuantityTypes;
	use App\Gamba\gambaUsers;

	class gambaCalc {
		/**
		 *
		 * Array list of all the possible calculations. Some can be used by more than one camp
		 *
		 */
		public static function enrollment_data() {
			$array = array(
					// Camp Galileo
					"1" => array(
						"theme_weeks" => array(
							"name" => "Theme Weeks by Grade and Location",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"theme_values" => "true",
							"extra_class" => "true"
						),
						"instructors" => array(
							"name" => "Theme Instructors",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
							"extra_class" => "true"
						),
						"total_classes" => array(
							"name" => "Total Classes for Theme by Grade and Location",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"theme_values" => "true",
							"extra_class" => "true"
						),
						"camper_weeks" => array(
							"name" => "Camper Weeks for Theme by Grade and Location",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"theme_values" => "true",
							"extra_class" => "true"
						),
						"kids_per_class" => array(
							"name" => "Max # of Kids per Class by Grade and Location",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
							"extra_class" => "true"
						),
						"num_classrooms" => array(
							"name" => "Number Classrooms by Grade and Location",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
							"extra_class" => "true"
						),
						"rev_enroll" => array(
							"name" => "Revised Average Weekly Enrollment",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"theme_values" => "true",
							"extra_class" => "true"
						),
						"no_value" => array(
							"name" => "No Value - Get Location and Theme Values from Enrollment Table",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"extra_class" => "true"
						),
					),
					// Camp Galileo Basic Supplies
					"17" => array(
						"theme_weeks" => array(
							"name" => "Theme Weeks by Grade and Location",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"theme_values" => "true",
							"extra_class" => "true"
						),
						"total_classes" => array(
							"name" => "Total Classes for Theme by Grade and Location",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"theme_values" => "true",
							"extra_class" => "true"
						),
						"camper_weeks" => array(
							"name" => "Camper Weeks for Theme by Grade and Location",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"theme_values" => "true",
							"extra_class" => "true"
						),
						"kids_per_class" => array(
							"name" => "Max # of Kids per Class by Grade and Location",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
							"extra_class" => "true"
						),
						"rev_enroll" => array(
							"name" => "Revised Average Weekly Enrollment",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"theme_values" => "true",
							"extra_class" => "true"
						),
						"num_classrooms" => array(
							"name" => "Number Classrooms by Grade and Location",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
							"extra_class" => "true"
						),
					),
					// Camp Galileo Outdoors
					"6" => array(
						"total_camp_weeks" => array(
							"name" => "All Grade Camper Weeks",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"grade_array" => array(6 => 10, 7 => 1, 8 => 3, 9 => 4),
							"location_id" => "true",
							"camp" => "true",
							"camp_id_value" => 1,
							"location_values" => "true",
							"extra_class" => "false"
						),
						"kids_per_class" => array(
							"name" => "All Grade Kids Per Class",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"grade_array" => array(6 => 10, 7 => 1, 8 => 3, 9 => 4),
							"location_id" => "true",
							"camp" => "true",
							"camp_id_value" => 1,
							"location_values" => "true",
							"extra_class" => "false"
						),
					),
					// Galileo Summer Quest
					"2" => array(
						"tot_enrollments" => array(
							"name" => "Total Enrollments",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"theme_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
							"extra_class" => "false"
						),
						"num_classrooms" => array(
							"name" => "Number Classrooms",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"theme_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
							"extra_class" => "false"
						),
						"sessions" => array(
							"name" => "Total Sessions",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"theme_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
							"extra_class" => "false"
						),
						"campers" => array(
							"name" => "Maximum Campers",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"theme_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
							"extra_class" => "false"
						),
						"instructors" => array(
							"name" => "Instructors",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"theme_id" => "true",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
							"extra_class" => "false"
						),
					),
					// Camp Galileo Extended Care
					"5" => array(
						"pm_enrollment_session" => array(
							"name" => "PM Enrollment per Session",
							// sql query values: table name and fields
							"table" => "enrollmentext",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
						),
						"total_enrollment" => array(
							"name" => "Total Enrollments",
							// sql query values: table name and fields
							"table" => "enrollmentext",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
						),
					),
					// Galileo Summer Quest Extended Care
					"10" => array(
						"pm_enrollment_session" => array(
							"name" => "PM Enrollment per Session",
							// sql query values: table name and fields
							"table" => "enrollmentext",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
						),
						"total_enrollment" => array(
							"name" => "Total Enrollments",
							// sql query values: table name and fields
							"table" => "enrollmentext",
							"location_id" => "true",
							"camp" => "true",
							"location_values" => "true",
						),
					),
					// Camp Galileo Kinder PM
					"4" => array(
						"tot_camper_weeks" => array(
							"name" => "Total Kinder Camper Weeks",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"grade_id_value" => 1,
							"location_id" => "true",
							"theme_values" => "true",
							"camp" => "true",
							"camp_id_value" => 1,
							"location_values" => "true"
						),
						"class_totals" => array(
							"name" => "Total Classes All Kinder Topics",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"grade_id_value" => 1,
							"location_id" => "true",
							"theme_values" => "true",
							"camp" => "true",
							"camp_id_value" => 1,
							"location_values" => "true"
						),
						"rev_enroll" => array(
							"name" => "Revised Enrollment",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"grade_id_value" => 1,
							"location_id" => "true",
							"theme_values" => "true",
							"camp" => "true",
							"camp_id_value" => 1,
							"location_values" => "true"
						),
						"num_classrooms" => array(
							"name" => "Quantity Per Classroom",
							// sql query values: table name and fields
							"table" => "enrollment",
							"grade_id" => "true",
							"grade_id_value" => 1,
							"theme_values" => "true",
							"location_id" => "true",
							"camp" => "true",
							"camp_id_value" => 1,
							"location_values" => "true"
						),
					),
					// Camp Galileo Curriculum Training
					"3" => array(
					),
					// Camp Galileo Position Training
					"11" => array(
					),
					// Galileo Summer Quest Curriculum Training
					"13" => array(
					),
					// Galileo Learning All Staff Position (Saturday) Training
					"12" => array(
						"train_classrooms" => array(
							"name" => "Total Training Classrooms",
							// sql query values: table name and fields
							"table" => "saturdaytraining",
							"activity_id" => "true",
							"location_id" => "false",
							"training_value" => "true"
						),
						"training_trainers" => array(
							"name" => "Total Trainers",
							// sql query values: table name and fields
							"table" => "saturdaytraining",
							"activity_id" => "true",
							"location_id" => "false",
							"training_value" => "true"
						),
						"train_activity_rotations" => array(
							"name" => "Total Rotations",
							// sql query values: table name and fields
							"table" => "saturdaytraining",
							"activity_id" => "true",
							"location_id" => "false",
							"training_value" => "true"
						),
						"train_max_staff_per_rotation" => array(
							"name" => "Max Staff Per Rotation",
							// sql query values: table name and fields
							"table" => "saturdaytraining",
							"activity_id" => "true",
							"location_id" => "false",
							"training_value" => "true"
						),
						"sat_activity_total_staff" => array(
							"name" => "Total Staff",
							// sql query values: table name and fields
							"table" => "saturdaytraining",
							"activity_id" => "true",
							"location_id" => "false",
							"training_value" => "true"
						),
					),

					// Office
					"7" => array(
						"total_staff_per_camp" => array(
							"name" => "Total Staff Per Camp",
							// sql query values: table name and fields
							"table" => "officedata",
							"location_id" => "true",
							"office_data_values" => "true",
							"sum_office_values" => "false",
							"camp_by_location" => "true",
							"office_field" => "total_staff_per_camp",
						),
						"total_camper_weeks" => array(
							"name" => "Total Camper Weeks",
							// sql query values: table name and fields
							"table" => "officedata",
							"location_id" => "true",
							"office_data_values" => "true",
							"sum_office_values" => "false",
							"camp_by_location" => "true",
							"office_field" => "total_camper_weeks",
						),
						"total_tls" => array(
							"name" => "CG Team Leaders and GSQ Assistant Instructors Per Site",
							// sql query values: table name and fields
							"table" => "officedata",
							"location_id" => "true",
							"office_data_values" => "true",
							"sum_office_values" => "false",
							"camp_by_location" => "true",
							"office_field" => "total_tls",
						),
						"revised_enrollment" => array(
							"name" => "Enrollment",
							// sql query values: table name and fields
							"table" => "officedata",
							"location_id" => "true",
							"office_data_values" => "true",
							"sum_office_values" => "false",
							"camp_by_location" => "true",
							"office_field" => "revised_enrollment",
						),
						"max_enrollment" => array(
							"name" => "Max Enrollment",
							// sql query values: table name and fields
							"table" => "officedata",
							"location_id" => "true",
							"office_data_values" => "true",
							"sum_office_values" => "false",
							"camp_by_location" => "true",
							"office_field" => "max_enrollment",
						),
						"classrooms" => array(
							"name" => "Classrooms",
							// sql query values: table name and fields
							"table" => "officedata",
							"location_id" => "true",
							"office_data_values" => "true",
							"sum_office_values" => "false",
							"camp_by_location" => "true",
							"office_field" => "classrooms",
							"camp_g_multiplier" => "true",
						),
					),

			);
			return $array;
		}

		private static function assign_packing_list($supply_id, $calc, $debug) {
			$url = url('/');
			$camp = $calc['camp'];
			$part = $calc['part'];
			$debug = $calc['debug'];
			$grade_id = $calc['grade_id'];
			$theme_id = $calc['theme_id'];
			$term = $calc['term'];
			$theme_type = $calc['theme_type'];
			$packing = gambaPacking::packing_lists(); $packing_camps = $packing['camps'][$camp];
			$standards_separate = $packing_camps['camp_values']['standard'];
			$set_theme_type = $packing_camps['camp_values']['theme_type'];
			// Camps with multiple packing lists. Works for Camp G
			if(count($packing_camps['lists']) > 1) {
				// Check Theme Type
				foreach($packing_camps['lists'] as $key => $values) {
					$list_theme_type = $values['theme_type'];
					if($list_theme_type == $theme_type) {
						// Assign List ID based on Theme Type
						$update_supplies = Supplies::where('id', $supply_id)->update([
							'packing_id' => $key,
							'nonstandard' => '0'
						]);
						if($debug == 1) { $debug_content .= "<td>$key</td>"; }
						$theme_packing_id = $key;
						break;
					}
				}
				foreach($packing_camps['lists'] as $key => $values) {
					if($values['theme_type']) {
						$theme_type_array[$values['theme_type']] = $key;
					}
				}
				// Check Standard/Non-Standard
				if($standards_separate == "true") {
					// Get Standards Packing ID
					foreach($packing_camps['lists'] as $key => $values) {
						if($values['separate'] == "nonstandard") {
							$packing_id = $key;
							break;
						}
					}
					$supplies = Supplies::select('supplies.id')->leftjoin('themes', 'themes.id', '=', 'supplies.theme_id')->where('supplies.part', '=', $part)->where('supplies.term', '=',  $term)->where('supplies.grade_id', '=', $grade_id)->where('themes.theme_type', '=', $theme_type)->where('supplies.camp_id', '=', $camp);
					$supplies->get();
					$num_rows = $supplies->count;
					if($num_rows == 1) {
						$update_supply = Supplies::where('id', '=', $supply_id)->update([
							'packing_id' => $packing_id,
							'nonstandard' => '1'
						]);
						$nonstandard = "true";
						if($debug == 1) { $debug_content .= "<td>Non Standard </td><td>$packing_id</td><td>"; }
					} else {
						if($num_rows > 0) {
							if($debug == 1) {
								$debug_content .= "<td>Standard </td><td>";
							}
							foreach($supplies as $key => $row) {
								$id = $row['id'];
								$update_supply = Supplies::where('id', '=', $id)->update([
									'packing_id' => $theme_packing_id,
									'nonstandard' => '0'
								]);
							}
							if($debug == 1) {
								$debug_content .= "$theme_packing_id</td>
								<td>$num_rows</td>";
							}
							$packing_id = $theme_packing_id;
						}
					}
				}
			} else {
				// Camps with only one packing list
				foreach($packing_camps['lists'] as $key => $values) {
					if($calc['debug'] == 1) {
						$debug_content .= "<td>$key</td><td>N/A</td><td>$key</td><td>N/A</td>";
					}
					$update_supply = Supplies::where('id', '=', $supply_id)->update([
						'packing_id' => $key
					]);
				}
			}
			// Set Highest
			if($nonstandard != "true" && $packing_camps['list_values']['highest'] == "true") {
				$supplies = Supplies::select('supplies.id');
				$supplies = $supplies->leftjoin('themes', 'themes.id', '=', 'supplies.theme_id');
				$supplies = $supplies->whereRaw("supplies.itemtype = 'NC' OR supplies.itemtype = 'NCx3'");
				$supplies = $supplies->where('supplies.part', '=', $part);
				$supplies = $supplies->where('supplies.term', '=', $term);
				$supplies = $supplies->where('supplies.packing_id', '=', $packing_id);
				if($packing_camps['list_values']['col_grade'] == "true") {
					$supplies = $supplies->where('supplies.grade_id', '=', $grade_id);
				}
				if($packing_camps['list_values']['col_theme'] == "true") {
					$supplies = $supplies->where('themes.theme_type', '=', $theme_type);
				}
				$supplies = $supplies->orderBy('supplies.total_amount', 'DESC')->get();
				$num_rows = $supplies->count();
				if($num_rows > 0) {
					$i = 0;
					foreach($supplies as $key => $row) {
						$id = $row->id;
						if($i == 0) {
							$update = Supplies::where('id', $id)->update([
								'lowest' => '0'
							]);
						} else {
							$update = Supplies::where('id', $id)->update([
								'lowest' => '1'
							]);
						}
						$i++;
					}
					if($debug == 1) { $debug_content .= "<td>Highest</td>"; }
				} else {
					if($debug == 1) { $debug_content .= "<td>Low</td>"; }
				}
			} else {
				if($debug == 1) { $debug_content .= "<td>N/A</td>"; }
				$update = Supplies::where('id', $id)->update([
					'lowest' => '0'
				]);
			}
			return $debug_content;
		}

		public static function view_basic_supplies_calculation() {
			$content_array['page_title'] = "Basic Supplies Packing Calculation";
			$url = url('/');
			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$date = date("YmdHis");
			$term = gambaTerm::year_by_status('C');
			$content_array['content'] .= <<<EOT
			<p><a href="{$url}/settings/calculate_basic_packing" class="button small radius">Recalculate Basic Supplies Packing Quantities</a></p>

			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/enroll_calc_log?limit=5k&logfile=basic_calc.log&{$date}', function(data) {
							var logfile = data;
							$("#basic_calc").html("<p>Log File <a href='{$url}/logs/basic_calc.log' target='new'>View Log File</a></p><pre>" + logfile + "</pre>");
							setTimeout(functionToLoadFile, 500);
						});
					};
					setTimeout(functionToLoadFile, 10);
				});
			</script>


			<div class="small-12 medium-12 large-12 columns" id="basic_calc"></div>
EOT;
			return $content_array;
		}

		public static function basic_calc_status() {
			$row = Config::select('value')->where('field', 'basic_calc')->first();
			$value = $row['value'];
			return $value;
		}

		public static function conversion_calc() {
			$row = Config::select('value')->where('field', 'convert_calc')->first();
			$value = $row['value'];
			if($value == "") { $value = 1; }
			return $value;
		}

		public static function basic_supplies_subtraction($term) {
			$url = url('/');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			gambaLogs::truncate_log('basic_calc.log');
			gambaLogs::data_log('Start Recalculating', 'basic_calc.log');
			$update = Supplies::where('term', $term)->update([
					'packing_recalc_quantities' => "",
					'packing_amount' => '0',
					'packing_subtracted' => '0'
			]);
// 			gambaLogs::data_log($sql, 'basic_calc.log');
			$basic_packing_supplies_lists = gambaPacking::basic_packing_supplies_lists();
			$locations = gambaLocations::locations_list();
			$basic_lists = $basic_packing_supplies_lists['basic'];
			$sales_pack_by = $basic_packing_supplies_lists['sales_pack_by'];
			foreach($basic_lists as $basic_list_id => $values) {
				$packing_id = $values['basic_supplies_list'];

				$supplies = Supplies::select('id', 'part', 'grade_id', 'theme_id', 'packing_quantities', 'total_amount')->where('packing_id', $basic_list_id)->where('term', $term)->where('lowest', 0)->get();
				$num_supplies = $supplies->count();
				//gambaLogs::data_log(\DB::last_query(), 'basic_calc.log');
				if($num_supplies > 0) {
					foreach($supplies as $key => $row) {
						$id = $row['id'];
						$part = $row['part'];
						$grade_id = $row['grade_id'];
						$theme_id = $row['theme_id'];
						$total_amount = $row['total_amount'];
						$orig_total_amount = $row['total_amount'];
						$packing_quantities = json_decode($row->packing_quantities, true);
						$string = "";
						foreach($packing_quantities as $location_id => $location_data) {
							foreach($location_data as $key => $values) {
								$string .= " | ".$locations[$location_id]['abbr']." ($key) ".$values['total'];
							}
						}
						gambaLogs::data_log("-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-.-", 'basic_calc.log');
						gambaLogs::data_log("Basic Supply ID: $id | Part: $part $string", 'basic_calc.log');
						$supply_grade = Supplies::select('id', 'packing_quantities', 'total_amount')->where('part', $part)->where('packing_id', $packing_id)->where('term', $term)->where('lowest', '0')->where('grade_id', $grade_id);
						if($sales_pack_by == "theme") {
							$supply_grade = $supply_grade->where('theme_id', $theme_id);
						}
						$supply_grade = $supply_grade->orderBy('part')->orderBy('total_amount', 'DESC')->get();
						//gambaLogs::data_log(\DB::last_query(), 'basic_calc.log');
						if($supply_grade->count() > 0) {
							foreach($supply_grade as $key2 => $row2) {
								$supply_id = $row2['id'];
								$supply_packing_quantities = json_decode($row2->packing_quantities, true);
								$supply_total_amount = $row2['total_amount'];
								$packing_recalc_quantites = array();
								if($total_amount > $supply_total_amount) {
									$total_amount = $total_amount - $supply_total_amount;
									$supply_total_amount = 0;
								} else {
									$supply_total_amount = $supply_total_amount - $total_amount;
									$total_amount = 0;
								}
								foreach($supply_packing_quantities as $location_id => $packing_values) {
									foreach($packing_values as $key => $location_values) {
										// If Basic Packing Quantity for Location is Greater Than Supply Packing Quantity
										$location_abbr = $locations[$location_id]['abbr'];
										gambaLogs::data_log("Supply ID: $supply_id - $part - Grade: $grade_id - Location: $location_id ($key) $location_abbr", 'basic_calc.log');
										gambaLogs::data_log("Basic Supply Total: ".$packing_quantities[$location_id][$key]['total'] . " - Calc Total Before: ".$packing_values[$key]['total'], 'basic_calc.log');
										if($packing_quantities[$location_id][$key]['total'] > $packing_values[$key]['total']) {
											$packing_quantities[$location_id][$key]['total'] = $packing_quantities[$location_id][$key]['total'] - $packing_values[$key]['total'];
											$packing_recalc_quantites[$location_id][$key]['total'] = 0;
										} else {
											$packing_recalc_quantites[$location_id][$key]['total'] = $packing_values[$key]['total'] - $packing_quantities[$location_id][$key]['total'];
											$packing_quantities[$location_id][$key]['total'] = 0;
										}
										gambaLogs::data_log("ReCalc Total After: ".$packing_recalc_quantites[$location_id][$key]['total'], 'basic_calc.log');
									}
								}
								$json_packing_recalc_quantites = json_encode($packing_recalc_quantites);
								$update = Supplies::where('id', $supply_id)->update([
										'packing_recalc_quantities' => $json_packing_recalc_quantites,
										'packing_total' => $supply_total_amount
								]);
// 								gambaLogs::data_log($sql3, 'basic_calc.log');
							}
						}
						$packing_subtracted = $orig_total_amount - $total_amount;
						gambaLogs::data_log("Basic Supply ID: $id | Part: $part | Packing Subtracted: $packing_subtracted ", 'basic_calc.log');
						$update = Supplies::where('id', $id)->update([
								'packing_subtracted' => $packing_subtracted
						]);
					}
				}
			}
			gambaLogs::data_log('End Recalculating', 'basic_calc.log');
		}


		/**
		 * Calculate all Camp Material Categories
		 */

		public static function calculate_all($term, $camp) {
			//$action_id = gambaLogs::action_start_log('calculate_all', "Calculate For Camp ID $camp and Term $term");
			$url = url('/');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			gambaLogs::truncate_log('enroll_calc.log');
			gambaLogs::truncate_log('camp_calc.log');
			gambaLogs::truncate_log('supplies_calc.log');
			gambaLogs::data_log("Start calculate_all | Term: $term | Camp ID: $camp", 'enroll_calc.log');
// 			sleep(10);
			$camps = gambaCampCategories::camps_list();
			$grades = gambaGrades::grade_list();
			if($camp == 0 || $camp == "") {
// 				exit;
// 				$camps_array = $camps;
			} else {
				$camps_array[$camp] = $camps[$camp];
// 				sleep(10);
			}
			$json_camps_array = json_encode($camps_array);
			gambaLogs::data_log("Camps Array: " . "$json_camps_array", 'enroll_calc.log'); //exit; die();
			sleep(10);
			foreach($camps_array as $camp => $camp_values) {
				$camp_name = $camp_values['name'];
				gambaLogs::data_log("Cycle Through Camps | Camp: $camp_name", 'enroll_calc.log');
				$json_camp_values = json_encode($camp_values);
				gambaLogs::data_log("Camp Values: " . $json_camp_values, 'enroll_calc.log');
// 				sleep(10);
				$camp_grades = $grades[$camp]['grades'];
				$json_camp_grades = json_encode($camp_grades);
				gambaLogs::data_log("Camp Grades: " . $json_camp_grades, 'enroll_calc.log');
// 				sleep(10);
				if(is_array($camp_grades)) {
					$grade_sql = "true";
					$grade_array = $camp_grades;
				} else {
					$grade_sql = "true";
					$camp_grades[$camp]['grades']["None"]['camp'] = $camp;
					$camp_grades[$camp]['grades']["None"]['camp_name'] = $camp_name;
					$grade_array = $camp_grades[$camp]['grades'];
				}
				$json_grade_array = json_encode($grade_array);
				gambaLogs::data_log("Grade Array: " . $json_grade_array, 'enroll_calc.log');
// 				sleep(15);
// 				exit; die();
				foreach($grade_array as $grade_id => $grade_values) {
					if($grade_id == "None") { $grade_id = 0; }
					$supplylists = SupplyLists::select('supplylists.id', 'supplylists.activity_id')->leftjoin('activities', 'activities.id', '=', 'supplylists.activity_id')->where('supplylists.locked', '=', 'true')->where('supplylists.term', '=', $term)->where('supplylists.camp_type', '=', $camp);
					if($grade_sql == "true") {
						$supplylists = $supplylists->where('activities.grade_id', '=', $grade_id);
					}
					$supplylists = $supplylists->get();
					gambaLogs::data_log("Grade: $grade_id | Term: $term | Camp: $camp_name", 'supplies_calc.log');
					//gambaLogs::data_log(\DB::last_query(), 'supplies_calc.log');
					if($supplylists->count() > 0) {
						foreach($supplylists as $key => $row) {
							$id = $row['id'];
							$supplylists_array[$id]['id'] = $id;
							$supplylists_array[$id]['term'] = $term;
							$supplylists_array[$id]['camp'] = $camp;
							$supplylists_array[$id]['activity_id'] = $row['activity_id'];
						}
					}
				}
				$json_supplylists_array = json_encode($supplylists_array);
				gambaLogs::data_log($json_supplylists_array, 'supplies_calc.log');
// 				exit; die();
// 				sleep(15);
				foreach($supplylists_array as $supplylist_id => $values) {
					$activity_info = gambaActivities::activity_info($values['activity_id']);

					gambaLogs::data_log("Supply List ID: $supplylist_id | Activity: ". $activity_info['name'], 'supplies_calc.log');
					$supplies = Supplies::select('id', 'part', 'request_quantities', 'location_quantities', 'itemtype')->where('supplylist_id', $supplylist_id)->get();
					if($supplies->count() > 0) {
						gambaLogs::data_log("Supply List ID: $supplylist_id CREATE ARRAY", 'supplies_calc.log');
						$array = "";
						foreach($supplies as $key => $row) {
							$supply_id = $row['id'];
							$array = $values;
							$array['debug'] = $debug;
							$array['activity_id'] = $values['activity_id'];
							$activity_info = gambaActivities::activity_info($activity_id);
							$array['update'][$supply_id]['part'] = $part = $row['part'];
							$part_info = gambaParts::part_info($part);
							$array['update'][$supply_id]['request_quantities'] = $request_quantities = json_decode($row->request_quantities, true);
							$array['update'][$supply_id]['location_quantities'] = $location_quantities = json_decode($row->location_quantities, true);
							$array['update'][$supply_id]['itemtype'] = $itemtype = $row['itemtype'];
							gambaLogs::data_log("Supply Item ID: $supply_id | Part: $part, ". $part_info['description'], 'supplies_calc.log');
							self::calculate_from_requests($array);
						}
					}
// 				sleep(15);
// 					gambaLogs::truncate_log('supplies_calc.log');
				}
// 				sleep(15);
// 				gambaLogs::truncate_log('camp_calc.log');
			}
// 				sleep(15);
			gambaLogs::data_log("Calculate All - Done!", 'camp_calc.log');
			gambaLogs::data_log("Calculate All - Done!", 'supplies_calc.log');
			gambaLogs::data_log("Calculate All - Done!", 'enroll_calc.log');
// 			gambaLogs::truncate_log('supplies_calc.log');
// 			gambaLogs::truncate_log('enroll_calc.log');

			gambaInventory::quantity_short();
			//gambaLogs::action_end_log($action_id);
		}

		/**
		 * Calculate From Camp G Enrollment
		 */
		public static function calculate_from_cg_enrollment($term, $grade_id, $camp, $debug = 0) {
			$url = url('/');
			if($debug == 1) {
				echo "<p>Start Calculating from Enrollment: $term, $grade_id, $camp, $debug</p>"; echo str_pad(' ', 4096)."\n"; ob_flush(); flush();
			}
			$date = date("Y-m-d H:i:s");
			// write to log file
			gambaLogs::data_log("Function: CG Enrollment | Term: $term | Grade: $grade_id | Camp: $camp", 'camp_calc.log');

			$supplylists = SupplyLists::select('supplylists.id', 'supplylists.activity_id')->leftjoin('activities', 'activities.id', '=', 'supplylists.activity')->where('supplylists.locked', 'true')->where('supplylists.term', $term)->where('supplylists.camp_type', $camp)->where('activities.grade_id', $grade_id)-get();
			if($supplylists->count() > 0) {
				foreach($supplylists as $key => $row) {
					$id = $row['id'];
					$supplylists_array[$id]['id'] = $id;
					$supplylists_array[$id]['term'] = $term;
					$supplylists_array[$id]['camp'] = $camp;
					$supplylists_array[$id]['activity_id'] = $row['activity_id'];
				}
			}
			foreach($supplylists_array as $supplylist_id => $values) {

				gambaLogs::data_log("Supply List ID: $supplylist_id", 'supplies_calc.log');
				$supplies = Supplies::select('id', 'part', 'request_quantities', 'location_quantities', 'itemtype')->where('supplylist_id', $supplylist_id)->get();

				if($debug == 1) {
					//$sql = \DB::last_query();
					echo "<p>Supply List ID: $supplylist_id - $sql</p>"; echo str_pad(' ', 4096)."\n"; ob_flush(); flush();
				}
				if($supplies->count() > 0) {
					$array = "";
					foreach($supplies as $key => $row) {
						$supply_id = $row['id'];
						$array = $values;
						$array['debug'] = $debug;
						$array['activity_id'] = $values['activity_id'];
						$array['update'][$supply_id]['part'] = $part = $row['part'];
						$array['update'][$supply_id]['request_quantities'] = $request_quantities = json_decode($row->request_quantities, true);
						$array['update'][$supply_id]['location_quantities'] = $location_quantities = json_decode($row->location_quantities, true);
						$array['update'][$supply_id]['itemtype'] = $itemtype = $row['itemtype'];
						$json_array = json_encode($array);
						gambaLogs::data_log("Supply Item ID: $supply_id: $json_array", 'supplies_calc.log');
						$json_values_array = json_encode($array);
						gambaLogs::data_log("Supply Item ID: $supply_id: $json_values_array", 'supplies_calc.log');
						if($array['debug'] == 1) {
							echo "<p>Supply Item ID: $supply_id: $json_values_array</p>"; echo str_pad(' ', 4096)."\n"; ob_flush(); flush();
						}
						self::calculate_from_requests($array);
					}
				}
			}
			if($debug == 1) {
// 				echo "<pre>" . print_r($supplylists_array, true) . "</pre>";
				exit; die();
			}
			gambaInventory::quantity_short();
		}

		/**
		 * Calculate From Material Requests
		 * @param array $supplies
		 * @param unknown $term
		 * @param unknown $camp
		 * @param unknown $activity_id
		 */
		public static function calculate_from_requests($array, $qty_short = 0) {
			$url = url('/');
			$camps = gambaCampCategories::camps_list();
			$calc['camp'] = $camp = $array['camp'];
			$camp_values = $camps[$camp]['camp_values'];
			$activity_info = gambaActivities::activity_info($array['activity_id']);
			$calc['debug'] = $debug = $array['debug'];

			//ob_end_clean();
			$debug_content .= "<h3>{$activity_info['theme_name']} - {$activity_info['grade_name']} - {$activity_info['name']} - {$camps[$camp]['name']}</h3>";
			//$debug_content .= str_pad(' ', 4096)."\n";
			//ob_flush(); flush();

			if($activity_info['grade_name'] == "") { $activity_info['grade_name'] = "No Grade"; }
			$calc['id'] = $supplylist_id = $id = $array['id'];
			gambaLogs::data_log($supplylist_id . " | " . $activity_info['theme_name'] . " | " . $activity_info['grade_name'] . " | " . $activity_info['name'] . " | " . $camps[$camp]['name'], 'camp_calc.log');
			$calc['term'] = $term = $array['term'];
			$calc['activity_id'] = $activity_id = $array['activity_id'];

			$calc['grade_id'] = $grade_id = $activity_info['grade_id'];
			$calc['theme_id'] = $theme_id = $activity_info['theme_id'];
			$calc['show_locations'] = $array['show_locations'];
			$theme = gambaThemes::theme_by_id($theme_id);
			$calc['theme_link_id'] = $theme['link_id'];
			$calc['theme_type'] = $theme_type = $activity_info['theme_type'];
			$enrollment_data = self::enrollment_data();
			$calc['enrollment_data'] = $enrollment_data[$camp];
			$camp_info = $camps[$camp];
			foreach($camps[$camp]['data_inputs'] as $key => $value) {
				if($value['enabled'] == "true") {
					$data_input_array[$key] = $value['name'];
				}
			}
			$supply_data_inputs = gambaSupplies::supplylist_data_inputs($id);

			$location_input = $camps[$camp]['location_input'];
			if($location_input == "true") {
				$data_locations = gambaLocations::locations_by_camp();
			}
			foreach($camps[$camp]['data_inputs'] as $key => $value) {
				if($value['enabled'] == "true") {
					$data_input_array[$key] = $value['name'];
				}
			}
			gambaDebug::preformatted_arrays($supply_data_inputs, 'supply_data_inputs', 'Supply Data Inputs');
			if(is_array($supply_data_inputs)) {
				$debug_content .= <<<EOT
			<table class="table table-striped table-bordered table-hover table-condensed table-small table-responsive" id="themes" style="width:300px;">
				<thead>
					<tr>
						<th>Data Input Type</th>
EOT;
				if($location_input == "true") {
					foreach($data_locations['locations'][$camp] as $key => $values) {
						if($values['terms'][$term]['active'] == "Yes") {
						$debug_content .= <<<EOT
						<th class="center" title="ID: {$key}">{$values['abbr']}</th>
EOT;
						}
					}
				} else {
					$debug_content .= <<<EOT
						<th class="center">Amount</th>
EOT;
				}
				$debug_content .= <<<EOT
					</tr>
				</thead>
				<tbody>
EOT;
				foreach($data_input_array as $key => $value) {
					$debug_content .= <<<EOT
					<tr>
						<td>{$value}</td>
EOT;
					if($location_input == "true") {
						foreach($data_locations['locations'][$camp] as $location_id => $location_values) {
							if($supply_data_inputs['data'][$key]['locations'][$location_id]['amount']) { $amount = $supply_data_inputs['data'][$key]['locations'][$location_id]['amount']; } else { $amount = 0; }
							if($location_values['terms'][$term]['active'] == "Yes") {
							print <<<EOT
						<td class="center">{$amount}</td>
EOT;
							}
						}
					} else {
						if($supply_data_inputs[$key]['amount']) { $amount = $supply_data_inputs[$key]['amount']; } else { $amount = 0; }
						$debug_content .= <<<EOT
						<td class="center">{$amount}</td>
EOT;
					}
					$debug_content .= <<<EOT
					</tr>
EOT;
				}
				$debug_content .= <<<EOT
				</tbody>
			</table>
EOT;
			}
			$packing_lists = gambaPacking::packing_lists();
			$qts = gambaQuantityTypes::quantity_types_by_camp($camp, $term);
			if(is_array($packing_lists['camps'][$camp]['list_values']['camp_locations'])) {
// 				gambaLogs::data_log('Packing Lists Camp Array', 'enroll_calc.log');
				$locations = gambaLocations::locations_by_camp();

				$debug_content .= <<<EOT
			<table class='table table-striped table-bordered table-hover table-condensed table-small'>
				<thead>
					<tr>
						<th></th>
						<th>Suppy<br />ID</th>
						<th>Part</th>
						<th>Description</th>
						<th>Item<br />Type</th>
EOT;
				if(is_array($qts['dropdown'])) {
					$debug_content .= <<<EOT
						<th>Qty Type</th>
						<th>Qty</th>
EOT;
				}
				if(is_array($qts['static'])) {
					foreach($qts['static'] as $key => $value) {
							$debug_content .= <<<EOT
						<th style="width:100px;" title="{$value['camp']}">{$value['name']}</th>
EOT;
					}
				}
				$data = "Locations: ";
				foreach($packing_lists['camps'][$camp]['list_values']['camp_locations'] as $key => $camp_id) {
					foreach($locations['locations'][$camp_id] as $location_id => $location_values) {
						if($location_values['terms'][$term]['active'] == "Yes") {
							$location_array[$location_id]['camp'] = $camp_id;
							$location_array[$location_id]['abbr'] = $abbr = $location_values['abbr'];
							$location_array[$location_id]['name'] = $name = $location_values['name'];
							$location_array[$location_id]['dstar'] = $dstar = $location_values['terms'][$term]['dstar'];
							$data .= " $location_id $name $abbr$dstar |";
							if($camp_values['dli_location'] == "true") { $dstar = $dstar; }
							else { $dstar = 0; }
							for($i = 0; $i <= $dstar; $i++) {
// 							if($debug == 1 && $calc['show_locations'] == 1) { echo "<th title='$i'>$abbr"; if($i == 1) { echo "2"; } echo "</th>"; }
								if($debug == 1) {
									if($i == 1) { $dstar_abbr = "2"; } else { $dstar_abbr = ""; }
									$debug_content .= <<<EOT
						<th title="ID: {$location_id} | {$dstar}">{$abbr}{$dstar_abbr}</th>
EOT;
								}
							}
						}
					}
				}
				//gambaLogs::data_log($data, 'camp_calc.log');
				$debug_content .= <<<EOT
						<th>Pack<br />ID</th>
						<th>Standard/<br />Non-Standard</th>
						<th>New<br />Pack<br />ID</th>
						<th>Rows</th>
						<th>Highest</th>
						<th>Total</th>
					</tr>
				</thead>
				<tbody>
EOT;
			}
			if(is_array($array['update'])) {
// 				echo "<pre>"; print_r($array['update']); echo "</pre>"; exit; die();
				$array['supplies'] = $array['update'];
				gambaLogs::data_log('Supplies Update Array', 'supplies_calc.log');
			}
			if(is_array($array['add'])) {
				$array['supplies'] = $array['add'];
				gambaLogs::data_log('Supplies Add Array', 'supplies_calc.log');
			}
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			foreach($array['supplies'] as $supply_id => $values) {
				// $values come directly from the gmb2_supplies table, request_quantities, location_quantities
				$calc['itemtype'] = $itemtype = $values['itemtype'];
				gambaLogs::data_log("Supply ID: $supply_id", 'supplies_calc.log');
				if(is_array($location_array)) {
					gambaLogs::data_log('Location Array', 'supplies_calc.log');
					$calc['part'] = $part = $values['part'];
					$part_info = gambaParts::part_info($part);
					$conversion = $part_info['conversion'];
					if($values['request_quantities']['quantity_val'] == "") {
						$values['request_quantities']['quantity_val'] = 0;
					}
					// 					echo "<pre>"; print_r($values); echo "</pre>";
					gambaLogs::data_log("\n\n\n\n[gambaCalc::calculate_from_requests] Part: {$values['part']} | Supply ID: {$supply_id} | Description: {$values['description']}", 'camp_calc.log');

					if($conversion > 0) { 
						$is_converted = " [F]";
						gambaLogs::data_log("[gambaCalc::calculate_from_requests] Part {$values['part']} [F] is Converted", 'camp_calc.log');
					} else { 
						$is_converted = ""; 
					}
					$debug_content .= <<<EOT
					<tr>
						<td title="Theme Type">{$theme_type}</td>
						<td title="Supply ID">{$supply_id}</td>
						<td title="Part Number">{$values['part']}</td>
						<td title="Part Description">{$values['description']}{$is_converted}</td>
						<td title="Item Type">$itemtype</td>
EOT;

					if(is_array($qts['dropdown'])) {
						$debug_content .= <<<EOT
						<td title="Drop Down Quantity Type"><strong>{$values['request_quantities']['quantity_type_id']}: {$qts['dropdown'][$values['request_quantities']['quantity_type_id']]['name']}</strong></td>
						<td title="Drop Down Quantity Type Value">{$values['request_quantities']['quantity_val']}</td>
EOT;
					}
					if(is_array($qts['static'])) {
						foreach($qts['static'] as $key => $value) {
							$debug_content .= <<<EOT
						<td title="Static Quantity Type Value" class="center">{$values['request_quantities']['static'][$key]}</td>
EOT;
						}
					}
					$total = 0;
					//$print_location_array = print_r($location_array, true);
					$print_location_array = "Array values commented out of log file.";
					gambaLogs::data_log("*    [gambaCalcLocations::calculate_locations] Part: {$values['part']} | Location Array: {$print_location_array}", 'camp_calc.log');
					foreach($location_array as $location_id => $location_values) {
						$calc_total['calc'] = "";
						// go through locations
						gambaLogs::data_log("*    [gambaCalcLocations::calculate_locations] Going Through Location Array | Is DLI Location: {$camp_values['dli_location']}, If true may be two locations calculated", 'camp_calc.log');
						if($camp_values['dli_location'] == "true") {
							$location_values_dstar = $location_values['dstar'];
						} else {
							$location_values_dstar = 0;
						}
						for($i = 0; $i <= $location_values_dstar; $i++) {
							if($location_values_dstar == 0) {
								$calc['location'][$location_id]['dli'] = 0;
							} else {
								$calc['location'][$location_id]['dli'] = $i + 1;
							}
							$calc['location'][$location_id]['dstar'] = $location_values_dstar;
							$calc['location'][$location_id]['camp'] = $location_values['camp'];
							$calc['location'][$location_id]['quantity_val'] = $quantity_val = $values['request_quantities']['quantity_val'];
							$calc['location'][$location_id]['quantity_type_id'] = $quantity_type_id = $values['request_quantities']['quantity_type_id'];
							//$qts_drop = json_encode($qts['dropdown'][$quantity_type_id]);
							//$qts_drop_print = print_r($qts['dropdown'][$quantity_type_id], true);
							//$qts_drop_print = "Array values commented out of log file.";
							//gambaLogs::data_log("Dropdown: " . $qts_drop_print, 'camp_calc.log');
							gambaLogs::data_log("*    [gambaCalcLocations::calculate_locations] Part: {$values['part']} | Camp: {$location_values['camp']} | Request Quantity Value: {$values['request_quantities']['quantity_val']} | Request Quantity Type ID: {$values['request_quantities']['quantity_type_id']}", 'camp_calc.log');
							// Example:
							gambaLogs::data_log("-----------------------------------------------", 'camp_calc.log');

							$data = "Location: {$location_values['abbr']} {$location_values['name']} | Double Star: {$location_values_dstar}";
							
							gambaLogs::data_log("*    [gambaCalcLocations::calculate_locations] Location: {$location_values['abbr']} {$location_values['name']} | Double Star: {$location_values_dstar} | DLI: {$calc['location'][$location_id]['dli']} | Location Total: 0", 'camp_calc.log');
							
							$location_total['total'] = 0;
							$calc_function = "";

							// Dropdown Values
							if(is_array($qts['dropdown'])) {
								//$qts_drop_print = print_r($qts_drop, true);
								$qts_drop_print = "Array values commented out of log file.";
								gambaLogs::data_log("*    [gambaCalcLocations::calculate_locations] Dropdown | Quantity Type Values: {$qts_drop_print}", 'camp_calc.log');
								gambaLogs::data_log("*    [gambaCalcLocations::calculate_locations] Location ID: $location_id | Get Dropdown Values", 'camp_calc.log');
								foreach($qts['dropdown'] as $key => $value) {
									if($key == $quantity_type_id) {
										$dropdown_itemtype = self::itemtype_set($itemtype, $value['qt_options']['cnc']);
										//echo " [" . $value['name'] . "] ";
										$calc['name'] = $value['name'];
										$calc['qt_camp'] = $value['camp']; // Added for shared quantity types
										// KQD Calculation C/NC
										if($value['qt_options']['kqdccalculation'] != "" && $dropdown_itemtype == "C") {
											gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] KQD C Calculation Field Value: {$value['qt_options']['kqdccalculation']}", 'camp_calc.log');
											$calc_total = self::kqd($calc, $value, $location_id, 'kqdccalculation', 'dropdown', $dropdown_itemtype);
											$calc_function .= "{$calc_total['location_name']}, {$calc['name']} Dropdown KQD C Calculation: ";
											$calc_function .= "{$calc_total['total']}, {$calc_total['calc']} | ";
											$calc_total = self::kqd($calc, $value, $location_id, 'kqdccalculation', 'dropdown', $dropdown_itemtype);
											if($calc_total['total'] > 0) {
												//gambaLogs::data_log("{$calc['name']} | Drop Down KQD C Calculation | {$calc_total['total']}", 'camp_calc.log'); // | Location Values: {$calc_total['location_values']} | Theme Values: {$calc_total['theme_values']}
											}

											$location_total['total'] += $calc_total['total'];
											// 											$location_total['sql'] = " - " . $calc_total['sql'];
											gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] Drop Down KQD C Calculation: {$calc_total ['total']}", 'camp_calc.log');
										}
										elseif($value['qt_options']['kqdnccalculation'] != "" && ($dropdown_itemtype == "NC" || $dropdown_itemtype == "NCx3")) {
											gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] KQD NC Calculation Field Value: {$value['qt_options']['kqdnccalculation']}", 'camp_calc.log');
											$calc_function .= "{$calc_total['location_name']}, {{$calc['name']}} Dropdown KQD NC Calculation: ";
											$calc_total = self::kqd($calc, $value, $location_id, 'kqdnccalculation', 'dropdown', $dropdown_itemtype);
											if($calc_total['total'] > 0) {
												gambaLogs::data_log("{$calc['name']} | Drop Down KQD NC Calculation | {$calc_total['total']}", 'camp_calc.log'); // | Location Values: {$calc_total['location_values']} | Theme Values: {$calc_total['theme_values']}
											}
											$calc_total['total'] = self::nc_multiplier($calc_total['total'], $dropdown_itemtype);
											$calc_function .= "{$calc_total['total']} | ";
											$location_total['total'] += $calc_total['total'];
											// 											$location_total['sql'] = " - " . $calc_total['sql'];
											gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] Drop Down KQD NC Calculation: {$calc_total ['total']}", 'camp_calc.log');
										}
										// Calculation C/NC
										elseif($value['qt_options']['ccalculation'] != "" && $dropdown_itemtype == "C") {
											gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] C Calculation Field Value: {$value['qt_options']['ccalculation']}", 'camp_calc.log');
											$calc_total = self::get_values($calc, $value, $location_id, 'ccalculation', 'dropdown', $dropdown_itemtype);
											if($calc_total['total'] > 0) {
												gambaLogs::data_log("{$calc['name']} | Drop Down C Calculation | {$calc_total['total']} | {$calc_total['sql']}", 'camp_calc.log'); // | Location Values: {$calc_total['location_values']} | Theme Values: {$calc_total['theme_values']}
												$calc_function .= "$camp - {$calc_total['location_name']}, Dropdown: C Calculation, ";
												$calc_function .= "{$calc_total['total']} | ";
												$calc_function .= $calc_total['calc'];
											}
											$location_total['total'] += $calc_total['total'];
											// 											$location_total['sql'] = " - " . $calc_total['sql'];
											gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] Drop Down C Calculation: {$calc_total ['total']}", 'camp_calc.log');
										}
										elseif($value['qt_options']['nccalculation'] != "" && ($dropdown_itemtype == "NC" || $dropdown_itemtype == "NCx3")) {
											gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] NC Calculation Field Value: {$value['qt_options']['nccalculation']}", 'camp_calc.log');
											$calc_total = self::get_values($calc, $value, $location_id, 'nccalculation', 'dropdown', $dropdown_itemtype);
											gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] Location Total: {$calc_total ['total']} | Function: {$calc_total ['calc_function']}", 'camp_calc.log');
											$calc_function .= "{$calc_total['location_name']}, {$calc['name']} Dropdown NC Calcuation, ";
											if($calc_total['total'] > 0) {
												//gambaLogs::data_log("{$calc['name']} | Drop Down NC Calculation | {$calc_total['total']}", 'camp_calc.log'); // | Location Values: {$calc_total['location_values']} | Theme Values: {$calc_total['theme_values']}

											}
											$calc_total['total'] = self::nc_multiplier($calc_total['total'], $dropdown_itemtype);
											$calc_function .= "{$calc_total['total']} | ";
											$location_total['total'] += $calc_total['total'];
											// 											$location_total['sql'] = " - " . $calc_total['sql'];
											gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] Drop Down NC Calculation: {$calc_total ['total']}", 'camp_calc.log');
										}
										elseif($value['qt_options']['data_input'] != "" || $value['qt_options']['data_input_c'] != "" || $value['qt_options']['data_input_nc'] != "") {
											$calc_function .= "Dropdown: Data Input, ";
											$calc_total = self::datainput_values($calc, $value, $location_id, $dropdown_itemtype, 'dropdown');
											if($calc_total['total'] > 0) {
												gambaLogs::data_log($calc['name'] . ' | Drop Down Data Input | '. $calc_total['total'], 'camp_calc.log');
											}
											$calc_total['total'] = self::nc_multiplier($calc_total['total'], $dropdown_itemtype);
											$calc_function .= "{$calc_total['total']} | ";
											$location_total['total'] += $calc_total['total'];
											gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] Drop Down Data Input C/NC: {$calc_total ['total']}", 'camp_calc.log');
										} else {
											if($value != "") {
												$check_location_enrollment = self::check_location_enrollment($calc, $value, $location_id, $location_values_dstar);

												if($check_location_enrollment['status'] == "Yes") {
													$calc_function .= "Dropdown: No Calc | Enrollment: {$check_location_enrollment['enrollment']} | ";
													$calc_total['total'] = $quantity_val;
													if($calc_total['total'] > 0) {
														gambaLogs::data_log($calc['name'] . ' | Drop Down No Calculation | '. $calc_total['total'], 'camp_calc.log');
													}
													$calc_total['calc'] = "";
// 													$calc_total['field'] = $values['request_quantities']['quantity_type_id'];
													$calc_total['total'] = self::nc_multiplier($calc_total['total'], $dropdown_itemtype);
													$calc_function .= "{$calc_total['total']} | ";
													$location_total['total'] += $calc_total['total'];
													//if($calc['debug'] == 1) { echo "<td title='dropdown - $field - ".$calc['name']." - $sql - $total_calc'>$quantity_val</td>"; }
													gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] Drop Down Value: {$calc_total ['total']}", 'camp_calc.log');
												}
											} else {
												$calc_function .= "";
												$calc_total['calc'] = "";
												gambaLogs::data_log("*    [gambaCalcLocations::calculate_dropdown_values] Drop Down No Value: 0", 'camp_calc.log');
											}
										}
									}
								}
							}

							// Static Values
							// Example: # per DLI Camp, # per Non-DLI Camp
							if(is_array($qts['static'])) {
								gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] Calculate Static Values", 'camp_calc.log');
								//$qts_static_print = print_r($qts_static, true);
								$qts_static_print = "Array values commented out of log file.";
								gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] Quantities Static Array: {$qts_static_print}", 'camp_calc.log');
								$request_qts_static_print = print_r($values['request_quantities']['static'], true);
								//$request_qts_static_print = "Array values commented out of log file.";
								gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] Request Quantities Static Array: {$request_qts_static_print}", 'camp_calc.log');
								foreach($qts['static'] as $key => $value) {
									if($values['request_quantities']['static'][$key] != "") {
										$static_itemtype = self::itemtype_set($itemtype, $value['qt_options']['cnc']);
										if($value['qt_options']['dli'] == "false") {
											$calc['location'][$location_id]['quantity_val'] = $values['request_quantities']['static'][$key];
											$calc['name'] = $value['name'];
											$calc['qt_camp'] = $value['camp']; // Added for shared quantity types
											// KQD Calculation C/NC
											if($value['qt_options']['kqdccalculation'] != "" && $static_itemtype == "C") {
												gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] KQD C Calculation Field Value: {$value['qt_options']['kqdccalculation']}", 'camp_calc.log');
												$calc_total = self::kqd($calc, $value, $location_id, 'kqdccalculation', 'static', $static_itemtype);
												$calc_function .= "{$calc_total['location_name']}, {$calc['name']} Static KQD C Calculation | ";
												if($calc_total['total'] > 0) {
													gambaLogs::data_log($calc['name'] . ' | Static KQD C Calculation | '. $calc_total['total'], 'camp_calc.log');
												}
												$calc_function .= "{$calc_total['total']} | ";
												//$calc_total['total'] = self::consumable_rotations($calc, $calc_total['total'], $value, $location_id, $static_itemtype);
												$location_total['total'] += $calc_total['total'];
												// 												$location_total['sql'] = " - " . $calc_total['sql'];
												$calc_total_print = print_r($calc_total, true);
												//$calc_total_print = "Array values commented out of log file.";
												gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] Calc Total Array: {$calc_total_print}", 'camp_calc.log');
											}
											elseif($value['qt_options']['kqdnccalculation'] != "" && ($static_itemtype == "NC" || $static_itemtype == "NCx3")) {
												gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] KQD NC Calculation Field Value: {$value['qt_options']['kqdnccalculation']}", 'camp_calc.log');
												$calc_total = self::kqd($calc, $value, $location_id, 'kqdnccalculation', 'static', $static_itemtype);
												$calc_function .= "{$calc_total['location_name']}, {$calc['name']} Static KQD NC Calculation, ";
												if($calc_total['total'] > 0) {
													gambaLogs::data_log($calc['name'] . ' | Static KQD NC Calculation | '. $calc_total['total'], 'camp_calc.log');
												}
												$calc_function .= "{$calc_total['total']} | ";
												$calc_total['total'] = self::nc_multiplier($calc_total['total'], $static_itemtype);
												$location_total['total'] += $calc_total['total'];
// 												$location_total['sql'] = " - " . $calc_total['sql'];
											}
											// Calculation C/NC
											elseif($value['qt_options']['ccalculation'] != "" && $static_itemtype == "C") {
												gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] C Calculation Field Value: {$value['qt_options']['ccalculation']}", 'camp_calc.log');
												$calc_total = self::get_values($calc, $value, $location_id, 'ccalculation', 'static', $static_itemtype);
												$calc_function .= "{$calc_total['location_name']}, {$calc['name']} Static C Calculation ";
												if($calc_total['total'] > 0) {
													gambaLogs::data_log($calc['name'] . ' | Static C Calculation | '. $calc_total['total'] . ' | '. $calc_total['sql'], 'camp_calc.log');
												}
												$calc_function .= "{$calc_total['total']} | ";
												//$calc_total['total'] = self::consumable_rotations($calc, $calc_total['total'], $value, $location_id, $static_itemtype);
												$location_total['total'] += $calc_total['total'];
// 												$location_total['sql'] = " - " . $calc_total['sql'];
											}
											elseif($value['qt_options']['nccalculation'] != "" && ($static_itemtype == "NC" || $static_itemtype == "NCx3")) {
												gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] NC Calculation Field Value: {$value['qt_options']['nccalculation']}", 'camp_calc.log');
												$calc_total = self::get_values($calc, $value, $location_id, 'nccalculation', 'static', $static_itemtype);
												$calc_function .= "{$calc_total['location_name']}, {$calc['name']} Static NC Calcuation, ";
												if($calc_total['total'] > 0) {
													gambaLogs::data_log($calc['name'] . ' | Static NC Calculation | '. $calc_total['total'], 'camp_calc.log');
												}
												$calc_total['total'] = self::nc_multiplier($calc_total['total'], $static_itemtype);
												$calc_function .= "{$calc_total['total']} | ";
												$location_total['total'] += $calc_total['total'];
												// 												$location_total['sql'] = " - " . $calc_total['sql'];
												gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] Location Total: {$calc_total ['location_total']} | Function: {$calc_total ['calc_function']}", 'camp_calc.log');
											}
											elseif($value['qt_options']['data_input'] != "" || $value['qt_options']['data_input_c'] != "" || $value['qt_options']['data_input_nc'] != "") {
												$calc_function .= "Static: Data Input, ";
												$calc_total = self::datainput_values($calc, $value, $location_id, $static_itemtype, 'static');
												if($calc_total['total'] > 0) {
													gambaLogs::data_log($calc['name'] . ' | Static Data Input | '. $calc_total['total'], 'camp_calc.log');
												}
												//$calc_total['total'] = self::consumable_rotations($calc, $calc_total['total'], $value, $location_id, $static_itemtype);
												$calc_total['total'] = self::nc_multiplier($calc_total['total'], $static_itemtype);
												$calc_function .= "{$calc_total['total']} | ";
												$location_total['total'] += $calc_total['total'];
												gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] Static Data Input C/NC: {$calc_total ['total']}", 'camp_calc.log');
											}
											elseif($value['qt_options']['location_size_option'] != "") {
												$calc_total_print = print_r($calc_total, true);
												//$calc_total_print = "Array values commented out of log file.";
												gambaLogs::data_log("*    [gambaCalcLocations::location_size_option] Calc Total Array: {$calc_total_print} | Calc Array: {$calc}  | Value Array: {$value} | Location ID: {$location_id} | Input Type: {$input_type} | Item Type: {$itemtype} | Location Values: {$location_values}", 'camp_calc.log');
												$calc_function .= "Static: NC Calcuation, ";
												$calc_total = self::camp_size($calc, $value, $location_id, $values['request_quantities']['static'][$key], 'static');
												if($calc_total['total'] > 0) {
													gambaLogs::data_log("*    [gambaCalcLocations::location_size_option] If Calc Total > 0  | {$calc['name']} | {$input_type} Camp Size | {$calc_total['total']}", 'camp_calc.log');
												}
												$calc_total['total'] = self::nc_multiplier($calc_total['total'], $static_itemtype);
												$calc_function .= "{$calc_total['total']} | ";
												$location_total['calc'] .= $calc_total['calc'];
												$location_total['total'] += $calc_total['total'];
// 												$location_total['sql'] = " - " . $calc_total['sql'];
												$return_array_print = print_r($return, true);
												//$return_array_print = "Array values commented out of log file.";
												//gambaLogs::data_log("*    [gambaCalcLocations::location_size_option] Return Values: {$return_array_print}", 'camp_calc.log');
												gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] Location Size Option | Total: {$calc_total ['total']}", 'camp_calc.log');
											}
											else {
												if($value != "") {
													// Check Location to See if it is a DLI in the Camp G A & S Enrollment Sheet
													$check_location_enrollment = self::check_location_enrollment($calc, $value, $location_id, $location_values_dstar);
													if($check_location_enrollment['status'] == "Yes") {
														$calc_function .= "Static: No Calc, ";
														$calc_total['total'] = $values['request_quantities']['static'][$key];
														$calc_total['total'] = self::static_input($calc_total['total'], $calc, $value, $location_id, $itemtypecalc, $qt_type, $itemtype);
														if($calc_total['total'] > 0) {
															gambaLogs::data_log($calc['name'] . ' | Static No Calculation | '. $calc_total['total'], 'camp_calc.log');
// 															$calc_total['total'] = 0;
														}
														$calc_total['total'] = self::nc_multiplier($calc_total['total'], $static_itemtype);
														$calc_function .= "{$calc_total['total']} | ";
														$calc_total['calc'] = "";
// 														$calc_total['field'] = $values['request_quantities']['quantity_type_id'];
														$location_total['total'] += $calc_total['total'];
													}
												} else {
													$calc_function .= $calc['name'] . ' | Static No Value | 0';
													gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] {$calc['name']} | Static No Value | 0", 'camp_calc.log');
													$calc_total['calc'] = "";
												}
											}
										// DLI and Non-DLI
										} else {
											gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] DLI and Non-DLI", 'camp_calc.log');
											$calc_function .= "DLI and Non-DLI, ";
											// Static Quantity is Greater Than Zero AND Quantity Type is DLI AND Location Is DLI
											if($values['request_quantities']['static'][$key] > 0 && $value['qt_options']['dli'] == "dli" && $location_values['dstar'] == 1) {
												$calc_function .= "Double Star, {$values['request_quantities']['static'][$key]}";
												$location_total['total'] += $values['request_quantities']['static'][$key];
												if($values['request_quantities']['static'][$key] > 0) {
													gambaLogs::data_log($calc['name'] . ' | Double Star | '. $values['request_quantities']['static'][$key], 'camp_calc.log');
												}
											}
											// Static Quantity is Greater Than Zero AND Quantity Type is DLI AND Location Is Not DLI
											if($values['request_quantities']['static'][$key] > 0 && $value['qt_options']['dli'] == "ndli" && $location_values['dstar'] == 0) {
												$calc_function .= "Non-Double Star, {$values['request_quantities']['static'][$key]}";
												$location_total['total'] += $values['request_quantities']['static'][$key];
												if($values['request_quantities']['static'][$key] > 0) {
													gambaLogs::data_log($calc['name'] . ' | Non-Double Star | '. $values['request_quantities']['static'][$key], 'camp_calc.log');
												}

											}
										}
									}
									$results['location_total'] = $location_total;
									$results['calc_function'] = $calc_function;
									$results['calc_total'] = $calc_total;
									$results_array_print = print_r($return, true);
									//$results_array_print = "Array values commented out of log file.";
									gambaLogs::data_log("*    [gambaCalcLocations::calculate_static_values] " .
											"Return Values: {$results_array_print}", 'camp_calc.log');
								}
							}


							// Location Assigned Amount (Override any calculted amount) - Office Inputs
							gambaLogs::data_log("Location ID: $location_id | Location Value: {$values['location_quantities'][$location_id]['value']}", 'camp_calc.log');
							if($values['location_quantities'][$location_id]['value'] != "") {
// 								$location_total['total'] = $values['location_quantities'][$location_id]['value'];
								$location_converted_total = self::conversion_amount($location_total, $values['location_quantities'][$location_id]['value'], $conversion, $camp, "true");
							} else {
								$location_converted_total = self::conversion_amount($location_total, $static_total, $conversion, $camp);
							}

							// Debug Display

							$total_converted_location = ceil($location_converted_total['total']);
							$debug_content .= <<<EOT
						<td class="center" title="QT ID: {$quantity_type_id} - DStar: {$location_values['dstar']} - Calc Function: {$calc_function} - {$location_converted_total['field']} - {$location_converted_total['name']} - {$location_total['sql']} - {$location_converted_total['calc']} - {$location_converted_total['total']}">{$total_converted_location}</td>
EOT;


							$supply_totals[$supply_id]['packing'][$location_id][$i] = $location_converted_total;
							$total += ceil($location_converted_total['total']);

							$supply_values[] = $values;
						}
						// end extra class

					}
					// end going through locations

					$supply_totals[$supply_id]['total'] = $total;

					// Assign Packing List based on Camp, Theme Type, Standard/NonStandard, Highest Amount
					$update = Supplies::where('id', $supply_id)->update([
							'lowest' => '0'
					]);
					$debug_content .= self::assign_packing_list($supply_id, $calc, $debug);



					$debug_content .= <<<EOT
						<td title="Total">{$total}</td>
					</tr>
EOT;

				}
			}
// 			exit; die();
			// Update packing_quantities and total_amount in gmb2_supplies
			gambaSupplies::update_totals($supply_totals);


			$debug_content .= <<<EOT
				</tbody>
			</table>
EOT;
// 				gambaDebug::preformatted_arrays($supply_values, 'supply_values', 'Supply Values');
// 				gambaDebug::preformatted_arrays($supply_totals, 'supply_totals', 'Supply Totals');
// 				gambaDebug::preformatted_arrays($camp_values, 'camp_values', 'Camp Values');
// 				gambaDebug::preformatted_arrays($camp_info, 'camp_info', 'Camps');
// 				gambaDebug::preformatted_arrays($qts, 'qts', 'Quantity Types');
// 				gambaDebug::preformatted_arrays($location_array, 'location_array', 'Locations');
// 				gambaDebug::preformatted_arrays($activity_info, 'activity_info', 'Activity Info');
// 				gambaDebug::preformatted_arrays($array, 'an_array', 'Some Array');
// 				gambaDebug::preformatted_arrays($calc, 'calc', 'Calc');
// 				gambaDebug::preformatted_arrays($packing_lists, 'packing_lists', 'Packing Lists');
			// Located in Routes/logs.php and LogsController@enroll_calc_log
				/*print <<<EOT
				<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/enroll_calc_log?logfile=camp_calc.log&{$date}', function(data) {
							var logfile = data;
							$("#camp_calc").html("<p><a href='{$url}/logs/camp_calc.log' target='camp_calc'>Camp Category</a></p><pre>" + logfile + "</pre>");
							setTimeout(functionToLoadFile, 500);
						});
					}
					setTimeout(functionToLoadFile, 10);
				});
			</script>
			<div class="small-12 medium-6 large-6 columns" id="camp_calc"></div>
EOT; */


// 			gambaLogs::truncate_log('enroll_calc.log');

			// Calculate Packing Totals
			// May change if we go back to standards/non-standards
			$camp_theme_type = $camp_values['theme_type'];

			$debug_content .= <<<EOT
			<p>Camp Theme Type: {$camp_theme_type}</p>
EOT;

			$lists = $packing_lists['camps'][$camp]['lists'];
			if($camp_theme_type == "true") {
				foreach($lists as $key => $pack_values) {
					if($pack_values['theme_type'] == $activity_info['theme_type']) {
						$packing_id = $key;
						$debug_content .= <<<EOT
			<p>Packing ID: {$packing_id}</p>
EOT;
					}
				}
			} else {
				$packing_id = key($lists);
			}
			// Added 2/19/17 Because we started using pack by grade.
			if($packing_lists['camps'][$camp]['list_values']['sales_pack_by'] == "grade") {
				$debug_content .= "<p>Sales Packing: Grade</p>";
				$packing_totals = gambaPacking::packing_totals_packingid_grade($term, $packing_id, $qty_short, $camp, $grade_id, $debug);
			} else {
				$debug_content .= "<p>Sales Packing: By Grade and/or Theme</p>";
				$packing_totals = gambaPacking::packing_totals_grade_theme($term, $packing_id, $qty_short, $camp, $theme_id, $grade_id, $debug);
			}

			$debug_content .= <<<EOT
			<p>Packing ID: {$packing_id} | Camp ID: {$camp} | packing_totals_grade_theme: Term {$term}, Packing ID {$packing_i}d, Qty Short {$qty_short}, Camp {$camp}, Theme {$theme_id}, Grade {$grade_id}</p>
EOT;
// 			echo "<pre>"; print_r($lists); echo "</pre>";
			// End Calculate Packing Totals
// 			if($qty_short == "") {
// 				gambaInventory::quantity_short();
// 			}
			$debug_content .= <<<EOT
			<p><a href="{$url}/supplies/supplylistview?id={$supplylist_id}&term={$array['term']}&camp={$camp}&activity_id={$array['activity_id']}&packtotalcalc=1&r={$return}" class="button small">Return to Material List</a></p>
EOT;
			if($array['debug'] == 1) {
				//echo $debug_content;
    			return $debug_content;
				exit; die();
			} else {
				return $return;
			}
		}

		/**
		 *
		 * @param unknown $itemtype
		 * @param unknown $cnc
		 * @return Ambigous <string, unknown>
		 */
		private static function itemtype_set($itemtype, $cnc) {
			gambaLogs::data_log("*    [gambaCalcLocations::itemtype_set] Item type: $itemtype | CNC: $cnc", 'camp_calc.log');
			if(itemtype_override == "true" && $cnc == "C" && $itemtype == "NC") {
				$new_itemtype = "C";
			} elseif(itemtype_override == "true" && $cnc == "NC" && $itemtype == "C") {
				$new_itemtype = "NC";
			} elseif(itemtype_override == "true" && $cnc == "NC" && $itemtype == "NCx3") {
				$new_itemtype = "NCx3";
			} else {
				$new_itemtype = $itemtype;
			}
			gambaLogs::data_log("*    [gambaCalcLocations::itemtype_set] New Item Type: $new_itemtype", 'camp_calc.log');
			return $new_itemtype;
		}

		private static function conversion_amount($total_array, $static_total, $conversion, $camp, $override = "false") {
			$total = $total_array['total']; if($override == "true") { $total = 0; $total_array['total'] = 0; }
			$total_json = json_encode($total_array);
			if($conversion != "" && $conversion > 0) {
				// If Conversion at Calculation has been turned off in Config
				if(convert_calc == 1) {
					$array['total'] = ceil(($static_total + $total) / $conversion);
				} else {
					$array['total'] = ceil($total + $static_total);
				}
			} else {
				$array['total'] = ceil($total + $static_total);
			}
			gambaLogs::data_log("*    [gambaCalcLocations::conversion_amount] Function Conversion Amount | New Total: {$array['total']} | Static Total: $static_total | Camp: $camp | Conversion: $conversion | Total Array: $total_json", 'camp_calc.log');
			return $array;
		}

		private static function datainput_values($calc, $value, $location_id, $itemtype, $qt_type) {
			// Calculate the Kid Quantity Divider
			$kqd = round($value['value'], 2);
			if($kqd == 0 || $kqd == 0.00 || $kqd == "") { $kqd = 1; }

			$supplylist_id = $calc['id'];
			$supplylist_data_inputs = gambaSupplies::supplylist_data_inputs($supplylist_id);
			$c_multiplier = $value['qt_options']['data_input_c'];
			$nc_multiplier = $value['qt_options']['data_input_nc'];
			if($supplylist_data_inputs['location_input'] == "true") {
				$table_value = $supplylist_data_inputs['data'][$value['qt_options']['data_input']]['locations'][$location_id]['amount'];
				$c_amount = $supplylist_data_inputs['data'][$c_multiplier]['data_input']['locations'][$location_id]['amount'];
				$nc_amount = $supplylist_data_inputs['data'][$nc_multiplier]['data_input']['locations'][$location_id]['amount'];
			} else {
				$table_value = $supplylist_data_inputs[$value['qt_options']['data_input']]['amount'];
				$c_amount = $supplylist_data_inputs[$c_multiplier]['amount'];
				$nc_amount = $supplylist_data_inputs[$nc_multiplier]['amount'];
			}
			$data_input_multiplier = $value['qt_options']['data_input'];
			if($itemtype == "C" && $c_multiplier != "" && $c_amount > 0 && $table_value > 0) {
				$total = (($calc['location'][$location_id]['quantity_val'] * $kqd) * $table_value) * $c_amount;
				$total_calc = $calc['camp'] . " - DI: $data_input_multiplier - C: $c_multiplier - NC: $nc_multiplier - $itemtype - $qt_type - datainput_values - ((".$calc['location'][$location_id]['quantity_val']." * $kqd) * $table_value) * $c_amount";
			} elseif($itemtype == "C" && $c_multiplier != "" && $c_amount > 0 && $table_value == 0) {
				$total = ($calc['location'][$location_id]['quantity_val'] * $kqd) * $c_amount;
				$total_calc = $calc['camp'] . " - DI: $data_input_multiplier - C: $c_multiplier - NC: $nc_multiplier - $itemtype - $qt_type - datainput_values - (".$calc['location'][$location_id]['quantity_val']." * $kqd) * $c_amount";
			} elseif($itemtype == "NC" && $nc_multiplier != "" && $nc_amount > 0 && $table_value > 0) {
				$total = (($calc['location'][$location_id]['quantity_val'] * $kqd) * $table_value) * $nc_amount;
				$total_calc = $calc['camp'] . " - DI: $data_input_multiplier - C: $c_multiplier - NC: $nc_multiplier - $itemtype - $qt_type - datainput_values - ((".$calc['location'][$location_id]['quantity_val']." * $kqd) * $table_value) * $nc_amount";
			} elseif($itemtype == "NC" && $nc_multiplier != "" && $nc_amount > 0 && $table_value == 0) {
				$total = ($calc['location'][$location_id]['quantity_val'] * $kqd) * $nc_amount;
				$total_calc = $calc['camp'] . " - DI: $data_input_multiplier - C: $c_multiplier - NC: $nc_multiplier - $itemtype - $qt_type - datainput_values - (".$calc['location'][$location_id]['quantity_val']." * $kqd) * $nc_amount";
			} elseif($itemtype == "NCx3" && $nc_multiplier != "" && $nc_amount > 0 && $table_value > 0) {
				$total = ((($calc['location'][$location_id]['quantity_val'] * $kqd) * $table_value) * $nc_amount) * 3;
				$total_calc = $calc['camp'] . " - DI: $data_input_multiplier - C: $c_multiplier - NC: $nc_multiplier - $itemtype - $qt_type - datainput_values - (((".$calc['location'][$location_id]['quantity_val']." * $kqd) * $table_value) * $nc_amount) * 3";
			} elseif($itemtype == "NCx3" && $nc_multiplier != "" && $nc_amount > 0 && $table_value == 0) {
				$total = (($calc['location'][$location_id]['quantity_val'] * $kqd) * $nc_amount) * 3;
				$total_calc = $calc['camp'] . " - DI: $data_input_multiplier - C: $c_multiplier - NC: $nc_multiplier - $itemtype - $qt_type - datainput_values - ((".$calc['location'][$location_id]['quantity_val']." * $kqd) * $nc_amount) * 3";
			} elseif($data_input_multiplier == "") {
				$total = $calc['location'][$location_id]['quantity_val'];
				$total_calc = $calc['camp'] . " - DI: $data_input_multiplier - C: $c_multiplier ($c_amount) - NC: $nc_multiplier ($nc_amount) - $itemtype - $qt_type - datainput_values - ".$calc['location'][$location_id]['quantity_val'];
			} else {
				$total = ($calc['location'][$location_id]['quantity_val'] * $kqd) * $table_value;
				$total_calc = $calc['camp'] . " - DI: $data_input_multiplier - C: $c_multiplier ($c_amount) - NC: $nc_multiplier ($nc_amount) - $itemtype - $qt_type - datainput_values - (".$calc['location'][$location_id]['quantity_val']." * $kqd) * $table_value";
			}

			$return['total'] = $total;
			if($total > 0) { $return['calc'] = $total_calc; }
			gambaLogs::data_log("*    [gambaCalcLocations::datainput_values] {$total_calc}", 'camp_calc.log');
			return $return;
		}

		private static function check_location_enrollment($calc, $value, $location_id, $dstar) {
			$field = $value['qt_options'][$itemtypecalc];
			$term = $calc['term'];

			// Seasons Data
			$terms = gambaTerm::terms();
			$season_data = $terms[$term]; $campg_packper = $season_data['campg_packper'];
			$campg_enroll_rotations = $season_data['campg_enroll_rotations'];


			$grade_id = $calc['grade_id'];
			$theme_id = $calc['theme_id'];
			$activity_id = $calc['activity_id'];
			$camp = $value['camp'];
			$dli = $calc['location'][$location_id]['dli'];
			$theme_link_id = $calc['theme_link_id'];

			if($dli == 2 && $camp == 1) {

				// SELECT
				$query = Enrollment::select('id', 'location_values', 'theme_values')->where('term', $term);
				// WHERE VALUES
				if($camp != "") {
					$query = $query->where('camp', $camp);
				}
				if($grade_id != "") {
					$query = $query->where('grade_id', $grade_id);
				}
				if($theme_id != "" && $camp != 1) {
					$query = $query->where('theme_id', $theme_id);
				}
// 				if($dli != "") {
// 					if($dli == 2) {
// 						$sql .= " AND extra_class = $dli";
// 					} else {
// 						$sql .= " AND (extra_class = 1 OR extra_class = 2)";
// 					}
// 				}
// 				$sql .= " AND (extra_class = 1 OR extra_class = 2)";
				$query = $query->where('extra_class', $dli);
				if($location_id != "") {
					$query = $query->where('location_id', $location_id);
				}
				$row = $query->first();
				if($campg_enroll_rotations == "true") {
					$theme_values = json_decode($row->theme_values, true);
					$rev_enroll = $theme_values[$theme_link_id][$theme_id]['rev_enroll'];
					$enrollment = $rev_enroll;
				} else {
					$location_values = json_decode($row->location_values, true);
					$rev_enroll = $location_values['rev_enroll'];
					$kids_per_class = $location_values['kids_per_class'];
					$enrollment = $rev_enroll;
				}
				$array['enrollment'] = $enrollment;
				if($enrollment > 0) {
					$array['status'] = "Yes";
				} else {
					$array['status'] = "No";
				}
			} elseif($camp == 2) {

				// SELECT
				$query = Enrollment::select('id', 'location_values', 'theme_values')->where('term', $term);
				// WHERE VALUES
				if($camp != "") {
					$query = $query->where('camp', $camp);
				}
				if($grade_id != "") {
					$query = $query->where('grade_id', $grade_id);
				}
				if($theme_id != "" && $camp != 1) {
					$query = $query->where('theme_id', $theme_id);
				}
				if($location_id != "") {
					$query = $query->where('location_id', $location_id);
				}
				$row = $query->first();

				$location_values = json_decode($row->location_values, true);
				$tot_enrollments = $location_values['tot_enrollments'];

				$array['enrollment'] = $tot_enrollments;
				if($tot_enrollments > 0) {
					$array['status'] = "Yes";
				} else {
					$array['status'] = "No";
				}
			} else {
				$array['status'] = "Yes";
			}
			gambaLogs::data_log("*    [gambaCalcLocations::check_location_enrollment] Check Location Enrollment SQL: | DLI: $dli | Terms: $terms | Theme ID: $theme_id | Theme Link ID: $theme_link_id | Enrollment: {$enrollment} | Camp G Enroll Rotations: {$campg_enroll_rotations} | Is Location in Enrollment: {$array['status']} | Quantity Type Camp: {$calc['qt_camp']}", 'camp_calc.log'); //
			return $array;


			$campg_enroll = Enrollment::select('id', 'theme_values')->whereRaw($where)->get()->toArray();
			$cg_themevalues = json_decode($campg_enroll[0]['theme_values'], true);
			$cg_rev_enroll = $cg_themevalues[$theme_link_id][$theme_id]['rev_enroll'];
		}

		private static function camp_size($calc, $value, $location_id, $amount, $qt_type) {
			$camp = $calc['location'][$location_id]['camp'];
			$term = $calc['term'];

			$location_size_option = $value['qt_options']['location_size_option'];
			$location_size = $value['qt_options']['location_size'];
			$field = $value['qt_options']['location_size_compare'];
			$row = EnrollmentExt::select('id', 'location_values')->where('camp', $camp)->where('location_id', $location_id)->where('term', $term)->first();
			$location_values = json_decode($row->location_values, true);
			$table_value = $location_values[$field];
			if($location_size_option == "greater" && $table_value > 0) {
				if($table_value > $location_size) {
					$total = $amount;
					$total_calc = "Greater Than - Size: $location_size - $qt_type";
				}
			} elseif($location_size_option == "less" && $table_value > 0) {
				if($table_value <= $location_size) {
					$total = $amount;
					$total_calc = "Less Than, Equal To - Size: $location_size - $qt_type";
				}
			} else {
				$total = 0;
			}
// 			$return['field'] = $field;
// 			$return['name'] = $calc['name'];
// 			$return['sql'] = $location_size_option . " - " . $sql . " - " . $field;
			$return['total'] = $total;
			gambaLogs::data_log($total_calc, 'camp_calc.log');
			$return['calc'] = $total_calc;
			return $return;
		}

		private static function static_input($total, $calc, $value, $location_id, $itemtypecalc, $qt_type, $itemtype) {
			$field = $value['qt_options'][$itemtypecalc];
			$calc_divider = $value['qt_options']['calc_divider'];
			$term = $calc['term'];

			// Seasons Data
			$terms = gambaTerm::terms();
			$season_data = $terms[$term]; $campg_packper = $season_data['campg_packper'];
			$campg_enroll_rotations = $season_data['campg_enroll_rotations'];

			$grade_id = $calc['grade_id'];
			$theme_id = $calc['theme_id'];
			$activity_id = $calc['activity_id'];
			$camp = $value['camp']; // $calc['qt_camp'];
			$dli = $calc['location'][$location_id]['dli'];
			$camp_by_location = $calc['location'][$location_id]['camp'];
			$theme_link_id = $calc['theme_link_id'];

			// SQL Statement - Begin
			$query = Enrollment::select('id', 'theme_values', 'location_values')->where('term', $term)->where('camp', $camp)->where('grade_id', $grade_id);
			if($dli == 2) {
				$query = $query->where('extra_class', $dli);
			} else {
				$query = $query->whereRaw('(extra_class = 0 OR extra_class = 1)');

			}
			$sql .= " AND location_id = $location_id";
			$row = $query->where('location_id', $location_id)->get();



			// Consumable Rotations - Static Input
			if($campg_enroll_rotations == "true") {
				$theme_values = json_decode($row->theme_values, true);
				if($value['qt_options']['crotations'] == 1 && $itemtype == "C") {
					$rotations = $theme_values[$theme_link_id][$theme_id]['rotations'];
					$c_rotations = "$rotations [C Rotations]";
				}
			} else {
				$location_values = json_decode($row->location_values, true);
				if($value['qt_options']['crotations'] == 1 && $itemtype == "C") {
					$rotations = $location_values['rotations'];
					$c_rotations = "$rotations [C Rotations]";
				}
			}
			// Consumable Theme Weeks
			$cthemeweeks = $value['qt_options']['cthemeweeks'];
			$theme_values = json_decode($row->theme_values, true);
			$theme_weeks = $theme_values[$theme_link_id][$theme_id]['theme_weeks'];
			$c_theme_weeks = "$theme_weeks [C Theme Weeks]";
			$orig_value = $total;
			if($camp == 1 && $itemtype == "C") {
				if($rotations != "" || $rotations > 0) {
					$total = $total * $rotations;
					$rotation_calucation = "$total = $orig_value * $rotations";
					$yes_rotations = "*";
				}

				if($theme_weeks != "" || $theme_weeks > 0) {
					$total = $total * $theme_weeks;
// 					$rotation_calucation = "$total = $orig_value * $rotations";
					$yes_themeweeks = "*";
				}
				gambaLogs::data_log("\n\nStatic Input - Orig Value: $orig_value | Total: $total | Rotations$yes_rotations: $rotations ({$rotation_calucation}) | Theme Week$yes_themeweeks: $theme_weeks | Item Type: $itemtype | Theme Values: {$row['theme_values']} | Location Values: {$row->location_values}", 'camp_calc.log'); // | SQL: $sql
			}
			return $total;
		}

		private static function get_values($calc, $value, $location_id, $itemtypecalc, $qt_type, $itemtype) {
			//$calc_print = print_r($calc, true);
			$calc_print = "Array values commented out of log file.";
			//$value_print = print_r($value, true);
			$value_print = "Array values commented out of log file.";
			gambaLogs::data_log(">>        [gambaCalcEnrollment::get_values] Get Values | Calc Array: {$calc_print} | Value Array: {$value_print} | Location ID: {$location_id} | Item Type Calc: {$itemtypecalc} | Quantity Type Calc: {$qt_type} | Item Type: {$itemtype} | Calc Type: {$calc_type}", 'camp_calc.log');

			// Enrollment Table Field from Value - Quantity Type Options and Item Type Calc from Material Request
			$field = $value['qt_options'][$itemtypecalc];

			// Calcultation Divider - From Quantity Types Packing Calculation Divider
			$calc_divider = $value['qt_options']['calc_divider']; //$value['calc_divider'];

			// Term						Grade ID								Theme ID
			$term = $calc['term'];		$grade_id = $calc['grade_id'];			$theme_id = $calc['theme_id'];

			// Seasons Data
			$terms = gambaTerm::terms();
			$season_data = $terms[$term]; $campg_packper = $season_data['campg_packper'];
			$campg_enroll_rotations = $season_data['campg_enroll_rotations'];

			// Activity ID														Camp ID
			$activity_id = $calc['activity_id'];								$camp = $value['camp']; // $calc['qt_camp'];

			// Location DLI														Camp G Theme Link ID
			$dli = $calc['location'][$location_id]['dli'];						$theme_link_id = $calc['theme_link_id'];

			// Camp by Location - Not used in KQD
			$camp_by_location = $calc['location'][$location_id]['camp'];

			// Enrollment Array Data self::enrollment_data() - Turns on and off options in SQL Statement
			$enrollment_data = $calc['enrollment_data'];
			//$enrollment_data_print = print_r($calc['enrollment_data'], true);
			$enrollment_data_print = "Array values commented out of log file.";
			gambaLogs::data_log(">>        [gambaCalcEnrollment::get_values] Calc Enrollment Data: {$enrollment_data_print}", 'camp_calc.log');
			$enrollment_table = $enrollment_data[$field];

			// Enrollment Database Table										Enrollment Grade ID
			$et_table = $enrollment_table['table'];								$et_grade_id = $enrollment_table['grade_id'];

			// Enrollment Grade ID Value
			$et_grade_id_value = $enrollment_table['grade_id_value'];
			if($et_grade_id_value != "") { $grade_id = $et_grade_id_value; }
			gambaLogs::data_log(">>        [gambaCalcEnrollment::get_values] Calc Grade ID: {$calc['grade_id']}", 'camp_calc.log');

			// Enrollment Grade Array - Some Camps use Enrollment data from Other Camps
			$et_grade_array = $enrollment_table['grade_array'];
			if(is_array($et_grade_array)) { $grade_id = $et_grade_array[$grade_id]; }

			// SQL Where Theme ID (True or False)								SQL Where Extra Class (True or False)
			$et_theme_id = $enrollment_table['theme_id'];						$et_extra_class = $enrollment_table['extra_class'];

			// SQL Where Location ID (True or False)							SQL Where Camp (True or False)
			$et_location_id = $enrollment_table['location_id'];					$et_camp = $enrollment_table['camp'];

			// SQL Where Office Field
			$et_office_field = $enrollment_table['office_field'];

			// Enrollment Camp ID Value - Some Camps use Enrollment data from Other Camps
			gambaLogs::data_log(">>        [gambaCalcEnrollment::enrollment_camp] Camp ID: {$camp} | Enrollment Table Camp ID Value: {$enrollment_table['camp_id_value']}", 'camp_calc.log');
			$et_camp_id_value = $enrollment_table['camp_id_value'];
			if($et_camp_id_value != "") { $camp = $et_camp_id_value; }

			// SQL Select Theme Values (True or False)							SQL Select Location Values (True or False)
			$et_theme_values = $enrollment_table['theme_values'];				$et_location_values = $enrollment_table['location_values'];

			// SQL Select Office Data Values									SQL Select Summed Office Values
			$et_office_data_values = $enrollment_table['office_data_values'];	$et_sum_office_values = $enrollment_table['sum_office_values'];

			// SQL Select Camp By Location
			$et_camp_by_location = $enrollment_table['camp_by_location'];

			// Camp G Multiplier Office Data (Need More Information)
			$et_camp_g_multiplier = $enrollment_table['camp_g_multiplier'];

			// SQL Statement - Begin
			$query = \DB::table($et_table);
			if($et_sum_office_values != "true") {
				$select .= " id";
			}
			if($et_theme_values == "true") {
				$select .= ", theme_values";
			}
			if($et_location_values == "true") {
				$select .= ", location_values";
			}
			if($et_office_data_values == "true") {
				$select .= ", value";
			}
			if($et_sum_office_values == "true") {
				$select .= " SUM(value) AS total";
			}
			if($et_training_values == "true") {
				$select .= ", training_value";
			}
			$query = $query->select(\DB::raw($select));
			$query = $query->where('term', $term);
			$where = " term = $term";
			if($et_camp == "true") {
				$query = $query->where('camp', $camp);
				$where .= " AND camp = $camp";
			}
			if($et_camp_by_location == "true") {
				$query = $query->where('camp', $camp_by_location);
				$where .= " AND camp = $camp_by_location";
			}
			if($et_office_field != "") {
				$query = $query->where('field', $et_office_field);
				$where .= " AND field = '$et_office_field'";
			}
			if($et_activity_id == "true") {
				$query = $query->where('activity_id', $activity_id);
				$where .= " AND activity_id = $activity_id";
			}
			if($et_grade_id == "true") {
				$query = $query->where('grade_id', $grade_id);
				$where .= " AND grade_id = $grade_id";
			}
			if($et_theme_id == "true") {
				$query = $query->where('theme_id', $theme_id);
				$where .= " AND theme_id = $theme_id";
			}
			if($et_extra_class == "true") {
				if($dli == 2) {
					$query = $query->where('extra_class', $dli);
					$where .= " AND extra_class = $dli";
				} else {
					$query = $query->whereRaw('(extra_class = 0 OR extra_class = 1)');
					$where .= " AND (extra_class = 0 OR extra_class = 1)";
				}
			}
			if($et_location_id == "true") {
				$query = $query->where('location_id', $location_id);
				$where .= " AND location_id = $location_id";
			}
			if($et_sum_office_values == "true") {
				$sql .= " GROUP BY id";
				$query = $query->groupBy('id');
			}
			$query = $query->orderBy('id', 'desc');
			$row = $query->first();
			// SQL Statement - End

			// Camp G Check Enrollment
// 			if($camp == 1 || $camp == 17) {
// 				$campg_enroll = Enrollment::select('id', 'theme_values')->whereRaw($where)->get()->toArray();
// 				$cg_themevalues = json_decode($campg_enroll[0]['theme_values'], true);
// 				$cg_rev_enroll = $cg_themevalues[$theme_link_id][$theme_id]['rev_enroll'];
// 				if($cg_rev_enroll == 0) {
// 					$campers_enroll = "false";
// 				} else {
// 					$campers_enroll = "true";
// 				}
// 			} else {
// 				$campers_enroll = "true";
// 			}

			// Camp G Check Enrollment
			gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp ID: {$value['camp']} | Calc Array - Theme Link ID: {$calc['theme_link_id']} | Calc Array - Theme ID: {$calc['theme_id']} | SQL Where: {$where}", 'camp_calc.log');
			if($value['camp'] == 1 || $value['camp'] == 17 || $value['camp'] == 6) {
				$campg_enroll = Enrollment::select('id', 'theme_values', 'location_values')->whereRaw($where)->orderBy('id', 'desc')->first();
				$camp_themevalues = json_decode($campg_enroll->theme_values, true);
				$camp_themevalues_print_array = print_r($camp_themevalues[$calc['theme_link_id']][$calc['theme_id']], true);
				//$camp_themevalues_print_array = "Array values commented out of log file.";
				gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp Theme Values Array: {$camp_themevalues_print_array}", 'camp_calc.log');
				$camp_location_values = json_decode($campg_enroll->location_values, true);
				$camp_location_values_print_array = print_r($camp_location_values, true);
				//$camp_location_values_print_array = "Array values commented out of log file.";
				gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp Location Values Array: {$camp_location_values_print_array}", 'camp_calc.log');
				if($value['camp'] == 6) {
					$camp_location_enrollment = $camp_location_values['kids_per_class'];
					gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp Location Enrollment (Kids Per Class): {$camp_location_enrollment}", 'camp_calc.log');
				} else {
					$camp_location_enrollment = $camp_themevalues[$theme_link_id][$theme_id]['rev_enroll'];
					gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp Location Enrollment (Revised Enrollment): {$camp_location_enrollment}", 'camp_calc.log');
				}
				gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp Location Enrollment: {$camp_location_enrollment}", 'camp_calc.log');
				if($camp_location_enrollment == 0) {
					$campers_enroll = "false";
				} else {
					$campers_enroll = "true";
				}
			} else {
				$campers_enroll = "true";
			}
			gambaLogs::data_log(">>        [gambaCalcEnrollment::get_values] Campers Enrollment: {$campers_enroll}", 'camp_calc.log');

			
			// Straighten out with Office
			if($et_office_data_values == "true") {
				$table_value = $row->value;
				if($table_value == "") { $table_value = 0; }
			}
			if($et_sum_office_values == "true") {
				$table_value = $row->total;
				if($table_value == "") { $table_value = 0; }
			}
			// Non Office
			if($et_theme_values == "true") {
				$theme_values = json_decode($row->theme_values, true);
				$table_value = $theme_values[$theme_link_id][$theme_id][$field];
// 				echo "<pre>"; print_r($theme_values); echo "</pre>";
				if($table_value == "") { $table_value = 0; }

			}
			// If the Camp Category allows individual
			if($et_location_values == "true") {
				$location_values = json_decode($row->location_values, true);
				$table_value = $location_values[$field];
// 				echo "<pre>"; print_r($location_values); echo "</pre>";
				if($table_value == "") { $table_value = 0; }

			}
			if($et_training_values == "true") {
				$table_value = $row['training_value'];
				if($table_value == "") { $table_value = 0; }

			}
			if($calc_divider == 0 || $calc_divider == "") { $calc_divider = 1.0; }

			// Consumable Rotations - Get Values
			if($campg_enroll_rotations == "true") {
				$theme_values = json_decode($row->theme_values, true);
				if($value['qt_options']['crotations'] == 1 && $itemtype == "C") {
					$rotations = $theme_values[$theme_link_id][$theme_id]['rotations'];
					$c_rotations = "$rotations [C Rotations]";
				}
			} else {
				$location_values = json_decode($row->location_values, true);
				if($value['qt_options']['crotations'] == 1 && $itemtype == "C") {
					$rotations = $location_values['rotations'];
					$c_rotations = "$rotations [C Rotations]";
				}
			}

			// Consumable Theme Weeks
			$cthemeweeks = $value['qt_options']['cthemeweeks'];
			$return['theme_values'] = $row->theme_values;
			$theme_values = json_decode($row->theme_values, true);
			if($value['qt_options']['cthemeweeks'] == 1 && $itemtype == "C") {
				$theme_weeks = $theme_values[$theme_link_id][$theme_id]['theme_weeks'];
				$c_theme_weeks = "$theme_weeks [C Theme Weeks]";
				$c_theme_weeks = "$theme_weeks [C Theme Weeks]";
			}
			// Yes there are Enrollment numbers at this location for this theme
			if($campers_enroll == "true") {
				if($et_camp_g_multiplier == "true" && $camp_by_location == 1) {
					$total = $calc['location'][$location_id]['quantity_val'] * $table_value;
					if($rotations != "" || $rotations > 0) {
						$total = $total * $rotations;
					}
					if($theme_weeks != "" || $theme_weeks > 0) {
						$total = $total * $theme_weeks;
					}
					$total = ($total  / $calc_divider) * 2;
					$total_calc = "Function: Get Values (Multiplier) | Camp: {$calc['camp']} | Static or Drop: $qt_type | DLI: $dli | Calculation ".
						"ceil(((".$calc['location'][$location_id]['quantity_val'].
						" * $table_value";
					if($rotations != "" || $rotations > 0) {
						$total_calc .= " * $c_rotations";
					}
					if($theme_weeks != "" || $theme_weeks > 0) {
						$total_calc .= " * $c_theme_weeks";
					}
					$total_calc .= ") / $calc_divider)  * 2) | {$location_values['enrollment_calc']}";
				} else {
// 					$total = ceil($calc['location'][$location_id]['quantity_val'] * $table_value);
					$total = $calc['location'][$location_id]['quantity_val'] * $table_value;
					if($rotations != "" || $rotations > 0) {
						$total = $total * $rotations;
					}
					if($theme_weeks != "" || $theme_weeks > 0) {
						$total = $total * $theme_weeks;
					}
					$total = $total / $calc_divider;
					$total_calc = "Function: Get Values | Camp: {$calc['camp']} | Static or Drop: $qt_type | DLI: $dli | Calculation: $total = ".
						"ceil((".$calc['location'][$location_id]['quantity_val'].
						" * $table_value";
					if($rotations != "" || $rotations > 0) {
						$total_calc .= " * $c_rotations";
					}
					if($theme_weeks != "" || $theme_weeks > 0) {
						$total_calc .= " * $c_theme_weeks";
					}
					$total_calc .= ") / {$calc_divider}) | CG Enroll Rotations: $campg_enroll_rotations | SQL: $sql"; //  | {$location_values['enrollment_calc']} | SQL: {$where}
				}
			} else {
				$total = 0;
				$total_calc = "Function: Get Values | QT Name: {$value['name']} | Camp ID: {$value['camp']} | QT Camp {$calc['qt_camp']} | No Enrollment at this location for this theme | $sql | Enrollment: $camp_location_enrollment | {$campg_enroll[0]['location_values']}"; // | {$campg_enroll[0]['theme_values']}
			}
			gambaLogs::data_log(">>        [gambaCalcEnrollment::standard_value_calc] Total Calc: {$total_calc}", 'camp_calc.log');
			if($calc['location'][$location_id]['quantity_val'] > 0) {
				gambaLogs::data_log(">>        [gambaCalcEnrollment::standard_value_calc] {$total_calc}", 'camp_calc.log');
			}
// 			if($calc['debug'] == 1 && $calc['show_locations'] == 1) { echo "<td title='$field - ".$calc['name']." - $sql - $total_calc'>$total</td>"; }
// 			if($calc['debug'] == 1) { echo "<td title='get_values - $field - ".$calc['name']." - $sql - $total_calc'>$total</td>"; }
			// Not sure why I have this here. Seems to cause problems. 11/25
// 			if($total == "") { $total = 0; }
// 			$return['field'] = $field;
// 			$return['name'] = $calc['name'];
			$return['total'] = $total;
			$locations = Locations::find($location_id);
			$return['location_name'] = $locations['location'];

			if($total > 0 ) {
				$return['calc'] = $total_calc;
			}
			gambaLogs::data_log(">>        [gambaCalcEnrollment::standard_value_calc] Total Quantity Value: {$calc['location'][$location_id]['quantity_val']} Return Total", 'camp_calc.log');
			if($calc['location'][$location_id]['quantity_val'] > 0) {
				return $return;
			}
// 			echo "<pre>"; print_r($calc); echo "</pre>";
// 			echo "<pre>"; print_r($value['qt_options']); echo "</pre>";
		}

		/**
		 * Key Quantity Driver - How many per kid
		 * Chosen in the Quantity Type Settings
		 * @param unknown $calc
		 * @param unknown $value
		 * @param unknown $location_id
		 * @param unknown $itemtypecalc
		 * @param unknown $qt_type
		 * @param unknown $itemtype
		 * @return string
		 */
		private static function kqd($calc, $value, $location_id, $itemtypecalc, $qt_type, $itemtype) {

			// Enrollment Table Field from Value - Quantity Type Options and Item Type Calc from Material Request
			$field = $value['qt_options'][$itemtypecalc];

			// Calcultation Divider - From Quantity Types Packing Calculation Divider
			$calc_divider = $value['qt_options']['calc_divider']; //$value['calc_divider'];

			// KQD - Quantity Type Person Multiplier (Default: 1)
			$kqd = round($value['value'], 2);

			// Term						Grade ID								Theme ID
			$term = $calc['term'];		$grade_id = $calc['grade_id'];			$theme_id = $calc['theme_id'];

			// Seasons Data
			$terms = gambaTerm::terms();
			$season_data = $terms[$term]; $campg_packper = $season_data['campg_packper'];
			$campg_enroll_rotations = $season_data['campg_enroll_rotations'];

			// Activity ID														Camp ID
			$activity_id = $calc['activity_id'];								$camp = $value['camp']; // $calc['qt_camp'];

			// Location DLI														Camp G Theme Link ID
			$dli = $calc['location'][$location_id]['dli'];						$theme_link_id = $calc['theme_link_id'];

			// Enrollment Array Data self::enrollment_data() - Turns on and off options in SQL Statement
			$enrollment_data = $calc['enrollment_data'];
			//$enrollment_data_print = print_r($calc['enrollment_data'], true);
			$enrollment_data_print = "Array values commented out of log file.";
			gambaLogs::data_log(">>        [gambaCalcEnrollment::get_values] Calc Enrollment Data: {$enrollment_data_print}", 'camp_calc.log');
			$enrollment_table = $enrollment_data[$field];

			// Enrollment Database Table										Enrollment Grade ID
			$et_table = $enrollment_table['table'];								$et_grade_id = $enrollment_table['grade_id'];

			// Enrollment Grade ID Value
			$et_grade_id_value = $enrollment_table['grade_id_value'];
			if($et_grade_id_value != "") { $grade_id = $et_grade_id_value; }

			// Enrollment Grade Array - Some Camps use Enrollment data from Other Camps
			$et_grade_array = $enrollment_table['grade_array'];
			if(is_array($et_grade_array)) { $grade_id = $et_grade_array[$grade_id]; }

			// SQL Where Theme ID (True or False)								SQL Where Extra Class (True or False)
			$et_theme_id = $enrollment_table['theme_id'];						$et_extra_class = $enrollment_table['extra_class'];

			// SQL Where Location ID (True or False)							SQL Where Camp (True or False)
			$et_location_id = $enrollment_table['location_id'];					$et_camp = $enrollment_table['camp'];

			// SQL Where Office Field
			$et_office_field = $enrollment_table['office_field'];

			// Enrollment Camp ID Value - Some Camps use Enrollment data from Other Camps
			gambaLogs::data_log(">>        [gambaCalcEnrollment::enrollment_camp] Camp ID: {$camp} | Enrollment Table Camp ID Value: {$enrollment_table['camp_id_value']}", 'camp_calc.log');
			$et_camp_id_value = $enrollment_table['camp_id_value'];
			if($et_camp_id_value != "") { $camp = $et_camp_id_value; }

			// SQL Select Theme Values (True or False)							SQL Select Location Values (True or False)
			$et_theme_values = $enrollment_table['theme_values'];				$et_location_values = $enrollment_table['location_values'];

			// SQL Select Office Data Values									SQL Select Summed Office Values
			$et_office_data_values = $enrollment_table['office_data_values'];	$et_sum_office_values = $enrollment_table['sum_office_values'];

			// SELECT
			$query = \DB::table($et_table);
			if($et_sum_office_values != "true") {
				$select .= " id";
			}
			if($et_theme_values == "true") {
				$select .= ", theme_values";
			}
			if($et_location_values == "true") {
				$select .= ", location_values";
			}
			if($et_office_data_values == "true") {
				$select .= ", value";
			}
			if($et_training_values == "true") {
				$select .= ", training_value";
			}
			if($et_sum_office_values == "true") {
				$select .= " SUM(value) AS total";
			}
			$query = $query->select(\DB::raw($select))->where('term', $term);
			// TABLE AND TERM
			$where = "term = $term";
			// WHERE VALUES
			if($et_camp == "true") {
				$query = $query->where('camp', $camp);
				$where .= " AND camp = $camp";
			}
			if($et_office_field != "") {
				$query = $query->where('field', $et_office_field);
				$where .= " AND field = '$et_office_field'";
			}
			if($et_activity_id == "true") {
				$query = $query->where('activity_id', $activity_id);
				$where .= " AND activity_id = $activity_id";
			}
			if($et_grade_id == "true") {
				$query = $query->where('grade_id', $grade_id);
				$where .= " AND grade_id = $grade_id";
			}
			if($et_theme_id == "true") {
				$query = $query->where('theme_id', $theme_id);
				$where .= " AND theme_id = $theme_id";
			}
			if($et_extra_class == "true") {
				if($dli == 2) {
					$query = $query->where('extra_class', $dli);
					$where .= " AND extra_class = $dli";
				} else {
					$query = $query->whereRaw('(extra_class = 0 OR extra_class = 1)');
					$where .= " AND (extra_class = 0 OR extra_class = 1)";
				}
			}
			if($et_location_id == "true") {
				$query = $query->where('location_id', $location_id);
				$where .= " AND location_id = $location_id";
			}
			if($et_sum_office_values == "true") {
				$query = $query->groupBy('id');
			}
			$query = $query->orderBy('id', 'desc');
			$row = $query->first();

			// Camp G Check Enrollment
			gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp ID: {$value['camp']} | Calc Array - Theme Link ID: {$calc['theme_link_id']} | Calc Array - Theme ID: {$calc['theme_id']} | SQL Where: {$where}", 'camp_calc.log');
			if($value['camp'] == 1 || $value['camp'] == 17 || $value['camp'] == 6) {
				$campg_enroll = Enrollment::select('id', 'theme_values', 'location_values')->whereRaw($where)->orderBy('id', 'desc')->first();
				$camp_themevalues = json_decode($campg_enroll->theme_values, true);
				$camp_themevalues_print_array = print_r($camp_themevalues, true);
				//$camp_themevalues_print_array = "Array values commented out of log file.";
				gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp Theme Values Array: {$camp_themevalues_print_array}", 'camp_calc.log');
				$camp_location_values = json_decode($campg_enroll->location_values, true);
				$camp_location_values_print_array = print_r($camp_location_values, true);
				//$camp_location_values_print_array = "Array values commented out of log file.";
				gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp Location Values Array: {$camp_location_values_print_array}", 'camp_calc.log');
				if($value['camp'] == 6) {
					$camp_location_enrollment = $camp_location_values['kids_per_class'];
					gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp Location Enrollment (Kids Per Class): {$camp_location_enrollment}", 'camp_calc.log');
				} else {
					$camp_location_enrollment = $camp_themevalues[$theme_link_id][$theme_id]['rev_enroll'];
					gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp Location Enrollment (Revised Enrollment): {$camp_location_enrollment}", 'camp_calc.log');
				}
				gambaLogs::data_log(">>        [gambaCalcEnrollment::campg_check_enrollment] Camp Location Enrollment: {$camp_location_enrollment}", 'camp_calc.log');
				if($camp_location_enrollment == 0) {
					$campers_enroll = "false";
				} else {
					$campers_enroll = "true";
				}
			} else {
				$campers_enroll = "true";
			}
			gambaLogs::data_log(">>        [gambaCalcEnrollment::get_values] Campers Enrollment: {$campers_enroll}", 'camp_calc.log');


			
			// Straighten out with Office
			if($et_office_data_values == "true") {
				$table_value = $row['value'];
				if($table_value == "") { $table_value = 0; }
			}
			if($et_sum_office_values == "true") {
				$table_value = $row->total;
				if($table_value == "") { $table_value = 0; }
			}
			// Non Office
			if($et_theme_values == "true") {
				$theme_values = json_decode($row->theme_values, true);
				$table_value = $theme_values[$theme_link_id][$theme_id][$field];
				if($table_value == "") { $table_value = 0; }
				gambaLogs::data_log(">>        [gambaCalcEnrollment::get_table_value] Theme Table Value ({$field}): {$table_value}", 'camp_calc.log');

			}
			if($et_location_values == "true") {
				$location_values = json_decode($row->location_values, true);
				$table_value = $location_values[$field];
				if($table_value == "") { $table_value = 0; }

			}
			if($et_training_values == "true") {
				$table_value = $row->training_value;
				if($table_value == "") { $table_value = 0; }

			}
			// Consumable Rotations - KQD
			if($campg_enroll_rotations == "true") {
				$theme_values = json_decode($row->theme_values, true);
				if($value['qt_options']['crotations'] == 1 && $itemtype == "C") {
					$rotations = $theme_values[$theme_link_id][$theme_id]['rotations'];
					$c_rotations = "$rotations [C Rotations]";
				}
			} else {
				$location_values = json_decode($row->location_values, true);
				if($value['qt_options']['crotations'] == 1 && $itemtype == "C") {
					$rotations = $location_values['rotations'];
					$c_rotations = "$rotations [C Rotations]";
				}
			}
			// Consumable Theme Weeks
			$cthemeweeks = $value['qt_options']['cthemeweeks'];
			$return['theme_values'] = $row->theme_values;
			$theme_values = json_decode($row->theme_values, true);
			$toggle_themeweeks = $value['qt_options']['cthemeweeks'];
			if($value['qt_options']['cthemeweeks'] == 1 && $itemtype == "C") {
				$theme_weeks = $theme_values[$theme_link_id][$theme_id]['theme_weeks'];
				$c_theme_weeks = "$theme_weeks [C Theme Weeks]";
				$c_theme_weeks = "$theme_weeks [C Theme Weeks]";
			} else {
				$theme_weeks = 1;
				$c_theme_weeks = "1.00 [C Theme Weeks]";
			}

			if($calc_divider == 0 || $calc_divider == "") { $calc_divider = 1; }
			// Yes there are Enrollment numbers at this location for this theme
			gambaLogs::data_log(">>        [gambaCalcEnrollment::kqd_value_calc] Is Campers Enroll True: {$campers_enroll}", 'camp_calc.log');
			if($campers_enroll == "true") {
				$total = $calc['location'][$location_id]['quantity_val'] * ceil($kqd * $table_value);
				if($rotations != "" || $rotations > 0) {
					$total = $total * $rotations;
				}
				if($theme_weeks != "" || $theme_weeks > 0) {
					$total = $total * $theme_weeks;
				}
				$total = $total / $calc_divider;
				$total_calc = "Function: KQD | Camp ID: {$calc['camp']} | Static or Drop: {$qt_type} | Theme Weeks: $theme_weeks | Rotations: $rotations | DLI: $dli | Item Type: $itemtype | Theme ID: $theme_id | Calculation: {$total} = ({$calc['location'][$location_id]['quantity_val']} * CEIL({$kqd} * {$table_value})";

				if($rotations != "" || $rotations > 0) {
					$total_calc .= " * $c_rotations";
				}
				if($theme_weeks != "" || $theme_weeks > 0) {
					$total_calc .= " * $c_theme_weeks";
				}
				$total_calc .= ") / $calc_divider | {$location_values['enrollment_calc']}  | Field: {$field} | CG Enroll Rotations: $campg_enroll_rotations"; //  | Theme Values: {$row['theme_values']} | SQL: {$sql}
			} else {
				$total = 0;
				$total_calc = "Function: KQD: QT Name: {$value['name']} | Camp ID: {$value['camp']} | QT Camp {$calc['qt_camp']} | No Enrollment at this location for this theme | $where | Enrollment: $camp_location_enrollment | {$campg_enroll[0]['location_values']}"; // | {$campg_enroll[0]['theme_values']}
			}
			gambaLogs::data_log(">>        [gambaCalcEnrollment::kqd_value_calc] Total Calc: {$total_calc}", 'camp_calc.log');
			if($calc['location'][$location_id]['quantity_val'] > 0) {
				gambaLogs::data_log(">>        [gambaCalcEnrollment::kqd_value_calc] {$total_calc}", 'camp_calc.log');
			}

			$return['total'] = $total;
			$locations = Locations::find($location_id);
			$return['location_name'] = $locations['location'];


			if($total > 0 ) {
				$return['calc'] = $total_calc;
			}
			gambaLogs::data_log(">>        [gambaCalcEnrollment::kqd_value_calc] Total Quantity Value: {$calc['location'][$location_id]['quantity_val']}", 'camp_calc.log');
			if($calc['location'][$location_id]['quantity_val'] > 0) {
				return $return;
			}
		}


		private static function nc_multiplier($total, $itemtype) {
			gambaLogs::data_log("*    [gambaCalcLocations::nc_multiplier] Total: {$total} | Item Type: {$itemtype}", 'camp_calc.log');
			if($itemtype == "NCx3") {
				$multiplier = 3;
			} else {
				$multiplier = 1;
			}
			$return_total = $total * $multiplier;
			gambaLogs::data_log("*    [gambaCalcLocations::nc_multiplier] Return Total: {$return_total} | Calculation: {$return_total} = {$total} * {$multiplier} [Multiplier]", 'camp_calc.log');
			return $return_total;
		}

		private static function part_conversion($number, $amount) {
			$part_info = gambaParts::part_info($number);
			$divider = $part_info['conversion'];
			$total = ceil($amount / $divider);
			return $total;
		}

		private static function total_grade_camper_weeks() {
			// Used in Camp G Kinder PM and Camp G Art & Science
			// Is Consumable
			// total # of camper weeks x Key Quantity Driver
// 			$total = ceil($total_grade_camper_weeks * $kqd);
			$total = $total_grade_camper_weeks * $kqd;
		}
		private static function total_topic_classes() {
			// Used in Camp G Art & Science
			// Is Consumable
			// total number of classes x total # per class for this unit (# per rotation)
// 			$total = ceil($total_topic_classes * $quantity_type_value[2]);
			$total = $total_topic_classes * $quantity_type_value[2];
		}
		private static function total_theme_wks_grade() {
			// Used in Camp G Art & Science
			// Consumable
			// # per theme * total theme weeks for that theme within that grade group
// 			$total = ceil($total_theme_wks_grade * $quantity_type_value[87]);
			$total = $total_theme_wks_grade * $quantity_type_value[87];
		}
		private static function total_grade_classrooms() {
			// Used in Camp G Art & Science
			// Is Non-Consumable
			// Total # of classrooms for that topic & grade level x amount per classroom
// 			$total = ceil($quantity_type_value[1] * $total_grade_classrooms);
			$total = $quantity_type_value[1] * $total_grade_classrooms;
		}
		private static function total_grade_kids_per_class() {
			// Used in Camp G Art & Science
			// Is Non-Consumable
			// KQD x total number of kids at any given time
// 			$total = ceil($kqd * $total_grade_kids_per_class);
			$total = $kqd * $total_grade_kids_per_class;
		}

	}
