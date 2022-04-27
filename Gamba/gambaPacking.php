<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\PackingLists;
	use App\Models\PackingTotals;
	use App\Models\Supplies;

	use App\Gamba\gambaCalc;
	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaGrades;
	use App\Gamba\gambaInventory;
	use App\Gamba\gambaLocations;
	use App\Gamba\gambaLogs;
	use App\Gamba\gambaParts;
	use App\Gamba\gambaQuantityTypes;
	use App\Gamba\gambaSupplies;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaThemes;
	use App\Gamba\gambaUsers;

	class gambaPacking {

		/**
		 * Packing Lists - Also by Camp
		 * @return Ambigous <string, mixed>
		 */
		public static function packing_lists() {
			$camps = gambaCampCategories::camps_list();
			$packinglists = PackingLists::select('id', 'list', 'camp', 'alt', 'list_values', 'hide');
				$packinglists = $packinglists->orderBy('camp');
				//$array['sql'] = $packinglists->toSql();
				$packinglists = $packinglists->get();
			if($packinglists->count() > 0) {
				foreach($packinglists as $key => $row) {
					$id = $row['id'];
					$array['packinglists'][$id]['list'] = $list = $row['list'];
					$array['packinglists'][$id]['camp'] = $camp = $row['camp'];
					$array['packinglists'][$id]['alt'] = $alt = $row['alt'];
					$array['packinglists'][$id]['list_values'] = $list_values = json_decode($row->list_values, true);
					$array['packinglists'][$id]['camp_values'] = $camps[$camp]['camp_values'];
					$array['packinglists'][$id]['hide'] = $hide = $row['hide'];
					// Display packing lists by camp
					$array['camps'][$camp]['name'] = $camps[$camp]['name'];
					$array['camps'][$camp]['lists'][$id]['camp'] = $camp;
					$array['camps'][$camp]['lists'][$id]['separate'] = $list_values['separate'];
					$array['camps'][$camp]['lists'][$id]['theme_type'] = $list_values['theme_type'];
					$array['camps'][$camp]['camp_values'] = $camps[$camp]['camp_values'];
					$array['camps'][$camp]['list_values'] = $list_values;
					if($list_values['theme_type'] != "true") {
						$array['camps'][$camp]['packing_id'] = $id;
					}
				}
			}
			return $array;
		}


		public static function view_packing_list_calculation($array) {
			$url = url('/');
			$content_array['page_title'] = "Packing Calculations";
			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$terms = gambaTerm::terms();
			$camps = gambaCampCategories::camps_list();
			if($array['term'] == "") { $term = gambaTerm::year_by_status('C'); } else { $term = $array['term']; }
			if($array['camp'] == "") { $camp = 1; } else { $camp = $array['camp']; }
			$content_array['content'] .= <<<EOT
			<form method="get" action="{$url}/settings/calculate_all" name="calculate_all" class="form-inline" role="form">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
				<dl class="sub-nav"><dt>Select Term:</dt>
EOT;
			foreach($terms as $year => $values) {
				$active = ""; if($term == $year) { $active = ' class="active"'; }
				$content_array['content'] .= <<<EOT
				<dd{$active}><a href="{$url}/settings/packing_calc?camp={$camp}&term={$year}" title="{$values['year_status']}">{$year}</a></dd>
EOT;
			}
			$content_array['content'] .= <<<EOT
				</dl>
				<dl class="sub-nav"><dt>Select Camp Category:</dt>
EOT;
			foreach($camps as $key => $value) {
				$active = ""; if($camp == $key) { $active = ' class="active"'; }
				$content_array['content'] .= <<<EOT
				<dd{$active}><a href="{$url}/settings/packing_calc?camp={$key}&term={$term}">{$value['alt_name']}</a></dd>
EOT;
			}
			$date = date("YmdHis");
			$content_array['content'] .= <<<EOT
				</dl>
				<input type="hidden" name="camp" value="{$camp}" />
				<input type="hidden" name="term" value="{$term}" />
				<input type="submit" name="submit" value="Perform Packing Calculations" class="button small radius success" />
			</form>
EOT;
			// Located in Routes/logs.php and LogsController@enroll_calc_log
			$content_array['content'] .= <<<EOT
			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/enroll_calc_log?logfile=enroll_calc.log&limit=5000&{$date}', function(data) {
							var logfile = data;
							$("#enroll_calc").html("<p><a href='{$url}/logs/enroll_calc.log' target='enroll_calc'>Location Calculation</a></p><pre>" + logfile + "</pre>");
							setTimeout(functionToLoadFile, 500);
						});
					};
					setTimeout(functionToLoadFile, 10);
				});
			</script>

			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/enroll_calc_log?logfile=supplies_calc.log&{$date}', function(data) {
							var logfile = data;
							$("#supplies_calc").html("<p><a href='{$url}/logs/supplies_calc.log' target='supplies_calc'>Supply Lists</a></p><pre>" + logfile + "</pre>");
							setTimeout(functionToLoadFile, 500);
						});
					};
					setTimeout(functionToLoadFile, 10);
				});
			</script>

			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/enroll_calc_log?logfile=camp_calc.log&{$date}', function(data) {
							var logfile = data;
							$("#camp_calc").html("<p><a href='{$url}/logs/camp_calc.log' target='camp_calc'>Camp Category</a></p><pre>" + logfile + "</pre>");
							setTimeout(functionToLoadFile, 500);
						});
					};
					setTimeout(functionToLoadFile, 10);
				});
			</script>

			<div class="row">
				<div class="small-12 medium-12 large-12 columns panel" id="enroll_calc"></div>
			</div>
			<div class="row">
				<div class="small-12 medium-12 large-12 columns panel" id="supplies_calc"></div>
			</div>
			<div class="row">
				<div class="small-12 medium-12 large-12 columns panel" id="camp_calc"></div>
			</div>
EOT;
			return $content_array;
