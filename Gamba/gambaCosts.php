<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\Camps;
	use App\Models\Config;
	use App\Models\CostSummaries;
	use App\Models\CostSummaryData;
	use App\Models\Enrollment;
	use App\Models\EnrollmentExt;
	use App\Models\QuantityTypes;
	use App\Models\Supplies;
	use App\Models\SupplyLists;
	use App\Models\Themes;

	use App\Gamba\gambaCampCategories;
//	use App\Gamba\gambaDirections;
//	use App\Gamba\gambaDebug;
	use App\Gamba\gambaGrades;
	use App\Gamba\gambaLogs;
	use App\Gamba\gambaParts;
	use App\Gamba\gambaQuantityTypes;
	use App\Gamba\gambaSupplies;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaThemes;
	use App\Gamba\gambaUsers;

	class gambaCosts {

		/**
		 * Step 1: Begin Calculation
		 *  - Get List of Camps
		 *  - Truncate Log
		 *  - Set Supply Lists Budget to 0
		 *  - Get Supply Activities
		 *  - Foreach where you go trough each activity and get parts
		 *  - Calculate Cost per Camper for Each Part - Formula
		 *  - Update each activities cost
		 * @param unknown $term
		 */
		public static function calculate($term, $camp_selected = NULL) {
			gambaLogs::truncate_log("costs.log");
			// Get Term or Current Term
			if($term == "") {
				$term = gambaTerm::year_by_status('C');
			}
			// Get List of Camp Categories
			if($camp_selected == "") {
				$camps = gambaCampCategories::camps_list();
			} else {
				$camp_info = gambaCampCategories::camp_info($camp_selected);
				$camps[$camp] = $camp_info;
			}
			self::update_calculation_date($term);
// 			mysql_query("UPDATE ".tbpre."supplies SET cost = 0 WHERE term = $term");
			//$update = Supplies::where('term', $term)->update(array('cost' => ""));
			//gambaLogs::data_log("Set $term Supplies Cost to 0.", "costs-{$camp}.log");
// 			mysql_query("UPDATE ".tbpre."supplylists SET budget = 0 WHERE term = $term");
			//$update = SupplyLists::where('term', $term)->update(array('budget' => "", 'costing_summary' => ""));
			//gambaLogs::data_log("Set $term Supply List Budget to 0.", "costs-{$camp}.log");
			$supplyactivities = gambaSupplies::supplyactivities($term, "camp_id", $camp_selected);
			foreach($supplyactivities['bycamp'] as $camp => $supplylistvalues) {
				gambaLogs::data_log("", "costs.log", 'false', 'false');
				gambaLogs::data_log(">>>>>>>>>>>>>>>>>>>>> {$camps[$camp]['name']} Begin Calculating Cost Analysis <<<<<<<<<<<<<<<<<<<<", "costs.log", 'false', 'false');
				gambaLogs::truncate_log("costs-{$camp}.log");
				$cost_analysis = $camps[$camp]['camp_values']['cost_analysis'];
				gambaLogs::data_log("{$camps[$camp]['name']} | Cost Analysis: {$camps[$camp]['camp_values']['cost_analysis']} | Cost Analysis Summary: {$camps[$camp]['camp_values']['cost_analysis_summary']}", "costs-{$camp}.log");
				gambaLogs::data_log("", "costs.log", 'false', 'false');
				// Camp Category has Cost Analysis Enabled
				if($camps[$camp]['camp_values']['cost_analysis'] == "true") {
					gambaLogs::data_log("Begin Calculating Supply Activity Costs for {$camps[$camp]['name']}", "costs-{$camp}.log", 'true', 'true');
					// Camp Galileo Format
					if($camps[$camp]['camp_values']['cost_grade_display'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] != "gsq") {
						gambaLogs::data_log("Calculate for Camp Galileo By Grade | {$camps[$camp]['name']}", "costs-{$camp}.log");
						// gambaLogs::data_log("", "costs.log", 'false', 'false');
						gambaLogs::data_log("", "costs.log", 'false', 'false');
						gambaLogs::data_log("Calculate for Camp Galileo By Grade | {$camps[$camp]['name']}", "costs.log", "false", "false", "false");
						foreach($supplylistvalues as $supplylistid => $values) {
							gambaLogs::data_log("Theme: {$values['theme_name']} | Activity: {$values['activity_name']} | Grade: {$values['grade_level']}", "costs-{$camp}.log");
							gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
							$activity_cost_per_camper = 0;
							$activity_cost_per_class = 0;
							$theme_id = $values['theme_id'];
							$array = array();
							$supply_parts = gambaSupplies::supplyparts($supplylistid, "false"); // Set to true to exclude EXCLUDED material list items
							gambaLogs::data_log("Supply Parts SQL: {$supply_parts['sql']}", "costs-{$camp}.log");
							foreach($supply_parts['supplies'] as $supply_id => $listvalues) {
								$formula_result = self::formula_campg($camp, $term, $supply_id, $listvalues, $supplylistid);
								$activity_cost_per_camper += $formula_result['part_cost_per_camper'];
								$activity_cost_per_class += $formula_result['parts']['actual_cost_per_class'];
								$array['supplylist'][$supply_id] = $formula_result['parts'];
							}
							$array['activity_cost_per_camper'] = $activity_cost_per_camper;
							$array['activity_cost_per_class'] = $activity_cost_per_class;
							$array['updated_on'] = date("Y-m-d H:i:s");
							$json_array = json_encode($array);
							$sql = "UPDATE ".tbpre."supplylists SET costing_summary = '$json_array' WHERE id = $supplylistid";

							//gambaLogs::data_log("Supply List: ".$sql, "costs-{$camp}.log");
							$update = SupplyLists::find($supplylistid);
								$update->costing_summary = $json_array;
								$update->save();
						}
					}
					// GSQ Format
					elseif($camps[$camp]['camp_values']['cost_grade_display'] != "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
						gambaLogs::data_log("Calculate for GSQ | {$camps[$camp]['name']}", "costs-{$camp}.log");
						gambaLogs::data_log("", "costs.log", 'false', 'false');
						gambaLogs::data_log("Calculate for GSQ | {$camps[$camp]['name']}", "costs.log", "false", "false", "false");
						foreach($supplylistvalues as $supplylistid => $values) {
							$activity_cost_per_camper = 0;
							$activity_cost_per_camper_yr2 = 0;
							$activity_cost_per_camper_consumable = 0;
							$activity_cost_per_camper_nonconsumable_yr1 = 0;
							$activity_cost_per_camper_nonconsumable_yr2 = 0;
							$activity_cost_per_class = 0;
							$theme_id = $values['theme_id'];
							$array = array();
							$supply_parts = gambaSupplies::supplyparts($supplylistid, "false"); // Set to true to exclude EXCLUDED material list items
							foreach($supply_parts['supplies'] as $supply_id => $listvalues) {
								$formula_result = self::formula_gsq($camp, $term, $supply_id, $listvalues, $supplylistid);
								$activity_cost_per_camper += $formula_result['part_cost_per_camper'];
								$activity_cost_per_camper_yr2 += $formula_result['part_cost_per_camper_yr2'];
								$activity_cost_per_camper_consumable += $formula_result['part_cost_per_camper_consumable'];
								$activity_cost_per_camper_nonconsumable_yr1 += $formula_result['part_cost_per_camper_nonconsumable_yr1'];
								$activity_cost_per_camper_nonconsumable_yr2 += $formula_result['part_cost_per_camper_nonconsumable_yr2'];
								$activity_cost_per_class += $formula_result['parts']['actual_cost_per_class'];
								$array['supplylist'][$supply_id] = $formula_result['parts'];
							}
							$array['activity_cost_per_camper'] = $activity_cost_per_camper;
							$array['activity_cost_per_camper_consumable'] = $activity_cost_per_camper_consumable;
							$array['activity_cost_per_camper_nonconsumable_yr1'] = $activity_cost_per_camper_nonconsumable_yr1;
							$array['activity_cost_per_camper_nonconsumable_yr2'] = $activity_cost_per_camper_nonconsumable_yr2;
							$array['activity_cost_per_camper_yr2'] = $activity_cost_per_camper_yr2;
							$array['activity_cost_per_class'] = $activity_cost_per_class;
							$array['updated_on'] = date("Y-m-d H:i:s");
							$json_array = json_encode($array);
							//$sql = "UPDATE ".tbpre."supplylists SET costing_summary = '$json_array' WHERE id = $supplylistid";

							//gambaLogs::data_log("Supply List: ".$sql, "costs-{$camp}.log");
							$update = SupplyLists::find($supplylistid);
								$update->costing_summary = $json_array;
								$update->save();
						}
					// Non Curriculum
					} else {
						foreach($supplylistvalues as $supplylistid => $values) {
							gambaLogs::data_log("Calculate for Non-Curriculum | {$camps[$camp]['name']}", "costs-{$camp}.log");
							gambaLogs::data_log("", "costs.log", 'false', 'false');
							gambaLogs::data_log("Calculate for Non-Curriculum | {$camps[$camp]['name']}", "costs.log", "false", "false", "false");
							$activity_cost_per_camper = 0;
							$activity_cost_per_class = 0;
							$theme_id = $values['theme_id'];
							$array = array();
							$supply_parts = gambaSupplies::supplyparts($supplylistid, "false"); // Set to true to exclude EXCLUDED material list items
							foreach($supply_parts['supplies'] as $supply_id => $listvalues) {
								$formula_result = self::formula_campg($camp, $term, $supply_id, $listvalues, $supplylistid);
								$activity_cost_per_camper += $formula_result['part_cost_per_camper'];
								$activity_cost_per_class += $formula_result['parts']['actual_cost_per_class'];
								$array['supplylist'][$supply_id] = $formula_result['parts'];
							}
							$array['activity_cost_per_camper'] = $activity_cost_per_camper;
							$array['activity_cost_per_class'] = $activity_cost_per_class;
							$array['updated_on'] = date("Y-m-d H:i:s");
							$json_array = json_encode($array);
							$sql = "UPDATE ".tbpre."supplylists SET costing_summary = '$json_array' WHERE id = $supplylistid";

							//gambaLogs::data_log("Supply List: ".$sql, "costs-{$camp}.log");
							$updateSupplyLists = SupplyLists::find($supplylistid);
								$updateSupplyLists->costing_summary = $json_array;
								$updateSupplyLists->save();
						}
					}
					gambaLogs::data_log("End Calculating Supply Activity Costs for {$camps[$camp]['name']}", "costs-{$camp}.log", 'true', 'true');
				}
				gambaLogs::data_log("", "costs.log", 'false', 'false');
				gambaLogs::data_log(">>>>>>>>>>>>>>>>>>>>> {$camps[$camp]['name']} All Done Calculating Cost Analysis <<<<<<<<<<<<<<<<<<<<", "costs.log", 'true', 'false');
			}

			self::calculate_themes($term);
			gambaLogs::data_log("", "costs.log", 'false', 'false');
			gambaLogs::data_log(">>>>>>>>>>>>>>>>>>>>> All Done Calculating Themes <<<<<<<<<<<<<<<<<<<<", "costs.log", 'true', 'false');
			self::cost_summarize_camps($term);
			gambaLogs::data_log("", "costs.log", 'false', 'false');
			gambaLogs::data_log(">>>>>>>>>>>>>>>>>>>>> All Done Cost Summarizing Camps <<<<<<<<<<<<<<<<<<<<", "costs.log", 'true', 'false');
// 			for($i = 1; $i <= 40; $i++) {
// 				gambaLogs::data_log(">>>>>>>>>>>>>>>>>>>>> {$camps[$camp]['name']} All Done Calculating <<<<<<<<<<<<<<<<<<<<", "costs-{$camp}.log", 'false', 'false');
// 			}
		}

		public static function update_calculation_date($term) {
			$terms = gambaTerm::terms();
			gambaLogs::data_log("Setting Calculation Date & Time for {$term}", "costs.log", 'true', 'false');
			$row = Config::select('value')->where('field', 'costing_summary_updated')->get();
				$term_data = json_decode($row->value, true);
// 				echo "<pre>"; print_r($term_data); echo "</pre>"; exit; die();
				foreach($terms as $key => $values) {
					if($key == $term) {
						$array[$term]['updated_on'] = date("Y-m-d H:i:s");
					} else {
						$array[$key]['updated_on'] = $term_data[$key]['updated_on'];
					}
				}
			$array = json_encode($array);
			//gambaLogs::data_log("Setting Calculation Date & Time JSON Array: {$array}", "costs.log", 'true', 'false');
			$update = Config::where('field', 'costing_summary_updated')->update(['value' => $array]);
		}

		/* Maybe obsolete */
		public static function material_list_calculate($camp, $term, $supplylist_id) {
			$camps = gambaCampCategories::camps_list();
			$array = array();
			$supply_parts = gambaSupplies::supplyparts($supplylist_id, "true");
			gambaLogs::data_log("Supply Parts SQL: {$supply_parts['sql']}", "costs-{$camp}.log");
			$activity_cost_per_camper = 0;
			$activity_cost_per_camper_yr2 = 0;
			foreach($supply_parts['supplies'] as $supply_id => $listvalues) {
				// Costing Summary
				gambaLogs::data_log("Costing Summary - {$listvalues['part']}", "costs-{$camp}.log");
				// Camp Galileo Format
				if($camps[$camp]['camp_values']['cost_grade_display'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] != "gsq") {
					$formula_result = self::formula_campg($camp, $term, $supply_id, $listvalues, $supplylist_id);
				}
				// GSQ Format
				elseif($camps[$camp]['camp_values']['cost_grade_display'] != "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
					$formula_result = self::formula_gsq($camp, $term, $supply_id, $listvalues, $supplylist_id);
				// Non Curriculum
				} else {
					$formula_result = self::formula_campg($camp, $term, $supply_id, $listvalues, $supplylist_id);

				}
				$activity_cost_per_camper += $formula_result['part_cost_per_camper'];
				$activity_cost_per_camper_yr2 += $formula_result['part_cost_per_camper_yr2'];
				$array['supplylist'][$supply_id] = $formula_result['parts'];

			}
			$array['activity_cost_per_camper'] = $activity_cost_per_camper;
			$array['activity_cost_per_camper_yr2'] = $activity_cost_per_camper_yr2;
			$json_array = json_encode($array);
			$updateSupplyLists = SupplyLists::find($supplylist_id);
			$updateSupplyLists->costing_summary = $json_array;
			$updateSupplyLists->save();
		}
		/**
		 * Step 1B: Calculate Each Activities Cost Per Camper
		 * @param unknown $camp
		 * @param unknown $term
		 * @param unknown $supply_id
		 * @param unknown $listvalues
		 * @return string
		 */
		public static function formula_campg($camp, $term, $supply_id, $listvalues, $supplylistid) {
// 			echo $supply_id;
// 			echo "<pre>"; print_r($listvalues); echo "</pre>";
			$array['parts']['part'] = $listvalues['part'];
			//$array['parts']['description'] = $desc = gambaParts::utf8_filter($listvalues['description']);
			$array['parts']['exclude'] = $listvalues['exclude'];
			$item_type = $listvalues['itemtype'];
			$amt_cost_per = gambaSupplies::amt_cost_per();
			$camp_info = gambaCampCategories::camp_info($camp);
			gambaLogs::data_log("Camp: {$camp_info['name']} ($camp) | Camp G Formula | Term: $term | Supply List ID: $supplylistid | Supply ID: $supply_id | Part: {$listvalues['part']}, {$listvalues['description']} | Item Type: $item_type | Gamba Cost: {$listvalues['cost']} | FB Cost: {$listvalues['fbcost']}", "costs-{$camp}.log");
			gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
			$quantity_types = gambaQuantityTypes::camp_quantity_types();
			$part_cost_per_camper = 0;
			$cost = self::formula_correct_cost($listvalues['suom'], $listvalues['fbuom'], $listvalues['cost'], $listvalues['fbcost'], $listvalues['conversion']);
			// Cycle through the Array of Quantity Types By Camp
			$actual_campers = 0;
			$array['parts']['actual_cost_per_class'] = 0;
			$array['parts']['actual_cost_per_class_calc'] = "";
			foreach($quantity_types[$camp]['quantity_types'] as $id => $values) {
				// Average is actually the higher value indicated by average and exclude
				$quantity_type_link = $values['cost_options'][$term]['quantity_type_link'];
				if($quantity_type_link == "average") { $qtl = " (Avg. Value)"; }
				if($quantity_type_link == "exclude") { $qtl = " (Excluded)"; }
// 				gambaLogs::data_log($values['name'], "costs-{$camp}.log");
				if($values['value'] == 0) { $kqd = 1; } else { $kqd = $values['value']; }
				if($values['qt_options']['terms'][$term] == "true") {

					// Is the Quantity Type Static or Dropdown?
					if($values['qt_options']['dropdown'] == "true") {
						$input_type = "dropdown";
					} else {
						$input_type = "static";
					}

					// Get the Quantity Type Value Requested
					if($input_type == "dropdown" && $id == $listvalues['request_quantities']['quantity_type_id'] && $listvalues['request_quantities']['quantity_val'] > 0) {
						$input_value = $listvalues['request_quantities']['quantity_val'];
					} else {
						$input_value = $listvalues['request_quantities']['static'][$id];
					}

					// If it is Greater Than Zero
					if($input_value > 0) {
						// If it is Consumable (C)
						if($item_type == "C") {

							// Get Number of Rotations
							if($quantity_types[$camp]['camp_cost_options'][$term]['rotations'] > 0 && $values['cost_options'][$term]['rotations_enabled'] == "true") {
								$rotations = $quantity_types[$camp]['camp_cost_options'][$term]['rotations'];
							} else {
								$rotations = 1;
							}

							// Get Theme Weeks
							if($quantity_types[$camp]['camp_cost_options'][$term]['theme_weeks'] > 0 && $values['cost_options'][$term]['theme_weeks_enabled'] == "true") {
								$theme_weeks = $quantity_types[$camp]['camp_cost_options'][$term]['theme_weeks'];
							} else {
								$theme_weeks = 1;
							}

							// Get Number of Campers
							if($quantity_types[$camp]['camp_cost_options'][$term]['campers'] > 0 && $values['cost_options'][$term]['campers_enabled'] == "true") {
								$campers = $quantity_types[$camp]['camp_cost_options'][$term]['campers'];
							} else {
								$campers = 1;
							}
							$actual_campers = $campers;
							// Calculation for Consumables - If it is Consumable Enabled in Material Cost Summary Quantity Type Setup
							if($values['cost_options'][$term]['C']['enabled'] == "true") {
								$total = ((((($input_value * $kqd) * $cost) / $rotations) / $campers) / $theme_weeks);
								$total = number_format($total, 8);
								if($quantity_type_link != "exclude") {
									$part_cost_per_camper += $total;
								}
								$array['parts']['calc'] .= "{$values['name']}{$qtl} | {$listvalues['part']} - C: {$total} = (((((Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}) / Rotations: {$rotations}) / Campers: {$campers}) / Theme Weeks: {$theme_weeks})   ";
								$log_calc = "{$values['name']}{$qtl} | {$listvalues['part']} {$listvalues['description']} - C: {$total} = (((((Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}) / Rotations: {$rotations}) / Campers: {$campers}) / Theme Weeks: {$theme_weeks})   ";
							} else {
								if($quantity_type_link != "exclude") {
									$part_cost_per_camper += $total = ($input_value * $kqd) * $cost;
								}
								$array['parts']['calc'] .= "{$values['name']}{$qtl} | {$listvalues['part']} - C: {$total} = (Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}   ";
								$log_calc = "{$values['name']}{$qtl} | {$listvalues['part']} {$listvalues['description']} - C: {$total} = (Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}   ";
							}

						// If it is Non-Consumable (NC/NCx3)
						} else {

							// Get Number of Rotations
							if($quantity_types[$camp]['camp_cost_options'][$term]['rotations'] > 0 && $values['cost_options'][$term]['rotations_enabled'] == "true") {
								$rotations = $quantity_types[$camp]['camp_cost_options'][$term]['rotations'];
							} else {
								$rotations = 1;
							}

							// Get Theme Weeks
							if($quantity_types[$camp]['camp_cost_options'][$term]['theme_weeks'] > 0 && $values['cost_options'][$term]['theme_weeks_enabled'] == "true") {
								$theme_weeks = $quantity_types[$camp]['camp_cost_options'][$term]['theme_weeks'];
							} else {
								$theme_weeks = 1;
							}

							// Get Number of Campers
							if($quantity_types[$camp]['camp_cost_options'][$term]['campers'] > 0 && $values['cost_options'][$term]['campers_enabled'] == "true") {
								$campers = $quantity_types[$camp]['camp_cost_options'][$term]['campers'];
							} else {
								$campers = 1;
							}
							$actual_campers = $campers;
							// Calculation for Consumables - If Non-Consumable Enabled in Material Cost Summary Quantity Type Setup
							if($values['cost_options'][$term]['NC']['enabled'] == "true") {
								$total = ((((($input_value * $kqd) * $cost) / $rotations) / $campers) / $theme_weeks);
								$nc_multi = ""; if($item_type == "NCx3") {
									$total * 3; $nc_multi = " * 3";
								}
								$total = number_format($total, 8);
								if($quantity_type_link != "exclude") {
									$part_cost_per_camper += $total;
								}
								$array['parts']['calc'] .= "{$values['name']}{$qtl} | {$listvalues['part']} - {$item_type}: {$total} = (((((Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}) / Rotations: {$rotations}) / Campers: {$campers}) / Theme Weeks: {$theme_weeks}){$nc_multi}   ";
								$log_calc = "{$values['name']}{$qtl} | {$listvalues['part']} {$listvalues['description']} - {$item_type}: {$total} = (((((Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}) / Rotations: {$rotations}) / Campers: {$campers}) / Theme Weeks: {$theme_weeks}){$nc_multi}   ";
							} else {
								$total = ($input_value * $kqd) * $cost;
								$nc_multi = ""; if($item_type == "NCx3") {
									$total * 3; $nc_multi = " * 3";
								}
								$total = number_format($total, 8);
								if($quantity_type_link != "exclude") {
									$part_cost_per_camper += $total;
								}
								$array['parts']['calc'] .= "{$values['name']}{$qtl} | {$listvalues['part']} - {$item_type}: {$total} = ((Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}){$nc_multi}   ";
								$log_calc = "{$values['name']}{$qtl} | {$listvalues['part']} {$listvalues['description']} - {$item_type}: {$total} = ((Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}){$nc_multi}   ";
							}
						}

						// Calculate Actual Cost Per Class
// 						if($values['cost_options'][$term]['campers_enabled'] == "true") {
						$actual_cost_total = $total * $quantity_types[$camp]['camp_cost_options'][$term]['campers'];
						$array['parts']['actual_cost_per_class'] += $part_cost_per_camper * $quantity_types[$camp]['camp_cost_options'][$term]['campers'];
						$array['parts']['actual_cost_per_class_calc'] .= " + $actual_cost_total($part_cost_per_camper * {$quantity_types[$camp]['camp_cost_options'][$term]['campers']})";
// 						} else {
// 							$array['parts']['actual_cost_per_class'] += $total;
// 							$array['parts']['actual_cost_per_class_calc'] .= " + $total";
// 						}

						gambaLogs::data_log($log_calc, "costs-{$camp}.log");
					}
				}
			}
			$array['part_cost_per_camper'] = $part_cost_per_camper;
			$array['parts']['cost_per_camper'] = $part_cost_per_camper;
			// Calculate Actual Cost Per Class
			//$array['parts']['actual_cost_per_class'] = $part_cost_per_camper * $quantity_types[$camp]['camp_cost_options'][$term]['campers']; // or $campers;
			$array['parts']['actual_cost_per_class_calc'] .= " = {$array['parts']['actual_cost_per_class']}";
			gambaLogs::data_log("Actual Cost Per Class: {$array['parts']['actual_cost_per_class']} = $part_cost_per_camper * {$quantity_types[$camp]['camp_cost_options'][$term]['campers']}", "costs-{$camp}.log", "false", "true");
			gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
			$json_array = json_encode($array);
// 			$sql = "UPDATE ".tbpre."supplies SET cost = '$json_array' WHERE id = $supply_id";
			if($supply_id != "") {
			$update = Supplies::find($supply_id);
				$update->costing_summary = $json_array;
				$update->save();
			} else {
				echo "No Supply ID"; exit; die();
			}
			//gambaLogs::data_log("Supply Part: {$listvalues['part']} {$listvalues['description']} | Total: {$array['part_cost_per_camper']} | Calc: {$array['parts']['calc']}", "costs-{$camp}.log");
// 			mysql_query($sql);
			return $array;
		}

		public static function formula_correct_cost($suom, $fbuom, $cost, $fbcost, $conversion) {
			if($suom != $fbuom && ($cost == $fbcost || $cost > 0 && $fbcost == 0.00 || $cost > 0 && $fbcost == "")) {
				if($conversion != "" || $conversion > 0) {
					$new_cost = $cost / $conversion;
					$new_cost = round($new_cost, 4, PHP_ROUND_HALF_UP);
					$return_cost = $new_cost;
				} else {
					$return_cost = $cost;
				}
			} else {
				$return_cost = $cost;
			}
			return $return_cost;
		}

		/**
		 * Step 1B: Calculate Each Activities Cost Per Camper
		 * @param unknown $camp
		 * @param unknown $term
		 * @param unknown $supply_id
		 * @param unknown $listvalues
		 * @return string
		 */
		public static function formula_gsq($camp, $term, $supply_id, $listvalues, $supplylistid) {
			$array['parts']['part'] = $listvalues['part'];
// 			$desc = gambaParts::utf8_filter($listvalues['description']);
// 			$array['parts']['description'] = $desc;
			$array['parts']['exclude'] = $listvalues['exclude'];
			$item_type = $listvalues['itemtype'];
			$amt_cost_per = gambaSupplies::amt_cost_per();
			$camp_info = gambaCampCategories::camp_info($camp);
			gambaLogs::data_log("Camp: {$camp_info['name']} ($camp) | GSQ Formula | Term: $term | Supply List ID: $supplylistid | Supply ID: $supply_id | Part: {$listvalues['part']}, {$listvalues['description']} | Item Type: $item_type", "costs-{$camp}.log");
			gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
			$quantity_types = gambaQuantityTypes::camp_quantity_types();
			$part_cost_per_camper = 0;
			$part_cost_per_camper_consumable = 0;
			$part_cost_per_camper_nonconsumable_yr1 = 0;
			$part_cost_per_camper_nonconsumable_yr2 = 0;
			$cost = self::formula_correct_cost($listvalues['suom'], $listvalues['fbuom'], $listvalues['cost'], $listvalues['fbcost'], $listvalues['conversion']);
			$cost2 = 0;

			// Cycle through the Array of Quantity Types By Camp
			$array['parts']['actual_cost_per_class'] = 0;
			$array['parts']['actual_cost_per_class_calc'] = "";
			foreach($quantity_types[$camp]['quantity_types'] as $id => $values) {
				// Average is actually the higher value indicated by average and exclude
				$quantity_type_link = $values['cost_options'][$term]['quantity_type_link'];
				if($quantity_type_link == "average") { $qtl = " (Avg. Value)"; }
				if($quantity_type_link == "exclude") { $qtl = " (Excluded)"; }
// 				gambaLogs::data_log($values['name'], "costs-{$camp}.log");
				if($values['value'] == 0) { $kqd = 1; } else { $kqd = $values['value']; }
				if($values['qt_options']['terms'][$term] == "true") {

					// Is the Quantity Type Static or Dropdown?
					if($values['qt_options']['dropdown'] == "true") {
						$input_type = "dropdown";
					} else {
						$input_type = "static";
					}

					// Get the Quantity Type Value Requested
					if($input_type == "dropdown" && $id == $listvalues['request_quantities']['quantity_type_id'] && $listvalues['request_quantities']['quantity_val'] > 0) {
						$input_value = $listvalues['request_quantities']['quantity_val'];
					} else {
						$input_value = $listvalues['request_quantities']['static'][$id];
					}

					// If it is Greater Than Zero
					if($input_value > 0) {
						// If it is Consumable (C)
						if($item_type == "C") {

							// Get Number of Rotations
							if($quantity_types[$camp]['camp_cost_options'][$term]['rotations'] > 0 && $values['cost_options'][$term]['rotations_enabled'] == "true") {
								$rotations = $quantity_types[$camp]['camp_cost_options'][$term]['rotations'];
							} else {
								$rotations = 1;
							}

							// Get Theme Weeks
							if($quantity_types[$camp]['camp_cost_options'][$term]['theme_weeks'] > 0 && $values['cost_options'][$term]['theme_weeks_enabled'] == "true") {
								$theme_weeks = $quantity_types[$camp]['camp_cost_options'][$term]['theme_weeks'];
							} else {
								$theme_weeks = 1;
							}

							// Get Number of Campers
							if($quantity_types[$camp]['camp_cost_options'][$term]['campers'] > 0 && $values['cost_options'][$term]['campers_enabled'] == "true") {
								$campers = $quantity_types[$camp]['camp_cost_options'][$term]['campers'];
							} else {
								$campers = 1;
							}
							$actual_campers = $campers;
							// Calculation for Consumables - If it is Consumable Enabled in Material Cost Summary Quantity Type Setup
							if($values['cost_options'][$term]['C']['enabled'] == "true") {
								$total = ((((($input_value * $kqd) * $cost) / $rotations) / $campers) / $theme_weeks);
								$total = number_format($total, 8);
								if($quantity_type_link != "exclude") {
									$part_cost_per_camper += $total;
									$part_cost_per_camper_consumable += $total;
								}
								$array['parts']['calc'] .= "{$values['name']}{$qtl} | {$listvalues['part']} - C: {$total} = (((((Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}) / Rotations: {$rotations}) / Campers: {$campers}) / Theme Weeks: {$theme_weeks})   ";
								$log_calc = "{$values['name']}{$qtl} | {$listvalues['part']} {$listvalues['description']} - C: {$total} = (((((Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}) / Rotations: {$rotations}) / Campers: {$campers}) / Theme Weeks: {$theme_weeks})   ";
							} else {
								if($quantity_type_link != "exclude") {
									$part_cost_per_camper += $total = ($input_value * $kqd) * $cost;
									$part_cost_per_camper_consumable += $total;
								}
								$array['parts']['calc'] .= "{$values['name']}{$qtl} | {$listvalues['part']} - C: {$total} = (Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}   ";
								$log_calc = "{$values['name']}{$qtl} | {$listvalues['part']} {$listvalues['description']} - C: {$total} = (Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}   ";
							}

						// If it is Non-Consumable (NC/NCx3)
						} else {

							// Get Number of Rotations
							if($quantity_types[$camp]['camp_cost_options'][$term]['rotations'] > 0 && $values['cost_options'][$term]['rotations_enabled'] == "true") {
								$rotations = $quantity_types[$camp]['camp_cost_options'][$term]['rotations'];
							} else {
								$rotations = 1;
							}

							// Get Theme Weeks
							if($quantity_types[$camp]['camp_cost_options'][$term]['theme_weeks'] > 0 && $values['cost_options'][$term]['theme_weeks_enabled'] == "true") {
								$theme_weeks = $quantity_types[$camp]['camp_cost_options'][$term]['theme_weeks'];
							} else {
								$theme_weeks = 1;
							}

							// Get Number of Campers
							if($quantity_types[$camp]['camp_cost_options'][$term]['campers'] > 0 && $values['cost_options'][$term]['campers_enabled'] == "true") {
								$campers = $quantity_types[$camp]['camp_cost_options'][$term]['campers'];
							} else {
								$campers = 1;
							}
							$actual_campers = $campers;
							// Calculation for Consumables - If Non-Consumable Enabled in Material Cost Summary Quantity Type Setup
							if($values['cost_options'][$term]['NC']['enabled'] == "true") {
								$total = ((((($input_value * $kqd) * $cost) / $rotations) / $campers) / $theme_weeks);
								$nc_multi = ""; if($item_type == "NCx3") { $total * 3; $nc_multi = " * 3"; }
								$total = number_format($total, 8);
								if($quantity_type_link != "exclude") {
									$part_cost_per_camper += $total;
									$part_cost_per_camper_nonconsumable_yr1 += $total;
								}
								$array['parts']['calc'] .= "{$values['name']}{$qtl} | {$listvalues['part']} - {$item_type}: {$total} = (((((Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}) / Rotations: {$rotations}) / Campers: {$campers}) / Theme Weeks: {$theme_weeks}){$nc_multi}   ";
								$log_calc = "{$values['name']}{$qtl} | {$listvalues['part']} {$listvalues['description']} - {$item_type}: {$total} = (((((Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}) / Rotations: {$rotations}) / Campers: {$campers}) / Theme Weeks: {$theme_weeks}){$nc_multi}   ";
							} else {
								$total = ($input_value * $kqd) * $cost;
								$nc_multi = ""; if($item_type == "NCx3") { $total * 3; $nc_multi = " * 3"; }
								$total = number_format($total, 8);
								if($quantity_type_link != "exclude") {
									$part_cost_per_camper += $total;
									$part_cost_per_camper_nonconsumable_yr1 += $total;
								}
								$array['parts']['calc'] .= "{$values['name']}{$qtl} | {$listvalues['part']} - {$item_type}: {$total} = (Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}{$nc_multi}   ";
								$log_calc = "{$values['name']}{$qtl} | {$listvalues['part']} {$listvalues['description']} - {$item_type}: {$total} = (Qty: {$input_value} * KQD: {$kqd}) * Cost: {$cost}{$nc_multi}   ";
							}

							// Amortorize for Year 2
							if($part_cost_per_camper > 0) {
								//gambaLogs::data_log("Amortization Rate: {$amt_cost_per[$camp][$term]}", "costs-{$camp}.log");

								$part_cost_per_camper_yr2 = $part_cost_per_camper * $amt_cost_per[$camp][$term];
								$part_cost_per_camper_nonconsumable_yr2 += $part_cost_per_camper_yr2;
								$array['parts']['calc_yr2'] = "{$values['name']}{$qtl} | {$listvalues['part']} - {$item_type} - Yr 2: {$part_cost_per_camper_yr2} = (Yr 1 Cost Per Camper: {$part_cost_per_camper} * Amortization Rate: {$amt_cost_per[$camp][$term]})";
							}
						}

						// Calculate Actual Cost Per Class
// 						if($values['cost_options'][$term]['campers_enabled'] == "true") {

						$actual_cost_total = $total * $quantity_types[$camp]['camp_cost_options'][$term]['campers'];
						$array['parts']['actual_cost_per_class'] += $part_cost_per_camper * $quantity_types[$camp]['camp_cost_options'][$term]['campers'];
						$array['parts']['actual_cost_per_class_calc'] .= " + $actual_cost_total($part_cost_per_camper * {$quantity_types[$camp]['camp_cost_options'][$term]['campers']})";

// 						} else {
// 							$array['parts']['actual_cost_per_class'] += $total;
// 							$array['parts']['actual_cost_per_class_calc'] .= " + $total";
// 						}

						gambaLogs::data_log($log_calc, "costs-{$camp}.log");
						gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
					}
				}
			}
			$array['part_cost_per_camper'] = $part_cost_per_camper;
			$array['parts']['cost_per_camper'] = $part_cost_per_camper;
			$array['part_cost_per_camper_consumable'] = $part_cost_per_camper_consumable;
			$array['parts']['cost_per_camper_consumable'] = $part_cost_per_camper_consumable;
			$array['part_cost_per_camper_nonconsumable_yr1'] = $part_cost_per_camper_nonconsumable_yr1;
			$array['parts']['cost_per_camper_nonconsumable_yr1'] = $part_cost_per_camper_nonconsumable_yr1;
			// Calculate Actual Cost Per Class
			//$array['parts']['actual_cost_per_class'] = $part_cost_per_camper * $quantity_types[$camp]['camp_cost_options'][$term]['campers']; // or $campers;
			$array['parts']['actual_cost_per_class_calc'] .= " = {$array['parts']['actual_cost_per_class']}";

			$array['part_cost_per_camper_yr2'] = $part_cost_per_camper_yr2;
			$array['parts']['cost_per_camper_yr2'] = $part_cost_per_camper_yr2;
			$array['parts']['cost_per_camper_consumable_yr2'] = $part_cost_per_camper_consumable;
			$array['part_cost_per_camper_nonconsumable_yr2'] = $part_cost_per_camper_nonconsumable_yr2;
			$array['parts']['cost_per_camper_nonconsumable_yr2'] = $part_cost_per_camper_nonconsumable_yr2;
			$json_array = json_encode($array);
// 			$sql = "UPDATE ".tbpre."supplies SET cost = '$json_array' WHERE id = $supply_id";
			$update = Supplies::find($supply_id);
				$update->costing_summary = $json_array;
				$update->save();
			//gambaLogs::data_log("Supply Part | Year 1: {$array['part_cost_per_camper']} | Year 2: {$array['part_cost_per_camper_yr2']} | Calc: {$array['parts']['calc']}", "costs-{$camp}.log");
// 			mysql_query($sql);
			return $array;
		}

		/**
		 * Step 2: Calculate Budget at Theme Level
		 *  - Get the List of Camp Categories
		 *  - Check if they are included in Material Cost Summaries
		 *  - Get the List of Themes by Camp
		 *
		 * @param unknown $term
		 */
		public static function calculate_themes($term) {
			$camps = gambaCampCategories::camps_list();
			//$term = gambaTerm::year_by_status('C');
			foreach($camps as $camp => $values) {
				$camp_info = gambaCampCategories::camp_info($camp);
				// Camp Category has Cost Analysis Enabled
				if($values['camp_values']['cost_analysis'] == "true") {
					gambaLogs::data_log("Begin Calculating Themes for {$values['name']}", "costs-{$camp}.log", 'true', 'true');

					$cost_analysis_summary = $values['camp_values']['cost_analysis_summary'];
					gambaLogs::data_log("Camp ID: $camp | Cost Analysis Summary: $cost_analysis_summary", "costs-{$camp}.log");
					gambaLogs::data_log("Begin Calculating Themes for {$values['name']}", "costs.log", "false", "false", "true");
					$cost_analysis_summary = $values['camp_values']['cost_analysis_summary'];
					$themes = gambaThemes::themes_by_camp($camp, $term);
					// Cycle Through Themes and
					foreach($themes as $theme_id => $theme_values) {
						$num_activities = $theme_values['number_activities'];
						gambaLogs::data_log("Camp: {$camp_info['name']}($camp) | Theme Budgets and Camper Costs: $budget | Theme: $theme_id | Grade Display: {$camps[$camp]['camp_values']['cost_grade_display']} | Number of Activities: {$num_activities}", "costs-{$camp}.log");
						gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
						$costs = self::supplylist_costing_summary($camp, $theme_id, $cost_analysis_summary, $camps[$camp]['camp_values']['cost_grade_display'], $num_activities);
						//$json_array = mysql_real_escape_string(json_encode($supplylist_budgets));
						//$sql = "UPDATE ".tbpre."themes SET budget = '$json_array' WHERE id = $theme_id";
						//mysql_query($sql);
						$updateThemes = Themes::find($theme_id);
							$updateThemes->costs = json_encode($costs);
							$updateThemes->save();
					}
					gambaLogs::data_log("End Calculating Themes for {$values['name']}", "costs-{$camp}.log", 'true', 'true');

				}
				gambaLogs::data_log("", "costs.log", 'false', 'false');
			}

		}

		/**
		 * Step 2B: Get the Supply List Budgets and Total up the Theme Costs
		 * @param unknown $camp
		 * @param unknown $theme
		 * @param unknown $cost_analysis_summary
		 * @param unknown $cost_grade_display
		 * @return number
		 */
		public static function supplylist_costing_summary($camp, $theme, $cost_analysis_summary, $cost_grade_display = NULL, $num_activities) {
			$sql = "SELECT sl.id, sl.costing_summary, act.grade_id FROM ".tbpre."supplylists sl LEFT JOIN ".tbpre."activities act ON act.id = sl.activity_id WHERE sl.camp_type = $camp AND act.theme_id = $theme";
			gambaLogs::data_log("Supply List Costing Summary | SQL: {$sql}", "costs-{$camp}.log");
			gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
			$query = SupplyLists::select('supplylists.id', 'supplylists.costing_summary', 'activities.grade_id')->leftjoin('activities', 'activities.id', '=', 'supplylists.activity_id')->where('supplylists.camp_type', $camp)->where('activities.theme_id', $theme)->get();
			$camps = gambaCampCategories::camps_list();
			foreach($query as $key => $row) {
				$costs = json_decode($row->costing_summary, true);
				if($cost_grade_display == "true") {
					$array['theme_cost_per_camper'][$row['grade_id']] += $costs['activity_cost_per_camper'];
					gambaLogs::data_log("In Supply List Budgets {$camps[$camp]['name']} | Supply ID: {$row['id']} Camp G Grade ID: {$row['grade_id']} Activity Cost Year 1: {$costs['activity_cost_per_camper']}", "costs-{$camp}.log");
					gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
				} else {
					$array['theme_cost_per_camper'] += $costs['activity_cost_per_camper'];
					gambaLogs::data_log("In Supply List Budgets {$camps[$camp]['name']} | Supply ID: {$row['id']} Non-Curriculum or GSQ Activity Cost Year 1: {$costs['activity_cost_per_camper']}", "costs-{$camp}.log");

					if($cost_analysis_summary == "gsq") {
						//$array['theme_cost_per_camper_yr2'] += $costs['activity_cost_per_camper_yr2'] + $costs['activity_cost_per_camper_consumable'];
						$array['theme_cost_per_camper_consumable'] += $costs['activity_cost_per_camper_consumable'];
						$array['theme_cost_per_camper_nonconsumable_yr1'] += $costs['activity_cost_per_camper_nonconsumable_yr1'];
						$array['theme_cost_per_camper_nonconsumable_yr2'] += $costs['activity_cost_per_camper_nonconsumable_yr2'];
						gambaLogs::data_log("In Supply List Budgets {$camps[$camp]['name']} | Supply ID: {$row['id']} | GSQ Activity Cost Year 2: {$costs['activity_cost_per_camper_yr2']} | activity_cost_per_camper_consumable: {$costs['activity_cost_per_camper_consumable']}", "costs-{$camp}.log");
						gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
					}
				}
			}
			if($cost_analysis_summary == "gsq") {
				if($array['theme_cost_per_camper'] > 0) {
					//gambaLogs::data_log("Error Line 447: {$array['theme_cost_per_camper']} / {$num_activities}", "costs-{$camp}.log");
					$array['theme_total_avg_cost'] = $array['theme_cost_per_camper'] / $num_activities;
				} else {
					$array['theme_total_avg_cost'] = 0;
				}
			}
			if($cost_analysis_summary == "gsq") {
				$price_theme_cost_per_camper_yr1 = "$".number_format($array['theme_cost_per_camper'], 2);
				$price_theme_cost_per_camper_consumable = "$".number_format($array['theme_cost_per_camper_consumable'], 2);
				$price_theme_cost_per_camper_nonconsumable_yr1 = "$".number_format($array['theme_cost_per_camper_nonconsumable_yr1'], 2);
				$array['theme_cost_per_camper_yr1_calc'] = "Theme Cost Per Camper Year 1 Calc: {$price_theme_cost_per_camper_yr1} = {$price_theme_cost_per_camper_consumable}(Consumable) + {$price_theme_cost_per_camper_nonconsumable_yr1}(Non-Consumable Year 1)";

				$array['theme_cost_per_camper_yr2'] = $array['theme_cost_per_camper_consumable'] + $array['theme_cost_per_camper_nonconsumable_yr2'];
				$price_theme_cost_per_camper_yr2 = "$".number_format($array['theme_cost_per_camper_yr2'], 2);
				$price_theme_cost_per_camper_consumable = "$".number_format($array['theme_cost_per_camper_consumable'], 2);
				$price_theme_cost_per_camper_nonconsumable_yr2 = "$".number_format($array['theme_cost_per_camper_nonconsumable_yr2'], 2);
				$array['theme_cost_per_camper_yr2_calc'] = "Theme Cost Per Camper Year 2 Calc: {$price_theme_cost_per_camper_yr2} = {$price_theme_cost_per_camper_consumable}(Consumable) + {$price_theme_cost_per_camper_nonconsumable_yr2}(Non-Consumable Year 2)";
				$theme_info = gambaThemes::theme_by_id($theme);
				$array['theme_cost_per_camper_total'] = $array['theme_cost_per_camper'] + $array['theme_cost_per_camper_yr2'];
				gambaLogs::data_log("In Supply List Budgets $camp, {$theme_info['name']}, GSQ Theme Cost Per Camper Year 2: {$costs['theme_cost_per_camper_yr2']} | Calc: {$array['theme_cost_per_camper_yr2_calc']}", "costs-{$camp}.log");
				gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
			}
			gambaLogs::data_log("In Supply List Budgets $camp, $theme Theme Cost Year 1: {$array['theme_cost_per_camper']}", "costs-{$camp}.log");
			gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
			gambaLogs::data_log("In Supply List Budgets $camp, $theme Theme Cost Year 2: {$array['theme_cost_per_camper_yr2']}", "costs-{$camp}.log");
			return $array;
		}

		/**
		 * Step 3: Summarize Data by Camp for Reports
		 *  - If it uses Camp G Format
		 *   - If it is is divided up by Grade
		 *  - If it uses GSQ Format
		 * @param unknown $term
		 */
		public static function cost_summarize_camps($term) {
			$camps = gambaCampCategories::camps_list();
			$grades = gambaGrades::grade_list();
			foreach($camps as $camp => $values) {
				if($values['camp_values']['cost_analysis'] == "true") {
					$cost_analysis_summary = $values['camp_values']['cost_analysis_summary'];
					// Camp Category has Cost Analysis Enabled
					gambaLogs::data_log("Begin $term {$values['name']} Cost Summary Report", "costs-{$camp}.log", 'true', 'true');
					gambaLogs::data_log("", "costs.log", "false", "false", "false");
					gambaLogs::data_log("\nBegin $term {$values['name']} Cost Summary Report", "costs.log", "false", "false", "false");

					gambaLogs::data_log("Cost Analysis Summary: {$camps[$camp]['camp_values']['cost_analysis_summary']}", "costs-{$camp}.log");
					if($camps[$camp]['camp_values']['cost_analysis_summary'] == "campg") {
						gambaLogs::data_log("$term Camp Galileo ({$values['name']}) Cost Summary Report", "costs-{$camp}.log");
						if($camps[$camp]['camp_values']['cost_enrollment_data'] == "true" && is_array($grades[$camp]['grades'])) {
							foreach($grades[$camp]['grades'] as $grade => $grade_values) {
								if($grade_values['enrollment'] == 1) {
									self::cost_summarize_campg_report($camp, $term, $grade, $camps[$camp]['camp_values']['cost_grade_display'], $camps[$camp]['camp_values']['cost_enrollment_data']);
								}
							}
						} else {
							self::cost_summarize_campg_report($camp, $term, "", $camps[$camp]['camp_values']['cost_grade_display'], $camps[$camp]['camp_values']['cost_enrollment_data']);
						}
					}
					if($camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
						gambaLogs::data_log("$term GSQ ({$values['name']}) Cost Summary Report", "costs-{$camp}.log");
						self::cost_summarize_gsq_report($camp, $term, $camps[$camp]['camp_values']['cost_enrollment_data']);
					}
					gambaLogs::data_log("End $term {$values['name']} Cost Summary Report", "costs-{$camp}.log", 'true', 'true');

				} else {
					gambaLogs::data_log("$term Cost Summary Report Failed", "costs-{$camp}.log");
				}

			}

		}

		/**
		 * Step 3B Alternative 1: Camp G Report Format
		 *  - Get Themes by Camp
		 *  - Get the Current Camp Values
		 *  - Cycle Through the Camps Themes
		 *  - Update Theme Cost By Year
		 *  - Update Camp values
		 * @param unknown $camp
		 * @param unknown $term
		 * @param unknown $grade
		 * @param unknown $cost_grade_display
		 * @param unknown $cost_enrollment_data
		 */
		public static function cost_summarize_campg_report($camp, $term, $grade, $cost_grade_display, $cost_enrollment_data) {
			$camp_info = gambaCampCategories::camp_info($camp);
			//$themes = gambaThemes::themes_by_camp($camp, $term);

			$themes = gambaThemes::themes_camps_all($term);

			$camp_update = Camps::find($camp);
			$camp_values = json_decode($camp_update->camp_values, true);
			$camp_costing_summary = json_decode($camp_update->costing_summary, true);

			gambaLogs::data_log("Camp: {$camp_info['name']} ($camp) | Cost Summarize Camp G Report | Term: $term | Grade: $grade | Grade Display: $cost_grade_display | Enrollment: $cost_enrollment_data", "costs-{$camp}.log");
			gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
			$total_enrollment = 0;
			$total_cost = 0;
			foreach($themes[$camp] as $theme_id => $values) {
				if($values['this_camp'] == "true" || ($values['theme_options']['this_camp'] == "false" && in_array($camp, $values['theme_options']['category_themes']))) {
					gambaLogs::data_log("$term Camp Galileo Cost Summary Report (Theme: {$values['name']})", "costs-{$camp}.log");
					gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
					$theme_budget_per_camper = $values['budget']['theme_budget_per_camper'];
					if($cost_enrollment_data == "true") {
						$theme_budget_yr1_enrollment = self::get_year1_enrollment($term, $camp, $theme_id, $grade, $values['link_id']);
						$source = "From Camper Enrollment Tables";
					} else {
						$theme_budget_yr1_enrollment = $values['budget']['theme_budget_yr1_enrollment'];
						$source = "From Cost Summary Themes";
					}
					gambaLogs::data_log("Got Enrollment: $theme_budget_yr1_enrollment ($source) | {$camp_info['name']} ($camp) | Theme: $theme_id | Grade: $grade | Link ID: {$values['link_id']}", "costs-{$camp}.log");
					$updateThemes = Themes::find($theme_id);
					$costing_summary = json_decode($updateThemes->costing_summary, true);

					if($cost_grade_display == "true") {
						// Updated Results
						$total_cost_year1 = $values['costs']['theme_cost_per_camper'][$grade] * $theme_budget_yr1_enrollment;
						$costing_summary['total_cost_year1'][$grade] = $total_cost_year1;

						$price_total_cost_year1 = '$'.number_format($total_cost_year1, 2);
						$price_theme_cost_per_camper = '$'.number_format($values['costs']['theme_cost_per_camper'][$grade], 2);
						$costing_summary['total_cost_year1_calc'] = "$price_total_cost_year1 = $price_theme_cost_per_camper * $theme_budget_yr1_enrollment";

						$total_enrollment += $theme_budget_yr1_enrollment;
						$total_cost += $total_cost_year1;

					} else {
						// Updated Results
						$total_cost_year1 = $values['costs']['theme_cost_per_camper'] * $theme_budget_yr1_enrollment;
						$costing_summary['total_cost_year1'] = $total_cost_year1;
						$price_total_cost_year1 = '$'.number_format($total_cost_year1, 2);
						$price_theme_cost_per_camper = '$'.number_format($values['costs']['theme_cost_per_camper'], 2);
						$costing_summary['total_cost_year1_calc'] = "$price_total_cost_year1 = $price_theme_cost_per_camper * $theme_budget_yr1_enrollment";
						$total_enrollment += $theme_budget_yr1_enrollment;
						$total_cost += $total_cost_year1;

					}
					$updateThemes->costing_summary = json_encode($costing_summary);
					$updateThemes->save();
					gambaLogs::data_log("$json_array", "costs-{$camp}.log");
				}
			}
			if($cost_grade_display == "true") {
				// Total Average Year 1
				$total_average = $total_cost / $total_enrollment;
				gambaLogs::data_log("Camp G Grade Total Average: $total_average = $total_cost / $total_enrollment", "costs-{$camp}.log");
				$camp_costing_summary[$term][$grade]['total_cost'] = $total_cost;
				$camp_costing_summary[$term][$grade]['total_enrollment'] = $total_enrollment;
				$camp_costing_summary[$term][$grade]['total_average'] = $total_average;
				$camp_costing_summary[$term][$grade]['updated_on'] = date("Y-m-d H:i:s");
			} else {
				// Total Average Year 1
				$total_average = $total_cost / $total_enrollment;
				gambaLogs::data_log("Camp G Total Average: $total_average = $total_cost / $total_enrollment", "costs-{$camp}.log");
				$camp_costing_summary[$term]['total_cost'] = $total_cost;
				$camp_costing_summary[$term]['total_enrollment'] = $total_enrollment;
				$camp_costing_summary[$term]['total_average'] = $total_average;
				$price_total_average = '$'.number_format($total_average, 2);
				$price_total_cost = '$'.number_format($total_cost, 2);
				$camp_costing_summary[$term]['total_average_calc'] = "{$price_total_average} = {$price_total_cost} * $total_enrollment | Formula: Total Average Actual Cost Per Camper = Total Cost * Total Enrollment";
				$camp_costing_summary[$term]['updated_on'] = date("Y-m-d H:i:s");
			}
// 			unset($camp_values['cost_summary']);
// 			unset($camp_costing_summary['total_cost_year1']);
// 			unset($camp_costing_summary['total_cost_year2']);
// 			unset($camp_costing_summary['total_cost_combined']);
			$camp_update->camp_values = json_encode($camp_values);
			$camp_update->costing_summary = json_encode($camp_costing_summary);
			$camp_update->save();
		}

		/**
		 * Step 3B Alternative 1b: Get Enrollment Numbers from Camper Data
		 * @param unknown $term
		 * @param unknown $camp
		 * @param unknown $theme_id
		 * @param unknown $grade
		 * @param unknown $theme_link_id
		 * @return mixed
		 */
		public static function get_year1_enrollment($term, $camp, $theme_id, $grade, $theme_link_id) {
			$camp_info = gambaCampCategories::camp_info($camp);
			gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
			// Camp G
			if($camp == 1) {
				$row = Enrollment::select('theme_values', 'location_values')->where('term', $term)->where('camp', $camp)->where('grade_id', $grade)->where('location_id', 0)->orderBy('id', 'desc')->take(1)->first();

				$theme_values = json_decode($row->theme_values, true);
				$location_values = json_decode($row->location_values, true);
				$json_values = $row['theme_values'];
					// Changed from rev_enroll to camper_weeks 3/6/17
				$enrollment = $theme_values[$theme_link_id][$theme_id]['camper_weeks'];
				if($term <= 2015) {
					// Changed from rev_enroll to camper_weeks 3/6/17
					$enrollment = $location_values['camper_weeks'];
					$json_values = $row['location_values'];
				}
				gambaLogs::data_log("{$camps['name']} ($camp) Is this the Enrollment?: $enrollment ({$location_values['rev_enroll']}) | Term: $term | Theme: $theme_id | Grade: $grade | Link ID: $theme_link_id | JSON: {$row['theme_values']}", "costs-{$camp}.log");
			}
			// GSQ
			if($camp == 2) {
				$sql = "SELECT `theme_values` FROM `gmb_enrollment` WHERE `term` = 2017 AND `camp` = 2 AND `grade_id` = 11 AND `location_id`  = 0";
				$row = Enrollment::select('theme_values')->where('term', $term)->where('camp', 2)->where('grade_id', 11)->where('theme_id', $theme_id)->where('location_id', 0)->first();
				$theme_values = json_decode($row->theme_values, true);
				$enrollment = $theme_values['tot_enrollments'];
				gambaLogs::data_log("{$camps['name']} ($camp) Is this the Enrollment?: $enrollment | Term: $term | Theme: $theme_id | Grade: $grade | JSON: {$row['theme_values']}", "costs-{$camp}.log");
			}
			// Camp G Basic Supplies
			if($camp == 17) {
				$row = Enrollment::select('theme_values', 'location_values')->where('term', $term)->where('camp', 1)->where('grade_id', $grade)->where('location_id', 0)->orderBy('id', 'desc')->take(1)->first();

				$theme_values = json_decode($row->theme_values, true);
				$location_values = json_decode($row->location_values, true);
				$json_values = $row['theme_values'];
				// Changed from rev_enroll to camper_weeks 3/6/17
				$enrollment = $theme_values[$theme_link_id][$theme_id]['camper_weeks'];
				if($term <= 2015) {
					$enrollment = $location_values['camper_weeks'];
					$json_values = $row['location_values'];
				}
				gambaLogs::data_log("{$camps['name']} ($camp) Is this the Enrollment?: $enrollment ({$location_values['rev_enroll']}) | Term: $term | Theme: $theme_id | Grade: $grade | Link ID: $theme_link_id | JSON: {$row['theme_values']}", "costs-{$camp}.log");
			}
			// Camp G Extension Materials
			if($camp == 4) {
				$row = EnrollmentExt::select('location_values')->where('term', $term)->where('camp', 1)->where('location_id', 0)->first();
				$location_values = json_decode($row->location_values, true);
				$enrollment = $location_values['total_enroll'];
				gambaLogs::data_log("{$camps['name']} ($camp) Is this the Enrollment?: $enrollment (PM Enrollment Sessions: {$location_values['pm_enroll_sess']} | Total Sessions: {$location_values['total_sessions']} | Total Enrollment: {$location_values['total_enroll']} | Total Large Camps: {$location_values['tot_lg_camps']} | Total Small Camps: {$location_values['tot_sm_camp']} | Average Enrollment: {$location_values['avg_enrollment']}) | Term: $term | Location ID: 0 | JSON: {$row['location_values']}", "costs-{$camp}.log");
			}
			// Camp G Extended Care
			if($camp == 5) {
				$row = EnrollmentExt::select('location_values')->where('term', $term)->where('camp', 1)->where('location_id', 0)->first();
				$location_values = json_decode($row->location_values, true);
				$enrollment = $location_values['total_enroll'];
				gambaLogs::data_log("{$camps['name']} ($camp) Is this the Enrollment?: $enrollment (PM Enrollment Sessions: {$location_values['pm_enroll_sess']} | Total Sessions: {$location_values['total_sessions']} | Total Enrollment: {$location_values['total_enroll']} | Total Large Camps: {$location_values['tot_lg_camps']} | Total Small Camps: {$location_values['tot_sm_camp']} | Average Enrollment: {$location_values['avg_enrollment']}) | Term: $term | Location ID: 0 | JSON: {$row['location_values']}", "costs-{$camp}.log");
			}
			// GSQ Extended Care
			if($camp == 10) {
				$row = EnrollmentExt::select('location_values')->where('term', $term)->where('camp', 2)->where('location_id', 0)->first();
				$location_values = json_decode($row->location_values, true);
				$enrollment = $location_values['total_enroll'];
				gambaLogs::data_log("{$camps['name']} ($camp) Is this the Enrollment?: $enrollment (PM Enrollment Sessions: {$location_values['pm_enroll_sess']} | Total Sessions: {$location_values['total_sessions']} | Total Enrollment: {$location_values['total_enroll']} | Total Large Camps: {$location_values['tot_lg_camps']} | Total Small Camps: {$location_values['tot_sm_camp']} | Average Enrollment: {$location_values['avg_enrollment']}) | Term: $term | Location ID: 0 | JSON: {$row['location_values']}", "costs-{$camp}.log");
			}
			return $enrollment;
		}

		/**
		 * Step 3B Alternative 2: GSQ Report Format
		 * @param unknown $camp
		 * @param unknown $term
		 */
		public static function cost_summarize_gsq_report($camp, $term, $cost_enrollment_data) {
			$camps = self::camp_categories();
			$themes = gambaThemes::themes_camps_all($term);

			$camp_update = Camps::find($camp);
			$camp_values = json_decode($camp_update->camp_values, true);
			$camp_costing_summary = json_decode($camp_update->costing_summary, true);

			// Updated Results
			$total_enrollment = 0;
			$total_cost = 0;
			foreach($themes[$camp] as $theme_id => $values) {
				if($values['this_camp'] == "true") {
					$budget = $values['budget'];
					$costs = $values['costs'];
					if($cost_enrollment_data == "true") {
						$theme_budget_yr1_enrollment = self::get_year1_enrollment($term, $camp, $theme_id, 11, "");
						$theme_budget_yr2_enrollment = $theme_budget_yr1_enrollment;
						$source = "From Camper Enrollment Tables";
					} else {
						$theme_budget_yr1_enrollment = $budget['theme_budget_yr1_enrollment'];
						$theme_budget_yr2_enrollment = $budget['theme_budget_yr2_enrollment'];
						$source = "From Cost Summary Themes";
					}
					// Updated Results
					// Year 1
					$total_cost_year1 = $costs['theme_cost_per_camper'] * $theme_budget_yr1_enrollment;
					$costing_summary['total_cost_year1'] = $total_cost_year1;
					// Year 2
					$total_cost_year2 = $costs['theme_cost_per_camper_yr2'] * $theme_budget_yr2_enrollment;
					$costing_summary['total_cost_year2'] = $total_cost_year2;
					// Year 1 & 2 Combined
					$total_cost_combined = $total_cost_year1 + $total_cost_year2;
					$costing_summary['total_cost_combined'] = $total_cost_combined;
					// Year 1 & 2 Combined Calculation
					$price_total_cost_combined = '$'.number_format($total_cost_combined, 2);
					$price_total_cost_year1 = '$'.number_format($total_cost_year1, 2);
					$price_theme_cost_per_camper = '$'.number_format($costs['theme_cost_per_camper'], 2);
					$price_total_cost_year2 = '$'.number_format($total_cost_year2, 2);
					$price_theme_cost_per_camper_yr2 = '$'.number_format($costs['theme_cost_per_camper_yr2'], 2);
					$costing_summary['total_cost_combined_calc'] = "$price_total_cost_combined = ($price_total_cost_year1 = ($price_theme_cost_per_camper * $theme_budget_yr1_enrollment)) + ($price_total_cost_year2 = ($price_theme_cost_per_camper_yr2 * $theme_budget_yr2_enrollment))";
					// Totals
					$combined_budget_enrollment = $theme_budget_yr1_enrollment + $theme_budget_yr2_enrollment;
					$total_enrollment += $combined_budget_enrollment;
					$theme_total_cost = $total_cost_year1 + $total_cost_year2;
					$total_cost += $theme_total_cost;
					gambaLogs::data_log("$term GSQ Cost Summary Report (Theme: {$values['name']}) Theme Cost Yr 1: {$total_cost_year1} | Got Enrollment: $theme_budget_yr1_enrollment ($source) | Theme Cost Yr 2: {$total_cost_year2}", "costs-{$camp}.log");
					gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
					// Update Theme Costing Summary
					$updateThemes = Themes::find($theme_id);
						$updateThemes->costing_summary = json_encode($costing_summary);
						$updateThemes->save();
					gambaLogs::data_log("GSQ Report {$values['name']}{$json_array}", "costs-{$camp}.log");
				}
			}

			// Updated Results
			// Total Average Year 1 & 2
			$total_average = $total_cost / $total_enrollment;
			gambaLogs::data_log("GSQ Total Average: $total_average = $total_cost / $total_enrollment", "costs-{$camp}.log");
// 			unset($camp_values['cost_summary']['']);
			$camp_costing_summary[$term]['total_cost'] = $total_cost;
			$camp_costing_summary[$term]['total_enrollment'] = $total_enrollment;
			$camp_costing_summary[$term]['total_average'] = $total_average;
			$camp_costing_summary[$term]['updated_on'] = date("Y-m-d H:i:s");
// 			unset($camp_values['cost_summary'][$term]['cost_summary_new_total']);
// 			unset($camp_values['cost_summary'][$term]['cost_summary_totalyr1']);
// 			unset($camp_values['cost_summary'][$term]['cost_summary_totalyr2']);
// 			unset($camp_values['cost_summary'][$term]['cost_summary_totalyr1yr2']);
// 			unset($camp_values['cost_summary'][$term]['cost_summary_budget_yr1_enrollment']);
// 			unset($camp_values['cost_summary'][$term]['cost_summary_weighted_total_cost_per_camper']);
// 			unset($costing_summary['total_cost_year1']);
// 			unset($costing_summary['total_cost_year2']);
// 			unset($costing_summary['total_cost_combined']);

// 			unset($camp_values['cost_summary']);
// 			unset($camp_costing_summary['total_cost_year1']);
// 			unset($camp_costing_summary['total_cost_year2']);
// 			unset($camp_costing_summary['total_cost_combined']);

			$camp_update->camp_values = json_encode($camp_values);
			$camp_update->costing_summary = json_encode($camp_costing_summary);
			$camp_update->save();
		}


		public static function camp_categories() {
			$camps = gambaCampCategories::camps_list();
			foreach($camps as $id => $values) {
				// Camp Category has Cost Analysis Enabled
				if($values['camp_values']['cost_analysis'] == "true") {
					$array[$id] = $values;
				}
			}
			return $array;
		}


		public static function themes_update($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>";
			foreach($array['theme_values'] as $theme_id => $values) {
				$updateThemes = Themes::find($theme_id);
				$theme_options = json_decode($update->theme_options, true);
// 				unset($theme_options['theme_budget_rotations_yr1']);
// 				unset($theme_options['theme_budget_per_camper_yr1']);
// 				unset($theme_options['theme_budget_rotations_yr2']);
// 				unset($theme_options['theme_budget_per_camper_yr2']);
// 				unset($theme_options['theme_budget_activities']);
// 				unset($theme_options['budget'][$array['term']]);
// 				unset($theme_options['budget']);
				$new_theme_options = array_merge($theme_options, $values['theme_options']);
// 				echo "<pre>"; print_r($values); echo "</pre>";
// 				echo "<pre>"; print_r($theme_options); echo "</pre>";
// 				echo "<pre>"; print_r($new_theme_options); echo "</pre>";
				$save_theme_options = json_encode($new_theme_options);
				//$update->theme_options = $save_theme_options;
				$updateThemes->budget = json_encode($values['budget']);
				$updateThemes->save();
			}
		}

		public static function check_quantity_type_values($term, $camp) {
			$quantity_types = gambaQuantityTypes::camp_quantity_types();
			$i = 0;
			if($quantity_types[$camp]['camp_cost_options'][$term]['rotations'] != "") { $i++; }
			if($quantity_types[$camp]['camp_cost_options'][$term]['theme_weeks'] != "") { $i++; }
			if($quantity_types[$camp]['camp_cost_options'][$term]['campers'] != "") { $i++; }
			if($i == 0) { return "true"; }
		}

		public static function copy_previous_quantity_types($array) {
			$new_array['camp'] = $camp = $array['camp'];
			$new_array['term'] = $term = $array['term'];
			$new_array['prev_term'] = $prev_term = $term - 1;
			$quantity_types = gambaQuantityTypes::camp_quantity_types();
			foreach($quantity_types[$camp]['quantity_types'] as $key => $values) {
				$new_array['update'][$key]['cost_options']['NC']['enabled'] = $values['cost_options'][$prev_term]['NC']['enabled'];
				$new_array['update'][$key]['cost_options']['C']['enabled'] = $values['cost_options'][$prev_term]['C']['enabled'];

				$new_array['update'][$key]['cost_options']['rotations_enabled'] = $values['cost_options'][$prev_term]['rotations_enabled'];
				$new_array['update'][$key]['cost_options']['theme_weeks_enabled'] = $values['cost_options'][$prev_term]['theme_weeks_enabled'];
				$new_array['update'][$key]['cost_options']['campers_enabled'] = $values['cost_options'][$prev_term]['campers_enabled'];

				$new_array['update'][$key]['cost_options']['quantity_type_link'] = $values['cost_options'][$prev_term]['quantity_type_link'];
			}
			$new_array['camp_cost_options']['rotations'] = $quantity_types[$camp]['camp_cost_options'][$prev_term]['rotations'];
			$new_array['camp_cost_options']['theme_weeks'] = $quantity_types[$camp]['camp_cost_options'][$prev_term]['theme_weeks'];
			$new_array['camp_cost_options']['campers'] = $quantity_types[$camp]['camp_cost_options'][$prev_term]['campers'];
			self::quantity_types_update($new_array);
		}

		public static function quantity_types_update($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$terms = gambaTerm::terms();
			// Add the camp values to the individual quantity types
			foreach($array['update'] as $key => $values) {
				//$new_cost_options['cost_options'] = $values['cost_options'];
				$update = QuantityTypes::find($key);
				$cost_options = json_decode($update->cost_options, true);
// 				echo "<pre>"; print_r($cost_options); echo "</pre>";
// 				echo "<pre>"; print_r($values['cost_options']); echo "</pre>";
				//$new_cost_options = array_merge($cost_options, $values['cost_options']);
// 				echo "<pre>"; print_r($new_cost_options); echo "</pre>";
				//$new_cost_options = $values['cost_options'];
				//echo "<pre>"; print_r($new_cost_options); echo "</pre>"; exit; die();
				//$cost_options = "";
				//$update->cost_options = json_encode($new_cost_options);
				if(is_array($cost_options)) {
					foreach($terms as $term_key => $term_values) {
						if($term_key == $array['term']) {
							$new_cost_options[$term_key] = $values['cost_options'];
						} else {
							$new_cost_options[$term_key] = $cost_options[$term_key];
						}
					}
				} else {
					$new_cost_options[$array['term']] = $values['cost_options'];
				}
				//$new_cost_options = "";
				$update->cost_options = json_encode($new_cost_options);
				$update->save();

			}
			// Save Camp Quantity Type Values: Rotations, Theme Weeks, and Campers
			$update_camp = Camps::find($array['camp']);
			$camp_cost_options = json_decode($update_camp->cost_options, true);
			if(is_array($cost_options)) {
				foreach($terms as $term_key => $term_values) {
					if($term_key == $array['term']) {
						$new_camp_cost_options[$term_key] = $array['camp_cost_options'];
					} else {
						$new_camp_cost_options[$term_key] = $camp_cost_options[$term_key];
					}
				}
			} else {
				$new_camp_cost_options[$array['term']] = $array['camp_cost_options'];
			}
			$update_camp->cost_options = json_encode($new_camp_cost_options);
			$update_camp->save();
// 			exit; die();
		}

		public static function cost_summaries() {
			$query = CostSummaries::orderBy('name')->get();
			foreach($query as $key => $value) {
				$array[$value['id']]['name'] = $value['name'];
				$array[$value['id']]['format'] = $value['format'];
				$array[$value['id']]['options'] = json_decode($value->options, true);
			}
			return $array;
		}

		public static function cost_summary_data($cost_id, $term) {
			$query = CostSummaryData::where('cost_id', $cost_id)->where('term', $term)->get();
			foreach($query as $key => $value) {
				$array[$value['id']]['cost_id'] = $cost_id;
				$array[$value['id']]['term'] = $term;
				$array[$value['id']]['data'] = json_decode($value->data, true);
				$array[$value['id']]['options'] = json_decode($value->options, true);
			}
			return $array;
		}

		public static function summary_report_list() {
			$array = array(
				"campg" => "Camp G Format",
				"campg_grade" => "Camp G by Grade Format",
				"gsq" => "GSQ Format",
			);
			return $array;
		}

		public static function camps_update($array) {
			foreach($array['values'] as $camp_id => $values) {
				$update = Camps::find($camp_id);
				$camp_values = json_decode($update->camp_values, true);
				// Camp Category has Cost Analysis Enabled
				if($values['camp_values']['cost_analysis'] == "") {
					$values['camp_values']['cost_analysis'] = "false";
					$values['camp_values']['cost_analysis_summary'] = "false";
				}
				if($values['camp_values']['cost_enrollment_data'] == "") {
					$values['camp_values']['cost_enrollment_data'] = "false";
				}
				if($values['camp_values']['quantity_type_avg'] == "") {
					$values['camp_values']['quantity_type_avg'] = "false";
				}
				if($values['camp_values']['cost_non_curriculum'] == "") {
				    $values['camp_values']['cost_non_curriculum'] = "false";
				}
				$new_camp_values = array_merge($camp_values, $values['camp_values']);
// 				echo "<pre>"; print_r($values); echo "</pre>";
// 				echo "<pre>"; print_r($camp_values); echo "</pre>";
// 				echo "<pre>"; print_r($new_camp_values); echo "</pre>";
				$save_camp_values = json_encode($new_camp_values);
				$update->camp_values = $save_camp_values;
				$update->save();
			}
			// Amortization Cost Percentages
			$amt_cost_percentages = json_encode($array['amt_cost_percentages']);
			$update = Config::select('value')->where('field', 'amt_cost_percentages')->update(['value' => $amt_cost_percentages]);
		}

	}