// 			<div id="logfile" style="height:530px; overflow:scroll;"></div>

		}
		public static function basic_packing_supplies_lists() {
			$packing_lists = self::packing_lists();
			foreach($packing_lists['packinglists'] as $packing_id => $values) {
				if($values['list_values']['basic_supplies'] == "true") {
					$array['basic'][$packing_id]['name'] = $values['alt'];
					$array['basic'][$packing_id]['basic_supplies_list'] = $values['list_values']['basic_supplies_list'];
					$array['basic'][$packing_id]['sales_pack_by'] = $values['list_values']['sales_pack_by'];
					$array['list'][$values['list_values']['basic_supplies_list']]['name'] = $packing_lists['packinglists'][$values['list_values']['basic_supplies_list']]['alt'];
					$array['list'][$values['list_values']['basic_supplies_list']]['basic'] = $packing_id;
				}
			}
			return $array;
		}

		public static function basic_master_supply_parts($term) {
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$basic_packing_supplies_lists = self::basic_packing_supplies_lists();
			foreach($basic_packing_supplies_lists['basic'] as $packing_id => $values) {
				$packing_supplies = self::packing_supplies_list($packing_id, $term, $theme);
				foreach($packing_supplies as $grade_id => $parts) {
					foreach($parts['parts'] as $part_num => $supplies) {
						foreach($supplies['supplies'] as $supply_id => $supply_values) {
							$array['parts'][$part_num]['description'] = $supply_values['description'];
							$array['parts'][$part_num]['total'] += $supply_values['total_amount'];
						}
					}
				}
			}
			return $array;
		}

		public static function packing_totals_all_lists($term, $qtshort) {
			$lists = self::packing_lists();
			foreach($lists['packinglists'] as $packing_id => $values) {
				self::packing_totals_calc_all($term, $packing_id, $qtshort);
			}
		}

		public static function packing_totals_orphaned($term, $packing_id = 1) {
			$query = PackingTotals::select('packingtotals.part', 'packingtotals.grade', 'packingtotals.theme', 'packingtotals.total', 'packingtotals.converted_total')->leftjoin('parts', 'parts.number', '=', 'packingtotals.part')->where('packingtotals.packing_id', $packing_id)->where('packingtotals.term', $term)->orderBy('parts.description')->get();
			//$array['sql'] = \DB::last_query();
			$i = 0;
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$part = $row['part'];
					$grade = $row['grade'];
					$theme = $row['theme'];
					$total = $row['total'];
					$coverted_total = $row['converted_total'];
					$query1 = Supplies::select('supplies.id', 'supplies.activity_id', 'activities.activity_name', 'supplies.packing_total')->leftjoin('activities', 'activities.id', '=', 'supplies.activity_id')->where('supplies.term', $term)->where('supplies.part', $part)->where('supplies.packing_id', $packing_id)->where('supplies.grade_id', $grade)->where('supplies.theme_id', $theme)->get();
					if($query1->count() > 0) {
						foreach($query1 as $key1 => $row1) {
							$supply_id = $row1['id'];
							$activity_id = $row1['activity_id'];
							$activity_name = $row1['activity_name'];
							$packing_total = $row1['packing_total'];
						}
					} else {
						$array['parts'][$part]['grade'][$grade]['theme'][$theme]['sql'] = $sql1;
						$array['parts'][$part]['grade'][$grade]['theme'][$theme]['total'] = $total;
						$array['parts'][$part]['grade'][$grade]['theme'][$theme]['converted_total'] = $coverted_total;
						$array['parts'][$part]['grade'][$grade]['theme'][$theme]['part_exists'] = "No";
						$i++;
					}
				}
			}
			$array['orphaned'] = $i;
			return $array;
		}

		public static function delete_all_orphans($term, $packing_id) {
			if($term != "" && $packing_id != "") {
				$orphaned_totals = self::packing_totals_orphaned($term, $packing_id);
// 				echo "<pre>"; print_r($orphaned_totals); echo "</pre>"; exit; die();
				foreach($orphaned_totals['parts'] as $part => $grades) {
					foreach($grades['grade'] as $grade => $themes) {
						foreach($themes['theme'] as $theme => $supplies) {
							if($supplies['part_exists'] == "No") {
								self::delete_orphan($term, $packing_id, $part, $grade, $theme);
							}
						}
					}
				}
			}
		}

		public static function delete_orphan($term, $packing_id, $part, $grade, $theme) {
			$delete = PackingTotals::where('term', $term)->where('packing_id', $packing_id)->where('part', $part)->where('grade', $grade)->where('theme', $theme)->delete();
		}

		public static function packing_totals_orphaned_view($term, $packing_id = 1) {
			$url = url('/');
			$orphaned_totals = self::packing_totals_orphaned($term, $packing_id);
			$packing_lists = self::packing_lists();
			$content_array['page_title'] = "Orphaned Packing Totals";
			$content_array['content'] .= <<<EOT
		<p><strong>{$packing_lists['packinglists'][$packing_id]['list']}:</strong> {$orphaned_totals['orphaned']}</p>

		  <button href="#" data-dropdown="drop1" aria-controls="drop1" aria-expanded="false" class="button dropdown">
		    Select List ({$packing_lists['packinglists'][$packing_id]['list']})
		  </button><br />
		  <ul id="drop1" data-dropdown-content class="f-dropdown" aria-hidden="true">
EOT;
			foreach($packing_lists['packinglists'] as $id => $values) {
				if($values['camp_values']['active'] != "false") {
					$content_array['content'] .= <<<EOT
		    <li><a href="{$url}/purchase/orphans?packing_id={$id}">{$values['list']}</a></li>
EOT;
				}
		    }
		    $content_array['content'] .= <<<EOT
		  </ul>
		<p>&nbsp;</p>
EOT;
			if($orphaned_totals['orphaned'] > 0) {
				$delete_all = "<a href='{$url}/purchase/delete_all_orphans?term=$term&packing_id=$packing_id' class='button small success' onclick=\"return confirm('Are you sure you want to delete all orphaned packing totals?');\">Delete All</a>";
				$content_array['content'] .= <<<EOT
					<table class="table table-striped table-bordered">
						<thead>
							<tr>
								<th>Packing ID</th>
								<th>Part</th>
								<th>Grade</th>
								<th>Theme</th>
								<th>Total</th>
								<th>{$delete_all}</th>
							</tr>
						</thead>
						<tbody>
EOT;
				foreach($orphaned_totals['parts'] as $part => $grades) {
					foreach($grades['grade'] as $grade => $themes) {
						foreach($themes['theme'] as $theme => $supplies) {
							if($supplies['part_exists'] == "No") {
								$part_info = gambaParts::part_info($part);
								$theme_info = gambaThemes::theme_by_id($theme);
								$content_array['content'] .= <<<EOT
							<tr>
								<td>{$packing_id}</td>
								<td>{$part} {$part_info['description']}</td>
								<td>{$grade}</td>
								<td>{$theme}: {$theme_info['name']}</td>
								<td>{$supplies['total']}</td>
								<td><a href="{$url}/purchase/delete_orphan?term={$term}&packing_id={$packing_id}&part={$part}&grade={$grade}&theme={$theme}" class="button small success" onclick="return confirm('Are you sure you want to delete {$part} {$part_info['description']}');">Delete</a></td>
							</tr>
EOT;
							}
						}
					}
				}
				$content_array['content'] .= <<<EOT
						</tbody>
					</table>
EOT;
			} else {
				$content_array['content'] .= "<p><strong>Congratulations! There are no orphaned packing totals in this list.</strong></p>";
			}

			return $content_array;
// 		echo "<pre>"; print_r($packing_lists); echo "</pre>";
// 		echo "<pre>"; print_r($orphaned_totals); echo "</pre>";
		}

		public static function packing_totals_calc_all($term, $packing_id, $qtshort = 1) {
			$date = date("Y-m-d H:i:s");
			$supplies = gambaSupplies::supplypartsbycamptheme($term, $packing_id);
			$delete = PackingTotals::where('term', $term)->where('packing_id', $packing_id)->delete();
			gambaLogs::truncate_log("packingtotal-$packing_id.log");
			gambaLogs::data_log('Begin Calculating Packing Totals '.$term, "packingtotal-$packing_id.log");
			foreach($supplies['camp'] as $camp => $camp_values) {
				foreach($camp_values['packing'] as $packing_id => $packing_values) {
					foreach($packing_values['grade'] as $grade => $grade_values) {
						foreach($grade_values['theme'] as $theme => $theme_values) {
							foreach($theme_values['part'] as $part => $part_values) {
// 								echo "<pre>"; print_r($part_values); echo "</pre>";
								$total = array();
								$total['camp'] = $camp;
								$total['packing'] = $packing_id;
								$total['grade'] = $grade;
								$total['theme'] = $theme;
								$total['part'] = $part . " - " . $part_values['description'];
								$total['conversion'] = $part_values['conversion'];
								if($part_values['conversion'] == "") { $part_values['conversion'] = 1; }
								$total['conversion'] = $part_values['conversion'];
								$raw_total = 0;
								$converted_total = 0;
// 								foreach($part_values['supply'] as $supply_id => $supply_values) {
// 									$total['supply_ids'][$supply_id] = 1;
// 									$packing_quantities = $supply_values['packing_quantities'];
// 									foreach($packing_quantities as $location => $location_values) {
// 										foreach($location_values as $key => $values) {
// 											$total['locations'][$location][$key]['raw_total'] += $values['total'];
// 											$raw_total += $values['total'];
// 										}
// 									}
// 								}
// 								foreach($total['locations'] as $location => $location_values) {
// 									foreach($location_values as $key => $values) {
// 										$total['locations'][$location][$key]['calc'] = $values['raw_total'] . " / " . $part_values['conversion'];
// 										$total['locations'][$location][$key]['converted'] = $values['raw_total'] / $part_values['conversion'];
// 										gambaLogs::data_log($total['locations'][$location][$key]['calc'], "packingtotal-$packing_id.log");
// 										$total['locations'][$location][$key]['total'] = $loc_convert_total = ceil($values['raw_total'] / $part_values['conversion']);
// 										$converted_total += $loc_convert_total;
// 									}
// 								}
								foreach($part_values['supply'] as $supply_id => $supply_values) {
									$packing_quantities = $supply_values['packing_quantities'];
									foreach($packing_quantities as $location => $location_values) {
										foreach($location_values as $key => $values) {
											$total['locations'][$location][$key]['raw_total'] += $values['total'];
											$raw_total += $values['total'];
										}
									}
								}
								foreach($total['locations'] as $location => $location_values) {
									foreach($location_values as $key => $values) {
										$total['locations'][$location][$key]['calc'] = $values['raw_total'] . " / " . $part_values['conversion'];
										$total['locations'][$location][$key]['converted'] = $values['raw_total'] / $part_values['conversion'];
										gambaLogs::data_log($total['locations'][$location][$key]['calc'], "packingtotal-$packing_id.log");
										$total['locations'][$location][$key]['total'] = $loc_convert_total = ceil($values['raw_total'] / $part_values['conversion']);
										$converted_total += $loc_convert_total;
									}
								}
								$total['raw_total'] = $raw_total;
								$total['converted_total'] = $converted_total;
								$json_locations = json_encode($total['locations']);

								$insert = new PackingTotals;
									$insert->term = $term;
									$insert->part = $part;
									$insert->packing_id = $packing_id;
									$insert->camp = $camp;
									$insert->grade = $grade;
									$insert->theme = $theme;
									$insert->total = $raw_total;
									$insert->converted_total = $converted_total;
									$insert->location_totals = $json_locations;
									$insert->created = $date;
									$insert->save();
// 								echo "<pre>"; print_r($total); echo "</pre>";
								gambaLogs::data_log($sql, "packingtotal-$packing_id.log");
// 								echo "<pre>$sql</pre>";
							}
						}
					}
				}
			}
			gambaLogs::data_log('End Calculating Packing Totals', "packingtotal-$packing_id.log");
			if($qtshort == 1) {
				gambaInventory::quantity_short();
			}
// 			exec(php_path . " " . Site_path . "execute_php quantity_short > /dev/null &");
		}

		public static function packing_totals_grade_theme($term, $packing_id, $qtshort = 0, $camp, $theme, $grade, $debug) {
			gambaLogs::data_log("Packing Totals by Grade Theme | Packing ID: $packing_id | Theme: $theme | Grade: $grade", 'camp_calc.log');
			$date = date("Y-m-d H:i:s");
			$supplies = gambaSupplies::supplypartsbypackinidgradetheme($term, $packing_id, $grade, $theme);

			$delete = PackingTotals::where('term', $term)->where('packing_id', $packing_id)->where('camp', $camp)->where('grade', $grade)->where('theme', $theme)->delete();
			foreach($supplies['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['theme'][$theme]['part'] as $part => $part_values) {
				$total = array();
				$total['camp'] = $camp;
				$total['packing'] = $packing_id;
				$total['grade'] = $grade;
				$total['theme'] = $theme;
				$total['part'] = $part . " - " . $part_values['description'];
				gambaLogs::data_log("Part: {$total['part']} | Camp: $camp | Packing ID: $packing_id | Grade: $grade | Theme: $theme", 'camp_calc.log');
				$total['conversion'] = $part_values['conversion'];
				if($part_values['conversion'] == "") { $part_values['conversion'] = 1; }
				$total['conversion'] = $part_values['conversion'];
				$raw_total = 0;
				$converted_total = 0;
				foreach($part_values['supply'] as $supply_id => $supply_values) {
					$exclude = $supply_values['exclude'];
					$packing_quantities = $supply_values['packing_quantities'];
					foreach($packing_quantities as $location => $location_values) {
						$location_info = gambaLocations::location_by_id($location);
						foreach($location_values as $key => $values) {
							$total['locations'][$location][$key]['location'] = $location_info['abbr'] . " - " . $location_info['name'] . " | " . date("Y-m-d H:i:s");
							//$total['locations'][$location][$key]['key'] = "[raw_total]: Total Unconverted with Exclusions | [total]: Total with Exclusions Converted | [converted]: Total without Exclusions Converted";
							$total['locations'][$location][$key]['raw_total'] += $values['total'];
							$total['locations'][$location][$key]['total'] += $values['total'];
							$total['locations'][$location][$key]['calc'] .= " + (Exclude: $exclude | Total: ".$values['total'].")";
							$total['locations'][$location][$key]['raw_total_calc'] .= " + ".$values['total'];
							$total['locations'][$location][$key]['conversion'] = $part_values['conversion'];
							if($exclude == 0) {
								$total['locations'][$location][$key]['converted'] += $values['total'];
							} else {
								$total['locations'][$location][$key]['converted'] += 0;
							}
						}
					}
				}
				foreach($total['locations'] as $location => $location_values) {
					foreach($location_values as $key => $values) {
						$total['locations'][$location][$key]['total'] = ceil( $values['total'] / $values['conversion'] );
						$total['locations'][$location][$key]['converted'] = ceil( $values['converted'] / $values['conversion'] );
						$converted_total += ceil( $values['converted'] / $values['conversion'] );
						$raw_total += ceil($values['total'] / $part_values['conversion']);
					}
				}

				$total['raw_total'] = $raw_total;
				$total['converted_total'] = $converted_total;
				$json_locations = json_encode($total['locations']);
// 				if($converted_total > 0) {
				$sql = "INSERT INTO gmb_packingtotals (term, part, packing_id, camp, grade, theme, total, converted_total, location_totals, created) VALUES ('$term', '$part', '$packing_id', '$camp', '$grade', '$theme', '$raw_total', '$converted_total', '$json_locations', '$date')";
				gambaLogs::data_log("Packing Total by Grade and Theme SQL: $sql", 'camp_calc.log');
				$insert = new PackingTotals;
					$insert->term = $term;
					$insert->part = $part;
					$insert->packing_id = $packing_id;
					$insert->camp = $camp;
					$insert->grade = $grade;
					$insert->theme = $theme;
					$insert->total = $raw_total;
					$insert->converted_total = $converted_total;
					$insert->location_totals = json_encode($total['locations']);
					$insert->created = $date;
					$insert->save();
// 				gambaLogs::data_log("Packing Total Grade Theme SQL: $sql", 'camp_calc.log');
// 				gambaLogs::data_log("\n\nPacking Totals SQL: $sql", 'camp_calc.log');
// 				}
			}
			if($qtshort == 1) {
				gambaInventory::quantity_short();
			}
		}

		public static function packing_totals_packingid_grade($term, $packing_id, $qtshort = 0, $camp, $grade, $debug) {
			gambaLogs::data_log("Packing Totals by Grade Theme | Packing ID: $packing_id | Theme: $theme | Grade: $grade", 'camp_calc.log');
			$date = date("Y-m-d H:i:s");
			$supplies = gambaSupplies::supplypartsbypackinidgrade($term, $packing_id, $grade);
// 			echo "<pre>"; print_r($supplies); echo "</pre>"; exit; die();
			//mysql_query("DELETE FROM ".tbpre."packingtotals WHERE term = '$term' AND packing_id = '$packing_id' AND camp = '$camp' AND grade = '$grade'") or die("Delete Error");
			$delete = PackingTotals::where('term', $term)->where('packing_id', $packing_id)->where('camp', $camp)->where('grade', $grade)->delete();
			foreach($supplies['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['part'] as $part => $part_values) {
				$total = array();
				$total['camp'] = $camp;
				$total['packing'] = $packing_id;
				$total['grade'] = $grade;
				$total['theme'] = $theme;
				$total['part'] = $part . " - " . $part_values['description'];
				gambaLogs::data_log("Part: {$total['part']} | Camp: $camp | Packing ID: $packing_id | Grade: $grade", 'camp_calc.log');
				$total['conversion'] = $part_values['conversion'];
				if($part_values['conversion'] == "") { $part_values['conversion'] = 1; }
				$total['conversion'] = $part_values['conversion'];
				$raw_total = 0;
				$converted_total = 0;
				foreach($part_values['supply'] as $supply_id => $supply_values) {
					$exclude = $supply_values['exclude'];
					$packing_quantities = $supply_values['packing_quantities'];
					foreach($packing_quantities as $location => $location_values) {
						$location_info = gambaLocations::location_by_id($location);
						foreach($location_values as $key => $values) {
							$total['locations'][$location][$key]['location'] = $location_info['abbr'] . " - " . $location_info['name'] . " | " . date("Y-m-d H:i:s");
							//$total['locations'][$location][$key]['key'] = "[raw_total]: Total Unconverted with Exclusions | [total]: Total with Exclusions Converted | [converted]: Total without Exclusions Converted";
							$total['locations'][$location][$key]['raw_total'] += $values['total'];
							$total['locations'][$location][$key]['total'] += $values['total'];
							$total['locations'][$location][$key]['calc'] .= " + (Exclude: $exclude | Total: ".$values['total'].")";
							$total['locations'][$location][$key]['raw_total_calc'] .= " + ".$values['total'];
							$total['locations'][$location][$key]['conversion'] = $part_values['conversion'];
							if($exclude == 0) {
								$total['locations'][$location][$key]['converted'] += $values['total'];
							} else {
								$total['locations'][$location][$key]['converted'] += 0;
							}
						}
					}
				}
				foreach($total['locations'] as $location => $location_values) {
					foreach($location_values as $key => $values) {
						$total['locations'][$location][$key]['total'] = ceil( $values['total'] / $values['conversion'] );
						$total['locations'][$location][$key]['converted'] = ceil( $values['converted'] / $values['conversion'] );
						$converted_total += ceil( $values['converted'] / $values['conversion'] );
						$raw_total += ceil($values['total'] / $part_values['conversion']);
					}
				}

				$total['raw_total'] = $raw_total;
				$total['converted_total'] = $converted_total;
// 				if($converted_total > 0) {
				$json_locations = json_encode($total['locations']);
				$sql = "INSERT INTO gmb_packingtotals (term, part, packing_id, camp, grade, theme, total, converted_total, location_totals, created) VALUES ('$term', '$part', '$packing_id', '$camp', '$grade', 0, '$raw_total', '$converted_total', '$json_locations', '$date')";
				gambaLogs::data_log("Packing Total by Grade SQL: $sql", 'camp_calc.log');
				//if($debug == 1) { echo "<p>$sql</p>"; }
				$insert = new PackingTotals;
					$insert->term = $term;
					$insert->part = $part;
					$insert->packing_id = $packing_id;
					$insert->camp = $camp;
					$insert->grade = $grade;
					$insert->theme = 0;
					$insert->total = $raw_total;
					$insert->converted_total = $converted_total;
					$insert->location_totals = json_encode($total['locations']);
					$insert->created = $date;
					$insert->save();

// 				}
			}
			if($qtshort == 1) {
				gambaInventory::quantity_short();
			}
		}

		/**
		 * Set in Editor, Used in Grouping Packing List,
		 * @return multitype:string
		 */
		public static function packing_types() {
			$array = array("galileo" => "Galileo Learning", "campg" => "Camp Galileo", "gsq" => "Galileo Summer Quest");
			return $array;
		}

		/**
		 * Set in Editor, Separate Supplies in Calculations
		 * @return multitype:string
		 */
		public static function packing_separate() {
			$array = array("false" => "No Standard/Non-Standard", "standard" => "Standard", "nonstandard" => "Non-Standard");
			return $array;
		}

		/**
		 * Navigate between Packing Lists Home Page by Term
		 * @param unknown $term
		 */
		public static function packing_term_dropdown($term) {
			$url = url('/');
			$terms = gambaTerm::terms();
			$current_term = gambaTerm::year_by_status('C');
			if($term == "") { $term = $current_term; }
			$content = <<<EOT
			<dl class="sub-nav">
				<dt>Select Term:</dt>
EOT;
			foreach($terms as $key => $value) {
				$active = ""; if($term == $key) { $active = ' class="active"'; }
				$content .= <<<EOT
				<dd{$active}><a href="{$url}/packing?term={$key}" title="{$value['year_status']}">{$key}</a></dd>
EOT;
			}
			$content .= <<<EOT
			</dl>
EOT;
			return $content;
		}

		/**
		 * Navigate by term within each Packing List
		 * @param unknown $camp
		 * @param unknown $term
		 */
		public static function packinglist_term_dropdown($camp, $term) {
			$url = url('/');
			$terms = gambaTerm::terms();
			$current_term = gambaTerm::year_by_status('C');
			if($term == "") { $term = $current_term; }
			$content = <<<EOT
			<button href="#" data-dropdown="drop1" aria-controls="drop1" aria-expanded="false" class="button dropdown">Select Term ({$term})</button><br />
			<ul id="drop1" data-dropdown-content class="f-dropdown" aria-hidden="true">
EOT;
			foreach($terms as $key => $value) {
				$content .= <<<EOT
				<li><a href="{$url}/settings/themes?camp={$camp}&term={$key}" title="{$value['year_status']}">{$key}</a></li>
EOT;
			}
			$content .= <<<EOT
			</ul>
EOT;
			return $content;
		}

		/**
		 * Add new Packing List
		 * @param unknown $array
		 * @return number
		 */
		public static function packing_add($array) {
			$list = htmlspecialchars($array['list']);
			$camp = $array['camp'];
			$alt = htmlspecialchars($array['alt']);
			$list_values = json_encode($array['list_values']);
			$return['add_id'] = PackingLists::insertGetId(['list' => $list, 'camp' => $camp, 'alt' => $alt, 'list_values' => $list_values]);
			$return['added'] = 1;
			return $return;
		}

		/**
		 * Update Packing List
		 * @param unknown $array
		 * @return number
		 */
		public static function packing_update($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$id = $array['id'];
			$list = htmlspecialchars($array['list']);
			$camp = $array['camp'];
			$alt = htmlspecialchars($array['alt']);
			$list_values = json_encode($array['list_values']);

			$update = PackingLists::find($id);
				$update->list = $list;
				$update->camp = $camp;
				$update->alt = $alt;
				$update->list_values = $list_values;
				$update->save();

			$return['updated'] = 1;
			return $return;
		}

		/**
		 * Packing List Supplies Array
		 * @param unknown $list
		 * @param unknown $term
		 * @param unknown $theme
		 * @return Ambigous <unknown, string>
		 */
		public static function packing_supplies_list($list, $term, $theme, $master_supply = 0) {
			$packing_lists = self::packing_lists();
			$packing_info = $packing_lists['packinglists'][$list];
			$camp_values = $packing_info['camp_values'];
			$camp = $packing_info['camp'];
			// Added to accomodate shared grades and camps
			if($camp_values['grade_select_camps'] != "") {
				$shared_camp = $camp_values['grade_select_camps'];
			} else {
				$shared_camp = $camp;
			}
			$grades = gambaGrades::grade_list();
			$themes = gambaThemes::quick_themes_by_camp($shared_camp, $term);
			$list_values= $packing_info['list_values'];
			$basic_calc_status = gambaCalc::basic_calc_status();
			$query = Supplies::select(\DB::raw('DISTINCT grade_id'))->where('packing_id', $list)->where('term', $term)->orderBy('grade_id')->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$grade_id = $row['grade_id'];
					$grade_level = $grades[$shared_camp]['grades'][$grade_id]['level'];
					$array[$grade_id]['grade'] = $grade_level;
					$query2 = Supplies::select(\DB::raw('DISTINCT gmb_supplies.part, gmb_parts.description, gmb_parts.suom, gmb_parts.fbuom, gmb_parts.conversion, gmb_parts.adminnotes'))->leftjoin('parts', 'parts.number', '=', 'supplies.part')->where('supplies.grade_id', $grade_id)->where('supplies.packing_id', $list)->where('supplies.term', $term)->orderBy('parts.description')->get();
					if($query2->count() > 0) {
						foreach($query2 as $key2 => $row2) {
							$part = $row2['part'];
							$description = $row2['description'];
							$conversion = $row2['conversion'];
							$suom = $row2['suom'];
							$fbuom = $row2['fbuom'];
							$adminnotes = $row2['adminnotes'];
							if($conversion > 0 && covert_calc == 1) {
								$uom = $fbuom; $converted = 1;
							} else {
								if($conversion > 0) { $flagged = 1; }
								$uom = $suom; $converted = 0;
							}
							$query3 = Supplies::select('supplies.id', 'supplies.theme_id', 'supplies.activity_id', 'activities.activity_name', 'supplies.itemtype', 'supplies.request_quantities', 'supplies.packing_quantities', 'supplies.lowest', 'supplies.notes', 'supplies.exclude', 'supplies.total_amount', 'supplies.packing_total')->leftjoin('activities', 'activities.id', '=', 'supplies.activity_id')->where('supplies.part', $part)->where('supplies.grade_id', $grade_id)->where('supplies.packing_id', $list)->where('supplies.term', $term);
							if($theme != "") {
								$query3 = $query3->where('supplies.theme_id', $theme);
							}
							$query3 = $query3->orderBy('supplies.part')->get();
							if($query3->count() > 0) {
								foreach($query3 as $row3) {
									$id = $row3['id'];
									$array[$grade_id]['parts'][$part]['supplies'][$id]['description'] = $description;
									$array[$grade_id]['parts'][$part]['supplies'][$id]['uom'] = $uom;
									$array[$grade_id]['parts'][$part]['supplies'][$id]['adminnotes'] = $adminnotes;
									$array[$grade_id]['parts'][$part]['supplies'][$id]['theme_id'] = $theme_id = $row3['theme_id'];
									$array[$grade_id]['parts'][$part]['supplies'][$id]['theme_name'] = $themes[$theme_id];
									$array[$grade_id]['parts'][$part]['supplies'][$id]['activity_id'] = $row3['activity_id'];
									$array[$grade_id]['parts'][$part]['supplies'][$id]['activity_name'] = $row3['activity_name'];
									$array[$grade_id]['parts'][$part]['supplies'][$id]['itemtype'] = $row3['itemtype'];
									$array[$grade_id]['parts'][$part]['supplies'][$id]['request_quantities'] = json_decode($row3->request_quantities, true);
									$array[$grade_id]['parts'][$part]['supplies'][$id]['packing_quantities'] = json_decode($row3->packing_quantities, true);
									$array[$grade_id]['parts'][$part]['supplies'][$id]['request_quantities']['grade_level'] = $grade_level;
									$total_amount = $row3['total_amount'];
									if($basic_calc_status == 1 && $master_supply == 1) {
										$total_amount = $row3['packing_total'];
									} else {
										$total_amount = $row3['total_amount'];
									}
									$array[$grade_id]['parts'][$part]['supplies'][$id]['total_amount'] = $total_amount;
									$array[$grade_id]['parts'][$part]['supplies'][$id]['lowest'] = $row3['lowest'];
									$array[$grade_id]['parts'][$part]['supplies'][$id]['notes'] = $row3['notes'];
									$array[$grade_id]['parts'][$part]['supplies'][$id]['exclude'] = $row3['exclude'];
									$array[$grade_id]['parts'][$part]['supplies'][$id]['converted'] = $converted;
									$array[$grade_id]['parts'][$part]['supplies'][$id]['flagged'] = $flagged;
									$array[$grade_id]['parts'][$part]['supplies'][$id]['conversion'] = $conversion;
									$array[$grade_id]['parts'][$part]['supplies'][$id]['suom'] = $suom;
									$array[$grade_id]['parts'][$part]['supplies'][$id]['fbuom'] = $fbuom;
									if($term == 2014) {
										$array[$grade_id]['parts'][$part]['supplies'][$id]['old_gamba_packing_total'] = $old_gamba_packing_total = self::old_gamba_packing_total($id);
										if($total_amount == $old_gamba_packing_total) { $unequal = "false"; } else { $unequal = "true"; }
										$array[$grade_id]['parts'][$part]['supplies'][$id]['unequal'] = $unequal;
									}
								}
							}
						}
					}
				}
			}
			return $array;
		}

		/**
		 * Gamba 1.0 Packing Totals
		 * @param unknown $id
		 * @return number
		 */
		private static function old_gamba_packing_total($id) {
			$row = Supplies::select('total_converted')->where('id', $id)->first();
			$value = ceil($row['total_converted']);
			return $value;
		}

		/**
		 * Packing List Edit Page
		 */
		public static function view_packing_lists() {
			$url = url('/');
			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$packing_list = self::packing_lists();
			$packing_types = self::packing_types();
			$packing_separate = self::packing_separate();
			$theme_types = gambaThemes::theme_types();
			$camps_with_locations = gambaLocations::camps_with_locations();
			$camps = gambaCampCategories::camps_list();
			$content_array['page_title'] .= "Packing Lists";
			$content_array['content'] .= gambaDirections::getDirections("packing_list_edit");

			$content_array['content'] .= <<<EOT

		<table>
			<thead>
				<tr>
					<th><a data-toggle="modal" href="{$url}/settings/packing_list_add?action=packing_list_add" class="button small radius success">Add</a></th>
					<th>ID</th>
					<th>Name/Alt Name</th>
					<th>Active</th>
					<th>Camp Material Categories</th>
					<th>Locations for<br />Packing</th>
					<th>Menu</th>
					<th>Standard/<br />Non-Standard</th>
					<th>Theme Type</th>
					<th>Grade Column</th>
					<th>Theme Column</th>
					<th>Activity Column</th>
					<th>Day Column</th>
					<th>Highest Amount</th>
					<th>Sales Order Packed By</th>
				</tr>
			</thead>
			<tbody>
EOT;
			foreach($packing_list['packinglists'] as $key => $values) {
				$camp = $values['camp'];
				$active = $values['active'];
				$separate = $values['list_values']['separate'];
				$theme_type = $values['list_values']['theme_type'];
				$menu = $values['list_values']['menu'];
				$packing_list_locations = $values['list_values']['camp_locations'];

				$updated = ""; if($return['updated'] == $key || $return['add_id'] == $key) { $updated .= ' class="success"'; }
				if($values['list_values']['active'] == "true") { $active = '<span style="font-weight:bold;color:green;">Yes</span>'; } else { $active = "No"; }
				$content_array['content'] .= <<<EOT
				<tr{$updated}>
					<td><a data-toggle="modal" href="{$url}/settings/packing_list_edit?action=packing_list_edit&id={$key}" class="button small radius">Edit</a></td>
					<td>{$key}</td>
					<td><strong>{$values['list']}</strong> / {$values['alt']}</td>
					<td class="center">{$active}</td>
					<td>{$camps[$camp]['name']}</td>
					<td>
EOT;
				foreach($packing_list_locations as $id => $location) {
					$content_array['content'] .= <<<EOT
						{$camps_with_locations[$location]},
EOT;
				}
				if($values['list_values']['col_grade'] == "true") { $col_grade = '<span style="font-weight:bold;color:green;">Yes</span>'; } else { $col_grade = "No"; }
				if($values['list_values']['col_theme'] == "true") { $col_theme = '<span style="font-weight:bold;color:green;">Yes</span>'; } else { $col_theme = "No"; }
				if($values['list_values']['col_activity'] == "true") { $col_activity = '<span style="font-weight:bold;color:green;">Yes</span>'; } else { $col_activity = "No"; }
				if($values['list_values']['col_day'] == "true") { $col_day =  '<span style="font-weight:bold;color:green;">Yes</span>'; } else { $col_day = "No"; }
				if($values['list_values']['highest'] == "true") { $highest =  '<span style="font-weight:bold;color:green;">Yes</span>'; } else { $highest = "No"; }
				$sales_pack_by = ucfirst($values['list_values']['sales_pack_by']);
				$content_array['content'] .= <<<EOT
					</td>
					<td>{$packing_types[$menu]}</td>
					<td>{$packing_separate[$separate]}</td>
					<td>{$theme_types[$camp][$theme_type]}</td>
					<td class="center">{$col_grade}</td>
					<td class="center">{$col_theme} ({$values['list_values']['theme_or_topic']})</td>
					<td class="center">{$col_activity}</td>
					<td class="center">{$col_day}</td>
					<td class="center">{$highest}</td>
					<td>{$sales_pack_by}</td>
				</tr>
EOT;
			}
			$content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
			return $content_array;
		}

		public static function form_data_all_packing_list($array, $return) {
			$url = url('/');
			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$packing_types = self::packing_types();
			$packing_separate = self::packing_separate();
			$theme_types = gambaThemes::theme_types();
			$packing_list = self::packing_lists();
			$camps_with_locations = gambaLocations::camps_with_locations();
			$camps = gambaCampCategories::camps_list();
			if($array['action'] == "packing_list_edit") {
				$row = PackingLists::select('id', 'list', 'camp', 'alt', 'list_values', 'hide')->where('id', $array['id'])->first();
					$id = $row['id'];
					$list = $row['list'];
					$camp = $row['camp'];
					$alt = $row['alt'];
					$list_values = json_decode($row->list_values, true);
					$camp_values = $camps[$camp]['camp_values'];
					$hide = $row['hide'];
				$content_array['page_title'] = "Update Packing List for $list";
				$form_action = "update_packing";
				$form_button = "Save Changes";
			}
			if($array['action'] == "packing_list_add") {
				$content_array['page_title'] = "Add Packing List";
				$form_action = "add_packing";
				$form_button = "Add Packing List";
			}
			$content_array['content'] .= <<<EOT
				<form method="post" name="packinglist" id="form-packinglist" class="form-horizontal" action="{$url}/settings/{$form_action}">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT

					<div class="row">
						<div class="small-12 medium-3 large-3 columns">
							<label for="list">Packing List Name</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<input type="text" name="list" id="list" value="{$list}" required />
						</div>
					</div>
					<div class="row">
						<div class="small-12 medium-3 large-3 columns">
							<label for="alt">Alternative Name</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<input type="text" name="alt" id="alt" value="{$alt}" />
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns">
							<label for="camp">Camp Material Categories</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<select name="camp" id="camp">
EOT;
			foreach($camps as $id => $camp_val) {
				$camp_selected = ""; if($camp == $id) { $camp_selected .= " selected"; }
				$content_array['content'] .= <<<EOT
								<option value="{$id}"{$camp_selected}>{$camp_val['name']}</option>
EOT;

			}
			$content_array['content'] .= <<<EOT
							</select>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns">
							<label for="menu">Packing List Menu</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<select name="list_values[menu]" id="menu">
EOT;
			foreach($packing_types as $id => $name) {
				$menu_selected = ""; if($list_values['menu'] == $id) { $menu_selected .= " selected"; }
				$content_array['content'] .= <<<EOT
								<option value="{$id}"{$menu_selected}>{$name}</option>
EOT;
			}
			$content_array['content'] .= <<<EOT
							</select>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns">
							<label for="menu">Camp Locations for Packing</label>
						</div>
						<div class="small-12 medium-9 large-9 columns switch small round">
							<ul class="small-block-grid-3">
EOT;
			foreach($camps_with_locations as $id => $name) {
				$location_checked = ""; if(in_array($id, $list_values['camp_locations'])) { $location_checked .= " checked"; }
				$content_array['content'] .= <<<EOT
							<li>
								<input type="checkbox" name="list_values[camp_locations][]" value="{$id}" id="location_checked_{$id}"{$location_checked} />
								<label for="location_checked_{$id}">Enabled</label>
								&nbsp;{$name}
							</li>
EOT;
			}
			$content_array['content'] .= <<<EOT
							</ul>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-2 large-2 columns">
							<label for="theme_type">Theme Type</label>
						</div>
						<div class="small-12 medium-3 large-3 columns">
							<select name="list_values[theme_type]" id="theme_type">
								<option value="">-----------------</option>
EOT;
			foreach($theme_types[$camp] as $id => $name) {
				$theme_type_selected = ""; if($list_values['theme_type'] == $id) { $theme_type_selected .= " selected"; }
				$content_array['content'] .= <<<EOT
								<option value="{$id}"{$theme_type_selected}>{$name}</option>
EOT;
			}
			$content_array['content'] .= <<<EOT
							</select>
						</div>
						<div class="small-12 medium-2 large-2 columns">
							<label for="menu">Separate</label>
						</div>
						<div class="small-12 medium-5 large-5 columns">
							<select name="list_values[separate]" id="menu">
EOT;
			foreach($packing_separate as $id => $name) {
				$separate_selected = ""; if($list_values['separate'] == $id) { $separate_selected .= " selected"; }
				$content_array['content'] .= <<<EOT
								<option value="{$id}"{$separate_selected}>{$name}</option>
EOT;
			}
			if($list_values['active'] == "true") { $active_true = " checked"; }
			if($list_values['active'] == "false") { $active_false = " checked"; }
			if($list_values['sub_lists'] == "true") { $sub_lists_true = " checked"; }
			if($list_values['sub_lists'] == "false") { $sub_lists_false = " checked"; }
			if($list_values['col_grade'] == "true") { $col_grade_true = " checked"; }
			if($list_values['col_grade'] == "false") { $col_grade_false = " checked"; }
			$content_array['content'] .= <<<EOT
							</select>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="list_values[active]" value="true"{$active_true} id="active_true" />
							<label for="active_true" class="radio-true">Yes</label>
							<input type="radio" name="list_values[active]" value="false"{$active_false} id="active_false" />
							<label for="active_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Active</label>
							<span class="help-block">Enable or disable access to this packing list.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="list_values[sub_lists]" value="true"{$sub_lists_true} id="sub_lists_true" />
							<label for="sub_lists_true" class="radio-true">Yes</label>
							<input type="radio" name="list_values[sub_lists]" value="false"{$sub_lists_false} id="sub_lists_false" />
							<label for="sub_lists_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Sub Packing Lists</label>
							<span class="help-block">Does this packing list have sub lists by individual themes/majors.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="list_values[col_grade]" value="true"{$col_grade_true} id="col_grade_true" />
							<label for="col_grade_true" class="radio-true">Yes</label>
							<input type="radio" name="list_values[col_grade]" value="false"{$col_grade_false} id="col_grade_false" />
							<label for="col_grade_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Grade</label>
							<span class="help-block">Show this column in packing list.</span>
						</div>
					</div>
EOT;
			if($list_values['theme_or_topic'] == "Theme") { $theme_selected = " selected"; }
			if($list_values['theme_or_topic'] == "Topic") { $topic_selected = " selected"; }
			if($list_values['theme_or_topic'] == "Major") { $major_selected = " selected"; }
			if($list_values['theme_or_topic'] == "Minor") { $minor_selected = " selected"; }
			if($list_values['col_theme'] == "true") { $col_theme_true = " checked"; }
			if($list_values['col_theme'] == "false") { $col_theme_false = " checked"; }
			if($list_values['col_activity'] == "true") { $col_activity_true = " checked"; }
			if($list_values['col_activity'] == "false") { $col_activity_false = " checked"; }
			if($list_values['col_day'] == "true") { $col_day_true = " checked"; }
			if($list_values['col_day'] == "false") { $col_day_false = " checked"; }
			if($list_values['highest'] == "true") { $highest_true = " checked"; }
			if($list_values['highest'] == "false") { $highest_false = " checked"; }
			$content_array['content'] .= <<<EOT
					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="list_values[col_theme]" value="true"{$col_theme_true} id="col_theme_true" />
							<label for="col_theme_true" class="radio-true">Yes</label>
							<input type="radio" name="list_values[col_theme]" value="false"{$col_theme_false} id="col_theme_false" />
							<label for="col_theme_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-4 large-4 end columns">
							<select name="list_values[theme_or_topic]" style="font-weight: bold;">
								<option value="Theme"{$theme_selected}>Theme</option>
								<option value="Topic"{$topic_selected}>Topic</option>
								<option value="Major"{$major_selected}>Major</option>
								<option value="Minor"{$minor_selected}>Minor</option>
							</select>
							<span class="help-block">Show this column in packing list.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="list_values[col_activity]" value="true"{$col_activity_true} id="col_activity_true" />
							<label for="col_activity_true" class="radio-true">Yes</label>
							<input type="radio" name="list_values[col_activity]" value="false"{$col_activity_false} id="col_activity_false" />
							<label for="col_activity_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-4 large-4 end columns">
							<label>Activity</label>
							<span class="help-block">Show this column in packing list.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="list_values[col_day]" value="true"{$col_day_true} id="col_day_true" />
							<label for="col_day_true" class="radio-true">Yes</label>
							<input type="radio" name="list_values[col_day]" value="false"{$col_day_false} id="col_day_false" />
							<label for="col_day_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-4 large-4 end columns">
							<label>Day</label>
							<span class="help-block">Show this column in packing list.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="list_values[highest]" value="true"{$highest_true} id="highest_true" />
							<label for="highest_true" class="radio-true">Yes</label>
							<input type="radio" name="list_values[highest]" value="false"{$highest_false} id="highest_false" />
							<label for="highest_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Highest Amount</label>
							<span class="help-block">Only Show Highest Amount in Packing List for Non-Conumable Items within a grade and theme.</span>
						</div>
					</div>
EOT;
			if($list_values['sales_pack_by'] == "theme") { $sales_pack_by_theme = " selected"; }
			if($list_values['sales_pack_by'] == "grade") { $sales_pack_by_grade = " selected"; }
// 			if($list_values['basic_supplies'] == "true") {  " checked"; }
			if($list_values['sales_pack_by'] == "theme-grade") { $sales_pack_by_both = " selected"; }
			if($list_values['basic_supplies'] == "true") { $basic_supplies_true = " checked"; }
			if($list_values['basic_supplies'] == "false") { $basic_supplies_false = " checked"; }
			$content_array['content'] .= <<<EOT
					<div class="row">
						<div class="small-12 medium-3 large-3 columns">
							<label>Sales Orders Pack By</label>
						</div>
						<div class="small-12 medium-6 large-6 end columns">
							<select name="list_values[sales_pack_by]">
								<option value="theme"{$sales_pack_by_theme}>Theme</option>
								<option value="grade"{$sales_pack_by_grade}>Grade</option>
								<option value="theme-grade"{$sales_pack_by_both}>Theme > Grade</option>
							</select>
							<span class="help-block">Designate how you want locations to be grouped.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="list_values[basic_supplies]" value="true"{$basic_supplies_true} id="basic_supplies_true" />
							<label for="basic_supplies_true" class="radio-true">Yes</label>
							<input type="radio" name="list_values[basic_supplies]" value="false"{$basic_supplies_false} id="basic_supplies_false" />
							<label for="basic_supplies_false" class="radio-false">No</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<label>Basic Supplies</label>
							<span class="help-block">To designate a packing list for Basic Supplies select Yes and then choose a list from the dropdown that will appear.</span>
						</div>
					</div>

 					<script type="text/javascript">
 						$(document).ready(function(){
 							if($('#basic_supplies_true').prop('checked')){
						        $('.basic_supplies_list').show();
							} else {
 								$('.basic_supplies_list').hide();
							}
							$('#form-packinglist').change(function() {
								$('.basic_supplies_list').hide();
								if($('#basic_supplies_true').prop('checked')){
						        	$('.basic_supplies_list').show();
 							    }
 							});
 						});
					</script>

					<div class="row basic_supplies_list">
						<div class="small-12 medium-3 large-3 columns">
							<label>Basic Packing List</label>
						</div>
						<div class="small-12 medium-9 large-9 columns">
							<select name="list_values[basic_supplies_list]">
								<option value="">-------------------</option>
EOT;
			foreach($packing_list['packinglists'] as $packing_id => $packing_list_values) {
				$basic_supplies_list = ""; if($packing_id == $list_values['basic_supplies_list']) { $basic_supplies_list .= " selected"; }
				$content_array['content'] .= <<<EOT
								<option value="{$packing_id}"{$basic_supplies_list}>{$packing_list_values['alt']}</option>
EOT;
			}
			$content_array['content'] .= <<<EOT
							</select>
							<span class="help-block">Select a packing list to subtract from.</span>
						</div>
					</div>

					<p><button type="submit" class="button small">{$form_button}</button></p>
					<input type="hidden" name="action" value="{$form_action}" />
					<input type="hidden" name="id" value="{$array['id']}" />
				</form>
EOT;
			return $content_array;
// 			gambaDebug::preformatted_arrays($packing_list, "packing_list", "Packing Lists");
			$basic_packing_supplies_lists = self::basic_packing_supplies_lists();
// 			gambaDebug::preformatted_arrays($basic_packing_supplies_lists, "basic_packing_list", "Basic Packing Lists");
// 			echo(count($packing_list['camps'][2]['lists']));
		}

		/**
		 * Packing List Supplies View
		 * @param unknown $array
		 */
		public static function packing_supplies($array) {
			$url = url('/');
			$user_id = Session::get('uid');
			$list = $array['list'];
			$term = $array['term'];
			$sub_list = $array['majors'];
			$theme = $array['theme'];
			$packing_lists = self::packing_lists();
			$locations = gambaLocations::locations_by_camp();
			if($list == "") { $list = 1; }
			$packing_info = $packing_lists['packinglists'][$list];
			$list_values = $packing_info['list_values'];
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$list_name = $packing_lists['packinglists'][$list]['alt'];
			$camp = $packing_lists['packinglists'][$list]['camp'];
			$qts = gambaQuantityTypes::quantity_types_by_camp($camp, $term);
			$theme_list = gambaThemes::quick_themes_by_camp($camp, $term);
			if($theme != "") {
				$themes = gambaThemes::theme_by_id($theme);
				$theme_name = ": ". $themes['name'];
			}
			$content_array['page_title'] = "Packing List $term: $list_name $theme_name";
			$packing_supplies = self::packing_supplies_list($list, $term, $theme, 1);
			if(count($packing_lists['camps'][$camp]['lists']) > 1) {
				$content_array['content'] = <<<EOT
				<ul class="pagination pagination-lg">
EOT;
				foreach($packing_lists['camps'][$camp]['lists'] as $list_id => $values) {
					if($packing_lists['packinglists'][$list_id]['list_values']['active'] == "true") {
						$page_active = ""; if($list_id == $list) { $page_active = ' class="active"'; }
						$content_array['content'] .= <<<EOT
						<li{$page_active}><a href="{$url}/packing/lists/{$term}/{$list_id}">{$packing_lists['packinglists'][$list_id]['alt']}</a></li>
EOT;
					}
				}
				$content_array['content'] .= <<<EOT
				</ul>
EOT;
			}
			if($theme > 0 || $theme != "") {
				$content_array['content'] .= <<<EOT

		  <button href="#" data-dropdown="drop1" aria-controls="drop1" aria-expanded="false" class="button dropdown">
		    Select Major ({$themes['name']})
		  </button><br />
		  <ul id="drop1" data-dropdown-content class="f-dropdown" aria-hidden="true">
EOT;
				foreach($theme_list as $theme_list_id => $theme_list_name) {
					$content_array['content'] .= <<<EOT
			<li><a href="{$url}/packing/lists/{$term}/{$list}?theme={$theme_list_id}">$theme_list_name</a></li>
EOT;
				}
				$content_array['content'] .= <<<EOT
		  </ul>
EOT;
			}
// 			$content_array['content'] .= gambaDirections::getDirections("packing_list".$list);
// 			if(covert_calc == 0) {
				$content_array['content'] .= <<<EOT
				<p class="alert-box info radius">Conversion of flagged materials is occuring in the <a href="{$url}/purchase/pos">Purchase Orders</a>.</p>
EOT;
// 			}
			if($list_values['highest'] == "true") {
				$content_array['content'] .= <<<EOT
				<script type="text/javascript">
				$(document).ready(function(){
					$('tr.lowest').hide();
					$('#showlowest').on('click', function(){
				        $('tr.lowest').toggle();
				    });
				});
				</script>
				<p><button id="showlowest">Show/Hide Lowest Amounts</button></p>
EOT;
			}
				$content_array['content'] .= <<<EOT
		<script type="text/javascript">
			$(function(){
				$("[data-toggle=popover]").popover();
			});
			// call the tablesorter plugin
			$(function(){
		        $("table").tablesorter({
		        	widgets: [ 'stickyHeaders' ],
		        	widgetOptions: { stickyHeaders_offset : 50 },
				});
			});
		</script>
				<table class="table table-bordered table-hover table-condensed table-small table-responsive tablesorter">
					<thead>
						<tr>
EOT;
				if($user_id == 1) {
					$content_array['content'] .= <<<EOT
					<th>Supply<br />ID</th>
EOT;
				}
				$content_array['content'] .= <<<EOT
							<th>#</th>
EOT;
				if($list_values['theme_type'] != "") {
					$content_array['content'] .= <<<EOT
					<th></th>
EOT;
				}
				if($list_values['col_grade'] == "true") {
					$content_array['content'] .= <<<EOT
					<th>Grade</th>
EOT;
				}
				if($list_values['col_theme'] == "true") {
					$content_array['content'] .= <<<EOT
					<th>{$list_values['theme_or_topic']}</th>
EOT;
				}
				if($list_values['col_activity'] == "true") {
					$content_array['content'] .= <<<EOT
							<th>Activity</th>
EOT;
				}
				if($list_values['col_day'] == "true") {
					$content_array['content'] .= <<<EOT
							<th>Day</th>
EOT;
				}
				$ncx3 = ""; if($camp == 1) { $ncx3 = "/NCx3"; }
				$content_array['content'] .= <<<EOT
							<th>C/NC{$ncx3}</th>
							<th>Multiplier</th>
							<th>Part #</th>
							<th>Material Name</th>
							<th>UoMs</th>
							<th>Notes</th>
EOT;
			foreach($quantity_types[$camp]['quantity_types'] as $key => $values) {
				$content_array['content'] .= <<<EOT
							<th>{$values['name']}</th>
EOT;
			}
			if(is_array($qts['static'])) {
				foreach($qts['static'] as $key => $value) {
					if($value['qt_options']['terms'][$term] == "true") {
							$content_array['content'] .= '<th title="Quantity ID: '.$key.'" width="50" class="center">'.$value['name'].'</th>';
					}
				}
			}
			if(is_array($qts['dropdown'])) {
				foreach($qts['dropdown'] as $key => $value) {
					if($value['qt_options']['terms'][$term] == "true") {
						$content_array['content'] .= '<th title="Quantity ID: '.$key.'" width="50" class="center">'.$value['name'].'</th>';
					}
				}
			}
			$content_array['content'] .= <<<EOT
							<th>KQD Quantity per kid</th>
							<th>Total Quantity</th>
EOT;
			/*
			 * Disabled - Remove Later - 10/17/16
			if(total_compare == 1) {
				$content_array['content'] .= <<<EOT
							<th>Gamba 1.0 Total Quantity</th>
EOT;
			} */

			foreach($packing_lists['packinglists'][$list]['list_values']['camp_locations'] as $camp_id) {
				foreach($locations['locations'][$camp_id] as $key => $values) {
					$term_data = $values['terms'][$term];
					if($term_data['active'] == "Yes") {
						$content_array['content'] .= <<<EOT
							<th title="{$values['name']}: {$key}">{$values['abbr']}</th>
EOT;
						if($term_data['dstar'] == 1 && $packing_lists['packinglists'][$list]['camp_values']['dli_location'] == "true") {
						$content_array['content'] .= <<<EOT
							<th title="{$values['name']}">{$values['abbr']}2</th>
EOT;
						}
					}
				}
			}
			$content_array['content'] .= <<<EOT
						</tr>
					</thead>
					<tbody>
EOT;
			if($_REQUEST['hide_list'] != "true") {
			$i = 1;
			foreach($packing_supplies as $grade_id => $parts) {
				foreach($parts['parts'] as $part_num => $supplies) {
					foreach($supplies['supplies'] as $id => $supply_info) {
						if($theme == "" || ($theme != "" && $theme == $supply_info['theme_id'])) {
							if($supply_info['exclude'] != 1) {
								$content_array['content'] .= '<tr class="';
								if($supply_info['lowest'] == 1 && $list_values['highest'] == "true") {
									$content_array['content'] .= 'lowest ';
								}
								/*
								 * Disabled - Remove Later - 10/17/16
								if($supply_info['unequal'] == "true" && total_compare == 1) {
									$content_array['content'] .= "unequal ";
								} */
								if($supply_info['exclude'] == 1) {
									$content_array['content'] .= " row-exclude";
								}
								$content_array['content'] .= '">';
								if($user_id == 1) {
									$content_array['content'] .= <<<EOT
									<td>{$id}</td>
EOT;
								}
								$content_array['content'] .= <<<EOT
							<td>{$i}</td>
EOT;
								$i++;
								if($list_values['theme_type'] != "") {
									$content_array['content'] .= <<<EOT
							<td>{$list_values['theme_type']}</td>
EOT;
								}
								if($list_values['col_grade'] == "true") {
									$content_array['content'] .= <<<EOT
							<td>{$supply_info['request_quantities']['grade_level']}</td>
EOT;
								}

								if($list_values['col_theme'] == "true") {
									$content_array['content'] .= <<<EOT
							<td>{$supply_info['theme_name']}</td>
EOT;
								}

								if($list_values['col_activity'] == "true") {
									$content_array['content'] .= <<<EOT
							<td>{$supply_info['activity_name']}</td>
EOT;
								}

								if($list_values['col_day'] == "true") {
									$content_array['content'] .= <<<EOT
							<td>{$supply_info['request_quantities']['day']}</td>
EOT;
								}
								$content_array['content'] .= <<<EOT
							<td class="center">{$supply_info['itemtype']}</td>
							<td class="center">
EOT;
								if($supply_info['itemtype'] == "NCx3") { $content_array['content'] .= 3; }
								else { $content_array['content'] .= 1; }
								$content_array['content'] .= <<<EOT
							</td>
							<td title="Supply ID: {$id} - Lowest: {$supply_info['lowest']}">{$part_num}</td>
							<td title="Supply ID: {$id}">{$supply_info['description']}
EOT;
								if($supply_info['converted'] == 1 || $supply_info['flagged'] == 1) { $content_array['content'] .= " [F]"; }
								if($supply_info['exclude'] == 1) { $content_array['content'] .= " [EXCLUDED]"; }
								$content_array['content'] .= <<<EOT
								</td>
							<td title="{$supply_info['conversion']} {$supply_info['suom']} per {$supply_info['fbuom']}">{$supply_info['uom']}</td>
							<td>
EOT;
							if($supply_info['notes'] != "" || $supply_info['adminnotes'] != "") {
								$notes = $supply_info['notes'];
								if($supply_info['notes'] != "" && $supply_info['adminnotes'] != "") { $notes .= " | "; }
								$notes .= $supply_info['adminnotes'];
								$sub_notes = substr($notes, 0, 15);
								$content_array['content'] .= <<<EOT
								<a data-dropdown="drop_{$id}" aria-controls="drop_{$id}" aria-expanded="false">{$sub_notes}...</a>
								<div id="drop_{$id}" data-dropdown-content class="f-dropdown content" aria-hidden="true" tabindex="-1">
								  {$notes}
								</div>
EOT;
							}
							$content_array['content'] .= <<<EOT
							</td>
EOT;

			if(is_array($qts['static'])) {
				foreach($qts['static'] as $key => $value) {
					if($value['qt_options']['terms'][$term] == "true") {
						if($value['old_table_identifier'] == "") {
							$identifier = $key;
						} else {
							$identifier = $value['old_table_identifier'];
						}
						$content_array['content'] .= '<td title="Quantity ID: '.$key.' ('.$identifier.')" class="center">';
						if($supply_info['request_quantities']['static'][$identifier] != "") {
							$content_array['content'] .= "<strong>".($supply_info['request_quantities']['static'][$identifier] + 0)."</strong>";
						} else {
							$content_array['content'] .= 0;
						}
						$content_array['content'] .= '</td>';
					}
				}
			}
			if(is_array($qts['dropdown'])) {
				foreach($qts['dropdown'] as $key => $value) {
					if($value['qt_options']['terms'][$term] == "true") {
						$content_array['content'] .= '<td title="Quantity ID: '.$key.': '.$value['name'].'" class="center">';
						if($key == $supply_info['request_quantities']['quantity_type_id']) {
							$content_array['content'] .= "<strong>".($supply_info['request_quantities']['quantity_val'] + 0)."</strong>";
						} else {
							$content_array['content'] .= 0;
						}
						$content_array['content'] .= '</td>';
					}
				}
			}
			$content_array['content'] .= <<<EOT
							<td class="center">{$supply_info['request_quantities']['kqd']}</td>
							<td class="center">{$supply_info['total_amount']}</td>
EOT;
			/*
			 * Disabled - Remove Later - 10/17/16
			if(total_compare == 1) {
				$content_array['content'] .= <<<EOT
				 <td class="center">{$supply_info['old_gamba_packing_total']}
EOT;
				 if($supply_info['unequal'] == "true") { $content_array['content'] .= " [NM] "; }
					$content_array['content'] .= <<<EOT
				 </td>
EOT;
			} */

			foreach($packing_lists['packinglists'][$list]['list_values']['camp_locations'] as $camp_id) {
				foreach($locations['locations'][$camp_id] as $key => $values) {
					$term_data = $values['terms'][$term];
					if($term_data['active'] == "Yes") {
						$content_array['content'] .= <<<EOT
				<td title="Location: {$values['name']} - Calc: {$supply_info['packing_quantities'][$key][0]['calc']}" class="center">{$supply_info['packing_quantities'][$key][0]['total']}</td>
EOT;
						if($term_data['dstar'] == 1 && $packing_lists['packinglists'][$list]['camp_values']['dli_location'] == "true") {
							$content_array['content'] .= <<<EOT
				<td title="Location: {$values['name']}2 - Calc: {$supply_info['packing_quantities'][$key][1]['calc']}" class="center">{$supply_info['packing_quantities'][$key][1]['total']}</td>
EOT;
						}
					}
				}
			}
			$content_array['content'] .= <<<EOT
						</tr>
EOT;
							}
						}
					}
				}
			}
			}
			$content_array['content'] .= <<<EOT
					</tbody>
				</table>
EOT;
//			ob_start();
//			var_dump($packing_supplies);
//			$content_array['content'] .= "<pre>" . ob_get_clean() . "</pre>";
			return $content_array;
// 			gambaDebug::preformatted_arrays($qts, "quantity_types", "Quantity Types", $array['debug_override']);
// 			gambaDebug::preformatted_arrays($theme_list, "theme_list", "Theme List", $array['debug_override']);
// 			gambaDebug::preformatted_arrays($packing_supplies, "supplies", "Packing Supplies", $array['debug_override']);
// 			gambaDebug::preformatted_arrays($packing_lists['packinglists'][$list], "list", "Packing List", $array['debug_override']);
// 			gambaDebug::preformatted_arrays($packing_lists['camps'][$camp], "camp_list", "Camp Packing List", $array['debug_override']);
// 			gambaDebug::preformatted_arrays($locations['locations'][$camp], "locations", "Loctions", $array['debug_override']);
		}

		/**
		 * Packing List Supplies Download
		 * @param unknown $array
		 */
		public static function packing_download($array) {
			$list = $array['list'];
			$term = $array['term'];
			$sub_list = $array['majors'];
			$theme = $array['theme'];
			$packing_lists = self::packing_lists();
			$locations = gambaLocations::locations_by_camp();
			if($list == "") { $list = 1; }
			$packing_info = $packing_lists['packinglists'][$list];
			$list_values = $packing_info['list_values'];
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$list_name = $packing_lists['packinglists'][$list]['alt'];
			$camp = $packing_lists['packinglists'][$list]['camp'];
			$qts = gambaQuantityTypes::quantity_types_by_camp($camp, $term);
			$theme_list = gambaThemes::quick_themes_by_camp($camp, $term);
			if($theme != "") {
				$themes = gambaThemes::theme_by_id($theme);
				$theme_name = ": ". $themes['name'];
			}

			$packing_supplies = self::packing_supplies_list($list, $term, $theme, 1);
			// Begin Header Row
			echo "\"\",";
			if($list_values['theme_type'] != "") { echo "\""."\","; }
			if($list_values['col_grade'] == "true") { echo "\""."Grade\","; }
			if($list_values['col_theme'] == "true") { echo "\"".$list_values['theme_or_topic']."\","; }
			if($list_values['col_activity'] == "true") { echo "\""."Activity\","; }
			if($list_values['col_day'] == "true") { echo "\""."Day\","; }
			echo "\""."C/NC\",";
			echo "\""."Multiplier\",";
			echo "\""."Part #\",";
			echo "\""."Material Name\",";
			echo "\""."UoMs\",";
			echo "\""."Notes\",";
			foreach($quantity_types[$camp]['quantity_types'] as $key => $values) {
				echo "\"".$values['name']."\",";
			}
			if(is_array($qts['static'])) {
				foreach($qts['static'] as $key => $value) {
					if($value['qt_options']['terms'][$term] == "true") {
						echo "\"".$value['name']."\",";
					}
				}
			}
			if(is_array($qts['dropdown'])) {
				foreach($qts['dropdown'] as $key => $value) {
					if($value['qt_options']['terms'][$term] == "true") {
						echo "\"".$value['name']."\",";
					}
				}
			}
			echo "\""."KQD Quantity per kid\",";
			echo "\""."Total Quantity\",";
			foreach($packing_lists['packinglists'][$list]['list_values']['camp_locations'] as $camp_id) {
				foreach($locations['locations'][$camp_id] as $key => $values) {
					$term_data = $values['terms'][$term];
					if($term_data['active'] == "Yes") {
						echo "\"".$values['abbr']."\",";
						if($term_data['dstar'] == 1 && $packing_lists['packinglists'][$list]['camp_values']['dli_location'] == "true") { echo "\"".$values['abbr']."2\","; }
					}
				}
			}
			echo "\r";
			// End Header Row
			$search_array = array("\r","\n","&quot;","'"); $replace_array = array(" "," ","”","’");

			foreach($packing_supplies as $grade_id => $parts) {
				foreach($parts['parts'] as $part_num => $supplies) {
					foreach($supplies['supplies'] as $id => $supply_info) {
						if($theme == "" || ($theme != "" && $theme == $supply_info['theme_id'])) {
							if($supply_info['lowest'] == 0) { echo "\"\","; } else { echo "\"L\","; }
							if($list_values['theme_type'] != "") { echo "\"".$list_values['theme_type']."\","; }
							if($list_values['col_grade'] == "true") { echo "\"".$supply_info['request_quantities']['grade_level']."\","; }
							if($list_values['col_theme'] == "true") { echo "\"".$supply_info['theme_name']."\","; }
							if($list_values['col_activity'] == "true") { echo "\"".$supply_info['activity_name']."\","; }
							if($list_values['col_day'] == "true") { echo "\"".$supply_info['request_quantities']['day']."\","; }
							echo "\"".$supply_info['itemtype']."\",";
							if($supply_info['itemtype'] == "NCx3") { echo "\"3"; } else { echo "\"1"; } echo "\",";
							echo "\""."$part_num\",";
							echo "\"".str_replace($search_array, $replace_array, $supply_info['description']); if($supply_info['converted'] == 1) { echo " [F]"; } echo "\",";
							echo "\"".$supply_info['uom']."\",";
							echo "\"".str_replace($search_array, $replace_array, $supply_info['notes'])."\",";

							if(is_array($qts['static'])) {
								foreach($qts['static'] as $key => $value) {
									if($value['qt_options']['terms'][$term] == "true") {
										if($value['old_table_identifier'] == "") {
											$identifier = $key;
										} else {
											$identifier = $value['old_table_identifier'];
										}
										if($supply_info['request_quantities']['static'][$identifier] != "") {
											echo "\"".($supply_info['request_quantities']['static'][$identifier] + 0)."\",";
										} else {
											echo "\""."0\",";
										}
									}
								}
							}

							if(is_array($qts['dropdown'])) {
								foreach($qts['dropdown'] as $key => $value) {
									if($value['qt_options']['terms'][$term] == "true") {
										if($key == $supply_info['request_quantities']['quantity_type_id']) {
											echo "\"".($supply_info['request_quantities']['quantity_val'] + 0)."\",";
										} else {
											echo "\""."0\",";
										}
									}
								}
							}

							echo "\"".$supply_info['request_quantities']['kqd']."\",";
							echo "\"".$supply_info['total_amount']."\",";

							foreach($packing_lists['packinglists'][$list]['list_values']['camp_locations'] as $camp_id) {
								foreach($locations['locations'][$camp_id] as $key => $values) {
									$term_data = $values['terms'][$term];
									if($term_data['active'] == "Yes") {
										echo "\"".$supply_info['packing_quantities'][$key][0]['total']."\",";
										if($term_data['dstar'] == 1 && $packing_lists['packinglists'][$list]['camp_values']['dli_location'] == "true") {
											echo "\"".$supply_info['packing_quantities'][$key][1]['total']."\",";
										}
									}
								}
							}
							echo "\r";

						}
						/* End If - $themes */
					}
					/* End Foreach - $supplies */
				}
				/* End Foreach - $parts */
			}
			/* End Foreach - $packing_supplies */
		}

		public static function view_packing_supply_lists($array) {
			$url = url('/');
			$content_array['page_title'] = "Packing Lists";
			$packing_lists = self::packing_lists();
			$term = $array['term'];
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$themes = gambaThemes::themes_camps_all($term);
			$dropdown = self::packing_term_dropdown($term);
// 			$directions = gambaDirections::getDirections('packing');
			$content_array['content'] = <<<EOT
					{$dropdown}

					{$directions}
			<div class="row">
				<div class="small-12 medium-6 large-6 columns">
					<h3>Galileo Learning</h3>
					<ul>
EOT;
			foreach($packing_lists['packinglists'] as $key => $values) {
				if($values['list_values']['menu'] == "galileo") {
					if($values['list_values']['active'] == "true") {
						$file_name = str_replace(" ", "_", strtolower($values['alt']));
						$content_array['content'] .= <<<EOT
						<li><a href="{$url}/packing/lists/{$term}/{$key}">{$values['alt']}</a> | <a href="{$url}/download/packing_lists?term={$term}&list={$key}&file_name={$file_name}">CSV</a></li>
EOT;
					}
				}
			}
			$content_array['content'] .= <<<EOT
					</ul>
					<h3>Camp Galileo</h3>
					<ul>
EOT;
			foreach($packing_lists['packinglists'] as $key => $values) {
				if($values['list_values']['menu'] == "campg") {
					if($values['list_values']['active'] == "true") {
						$file_name = str_replace(" ", "_", strtolower($values['alt']));
						$content_array['content'] .= <<<EOT
						<li><a href="{$url}/packing/lists/{$term}/{$key}">{$values['alt']}</a> | <a href="{$url}/download/packing_lists?term={$term}&list={$key}&file_name={$file_name}">CSV</a></li>
EOT;
					}
				}
			}
			$content_array['content'] .= <<<EOT
					</ul>
				</div>
				<div class="small-12 medium-6 large-6 columns">
					<h3>Galileo Summer Quest</h3>
					<ul>
EOT;
			foreach($packing_lists['packinglists'] as $key => $values) {
				if($values['list_values']['menu'] == "gsq") {
					if($values['list_values']['sub_lists'] != "true") {
						$file_name = str_replace(" ", "_", strtolower($values['alt']));
						$content_array['content'] .= <<<EOT
						<li><a href="{$url}/packing/lists/{$term}/{$key}"{$values['alt']}</a> | <a href="{$url}/download/packing_lists?term={$term}&list={$key}&file_name={$file_name}">CSV</a></li>
EOT;
					} else {
						foreach($themes[2] as $theme_id => $theme_values) {
							$file_name = str_replace(" ", "_", strtolower($theme_values['name']));
							$content_array['content'] .= <<<EOT
						<li><a href="{$url}/packing/lists/{$term}/4?theme={$theme_id}">{$theme_values['name']}</a> | <a href="{$url}/download/packing_lists?term={$term}&list=4&theme={$theme_id}&file_name={$file_name}">CSV</a></li>
EOT;
						}
					}
				}
			}
			$content_array['content'] .= <<<EOT
					</ul>
				</div>
			</div>
EOT;
			return $content_array;
		}

	}
