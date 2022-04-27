<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\Enrollment;
	use App\Models\EnrollmentExt;
	use App\Models\EnrollSheets;
	use App\Models\OfficeData;
	use App\Models\ThemeLink;
	use App\Models\Themes;

	use App\Gamba\gambaAdmin;
	use App\Gamba\gambaCalc;
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaGrades;
	use App\Gamba\gambaLocations;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaThemes;
	use App\Gamba\gambaUsers;

	use App\Jobs\CalcCampGEnrollment;
	use App\Jobs\CalcAllGradesTotal;
	use App\Jobs\CalcCampGOfficeData;
	use App\Jobs\CalcAllPackingLists;
	use App\Jobs\CalcGSQOfficeData;
	use App\Jobs\CalcPackingTotals;

	class gambaEnroll {

		public static function enrollment_sub_nav($array) {
			$url = url('/');
			$user_id = Session::get('uid');
			$content .= <<<EOT
	<div class="row">
		<div class="small-12 medium-2 large-2 columns">
EOT;
			self::term_dropdown($_REQUEST['action'], $_REQUEST['term']);
			if($user_id == 1) {
				$nav_enroll_sheets = '<li><a href="{$url}/enrollment/sheet_locations?term='.$_REQUEST['term'].'">Enroll Sheets</a></li>';
			}
			$content .= <<<EOT
		</div>
		<div class="small-12 medium-10 large-10 columns">
			<ul class="pagination pagination-lg">
				<li><a href="$url/enrollment/{$_REQUEST['term']}/cg_campers">Camp Galileo Enrollment</a></li>
				<li><a href="$url/enrollment/{$_REQUEST['term']}/gsq_campers">Galileo Summer Quest Enrollment</a></li>
				<li><a href="$url/enrollment/{$_REQUEST['term']}/cg_ext">Camp Galileo Extended Care</a></li>
				<li><a href="$url/enrollment/{$_REQUEST['term']}/gsq_ext">Galileo Summer Quest Extended Care</a></li>
				{$nav_enroll_sheets}
			</ul>
		</div>
	</div>
EOT;
				return $content;
		}


		public static function cg_campers($term, $grade) {
			$terms = gambaTerm::terms();
			$theme_enroll_rotations = $terms[$term]['campg_enroll_rotations'];
			$pack_per_hide = $terms[$term]['campg_packper'];

			if($term == "") { $term = gambaTerm::year_by_status('C'); }

 /**
SELECT gmb_enrollment.id, gmb_enrollment.sheet_id, gmb_enrollment.term,
gmb_enrollment.camp, gmb_enrollment.grade_id, gmb_grades.level,
gmb_enrollment.location_id, gmb_locations.location, gmb_enrollment.extra_class,
gmb_enrollment.location_values, gmb_enrollment.theme_values, gmb_grades.enrollment,
gmb_locations.term_data

FROM gmb_enrollment

LEFT JOIN gmb_locations ON gmb_locations.id = gmb_enrollment.location_id
LEFT JOIN gmb_grades ON gmb_grades.id = gmb_enrollment.grade_id

WHERE gmb_enrollment.location_id != '0'
AND gmb_enrollment.term = 2017
AND gmb_enrollment.camp = 1
AND gmb_enrollment.grade_id = 1

ORDER BY gmb_grades.level, gmb_locations.location, gmb_enrollment.extra_class

 */

			$enrollment = Enrollment::select(
					'enrollment.id', 'enrollment.sheet_id', 'enrollment.term',
					'enrollment.camp', 'enrollment.grade_id', 'grades.level',
					'enrollment.location_id', 'locations.location', 'enrollment.extra_class',
					'enrollment.location_values', 'enrollment.theme_values', 'grades.enrollment',
					'locations.term_data');

			$enrollment = $enrollment->leftjoin('locations', 'locations.id', '=', 'location_id');
			$enrollment = $enrollment->leftjoin('grades', 'grades.id', '=', 'grade_id');

			$enrollment = $enrollment->where('enrollment.location_id', '!=', '0');
			$enrollment = $enrollment->where('enrollment.term', $term);
			$enrollment = $enrollment->where('enrollment.camp', 1);
			if($grade != "") {
				$enrollment = $enrollment->where('enrollment.grade_id', $grade);
			}

			$enrollment = $enrollment->orderBy('grades.level');
			$enrollment = $enrollment->orderBy('locations.location');
			$enrollment = $enrollment->orderBy('enrollment.extra_class');

			$enrollment = $enrollment->get();

			if(!empty($enrollment)) {
				foreach($enrollment as $key => $row) {
					$id = $row['id'];
					$grade_id = $row['grade_id'];
					$term = $row['term'];
					$enrollment = $row['enrollment'];
					$array['sheet_id'] = $row['sheet_id'];
					$array['camp_type'] = 1;
					if($enrollment == 1) {
						$array['enrollment'][$grade_id]['grade'] = $row['level'];
						$array['enrollment'][$grade_id]['sheet_id'] = $row['sheet_id'];
						$array['enrollment'][$grade_id]['term'] = $term;
						if($row['location_id'] > 0) {
							$array['enrollment'][$grade_id]['locations'][$id]['location'] =
								$row['location'];
							$array['enrollment'][$grade_id]['locations'][$id]['location_id'] =
								$row['location_id'];
							$array['enrollment'][$grade_id]['locations'][$id]['extra_class'] =
								$row['extra_class'];
							$array['enrollment'][$grade_id]['locations'][$id]['term_data'] =
								json_decode($row->term_data, true);
							$array['enrollment'][$grade_id]['locations'][$id]['location_values'] =
								$location_values = json_decode($row->location_values, true);
							$array['enrollment'][$grade_id]['locations'][$id]['theme_values'] =
								json_decode($row->theme_values, true);
							$array['all_grades_total'][$row['location_id']]['kids_per_class'] +=
								$location_values['kids_per_class'];
							$array['all_grades_total'][$row['location_id']]['total_camp_weeks'] +=
								$location_values['total_camp_weeks'];
							$array['all_grades_total'][$row['location_id']]['num_classrooms'] +=
								$location_values['num_classrooms'];
							$array['all_grades_total'][$row['location_id']]['class_totals'] +=
								$location_values['class_totals'];
						} else {
							$array['enrollment'][$grade_id]['totals']['location'] =
								$row['location'];
							$array['enrollment'][$grade_id]['totals']['location_id'] =
								$row['location_id'];
							$array['enrollment'][$grade_id]['locations'][$id]['term_data'] =
								json_decode($row->option_values, true);
							$array['enrollment'][$grade_id]['totals']['location_values'] =
								json_decode($row->location_values, true);
							$array['enrollment'][$grade_id]['totals']['theme_values'] =
								json_decode($row->theme_values, true);
						}
					}
				}
			}
			return $array;
		}

		public static function cg_all_grades_total($term) {
			$cg_campers = self::cg_campers($term);
			$sheet_id = $cg_campers['sheet_id'];
			foreach($cg_campers['all_grades_total'] as $location_id => $values) {
				$query = Enrollment::select('id')->where('sheet_id', $sheet_id)->where('term', $term)->where('camp', 1)->where('theme_id', 0)->where('grade_id', 10)->where('extra_class', 0)->where('dli', 0)->where('location_id', $location_id)->orderBy('id', 'desc')->first();
				if($row['id'] != "") {
					$update = Enrollment::find($row['id']);
						$update->location_values = json_encode($values);
						$update->save();
					// Clean up duplicates
					$delete = Enrollment::where('id', '!=', $row['id'])->where('sheet_id', $sheet_id)->where('term', $term)->where('camp', 1)->where('theme_id', 0)->where('grade_id', 10)->where('extra_class', 0)->where('dli', 0)->where('location_id', $location_id)->delete();
				} else {
					$insert = new Enrollment;
						$insert->sheet_id = $sheet_id;
						$insert->term = $term;
						$insert->camp = 1;
						$insert->theme_id = 0;
						$insert->grade_id = 10;
						$insert->extra_class = 0;
						$insert->dli = 0;
						$insert->location_id = $location_id;
						$insert->location_values = json_encode($values);
						$insert->save();
				}
			}
		}

		public static function cg_linked_themes($term) {
			$query = ThemeLink::select('id')->where('term', $term)->where('camp_type', 1)->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$query2 = Themes::select('id', 'name', 'theme_type')->where('link_id', $id)->orderBy('name')->get();
					if($query2->count() > 0)  {
						$i = 0;
						foreach($query2 as $key2 => $row2) {
							$array['linked'][$id]['themes'][$i]['theme_id'] = $row2['id'];
							$array['linked'][$id]['themes'][$i]['theme_name'] = $row2['name'];
							$array['linked'][$id]['themes'][$i]['theme_type'] = $row2['theme_type'];
							$i++;
						}
					}
				}
			}
			return $array;
		}

		public static function campg_sheet_locations($array) {
			$camp = $array['camp']; $term = $array['term'];
			$locations_by_camp = gambaLocations::locations_by_camp();
			$locations_with_camps = gambaLocations::locations_with_camps($term);
			$grades = gambaGrades::grade_list();
			$prev_term = $term - 1;

			$terms = gambaTerm::terms();
			$campg_themes_linked = $terms[$term]['campg_themes_linked'];
			// Camp Galileo
			if($camp == 1) {
				$row = EnrollSheets::select('id')->where('term', $term)->where('camp', 1)->first();
				if($row['id'] != "") {
					$cg_sheetid = $row['id'];
				} else {
					$cg_sheetid = EnrollSheets::insertGetId([
							'term' => $term,
							'camp' => 1
					]);
				}
				foreach($grades[1]['grades'] as $grade_id => $grade_values) {
					if($grade_values['enrollment'] == 1) {
						foreach($locations_by_camp['locations'][1] as $id => $values) {
							if($values['terms'][$term]['active'] == "Yes") {
								$query = Enrollment::select('id')->where('sheet_id', $cg_sheetid)->where('term', $term)->where('camp', 1)->where('theme_id', 0)->where('grade_id', $grade_id)->where('location_id', $id);
								$sql = $query->toSql();
								$query = $query->first();
								if($query['id'] == "") {
									$query2 = Enrollment::select('location_values')->where('term', $prev_term)->where('camp', 1)->where('grade_id', $grade_id)->where('location_id', $id)->where('dli', 0)->get();
									if($query2['location_values'] != "") {
										$location_values = $row['location_values'];
									} else {
										$location_values = "";
									}
									$insert = new Enrollment;
										$insert->sheet_id = $cg_sheetid;
										$insert->term = $term;
										$insert->camp = 1;
										$insert->theme_id = 0;
										$insert->grade_id = $grade_id;
										$insert->location_id = $id;
										$insert->location_values = $location_values;
										$insert->save();
								}
							} else {
								$delete = Enrollment::where('sheet_id', $cg_sheetid)->where('term', $term)->where('camp', 1)->where('theme_id', 0)->where('grade_id', $grade_id)->where('location_id', $id)->delete();
							}
						}
					}
				}
			}
			return $result;
		}

		public static function gsq_sheet_locations($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$camp = $array['camp']; $term = $array['term'];
			$locations = gambaLocations::locations_with_camps();
// 			echo "<p>Camp: $camp | Term: $term</p>";
// 			echo "<pre>"; print_r($locations); echo "</pre>"; exit; die();
			$grades = gambaGrades::grade_list();
			$prev_term = $term - 1;

			$terms = gambaTerm::terms();

			// Galileo Summer Quest
			if($camp == 2) {
				$themes = gambaThemes::quick_themes_by_camp(2, $term);
				if(empty($themes)) {

					$no_themes['no_themes'] = 1;
					return $no_themes; exit;
				}

				$sheet_info = EnrollSheets::firstOrCreate(
					array(
						'term' => "$term",
						'camp' => '2'
					))->toArray();
				$gsq_sheetid = $sheet_info['id'];

				foreach($grades[2]['grades'] as $grade_id => $grade_values) {
					foreach($themes as $theme_id => $theme_values) {
						if($grade_values['enrollment'] == 1) {
							foreach($locations['camps'][2]['locations'] as $id => $values) {
								$enrollment = Enrollment::firstOrCreate(
									array(
										'sheet_id' => "$gsq_sheetid",
										'term' => "$term",
										'camp' => "2",
										'theme_id' => "$theme_id",
										'grade_id' => "$grade_id",
										'location_id' => "$id"
									))->toArray();
							}
						}
					}
				}
			}
		}

		public static function reset_enrollment_sheet($array) {
			$url = url('/');
			$reset = Enrollment::where('sheet_id', $array['sheet_id'])->where('term', $array['term'])->where('camp', $array['camp'])->delete();

			self::sheet_locations($array['term'], $array['camp']);
			if($array['camp'] == 1) { $camp_action = "cg_campers"; }
			if($array['camp'] == 2) { $camp_action = "gsq_campers"; }
			return $camp_action;
		}

		public static function cg_campers_update($array) {
			$url = url('/');
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$term = $array['term'];
			$terms = gambaTerm::terms();
			$theme_enroll_rotations = $terms[$term]['campg_enroll_rotations'];
			$pack_per_hide = $terms[$term]['campg_packper'];
			/* Debug */ if($debug == 1) { echo "<p>Theme Enroll Rotations: $theme_enroll_rotations<br />Pack Percentage Hide: $pack_per_hide</p>"; }
			$grade = $array['grade'];
			$sheet_id = $array['sheet_id'];
			$debug = $array['debug'];
			/* Debug */ if($debug == 1) { echo "<p>Start Camp G Campers Update: $term, $grade, $debug</p>"; echo str_pad(' ', 4096)."\n"; ob_flush(); flush(); }
			$themelinkarray = gambaThemes::themes_by_linkid($term, 1);
			// Cycle through the locations for this grade
			foreach($array['locations'] as $id => $values) {
				$total_rev_enroll = 0;
				if($theme_enroll_rotations != "true") {
					/* Debug */ if($debug == 1) { echo "<p>[$id] Per Location - Theme Enroll Rotations: False</p>"; }
					if($pack_per_hide != "true") {
						/* Debug */ if($debug == 1) { echo "<p>[$id] Per Location - Pack Percentage Hide: False</p>"; }
						// Add Up Max Enrollments from Themes for Location
						$grade_location_value_array['max_enroll'] += $location_value_array['max_enroll'] = $max_enroll = $values['max_enroll'];
						/* Debug */ if($debug == 1) { echo "<p>[$id] Per Location - Grade Location Max Enroll Col Sum: {$grade_location_value_array['max_enroll']} += Location Max Enroll: {$location_value_array['max_enroll']} = $max_enroll = {$values['max_enroll']}</p>"; }
						// Calculate the Packing Percentage
						$location_value_array['pack_per'] = $pack_per = $values['pack_per'] / 100;
						/* Debug */ if($debug == 1) { echo "<p>[$id] Per Location - Location Pack Per: {$location_value_array['pack_per']} = $pack_per = {$values['pack_per']} / 100</p>"; }
					}

					$grade_location_value_array['rev_enroll'] += $location_value_array['rev_enroll'] = $rev_enroll = ceil($max_enroll * $pack_per);
					$total_rev_enroll += $rev_enroll;
					/* Debug */ if($debug == 1) { echo "<p>[$id] Grade Location Revised Enroll Col Sum: {$grade_location_value_array['rev_enroll']} += Location Revised Enroll: {$location_value_array['rev_enroll']} = $rev_enroll = ceil(Max Enroll: $max_enroll * Pack Per: $pack_per)</p>"; }
					$grade_location_value_array['rotations'] += $location_value_array['rotations'] = $rotations = $values['rotations'];
					/* Debug */ if($debug == 1) { echo "<p>[$id] Grade Location Rotations Col Sum: {$grade_location_value_array['rotations']} += Location Rotations: {$location_value_array['rotations']} = $rotations = {$values['rotations']}</p>"; }

				}
				// Set values to Zero
				$class_totals = 0;
				$total_camp_weeks = 0;
				// Cycle through the linked themes for this location
				/* Debug */ if($debug == 1) { echo "<p>Grade ID: {$grade}</p>"; }
				foreach($values['themelinks'] as $linkid => $linkid_values) {

					if($theme_enroll_rotations == "true") {
						/* Debug */ if($debug == 1) { echo "<p>[$id] Theme Enroll Rotations: True</p>"; }
						if($pack_per_hide != "true") {
							/* Debug */ if($debug == 1) { echo "<p>[$id] Per Theme Pack Percentage Hide: False</p>"; }
							$max_enroll = $linkid_values['max_enroll'];
							/* Debug */ if($debug == 1) { echo "<p>[$id] Per Theme Max Enroll: $max_enroll</p>"; }
							$pack_per = $linkid_values['pack_per'] / 100;
							/* Debug */ if($debug == 1) { echo "<p>[$id] Per Theme Packing Per: $pack_per = {$linkid_values['pack_per']} / 100</p>"; }
						}

						if($pack_per_hide == "true") {
							/* Debug */ if($debug == 1) { echo "<p>[$id] Per Theme Pack Percentage Hide: True</p>"; }

							$rev_enroll = $linkid_values['rev_enroll'];
							/* Debug */ if($debug == 1) { echo "<p>[$id] Per Theme Revised Enroll: $rev_enroll = {$linkid_values['rev_enroll']}</p>"; }
						} else {
							/* Debug */ if($debug == 1) { echo "<p>[$id] Per Theme Pack Percentage Hide: False</p>"; }

							$rev_enroll = ceil($max_enroll * $pack_per);

							/* Debug */ if($debug == 1) { echo "<p>[$id] Per Theme Revised Enroll: $rev_enroll = ceil($max_enroll * $pack_per)</p>"; }
						}

						$rotations = $linkid_values['rotations'];
						/* Debug */ if($debug == 1) { echo "<p>[$id] Per Theme Rotations: $rotations = {$linkid_values['rotations']}</p>"; }
					}

					$theme_weeks = $linkid_values['theme_weeks'];

					$theme_instructors = $linkid_values['instructors'];

					$total_classes = $rotations * $theme_weeks;

					$camper_weeks = $rev_enroll * $theme_weeks * $rotations;

					/* Debug */ if($debug == 1) { echo "<p>[$id] This Camp: {$themelinkarray['themes'][$linkid]['this_camp']}</p>"; }

					if($themelinkarray['themes'][$linkid]['this_camp'] == "false") {
						$class_totals = $class_totals + 0;
						$total_camp_weeks = $total_camp_weeks + 0;
						/* Debug */ if($debug == 1) { echo "<p>[$id] Sum Class Totals: $class_totals += 0;</p>"; }

						/* Debug */ if($debug == 1) { echo "<p>[$id] Sum Total Camper Weeks: $total_camp_weeks += 0;</p>"; }
					} else {
						$class_totals = $class_totals + $total_classes;
						$total_camp_weeks = $total_camp_weeks + $camper_weeks;
						/* Debug */ if($debug == 1) { echo "<p>[$id] Sum Class Totals: $class_totals += $total_classes;</p>"; }

						/* Debug */ if($debug == 1) { echo "<p>[$id] Sum Total Camper Weeks: $total_camp_weeks += $camper_weeks;</p>"; }

						if($theme_enroll_rotations == "true") {
							$total_rev_enroll += $rev_enroll;
						}
					}


					foreach($themelinkarray['themes'][$linkid] as $theme_id => $theme_values) {

						/* Debug */ if($debug == 1) { echo "<p>[$id] Theme Enroll Rotations: True</p>"; }

						$theme_name = $theme_values['name'];

						/* Debug */ if($debug == 1) { echo "<p>[$id] {$theme_values['name']}</p>"; }

						if($theme_enroll_rotations == "true") {

							if($pack_per_hide != "true") {

								/* Debug */ if($debug == 1) { echo "<p>[$id] Pack Percentage Hide: False</p>"; }

								$grade_theme_value_array[$linkid][$theme_id]['max_enroll'] += $theme_values_array[$id][$linkid][$theme_id]['max_enroll'] = $max_enroll;

								/* Debug */ if($debug == 1) { echo "<p>[$id] Grade Theme Max Enroll Sum: {$grade_theme_value_array[$linkid][$theme_id]['max_enroll']} += Location Theme Max Enroll: {$theme_values_array[$id][$linkid][$theme_id]['max_enroll']} = $max_enroll;</p>"; }

								$theme_values_array[$id][$linkid][$theme_id]['pack_per'] = $pack_per;

								/* Debug */ if($debug == 1) { echo "<p>[$id] Theme Pack Percentage: {$theme_values_array[$id][$linkid][$theme_id]['pack_per']} = $pack_per</p>"; }

							}

							$grade_theme_value_array[$linkid][$theme_id]['rev_enroll'] += $theme_values_array[$id][$linkid][$theme_id]['rev_enroll'] = $rev_enroll;

							/* Debug */ if($debug == 1) { echo "<p>[$id] Grade Theme Revised Enroll Col Sum: {$grade_theme_value_array[$linkid][$theme_id]['rev_enroll']} += Location Theme Revised Enroll: {$theme_values_array[$id][$linkid][$theme_id]['rev_enroll']} = $rev_enroll</p>"; }

							$grade_theme_value_array[$linkid][$theme_id]['rotations'] += $theme_values_array[$id][$linkid][$theme_id]['rotations'] = $rotations;

							/* Debug */ if($debug == 1) { echo "<p>[$id] Grade Theme Rotations Sum: {$grade_theme_value_array[$linkid][$theme_id]['rotations']} += Location Theme Rotations: {$theme_values_array[$id][$linkid][$theme_id]['rotations']} = $rotations</p>"; }

						}

						$grade_theme_value_array[$linkid][$theme_id]['theme'] += $theme_values_array[$id][$linkid][$theme_id]['theme'] = $theme_name;

						$grade_theme_value_array[$linkid][$theme_id]['theme_weeks'] += $theme_values_array[$id][$linkid][$theme_id]['theme_weeks'] = $theme_weeks;

						/* Debug */ if($debug == 1) { echo "<p>[$id] Grade Theme Theme Weeks Col Sum: {$grade_theme_value_array[$linkid][$theme_id]['theme_weeks']} += Location Theme Theme Weeks: {$theme_values_array[$id][$linkid][$theme_id]['theme_weeks']} = $theme_weeks;</p>"; }

						$grade_theme_value_array[$linkid][$theme_id]['instructors'] += $theme_values_array[$id][$linkid][$theme_id]['instructors'] = $theme_instructors;

						/* Debug */ if($debug == 1) { echo "<p>[$id] Grade Theme Instructors Col Sum: {$grade_theme_value_array[$linkid][$theme_id]['instructors']} += Location Theme Instructors: {$theme_values_array[$id][$linkid][$theme_id]['instructors']} = $theme_instructors</p>"; }

						$grade_theme_value_array[$linkid][$theme_id]['total_classes'] += $theme_values_array[$id][$linkid][$theme_id]['total_classes'] = $total_classes;

						/* Debug */ if($debug == 1) { echo "<p>[$id] Grade Theme Total Classes Col Sum: {$grade_theme_value_array[$linkid][$theme_id]['total_classes']} += Location Theme Total Classes: {$theme_values_array[$id][$linkid][$theme_id]['total_classes']} = $total_classes</p>"; }

						$grade_theme_value_array[$linkid][$theme_id]['camper_weeks'] += $theme_values_array[$id][$linkid][$theme_id]['camper_weeks'] = $camper_weeks;

						/* Debug */ if($debug == 1) { echo "<p>[$id] Grade Theme Camper Weeks Col Sum: {$grade_theme_value_array[$linkid][$theme_id]['camper_weeks']} += Location Theme Camper Weeks: {$theme_values_array[$id][$linkid][$theme_id]['camper_weeks']} = $camper_weeks</p>"; }
					}

				}
				$grade_location_value_array['num_classrooms'] += $location_value_array['num_classrooms'] = $num_classrooms = $values['num_classrooms'];
				/* Debug */ if($debug == 1) { echo "<p>[$id] Number Classrooms: $num_classrooms</p>"; }
				$grade_location_value_array['kids_per_class'] += $location_value_array['kids_per_class'] = $values['kids_per_class'];
				/* Debug */ if($debug == 1) { echo "<p>[$id] Max Kids Per Class: {$values['kids_per_class']}</p>"; }
				$grade_location_value_array['class_totals'] += $location_value_array['class_totals'] = $class_totals;
				/* Debug */ if($debug == 1) { echo "<p>[$id] Total Class Totals: $class_totals</p>"; }
				$grade_location_value_array['total_camp_weeks'] += $location_value_array['total_camp_weeks'] = $total_camp_weeks;
				/* Debug */ if($debug == 1) { echo "<p>[$id] Total Camper Weeks: $total_camp_weeks</p>"; }
				$grade_location_value_array['total_rev_enroll'] += $location_value_array['total_rev_enroll'] = $total_rev_enroll;
				/* Debug */ if($debug == 1) { echo "<p>[$id] Total Revised Enrollment: $total_rev_enroll</p>"; }
				$location_value_json = json_encode($location_value_array);
				$theme_value_json = json_encode($theme_values_array[$id]);
				$dli = $array['dli'][$id]['dli'];
				$enrollment = Enrollment::find($id);
					$enrollment->dli = $dli;
					$enrollment->location_values = $location_value_json;
					$enrollment->theme_values = $theme_value_json;
					$enrollment->save();
// 				echo "<pre>"; print_r($enrollment); echo "</pre>";
			}
			// All Grades
			$grade_location_value_json = json_encode($grade_location_value_array);
			$grade_theme_value_json = json_encode($grade_theme_value_array);
			$query = Enrollment::select('id');
				//$query = $query->where('sheet_id', $sheet_id);
				$query = $query->where('term', $term);
				$query = $query->where('camp', 1);
				//$query = $query->where('theme_id', 0);
				$query = $query->where('grade_id', $grade);
				//$query = $query->where('extra_class', 0);
				//$query = $query->where('dli', 0);
				$query = $query->where('location_id', 0);
				$query = $query->first();
			/* Debug */ if($debug == 1) { echo "<p>Enrollment All Grades Row ID: {$query['id']}</p>"; }
			/* Debug */ if($debug == 1) { echo "<p>Sheet ID: {$sheet_id}</p>"; }
			if($query['id'] == "") {
				$insert = new Enrollment;
					$insert->sheet_id = $sheet_id;
					$insert->term = $term;
					$insert->camp = 1;
					$insert->theme_id = 0;
					$insert->grade_id = $grade;
					$insert->extra_class = 0;
					$insert->dli = 0;
					$insert->location_id = 0;
					$insert->location_values = $grade_location_value_json;
					$insert->theme_values = $grade_theme_value_json;
					$insert->save();
			} else {
				$update = Enrollment::find($query['id']);
					$update->location_values = $grade_location_value_json;
					$update->theme_values = $grade_theme_value_json;
					$update->save();
			}
			if($debug == 1) { exit; die(); }


			if(site == 1) {

			} elseif(site == 2) {
				if($debug == 1) {
// 					gambaCalc::calculate_from_cg_enrollment($term, $grade, 1, $debug);
				} else {
// 					exec(php_path . " " . Site_path . "enroll_calc_php calculate_from_cg_enrollment $term $grade 1 > /dev/null &");
				}
// 				exec(php_path . " " . Site_path . "execute_php cg_all_grades_total $term > /dev/null &");
			} else {
			}
			//exec(php_path . " " . Site_path . "enroll_calc_php calculate_from_cg_enrollment $term $grade 1 > /dev/null &");
			$job = (new CalcCampGEnrollment($term, $grade, 1))->onQueue('calculate');
			dispatch($job);

			//exec(php_path . " " . Site_path . "execute_php cg_all_grades_total $term > /dev/null &");
			$job = (new CalcAllGradesTotal($term))->onQueue('calculate');
			dispatch($job);

			$return = base64_encode(json_encode($return));
			// Sum Office Data
			//exec(php_path . " " . Site_path . "execute_php calculate_cg_office_data $term $sheet_id > /dev/null &");
			$job = (new CalcCampGOfficeData($term, $sheet_id))->onQueue('calculate');
			dispatch($job);
			// Camp G Basic Supplies
			//exec(php_path . " " . Site_path . "execute_php calculate_all $term 17 > /dev/null &");
			//$job = (new CalcAllPackingLists($term, 17))->onQueue('calculate');
			//dispatch($job);
			// Camp G Kinder PM
			//exec(php_path . " " . Site_path . "execute_php calculate_all $term 4 > /dev/null &");
			//$job = (new CalcAllPackingLists($term, 4))->onQueue('calculate');
			//dispatch($job);
			// Camp G Outdoors
			//exec(php_path . " " . Site_path . "execute_php calculate_all $term 6 > /dev/null &");
			//$job = (new CalcAllPackingLists($term, 6))->onQueue('calculate');
			//dispatch($job);
			// Office
			//exec(php_path . " " . Site_path . "execute_php calculate_all $term 7 > /dev/null &");
			//$job = (new CalcAllPackingLists($term, 7))->onQueue('calculate');
			//dispatch($job);
			//exec(php_path . " " . Site_path . "execute_php packing_totals_calc_all $term > /dev/null &");
			//$job = (new CalcPackingTotals($term))->onQueue('calculate');
			//dispatch($job);
		}



		public static function gsq_campers_update($array) {
// 			echo "<pre>"; print_r($array['term']); echo "</pre>"; exit; die();

			$term = $array['term'];
			$grade_id = $array['grade'];
			$sheet_id = $array['sheet_id'];
			$camp = $array['camp'];
			foreach($array['locations'] as $location_id => $values) {
				foreach($values as $theme_id => $location_values) {
					// Summation for Theme Enrollment
					$total_array[$theme_id]['enroll_per_session'] += $location_values['location_values']['enroll_per_session'];
					$total_array[$theme_id]['sessions'] += $location_values['location_values']['sessions'];
					$total_array[$theme_id]['tot_enrollments'] += $location_values['location_values']['tot_enrollments'];
					$total_array[$theme_id]['campers'] += $location_values['location_values']['campers'];
					$total_array[$theme_id]['num_classrooms'] += $location_values['location_values']['num_classrooms'];
					$total_array[$theme_id]['instructors'] += $location_values['location_values']['instructors'];

					$location_array_sum = array_sum($location_values['location_values']);
					if($location_array_sum == 0) {
						$location_values['location_values']['enrollment_calc'] = "false";
					} else {
						$location_values['location_values']['enrollment_calc'] = "true";
					}
// 					echo "<pre>"; print_r($location_values); echo "</pre>";
					$json_location_values = json_encode($location_values['location_values']);
					// Cleanup check, How many duplicate rows were created
					$row_totals = Enrollment::select('id')->where('term', $term)->where('theme_id', $theme_id)->where('grade_id', $grade_id)->where('location_id', $location_id)->where('camp', $camp)->where('sheet_id', $sheet_id)->where('extra_class', 0)->where('dli', 0)->count();
// 					$row_totals = $row->count();
					// If Just 1 enry update
					if($row_totals == 1) {
						$update = Enrollment::where('term', $term)->where('theme_id', $theme_id)->where('grade_id', $grade_id)->where('location_id', $location_id)->where('camp', 2)->where('sheet_id', $sheet_id)->where('extra_class', 0)->where('dli', 0)->update(['location_values' => $json_location_values]);
// 							$update->location_values = $json_location_values;
// 							$update->save();
					// If More than 1 delete than insert
					}
					elseif($row_totals > 1) {
						$delete = Enrollment::where('term', $term)->where('theme_id', $theme_id)->where('grade_id', $grade_id)->where('location_id', $location_id)->where('camp', 2)->where('sheet_id', $sheet_id)->where('extra_class', 0)->where('dli', 0);
						$delete->delete();

						$insert = new Enrollment;
							$insert->term = $term;
							$insert->theme_id = $theme_id;
							$insert->grade_id = $grade_id;
							$insert->location_id = $location_id;
							$insert->camp = 2;
							$insert->sheet_id = $sheet_id;
							$insert->extra_class = 0;
							$insert->dli = 0;
							$insert->location_values = $json_location_values;
							$insert->save();
					// If none insert
					} else {

						$insert = new Enrollment;
							$insert->term = $term;
							$insert->theme_id = $theme_id;
							$insert->grade_id = $grade_id;
							$insert->location_id = $location_id;
							$insert->camp = 2;
							$insert->sheet_id = $sheet_id;
							$insert->extra_class = 0;
							$insert->dli = 0;
							$insert->location_values = $json_location_values;
							$insert->save();
					}
				}
			}
			// Summation for Theme Enrollment
			$themes_by_camp = gambaThemes::themes_by_camp($camp, $term);
			foreach($themes_by_camp as $theme_id => $values) {
				$select = Enrollment::select('id')->where('term', $term)->where('theme_id', $theme_id)->where('grade_id', $grade_id)->where('location_id', '0')->where('camp', '2')->where('sheet_id', $sheet_id)->where('extra_class', '0')->where('dli', '0');
				$count = $select->count();
				$row = $select->first();
				if($row['id'] != "") {
					$update = Enrollment::find($row['id']);
						$update->theme_values = json_encode($total_array[$theme_id]);
					$update->save();
				} else {
					$insert = new Enrollment;
						$insert->term = $term;
						$insert->theme_id = $theme_id;
						$insert->grade_id = $grade_id;
						$insert->location_id = 0;
						$insert->camp = 2;
						$insert->sheet_id = $sheet_id;
						$insert->extra_class = 0;
						$insert->dli = 0;
						$insert->theme_values = json_encode($total_array[$theme_id]);
					$insert->save();
				}
			}
// 			exit; die();
			// Sum Office Data
			//exec(php_path . " " . Site_path . "execute_php calculate_gsq_office_data $term $sheet_id > /dev/null &");
// 			$job = (new CalcGSQOfficeData($term, $sheet_id))->onQueue('calculate');
// 			dispatch($job);
			gambaEnroll::gsq_sumofficedata($sheet_id, $term);
			// GSQ
			//exec(php_path . " " . Site_path . "execute_php calculate_all $term 2 > /dev/null &");
			//$job = (new CalcAllPackingLists($term, 2))->onQueue('calculate');
			//dispatch($job);
			//exec(php_path . " " . Site_path . "execute_php packing_totals_calc_all $term 60 > /dev/null &");
			//$job = (new CalcPackingTotals($term, 60))->onQueue('calculate');
			//dispatch($job);
		}

		public static function gsq_campers($term) {
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$array['term'] = $term;
			$enrollsheet = EnrollSheets::select('id')->where('term', $term)->where('camp', '2')->first();
			$array['sheet_id'] = $enrollsheet['id'];
			$array['camp_type'] = 2;

			// SELECT gmb_enrollment.id, gmb_enrollment.sheet_id, gmb_enrollment.term, gmb_enrollment.camp, gmb_enrollment.grade_id, gmb_grades.level, gmb_enrollment.location_id, gmb_locations.location, gmb_locations.term_data, gmb_enrollment.location_values, gmb_enrollment.theme_id FROM gmb_enrollment LEFT JOIN gmb_locations ON gmb_locations.id = gmb_enrollment.location_id LEFT JOIN gmb_grades ON gmb_grades.id = gmb_enrollment.grade_id WHERE gmb_enrollment.term = 2017 AND gmb_enrollment.camp = 2 AND gmb_grades.enrollment = 1 ORDER BY gmb_grades.level, gmb_locations.location
			$enrollment = Enrollment::select('enrollment.id', 'enrollment.sheet_id', 'enrollment.term', 'enrollment.camp', 'enrollment.grade_id', 'grades.level', 'enrollment.location_id', 'locations.location', 'locations.term_data', 'enrollment.location_values', 'enrollment.theme_id')->leftjoin('locations', 'locations.id', '=', 'enrollment.location_id')->leftjoin('grades', 'grades.id', '=', 'enrollment.grade_id')->where('enrollment.term', $term)->where('enrollment.camp', '2')->where('grades.enrollment', '1')->orderBy('grades.level')->orderBy('locations.location')->get();

			if(!empty($enrollment)) {
				foreach($enrollment as $key => $row) {
					$grade_id = $row['grade_id'];
					$id = $row['id'];
					$theme_id = $row['theme_id'];
					$term_data = json_decode($row->term_data, true);
					if($term_data[$term]['active'] == "Yes") {
						$array['enrollment'][$grade_id]['grade'] = $row['level'];
						$array['enrollment'][$grade_id]['locations'][$row['location_id']]['location'] = $row['location'];
						$array['enrollment'][$grade_id]['locations'][$row['location_id']]['enroll_id'] = $row['id'];
						$array['enrollment'][$grade_id]['locations'][$row['location_id']]['values'][$theme_id]['location_values'] = json_decode($row->location_values, true);
						$array['enrollment'][$grade_id]['locations'][$row['location_id']]['term_data'] = $term_data;
					}
				}
			}
			return $array;

		}

		public static function create_enrollment_sheet($term, $camp) {
			$lastid = EnrollSheets::insertGetId(['term' => $term, 'camp' => $camp]);
			return $lastid;
		}

		public static function ext_enrollment($term, $camp) {
			$query = EnrollmentExt::select('id', 'location_id', 'camp', 'term', 'location_values')->where('term', $term)->where('camp', $camp)->get();

			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$location = $row['location_id'];
					$array['locations'][$location]['id'] = $row['id'];
					$array['locations'][$location]['camp'] = $row['camp'];
					$array['locations'][$location]['term'] = $row['term'];
					$array['locations'][$location]['location_values'] = json_decode($row->location_values, true);
				}
			}
			return $array;
		}

		public static function ext_update($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>";
			$camp_size = gambaAdmin::config_val('cg_campsize');
			$camp = $array['camp'];
			$term = $array['term'];
			$small_camp_enrollment = 0;
			$large_camp_enrollment = 0;
			foreach($array['location'] as $location_id => $values) {
				$values['total_enrollment'] = $values['pm_enrollment_session'] * $values['total_sessions'];
				// Total PM Enrollment/Session
				$cache_values['pm_enroll_sess'] += $values['pm_enrollment_session'];
				// Total of Total Sessions
				$cache_values['total_sessions'] += $values['total_sessions'];
				// Total of Total Enrollments
				$cache_values['total_enroll'] += $values['total_enrollment'];
				if($values['pm_enrollment_session'] <= $camp_size) {
					$cache_values['tot_sm_camp'] += 1;
					$small_camp_enrollment += $values['total_enrollment'];
				}
				if($values['pm_enrollment_session'] > $camp_size) {
					$cache_values['tot_lg_camps'] += 1;
					$large_camp_enrollment += $values['total_enrollment'];
				}

				$json_values = json_encode($values);
				$query = EnrollmentExt::select('id')->where('location_id', $location_id)->where('camp', $camp)->where('term', $term)->first();
				if($query['id'] == "") {
					$insert = new EnrollmentExt;
					$insert->location_id = $location_id;
					$insert->camp = $camp;
					$insert->term = $term;
					$insert->location_values = $json_values;
					$insert->save();
				} else {
					$update = EnrollmentExt::find($query['id']);
					$update->location_values = $json_values;
					$update->save();
				}
			}

			$cache_values['avg_enrollment'] = ($small_camp_enrollment + $large_camp_enrollment) / 2;
			// Total of Total Enrollments
			$json_cache_values = json_encode($cache_values);
			$query = EnrollmentExt::select('id')->where('location_id', 0)->where('camp', $camp)->where('term', $term)->first();
			if($query['id'] == "") {
				$insert = new EnrollmentExt;
				$insert->location_id = 0;
				$insert->camp = $camp;
				$insert->term = $term;
				$insert->location_values = $json_cache_values;
				$insert->save();
			} else {
				$update = EnrollmentExt::find($query['id']);
				$update->location_values = $json_cache_values;
				$update->save();
			}

			if($camp == 1) {
				$action = "cg_ext";
				// Camp G Extended Care
				//exec(php_path . " " . Site_path . "execute_php calculate_all $term 5 > /dev/null &");
				//$job = (new CalcAllPackingLists($term, 5))->onQueue('calculate');
				//dispatch($job);
			}
			if($camp == 2) {
				$action = "gsq_ext";
				// GSQ Extended Care
				//exec(php_path . " " . Site_path . "execute_php calculate_all $term 10 > /dev/null &");
				//$job = (new CalcAllPackingLists($term, 10))->onQueue('calculate');
				//dispatch($job);
			}
// 			exit; die();
			//exec(php_path . " " . Site_path . "execute_php packing_totals_calc_all $term 60 > /dev/null &");
			//$job = (new CalcPackingTotals($term, 60))->onQueue('calculate');
			//dispatch($job);
			return $action;
		}

		public static function create_cg_enrollment($term) {

			$sheet_id = self::create_enrollment_sheet($term, 1);
			echo "<p>$term</p>";
			$grades = gambaGrades::grade_list();
// 			echo "<pre>"; print_r($grades); echo "</pre>";
			$locations = gambaLocations::locations_by_camp();
// 			echo "<pre>"; print_r($locations); echo "</pre>";
			foreach($grades[1]['grades'] as $grade_id => $grade_values) {
// 				echo "<pre>"; print_r($grade_values); echo "</pre>";
				if($grade_values['enrollment'] == 1) {
					foreach($locations['locations'][1] as $location_id => $location_values) {
// 						echo "<pre>"; print_r($location_values); echo "</pre>";
// 						echo "<p>$location_id</p>";
						if($location_values['terms'][$term]['active'] == "Yes") {
							$insert = new Enrollment;
							$insert->sheet_id = $sheet_id;
							$insert->term = $term;
							$insert->camp = 1;
							$insert->grade_id = $grade_id;
							$insert->location_id = $location_id;
							$insert->save();

							//$sql = \DB::last_query();
							echo "<p>$sql</p>"; echo str_pad(' ', 4096)."\n"; ob_flush();
						}
					}
				}
			}
		}

		public static function duplicate_location($array) {
			$id = $array['enroll_id'];
			//echo "<pre>"; print_r($array); echo "</pre>";	exit; die();
			$row = Enrollment::where('id', $id)->select('sheet_id', 'term', 'camp', 'grade_id', 'location_id', 'location_values', 'theme_values')->first();
    			$sheet_id = $row['sheet_id'];
    			$term = $row['term'];
    			$camp = $row['camp'];
    			$grade_id = $row['grade_id'];
    			$location_id = $row['location_id'];
    			$location_values = $row['location_values'];
    			$theme_values = $row['theme_values'];

    		$update = Enrollment::where('id', $id)->update([extra_class => 1]);

			$insert = new Enrollment;
    			$insert->sheet_id = $sheet_id;
    			$insert->term = $term;
    			$insert->camp = $camp;
    			$insert->theme_id = 0;
    			$insert->grade_id = $grade_id;
    			$insert->location_id = $location_id;
    			$insert->extra_class = 2;
    			$insert->dli = 0;
    			$insert->location_values = $location_values;
    			$insert->theme_values = $theme_values;
    			$insert->save();

			//exec(php_path . " " . Site_path . "execute_php packing_totals_calc_all $term > /dev/null &");
			//$job = (new CalcPackingTotals($term))->onQueue('calculate');
			//dispatch($job);
		}

		public static function remove_duplicate_location($array) {
			$id = $array['enroll_id'];
// 			echo "<pre>"; print_r($array); echo "</pre>";	exit; die();


			$delete = Enrollment::find($id)->delete();

			$update = Enrollment::where('id', $array['original'])->update(['extra_class' => 0]);

			//exec(php_path . " " . Site_path . "execute_php packing_totals_calc_all $term > /dev/null &");
			//$job = (new CalcPackingTotals($term))->onQueue('calculate');
			//dispatch($job);
		}

		public static function enroll_extra_class($id, $term, $location, $link = "true") {
			$url = url('/');
			$row = Enrollment::select('location_id', 'extra_class', 'term', 'grade_id', 'camp', 'sheet_id')->where('id', $id)->first();
			$location_id = $row['location_id'];
			$location_name = $location;
			$extra_class = $row['extra_class'];
			$term = $row['term'];
			$camp = $row['camp'];
			$grade_id = $row['grade_id'];
			$sheet_id = $row['sheet_id'];
			if($extra_class == 0) {
				if($link == "true") {
					$content .= "<a href=\"{$url}/enrollment/duplicate_location?grade={$grade_id}&term={$term}&enroll_id={$id}&camp={$camp}\" onClick=\"return confirm('Are you sure you want to duplicate {$location_name}?');\" class=\"location-enroll\">{$location_name}</a>";
				} else {
				    $content .= $location_name;
				}
			}
			if($extra_class == 1) {
			    $content .= $location_name;
			}
			if($extra_class == 2) {
				if($link == "true") {
				    $query = Enrollment::select('id')->where('sheet_id', $sheet_id)->where('term', $term)->where('camp', $camp)->where('grade_id', $grade_id)->where('location_id', $location_id)->where('extra_class', 1)->first();
					$content .= "<a href=\"{$url}/enrollment/remove_duplicate_location?grade={$grade_id}&term={$term}&enroll_id={$id}&camp={$camp}&original={$query['id']}\" onClick=\"return confirm('Are you sure you want to remove the duplicate {$location_name}?');\" class=\"location-enroll\">{$location_name}</a> - {$extra_class}";
				} else {
					$content .= "$location_name - $extra_class";
				}
			}
			return $content;
		}

		public static function term_dropdown($action, $term) {
			$url = url('/');
			$terms = gambaTerm::terms();
			$current_term = gambaTerm::year_by_status('C');
			if($term == "") { $term = $current_term; }
			$content .= <<<EOT

		  <button href="#" data-dropdown="drop1" aria-controls="drop1" aria-expanded="false" class="button dropdown">Select Term ({$term})</button>
		  <ul id="drop1" data-dropdown-content class="f-dropdown" aria-hidden="true">
EOT;
			foreach($terms as $year => $values) {
		    	$content .= "<li><a href='{$url}/enrollment/{$term}/{$action}'>{$year}</a></li>\n";
		    }
			$content .= <<<EOT
		  </ul>
EOT;
			return $content;
		}

		public static function cg_sumofficedata($sheet_id, $term) {
// 		    echo "<p>$sheet_id</p>";
// 		    echo "<p>$term</p>"; 
			$date = date("Y-m-d H:i:s");
			$delete = OfficeData::where('sheet_id', $sheet_id)->where('camp', 1)->delete();
			$query = Enrollment::select('enrollment.camp', 'enrollment.grade_id', 'enrollment.location_id', 'enrollment.extra_class', 'enrollment.location_values', 'enrollment.theme_values', 'grades.grade_options')->leftjoin('grades', 'grades.id', '=', 'enrollment.grade_id')->where('enrollment.sheet_id', $sheet_id)->where('enrollment.term', $term)->where('enrollment.camp', 1)->where('enrollment.location_id', '>', 0)->get();
// 			echo "<pre>"; print_r($query); echo "</pre>"; 
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$camp = $row['camp'];
					$grade = $row['grade_id'];
					$location_id = $row['location_id'];
					$extra_class = $row['extra_class'];
					$location_values = json_decode($row->location_values, true);
					$theme_values = json_decode($row->theme_values, true);
					$grade_options = json_decode($row->grade_options, true);
					if($grade_options['exclude_packing'] != "true") {
						$array['grades']["grade-".$grade]['locations'][$location_id]['dli'][$extra_class]['location_values'] = $location_values;
						$array['grades']["grade-".$grade]['locations'][$location_id]['dli'][$extra_class]['theme_values'] = $theme_values;
					}
				}
			}
// 						echo "<pre>"; print_r($array['grades']); echo "</pre>"; 
			foreach($array['grades'] as $grade => $locations) {
				foreach($locations['locations'] as $location_id => $classes) {
					foreach ($classes['dli'] as $key => $values) {
						$array['totals'][$location_id]['classrooms'] += $values['location_values']['num_classrooms'];
						$array['totals'][$location_id]['max_enrollment'] += $values['location_values']['kids_per_class'];
// 						$array['totals'][$location_id]['revised_enrollment'] += $values['location_values']['rev_enroll'];
// 						$array['totals'][$location_id]['total_rev_enroll'] += $values['location_values']['total_rev_enroll'];
						$array['totals'][$location_id]['revised_enrollment'] += $values['location_values']['total_rev_enroll'];
						$array['totals'][$location_id]['total_camper_weeks'] += $values['location_values']['total_camp_weeks'];
// 						foreach($values['theme_values'] as $themelinkid => $themelinkid_values) {
// 							foreach($themelinkid_values as $theme_id => $theme_values) {
// 								$array['totals'][$location_id]['total_camper_weeks'] += ($theme_values['camper_weeks'] / 2);
// 							}
// 						}
					}
				}
			}
// 			echo "<pre>"; print_r($array['totals']); echo "</pre>";
// 			echo "<pre>"; print_r($array['grades']); echo "</pre>"; 
// 			exit; die();
			foreach($array['totals'] as $location_id => $values) {
				$insert = new OfficeData;
					$insert->term = $term;
					$insert->sheet_id = $sheet_id;
					$insert->camp = 1;
					$insert->location_id = $location_id;
					$insert->field = 'classrooms';
					$insert->value = $values['classrooms'];
					$insert->changedate = $date;
					$insert->save();
				$insert = new OfficeData;
					$insert->term = $term;
					$insert->sheet_id = $sheet_id;
					$insert->camp = 1;
					$insert->location_id = $location_id;
					$insert->field = 'max_enrollment';
					$insert->value = $values['max_enrollment'];
					$insert->changedate = $date;
					$insert->save();
				$insert = new OfficeData;
					$insert->term = $term;
					$insert->sheet_id = $sheet_id;
					$insert->camp = 1;
					$insert->location_id = $location_id;
					$insert->field = 'revised_enrollment';
					$insert->value = $values['revised_enrollment'];
					$insert->changedate = $date;
					$insert->save();
				$insert = new OfficeData;
					$insert->term = $term;
					$insert->sheet_id = $sheet_id;
					$insert->camp = 1;
					$insert->location_id = $location_id;
					$insert->field = 'total_camper_weeks';
					$insert->value = $values['total_camper_weeks'];
					$insert->changedate = $date;
					$insert->save();
// 				$insert = new OfficeData;
// 					$insert->term = $term;
// 					$insert->sheet_id = $sheet_id;
// 					$insert->camp = 1;
// 					$insert->location_id = $location_id;
// 					$insert->field = 'total_rev_enroll';
// 					$insert->value = $values['total_rev_enroll'];
// 					$insert->changedate = $date;
// 					$insert->save();
			}
		}

		public static function gsq_sumofficedata($sheet_id, $term) {
			$date = date("Y-m-d H:i:s");
			$delete = OfficeData::where('sheet_id', $sheet_id)->where('camp', 2)->delete();

			$query = Enrollment::select('enrollment.camp', 'enrollment.grade_id', 'enrollment.theme_id', 'enrollment.location_id', 'enrollment.location_values', 'grades.grade_options')->leftjoin('grades', 'grades.id', '=', 'enrollment.grade_id')->where('enrollment.sheet_id', $sheet_id)->where('enrollment.term', $term)->where('enrollment.camp', 2)->where('enrollment.location_id', '>', 0)->get();
			//$array['sql'] = \DB::last_query();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$camp = $row['camp'];
					$grade = $row['grade_id'];
					$theme = $row['theme_id'];
					$location_id = $row['location_id'];
					$extra_class = $row['extra_class'];
					$location_values = json_decode($row->location_values, true);
					$grade_options = json_decode($row->grade_options, true);
					if($grade_options['exclude_packing'] != "true") {
						$array['grades'][$grade][$location_id][$theme] = $location_values;
					}
				}
			}


			foreach($array['grades'] as $grade => $locations) {
				foreach($locations as $location_id => $location_values) {
					foreach($location_values as $theme => $values) {
						$array['totals'][$location_id]['classrooms'] += $values['num_classrooms'];
						$array['totals'][$location_id]['max_enrollment'] += $values['enroll_per_session'];
						$array['totals'][$location_id]['revised_enrollment'] += $values['campers'];
						$array['totals'][$location_id]['total_camper_weeks'] += $values['sessions'] * $values['campers'];
					}
				}
			}

// 			echo "<pre>"; print_r($array['totals']); echo "</pre>";
// 			echo "<pre>"; print_r($array['grades']); echo "</pre>";

			foreach($array['totals'] as $location_id => $values) {
				$insert = new OfficeData;
					$insert->term = $term;
					$insert->sheet_id = $sheet_id;
					$insert->camp = 2;
					$insert->location_id = $location_id;
					$insert->field = 'classrooms';
					$insert->value = $values['classrooms'];
					$insert->changedate = $date;
					$insert->save();
				$insert = new OfficeData;
					$insert->term = $term;
					$insert->sheet_id = $sheet_id;
					$insert->camp = 2;
					$insert->location_id = $location_id;
					$insert->field = 'max_enrollment';
					$insert->value = $values['max_enrollment'];
					$insert->changedate = $date;
					$insert->save();
				$insert = new OfficeData;
					$insert->term = $term;
					$insert->sheet_id = $sheet_id;
					$insert->camp = 2;
					$insert->location_id = $location_id;
					$insert->field = 'revised_enrollment';
					$insert->value = $values['revised_enrollment'];
					$insert->changedate = $date;
					$insert->save();
				$insert = new OfficeData;
					$insert->term = $term;
					$insert->sheet_id = $sheet_id;
					$insert->camp = 2;
					$insert->location_id = $location_id;
					$insert->field = 'total_camper_weeks';
					$insert->value = $values['total_camper_weeks'];
					$insert->changedate = $date;
					$insert->save();
			}
		}

		public static function enrollment_home_view($array) {
			$url = url('/');
			$user_group = Session::get('group');
			$content_array['page_title'] = "Camper Enrollment";
			if($array['term'] == "") { $term = gambaTerm::year_by_status('C'); } else { $term = $array['term']; }
			$current_term = $term = gambaTerm::year_by_status('C');
// 			self::sheet_locations($term);
			if($array['gsq_themes'] == "false") {
				$content_array['content'] .= gambaDebug::alert_box("Unable to create {$term} GSQ Enrollment Sheet. There are currently no themes for the {$array['term']} Season.", 'warning');
			}
			$content_array['content'] .=  <<<EOT
		<p>Please select a season and a camp enrollment sheet. (C) is the current season.</p>
		<div class="row">
EOT;
			$terms = gambaTerm::terms();
			$i = 0;
			foreach($terms as $key => $values) {
				$nav_enroll_sheets = "";
				// Check if the Sheet is created
				if($user_group <= 1 && $key >= $current_term) {
					$nav_enroll_sheets = <<<EOT
					<li><a href="$url/enrollment/{$key}/sheet_locations?camp=1">Setup/Refresh Camp G Enroll Sheets</a></li>
					<li><a href="$url/enrollment/{$key}/sheet_locations?camp=2">Setup/Refresh GSQ Enroll Sheets</a></li>
EOT;
				}
				$current_term_display = ""; if($values['year_status'] == "C") {
					$current_term_display = " (C)";
				}
				$content_array['content'] .=  <<<EOT
			<div class="small-6 medium-3 large-3 columns">
				<h3>{$key} Season{$current_term_display}</h3>
				<ul>
					<li><a href="$url/enrollment/{$key}/cg_campers">Camp Galileo</a></li>
					<li><a href="$url/enrollment/{$key}/gsq_campers">Galileo Summer Quest</a></li>
					<li><a href="$url/enrollment/{$key}/cg_ext">Camp G Ext Care</a></li>
					<li><a href="$url/enrollment/{$key}/gsq_ext">GSQ Ext Care</a></li>
					{$nav_enroll_sheets}
				</ul>
			</div>
EOT;
				$i++;
				if($i == 4) {
					$i = 0;
					$content_array['content'] .=  <<<EOT
		</div>
		<div class="row">
EOT;
				}
			}
			$content_array['content'] .=  <<<EOT
		</div>
EOT;
			return $content_array;
// 			echo "<pre>"; print_r($terms); echo "</pre>";
		}

		public static function cg_campers_subnav($camp, $grade, $term) {
			$grades = gambaGrades::grade_list();
			$url = url('/');
			$content = <<<EOT
			<dl class="sub-nav">
				<dt>Grades View:</dt>
EOT;
			foreach($grades[$camp]['grades'] as $grade_id => $values) {
				if($grade == $grade_id) { $mark_active = ' class="active"'; } else { $mark_active = ""; }
				if($values['enrollment'] == 1) {
					$content .= <<<EOT
				<dd{$mark_active}><a href="{$url}/enrollment/{$term}/cg_campers?grade={$grade_id}">{$values['level']} - {$values['altname']}</a></dd>
EOT;
				}
			}
			$content .= <<<EOT
				<dd><a href="{$url}/enrollment">Return to Campers</a></dd>
			</dl>
EOT;
			return $content;
		}

		public static function cg_campers_csv_subnav($camp, $grade, $term) {
			$grades = gambaGrades::grade_list();
			$url = url('/');
			$content .= <<<EOT
			<dl class="sub-nav">
				<dt>CSV View:</dt>
EOT;
			foreach($grades[$camp]['grades'] as $grade_id => $values) {
				if($grade == $grade_id) { $mark_active = ' class="active"'; } else { $mark_active = ""; }
				if($values['enrollment'] == 1) {
					$content .= <<<EOT
				<dd{$mark_active}><a href="{$url}/enrollment/{$values['term']}/cg_campers_csv_view/{$grade_id}">{$values['level']} - {$values['altname']}</a></dd>
EOT;
				}
			}
			$content .= <<<EOT
				<dd><a href="{$url}/enrollment/{$term}/cg_campers?grade={$grade}">Return to {$term} {$grades[$camp]['grades'][$grade]['level']} Enrollment</a></dd>
			</dl>
EOT;
			return $content;
		}

		// Now in /resources/views/app/enrollment/cgcampers.blade.php
		public static function cg_campers_view($term, $grade) {
			$url = url('/');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($grade == "") { $grade = 1; }
			$cur_term = gambaTerm::year_by_status('C');
			$terms = gambaTerm::terms();
			$theme_enroll_rotations = $terms[$term]['campg_enroll_rotations'];
			$pack_per_hide = $terms[$term]['campg_packper'];
			$themes = gambaThemes::themes_by_camp(1, $term);
			$enrollment = self::cg_campers($term, $grade);
			$group = Session::get('group');
			$cg_linked_themes = self::cg_linked_themes($term);
			$content_array['page_title'] = "Camp Galileo Camper Enrollment $term";
			$content_array['sub_nav'] = self::cg_campers_subnav(1, $grade, $term);
			$content_array['content'] .= gambaDirections::getDirections('cg_campers_view');
			$content_array['header_js'] = <<<EOT
			<script language="JavaScript" type="text/javascript" src="{$url}/js/jquery.fixedheadertable.js"></script>
			<script>
				$(document).ready(function() {
					//$('#enrollment').fixedHeaderTable({ footer: true, cloneHeadToFoot: false, fixedColumn: false, height: 500 });
				});
			</script>
EOT;
			$content_array['header_css'] .= <<<EOT
<link rel="stylesheet" href="css/component.css" />
EOT;
			//if($group <= 1 && $term == $cur_term) {
				$content_array['content'] .= <<<EOT
				<p><a href="{$url}/enrollment/{$term}/reset_enrollment_sheet?action=reset_enrollment_sheet&camp=1&term={$term}" class="button small alert radius" onClick="return confirm('Warning! Reseting this sheet will result in the loss of all current enrollment data. This can not be undone.');">Reset Enrollment Sheet</a></p>
EOT;
			//}
			foreach($enrollment['enrollment'] as $key => $values) {
				$content_array['content'] .= <<<EOT
		<p><a href="{$url}/enrollment/{$term}/cg_campers_edit?action=cg_campers_edit&term={$term}&grade={$key}" class="button small success radius">Edit {$values['grade']} Enrollment</a>
			<a href="{$url}/enrollment/{$term}/cg_campers_csv_view?action=cg_campers_csv_view&term={$term}&grade={$key}" class="button small success radius">CSV {$values['grade']} Download/Upload</a></p>
		<table id="enrollment">
			<thead>
EOT;
				$content_array['content'] .= <<<EOT
				<tr>
					<th></th>
					<th></th>
EOT;
				if($theme_enroll_rotations != "true") {
					if($pack_per_hide != "true") {
						$content_array['content'] .= <<<EOT
					<th></th>
					<th></th>
EOT;
					}
					$content_array['content'] .= <<<EOT
					<th></th>
					<th></th>
EOT;
				}
				$i = 1;
				foreach($cg_linked_themes['linked'] as $link_id => $themevalues) {
					$colspan = "";
					if($theme_enroll_rotations == "true" && $pack_per_hide == "true") { $colspan = "5"; }
					elseif($theme_enroll_rotations == "true" && $pack_per_hide != "true") { $colspan = "7"; }
					else { $colspan = "3"; }
					if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
						$content_array['content'] .= <<<EOT
					<th colspan="{$colspan}" class="table-header-theme-{$col_color}" style="max-width: 230px !important;">{$themevalues['themes'][0]['theme_name']} &<br />{$themevalues['themes'][1]['theme_name']}</th>
EOT;
					$i++;
				}
				$content_array['content'] .= <<<EOT
					<th colspan="4" class="center">TOTALS</th>
				</tr>
EOT;
				$content_array['content'] .= <<<EOT
				<tr>
					<th></th>
					<th></th>
EOT;
				if($theme_enroll_rotations != "true") {
					if($pack_per_hide != "true") {
						$content_array['content'] .= <<<EOT
					<th class="image"><img src="{$url}/img/header-avg-wkly-enroll.png" title="Average Weekly Enrollment (Formerly Max Enrollment)" style="width: 32px !important; height: 113px !important;" /></th>
					<th class="image"><img src="{$url}/img/header-pack-per.png" title="Pack Percentage" /></th>
EOT;
					}
					$content_array['content'] .= <<<EOT
					<th class="image"><img src="{$url}/img/header-rev-avg-wkly-enroll.png" title="Revised Average Weekly Enrollment" /></th>
					<th class="image"><img src="{$url}/img/header-rotations.png" title="Rotations" /></th>
EOT;
				}
				$i = 1;
				foreach($cg_linked_themes['linked'] as $link_id => $themevalues) {
					if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
					if($theme_enroll_rotations == "true") {
						if($pack_per_hide != "true") {
							$content_array['content'] .= <<<EOT
					<th class="table-header-theme-{$col_color} image"><img src="{$url}/img/header-avg-wkly-enroll.png" title="Average Weekly Enrollment (Formerly Max Enrollment)" style="width: 32px !important; height: 113px !important;" /></th>
					<th class="table-header-theme-{$col_color} image"><img src="{$url}/img/header-pack-per.png" title="Pack Percentage" /></th>
EOT;
						}
						$content_array['content'] .= <<<EOT
					<th class="table-header-theme-{$col_color} image"><img src="{$url}/img/header-rev-avg-wkly-enroll.png" title="Revised Average Weekly Enrollment" /></th>
					<th class="table-header-theme-{$col_color} image"><img src="{$url}/img/header-rotations.png" title="Rotations" /></th>
EOT;
					}
					$content_array['content'] .= <<<EOT
					<th class="center table-header-theme-{$col_color} image"><img src="{$url}/img/header-instructors.png" title="Instructors" style="width: 32px !important; height: 113px !important;" /></th>
					<th class="center table-header-theme-{$col_color} image"><img src="{$url}/img/header-theme-weeks.png" title="Theme Weeks" /></th>
EOT;
					//<th class="center table-header-theme-{$col_color} image"><img src="{$url}/img/header-total-classes.png" title="Total Classes" /></th>
					$content_array['content'] .= <<<EOT
					<th class="center table-header-theme-{$col_color} image"><img src="{$url}/img/header-camper-weeks.png" title="Camper Weeks" /></th>
EOT;
					$i++;
				}
				$content_array['content'] .= <<<EOT
					<th class="image"><img src="{$url}/img/header-sessions.png" title="Sessions" width="32" /></th>
					<th class="image"><img src="{$url}/img/header-classrooms.png" title="Classrooms" /></th>
					<th class="image"><img src="{$url}/img/header-max-kids-per-class.png" title="Max # of Kids per Class (Formerly Kids/Class (SSs))" /></th>
					<th class="image"><img src="{$url}/img/header-class-totals.png" title="Class Totals" /></th>
					<th class="image"><img src="{$url}/img/header-camper-weeks.png" title="Camper Weeks" /></th>
				</tr>
EOT;
				$content_array['content'] .= <<<EOT
			</thead>
			<tbody>
EOT;
				foreach($values['locations'] as $id => $location) {
					if($location['location_id'] > 0) {
					    $location_name = self::enroll_extra_class($id, $term, $location['location']);
						if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
						$avg_weekly_enrollment = ceil($location['location_values']['max_enroll'] * $location['location_values']['pack_per']);
						$pack_per = $location['location_values']['pack_per'] * 100;
						$content_array['content'] .= <<<EOT
				<tr>
					<td><a href="{$url}/enrollment/{$term}/cg_campers_edit?action=cg_campers_edit&term={$term}&grade={$key}&location={$id}" class="button small success radius">Edit</a></td>
					<td title="{$id}" class="row-location">{$location_name}</td>
EOT;
						if($theme_enroll_rotations != "true") {
							if($pack_per_hide != "true") {
								$content_array['content'] .= <<<EOT
					<td>{$location['location_values']['max_enroll']}</td>
					<td>{$pack_per}%</td>
EOT;
							}
							$content_array['content'] .= <<<EOT
					<td>{$avg_weekly_enrollment}</td>
					<td>{$location['location_values']['rotations']}</td>
EOT;
						}
						$i = 1;
						foreach($cg_linked_themes['linked'] as $link_id => $themevalues) {
							$theme_id = $themevalues['themes'][0]['theme_id'];
							if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
							$camp_weeks[$id] = $camp_weeks[$id] + $location['theme_values'][$link_id][$theme_id]['camper_weeks'];
							if($theme_enroll_rotations == "true") {
								if($pack_per_hide != "true") {
							$content_array['content'] .= <<<EOT
					<td class="table-header-theme-{$col_color}">{$location['theme_values'][$link_id][$theme_id]['max_enroll']}</td>
					<td class="table-header-theme-{$col_color}">{$location['theme_values'][$link_id][$theme_id]['pack_per']}%</td>
EOT;
								}
							$content_array['content'] .= <<<EOT
					<td class="table-header-theme-{$col_color}">{$location['theme_values'][$link_id][$theme_id]['rev_enroll']}</td>
					<td class="table-header-theme-{$col_color}">{$location['theme_values'][$link_id][$theme_id]['rotations']}</td>
EOT;
							}
							$content_array['content'] .= <<<EOT
					<td class="table-header-theme-{$col_color}">{$location['theme_values'][$link_id][$theme_id]['instructors']}</td>
					<td class="table-header-theme-{$col_color}">{$location['theme_values'][$link_id][$theme_id]['theme_weeks']}</td>
EOT;
					//<td class="table-header-theme-{$col_color}">{$location['theme_values'][$link_id][$theme_id]['total_classes']}</td>
							$content_array['content'] .= <<<EOT
					<td class="table-header-theme-{$col_color}">{$location['theme_values'][$link_id][$theme_id]['camper_weeks']}</td>
EOT;
							$i++;
						}
						$content_array['content'] .= <<<EOT
					<td>{$location['term_data'][$term]['tot_sessions']}</td>
					<td>{$location['location_values']['num_classrooms']}</td>
					<td>{$location['location_values']['kids_per_class']}</td>
					<td>{$location['location_values']['class_totals']}</td>
					<td>{$location['location_values']['total_camp_weeks']}</td>
				</tr>
EOT;
					}
				}
				$content_array['content'] .= <<<EOT
			</tbody>
			<tfoot>
				<tr>
					<td></td>
					<td><strong>Total</strong></td>
EOT;
				if($theme_enroll_rotations != "true") {
					if($pack_per_hide != "true") {
						$content_array['content'] .= <<<EOT
					<td>{$values['totals']['location_values']['max_enroll']}</td>
					<td>-------</td>
EOT;
					}
					$content_array['content'] .= <<<EOT
					<td>{$values['totals']['location_values']['rev_enroll']}</td>
					<td>{$values['totals']['location_values']['rotations']}</td>
EOT;
				}
				$i = 1;
				foreach($cg_linked_themes['linked'] as $link_id => $themevalues) {
					$theme_id = $themevalues['themes'][0]['theme_id'];
					$camp_weeks[$id] = $camp_weeks[$id] + $location['theme_values'][$link_id][$theme_id]['camper_weeks'];
					if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
					if($theme_enroll_rotations == "true") {
						if($pack_per_hide != "true") {
							$content_array['content'] .= <<<EOT
					<td class="table-header-theme-{$col_color}">{$values['totals']['theme_values'][$link_id][$theme_id]['max_enroll']}</td>
					<td class="table-header-theme-{$col_color}"></td>
EOT;
						}
						$content_array['content'] .= <<<EOT
					<td class="table-header-theme-{$col_color}">{$values['totals']['theme_values'][$link_id][$theme_id]['rev_enroll']}</td>
					<td class="table-header-theme-{$col_color}">{$values['totals']['theme_values'][$link_id][$theme_id]['rotations']}</td>
EOT;
					}
					$content_array['content'] .= <<<EOT
					<td class="table-header-theme-{$col_color}">{$values['totals']['theme_values'][$link_id][$theme_id]['instructors']}</td>
					<td class="table-header-theme-{$col_color}">{$values['totals']['theme_values'][$link_id][$theme_id]['theme_weeks']}</td>
EOT;
					//<td class="table-header-theme-{$col_color}">{$values['totals']['theme_values'][$link_id][$theme_id]['total_classes']}</td>
					$content_array['content'] .= <<<EOT
					<td class="table-header-theme-{$col_color}">{$values['totals']['theme_values'][$link_id][$theme_id]['camper_weeks']}</td>
EOT;
					$i++;
				}
				$content_array['content'] .= <<<EOT
					<td></td>
					<td>{$values['totals']['location_values']['num_classrooms']}</td>
					<td>{$values['totals']['location_values']['kids_per_class']}</td>
					<td>{$values['totals']['location_values']['class_totals']}</td>
					<td>----</td>
				</tr>
			</tfoot>
		</table>
		<p><a href="{$url}/enrollment/{$term}/cg_campers_edit?action=cg_campers_edit&term={$term}&grade={$key}" class="button small success radius">Edit {$values['grade']} Enrollment</a></p>
EOT;
			}
			$content_array['footer_js'] .= <<<EOT
EOT;
			return $content_array;
// 			echo "<pre>"; print_r($enrollment); echo "</pre>";
// 			Debug::preformatted_arrays($enrollment, 'enrollment_array', 'Enrollment');
		}


		public static function cg_campers_csv_view($term, $grade) {
			$url = url('/');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($grade == "") { $grade = 1; }
			$grades = gambaGrades::grade_list();
			$cur_term = gambaTerm::year_by_status('C');
			$terms = gambaTerm::terms();
			$theme_enroll_rotations = $terms[$term]['campg_enroll_rotations'];
			$pack_per_hide = $terms[$term]['campg_packper'];
			$themes = gambaThemes::themes_by_camp(1, $term);
			$enrollment = self::cg_campers($term, $grade);
			$cg_linked_themes = self::cg_linked_themes($term);

			$content_array['page_title'] = "Camp Galileo Camper CSV Upload/Download " . $term . " " . $grades[1]['grades'][$grade]['level'];


			$content_array['sub_nav'] = self::cg_campers_csv_subnav(1, $grade, $term);
			$content_array['content'] .= gambaDirections::getDirections('cg_campers_csv_view');
			$csv_view = self::cg_campers_csv($term, $grade);
			$content_array['content'] .= <<<EOT
		<div class="panel">
		<form method="post" action="{$url}/enrollment/{$term}/cg_campers_edit/{$grade}/upload?csv_upload=true" name="update" enctype="multipart/form-data">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
			<h4>Upload CSV File</h4>
			<input type="hidden" name="camp" value="1" />
			<input type="hidden" name="sheet_id" value="{$enrollment['enrollment'][$grade]['sheet_id']}" />
			<p><input type="file" name="csv_file" /></p>
			<p><input type="submit" name="submit" value="Upload File" class="button small radius" /></p>
		</form>
		</div>
		<div class="panel">
			<h4>Download Latest CSV File</h4>
			<a href="{$url}/enrollment/{$term}/cg_campers_csv_download/{$grade}" class="button small radius success">Download</a>
		</div>
		<div class="panel">
EOT;
			$content_array['content'] .= nl2br($csv_view);
			$content_array['content'] .= <<<EOT
		</div>
EOT;
			return $content_array;
// 			$grades = gambaGrades::grade_list();
// 			echo "<pre>"; print_r($grades); echo "</pre>";
		}

		public static function cg_campers_csv_upload($array, $csv_file_data_array) {
// 			echo $csv_file_data_array[18506][1]; exit; die();
// 			echo "<pre>"; print_r($csv_file_data_array); echo "</pre>";
// 			exit; die();
			$grade = $array['grade'];
			$term = $array['term'];
			$grades = gambaGrades::grade_list();
			$terms = gambaTerm::terms();
// 			echo "<pre>"; print_r($terms); echo "</pre>";
			$theme_enroll_rotations = $terms[$term]['campg_enroll_rotations'];
			$pack_per_hide = $terms[$term]['campg_packper'];
// 			echo "<pre>"; print_r($array); echo "</pre>";
// 			echo "<pre>"; print_r($files); echo "</pre>";
// 			exit; die();
			$themes = gambaThemes::themes_by_camp(1, $term);
// 			echo "<pre>";
			//print_r($themes);
			$enrollment = self::cg_campers($term, $grade);
// 			print_r($enrollment);
			$cg_linked_themes = self::cg_linked_themes($term);
			//print_r($cg_linked_themes);
// 			echo "</pre>";
// 			die();

			$return_array['camp_type'] = 1;
			$return_array['sheet_id'] = $array['sheet_id'];
			$return_array['theme_enroll_rotations'] = $theme_enroll_rotations;
			$return_array['pack_per_hide'] = $pack_per_hide;
			$return_array['enrollment'][$grade]['grade'] = $grades[1]['grades'][$grade]['level'];
			$return_array['enrollment'][$grade]['term'] = $term;
			$return_array['enrollment'][$grade]['sheet_id'] = $array['sheet_id'];

			foreach($enrollment['enrollment'] as $key => $values) {

				foreach($values['locations'] as $id => $location) {
// 					echo "<p>$id</p>";
					$row_csv_file_data = $csv_file_data_array["$id"];
// 					echo "<pre>"; print_r($row_csv_file_data); echo "</pre>";
					if($location['location_id'] > 0) {
						$i = 0;
						// Location ID
						$i = 1;
						// Location Name

						$return_array['enrollment'][$grade]['locations'][$id]['location'] = self::enroll_extra_class($id, $term, $location['location'], "false");
						$return_array['enrollment'][$grade]['locations'][$id]['location_id'] = $id;
						$extra_class = Enrollment::where('id', $id)->select('extra_class')->first();
						$return_array['enrollment'][$grade]['locations'][$id]['extra_class'] = $extra_class['extra_class'];
						$i = 2;

						// If Theme Enroll Rotation is Turned Off
						if($theme_enroll_rotations != "true") {
							// Use If Pack Per is Not Hidden
							if($pack_per_hide != "true") {
								$return_array['enrollment'][$grade]['locations'][$id]['location_values']['max_enroll'] = $row_csv_file_data[$i];
								$i = $i + 1;
								$return_array['enrollment'][$grade]['locations'][$id]['location_values']['pack_per'] = $row_csv_file_data[$i] / 100;
								$i = $i + 2;
							}
							$return_array['enrollment'][$grade]['locations'][$id]['location_values']['rotations'] = $row_csv_file_data[$i];
							$i = $i + 1;
						} else {
							$i = 2;
						}

						foreach($cg_linked_themes['linked'] as $link_id => $themevalues) {
							$theme_id_first = $themevalues['themes'][0]['theme_id'];
							$theme_id_second = $themevalues['themes'][1]['theme_id'];
							$i = $i + 1;

							// If Theme Enroll Rotation is Turned On
							if($theme_enroll_rotations == "true") {
								// Use If Pack Per is Not Hidden
								if($pack_per_hide != "true") {
									$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_first]['max_enroll'] = $row_csv_file_data[$i];
									$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_second]['max_enroll'] = $row_csv_file_data[$i];
									$i = $i + 1;
									$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_first]['pack_per'] = $row_csv_file_data[$i] / 100;
									$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_second]['pack_per'] = $row_csv_file_data[$i] / 100;
									$i = $i + 1;
								}
								$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_first]['rev_enroll'] = $row_csv_file_data[$i];
								$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_second]['rev_enroll'] = $row_csv_file_data[$i];
								$i = $i + 1;
								$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_first]['rotations'] = $row_csv_file_data[$i];
								$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_second]['rotations'] = $row_csv_file_data[$i];
								$i = $i + 1;
							}

							$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_first]['instructors'] = $row_csv_file_data[$i];
							$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_second]['instructors'] = $row_csv_file_data[$i];
							$i = $i + 1;
							$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_first]['theme_weeks'] = $row_csv_file_data[$i];
							$return_array['enrollment'][$grade]['locations'][$id]['theme_values'][$link_id][$theme_id_second]['theme_weeks'] = $row_csv_file_data[$i];
							$i = $i + 1;
						}

						$return_array['enrollment'][$grade]['locations'][$id]['location_values']['num_classrooms'] = $row_csv_file_data[$i];
						$return_array['enrollment'][$grade]['locations'][$id]['location_values']['num_classrooms#'] = $i;
						$i = $i + 1;
						$return_array['enrollment'][$grade]['locations'][$id]['location_values']['kids_per_class'] = $row_csv_file_data[$i];
						$return_array['enrollment'][$grade]['locations'][$id]['location_values']['kids_per_class#'] = "$id - $i";
					}


				}

			}
// 			echo "<pre>"; print_r($return_array); echo "</pre>"; die();
			return $return_array;
// 			echo "<pre>"; print_r($return_array); echo "</pre>";
		}

		public static function cg_campers_csv($term, $grade) {
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($grade == "") { $grade = 1; }
			$cur_term = gambaTerm::year_by_status('C');
			$terms = gambaTerm::terms();
			$theme_enroll_rotations = $terms[$term]['campg_enroll_rotations'];
			$pack_per_hide = $terms[$term]['campg_packper'];
			$themes = gambaThemes::themes_by_camp(1, $term);
			$enrollment = self::cg_campers($term, $grade);
			$cg_linked_themes = self::cg_linked_themes($term);


			foreach($enrollment['enrollment'] as $key => $values) {

				$content .= <<<EOT
"Location ID","Location Name",
EOT;
				if($theme_enroll_rotations != "true") {
					if($pack_per_hide != "true") {
						$content .= <<<EOT
"Average Weekly Enrollment","Pack Percentage",
EOT;
									}
									$content .= <<<EOT
"Revised Average Weekly Enrollment","Rotations",
EOT;
				}
				$i = 1;
				foreach($cg_linked_themes['linked'] as $link_id => $themevalues) {
					$content .= <<<EOT
"{$themevalues['themes'][0]['theme_name']} & {$themevalues['themes'][1]['theme_name']}",
EOT;
					if($theme_enroll_rotations == "true") {
						if($pack_per_hide != "true") {
							$content .= <<<EOT
"Average Weekly Enrollment","Pack Percentage",
EOT;
						}
						$content .= <<<EOT
"Revised Average Weekly Enrollment","Rotations",
EOT;
					}
					$content .= <<<EOT
"Instructors","Theme Weeks",
EOT;
					$i++;
				}
				$content .= <<<EOT
"Classrooms","Max # of Kids per Class"

EOT;
				foreach($values['locations'] as $id => $location) {
					if($location['location_id'] > 0) {
					    $location_name = self::enroll_extra_class($id, $term, $location['location'], "false");
						if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
						$avg_weekly_enrollment = ceil($location['location_values']['max_enroll'] * $location['location_values']['pack_per']);
						$pack_per = $location['location_values']['pack_per'] * 100;
						$content .= <<<EOT
"{$id}","{$location_name}",
EOT;
						if($theme_enroll_rotations != "true") {
							if($pack_per_hide != "true") {
								$content .= <<<EOT
"{$location['location_values']['max_enroll']}","{$pack_per}",
EOT;
							}
							$content .= <<<EOT
"{$avg_weekly_enrollment}","{$location['location_values']['rotations']}",
EOT;
						}
						$i = 1;
						foreach($cg_linked_themes['linked'] as $link_id => $themevalues) {
							$content .= <<<EOT
"{$link_id}",
EOT;
							$theme_id = $themevalues['themes'][0]['theme_id'];
							if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
							$camp_weeks[$id] = $camp_weeks[$id] + $location['theme_values'][$link_id][$theme_id]['camper_weeks'];
							if($theme_enroll_rotations == "true") {
								if($pack_per_hide != "true") {
							$content .= <<<EOT
"{$location['theme_values'][$link_id][$theme_id]['max_enroll']}","{$location['theme_values'][$link_id][$theme_id]['pack_per']}",
EOT;
								}
								$content .= <<<EOT
"{$location['theme_values'][$link_id][$theme_id]['rev_enroll']}","{$location['theme_values'][$link_id][$theme_id]['rotations']}",
EOT;
							}
							$content .= <<<EOT
"{$location['theme_values'][$link_id][$theme_id]['instructors']}","{$location['theme_values'][$link_id][$theme_id]['theme_weeks']}",
EOT;
							$i++;
						}
						$content .= <<<EOT
"{$location['location_values']['num_classrooms']}","{$location['location_values']['kids_per_class']}"

EOT;
					}
				}

			}
			return $content;

		}

		public static function cg_campers_csv_download($term, $grade) {
			$grades = gambaGrades::grade_list();
			$file = "CampG-CSV_Season-{$term}_Grade-{$grades[1]['grades'][$grade]['level']}_DownloadDate-" . date("Y-m-d-His").".csv";
			header("Content-Type: text/csv; charset=utf-8");
			header("Content-Disposition: attachment;filename=" . urlencode($file));
			header("Content-Description: File Transfer");
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");

			print self::cg_campers_csv($term, $grade);
		}

		public static function cg_campers_view_edit($array, $csv_file_data_array) {
    		$url = url('/');
    		$user_id = Session::get('uid');
			$term = $array['term'];
			$grade = $array['grade'];
			$location_id = $array['location'];
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($array['csv_upload'] == "true") {
				$enrollment = self::cg_campers_csv_upload($array, $csv_file_data_array);
			} else {
				$enrollment = self::cg_campers($term, $grade);
			}

			$cg_linked_themes = self::cg_linked_themes($term);
			$terms = gambaTerm::terms();
			$theme_enroll_rotations = $terms[$term]['campg_enroll_rotations'];
			$pack_per_hide = $terms[$term]['campg_packper'];
			$themes = gambaThemes::themes_by_camp(1, $term);
			$content_array['page_title'] = "Edit Camp Galileo Camper Enrollment $term: ".$enrollment['enrollment'][$grade]['grade'];
// 			$content_array['content'] .= gambaDirections::getDirections('cg_campers_view');
			$content_array['content'] .= <<<EOT
		<script type="text/javascript">
		// Get this working one day
// 		$(function() {
// 			$('#quantity, #item_price') {
// 				var max_enroll = $("#maxenroll[1]").val();
// 				var pack_per = $("#packper[1]").val();
// 				var total = max_enroll * ( pack_per / 100);
// 				$("#revenroll[]").val(total);
// 			}
// 		});
		</script>

		<form method="post" action="{$url}/enrollment/{$term}/cg_campers_update/{$grade}" name="update">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
			<button type="submit" class="button small radius">Update Enrollment</button>
EOT;
			if($location_id != "") {
			$content_array['content'] .= <<<EOT
			<button class="toggle-rows button small radius">Toggle Hidden Rows</button>
EOT;
			}
			$content_array['content'] .= <<<EOT
			<input type="hidden" name="action" value="cg_campers_update" />
			<input type="hidden" name="term" value="{$term}" />
			<input type="hidden" name="grade" value="{$grade}" />
			<input type="hidden" name="camp" value="1" />
			<input type="hidden" name="sheet_id" value="{$enrollment['enrollment'][$grade]['sheet_id']}" />
			<table class="form">
				<thead>
					<tr>
						<th></th>
EOT;
			if($theme_enroll_rotations != "true") {
				if($pack_per_hide != "true") {
					$content_array['content'] .= <<<EOT
					<th></th>
					<th></th>
EOT;
				}
					$content_array['content'] .= <<<EOT
					<th></th>
					<th></th>
EOT;
			}
			$i = 1;
			foreach($cg_linked_themes['linked'] as $link_id => $themevalues) {
				if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
				$colspan = "";
				if($theme_enroll_rotations == "true" && $pack_per_hide == "true") { $colspan = "5"; }
				elseif($theme_enroll_rotations == "true" && $pack_per_hide != "true") { $colspan = "7"; }
				else { $colspan = "3"; }
				$content_array['content'] .= <<<EOT
						<th colspan="{$colspan}" class="table-header-theme-{$col_color}">{$themevalues['themes'][0]['theme_name']} & {$themevalues['themes'][1]['theme_name']}</th>
EOT;
				$i++;
			}
			$content_array['content'] .= <<<EOT
						<th colspan="4" class="center">TOTALS</th>
					</tr>
					<tr>
						<th></th>
EOT;
			if($theme_enroll_rotations != "true") {
				if($pack_per_hide != "true") {
					$content_array['content'] .= <<<EOT
						<th class="image"><img src="{$url}/img/header-avg-wkly-enroll.png" title="Average Weekly Enrollment (Formerly Max Enrollment)" style="width: 32px !important; height: 113px !important;" /></th>
						<th class="image"><img src="{$url}/img/header-pack-per.png" title="Pack Percentage" /></th>
EOT;
				}
				$content_array['content'] .= <<<EOT
						<th class="image"><img src="{$url}/img/header-rev-avg-wkly-enroll.png" title="Revised Average Weekly Enrollment" /></th>
						<th class="image"><img src="{$url}/img/header-rotations.png" title="Rotations" /></th>
EOT;
			}
			$i = 1;
			foreach($cg_linked_themes['linked'] as $link_id => $themevalues) {
				if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
				if($theme_enroll_rotations == "true") {
					if($pack_per_hide != "true") {
						$content_array['content'] .= <<<EOT
						<th class="table-header-theme-{$col_color} image"><img src="{$url}/img/header-avg-wkly-enroll.png" title="Average Weekly Enrollment (Formerly Max Enrollment)" style="width: 32px !important; height: 113px !important;" /></th>
						<th class="table-header-theme-{$col_color} image"><img src="{$url}/img/header-pack-per.png" title="Pack Percentage" /></th>
EOT;
					}
					if($pack_per_hide != "true") {
						$content_array['content'] .= <<<EOT
						<th class="table-header-theme-{$col_color} image"><img src="{$url}/img/header-rev-avg-wkly-enroll.png" title="Revised Average Weekly Enrollment" /></th>
EOT;
					} else {
						$content_array['content'] .= <<<EOT
						<th class="table-header-theme-{$col_color} image"><img src="{$url}/img/header-avg-wkly-enroll.png" title="Average Weekly Enrollment (Formerly Max Enrollment)" style="width: 32px !important; height: 113px !important;" /></th>
EOT;
					}
						$content_array['content'] .= <<<EOT
						<th class="table-header-theme-{$col_color} image"><img src="{$url}/img/header-rotations.png" title="Rotations" /></th>
EOT;
				}
				$content_array['content'] .= <<<EOT
						<th class="center table-header-theme-{$col_color} image"><img src="{$url}/img/header-instructors.png" title="Instructors" style="width: 32px !important; height: 113px !important;" /></th>
						<th class="center table-header-theme-{$col_color} image"><img src="{$url}/img/header-theme-weeks.png" title="Theme Weeks" /></th>
EOT;
						//<th class="center table-header-theme-{$col_color} image"><img src="{$url}/img/header-total-classes.png" title="Total Classes" /></th>
				$content_array['content'] .= <<<EOT
						<th class="center table-header-theme-{$col_color} image"><img src="{$url}/img/header-camper-weeks.png" title="Camper Weeks" /></th>
EOT;
				$i++;
			}
			$content_array['content'] .= <<<EOT
						<th><img src="{$url}/img/header-classrooms.png" title="Classrooms" /></th>
						<th><img src="{$url}/img/header-max-kids-per-class.png" title="Max # of Kids per Class" /></th>
						<th><img src="{$url}/img/header-class-totals.png" title="Class Totals" /></th>
						<th><img src="{$url}/img/header-camper-weeks.png" title="Camper Weeks" /></th>
					</tr>
				</thead>
				<tbody>
EOT;
			$a = 1;
			foreach($enrollment['enrollment'][$grade]['locations'] as $id => $location) {
				if($location['location_id'] > 0) {
					$location_name = $location['location']; if($location['extra_class'] == 2) { $location_name .= " - 2"; }
					if($location['extra_class'] == 0 || $location['extra_class'] == 1) { $dli_hidden = 0; } else { $dli_hidden = 1; }
					$row_css = ""; $readonly = "";
					if($location_id != "" && $id != $location_id) {
						$row_css = "toggle-row row-hidden";
						$readonly = " readonly";
					}
					if($location_id != "" && $id == $location_id) {
						$row_css = "row-shown";
					}
					$content_array['content'] .= <<<EOT
					<tr class="{$row_css}">
						<td class="row-location">{$location_name} <input type="hidden" name="dli[{$id}][dli]" value="{$dli_hidden}" /></td>
EOT;
					if($theme_enroll_rotations != "true") {
						if($pack_per_hide != "true") {
							if($location['location_values']['pack_per'] == "") { $pack_per = 100; } else { $pack_per = $location['location_values']['pack_per'] * 100; }
							$revised_enrollment = ceil($location['location_values']['max_enroll'] * $location['location_values']['pack_per']);
							$content_array['content'] .= <<<EOT
						<td class="input" title="Max Enrollment"><input type="text" name="locations[{$id}][max_enroll]"
									value="{$location['location_values']['max_enroll']}"
									id="maxenroll[{$a}]"{$readonly} /></td>
EOT;
							$content_array['content'] .= <<<EOT
						<td class="input" title="Pack Percentage"><input type="text" name="locations[{$id}][pack_per]"
									value="{$pack_per}"
									id="packper[{$a}]"{$readonly} /></td>
EOT;
						}
						if($theme_enroll_rotations != "true") {
							$content_array['content'] .= <<<EOT
						<td class="input" title="Revised Enrollment (Disabled)"><input type="text" value="{$revised_enrollment}"
									id="revenroll[{$a}]" readonly /></td>
EOT;
							$content_array['content'] .= <<<EOT
						<td class="input" title="Rotations"><input type="text" name="locations[{$id}][rotations]" value="{$location['location_values']['rotations']}"
									id="rotations[{$a}]"{$readonly} /></td>
EOT;
						}
					}
					$i = 1;
					foreach($cg_linked_themes['linked'] as $link_id => $themevalues) {
						if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
						$theme_id = $themevalues['themes'][0]['theme_id'];
						if($location['theme_values'][$link_id][$theme_id]['instructors'] == 0) { $location['theme_values'][$link_id][$theme_id]['instructors'] = 0; }
						if($location['theme_values'][$link_id][$theme_id]['theme_weeks'] == 0) { $location['theme_values'][$link_id][$theme_id]['theme_weeks'] = 0; }

						if($theme_enroll_rotations == "true") {
							if($pack_per_hide != "true") {

								if($location['theme_values'][$link_id][$theme_id]['pack_per'] == "") { $pack_per = 100; } else { $pack_per = $location['theme_values'][$link_id][$theme_id]['pack_per'] * 100; }
								$revised_enrollment = ceil($location['theme_values'][$link_id][$theme_id]['max_enroll'] * $location['theme_values'][$link_id][$theme_id]['pack_per']);
								$content_array['content'] .= <<<EOT
						<td class="input table-header-theme-{$col_color}" title="Max Enrollment"><input type="text" name="locations[{$id}][themelinks][{$link_id}][max_enroll]"
									value="{$location['theme_values'][$link_id][$theme_id]['max_enroll']}"
									id="maxenroll[{$a}]"{$readonly} /></td>
EOT;
								$content_array['content'] .= <<<EOT
						<td class="input table-header-theme-{$col_color}" title="Pack Percentage"><input type="text" name="locations[{$id}][themelinks][{$link_id}][pack_per]"
									value="{$pack_per}"
									id="packper[{$a}]"{$readonly} /></td>
EOT;
							}
							if($pack_per_hide != "true") {
								$content_array['content'] .= <<<EOT
						<td class="input table-header-theme-{$col_color}" title="Revised Enrollment (Disabled)"><input type="text" name="locations[{$id}][themelinks][{$link_id}][rev_enroll]" value="{$revised_enrollment}"
									id="revenroll[{$a}]" readonly /></td>
EOT;
							} else {
								$content_array['content'] .= <<<EOT
						<td class="input table-header-theme-{$col_color}" title="Revised Enrollment (Disabled)"><input type="text" name="locations[{$id}][themelinks][{$link_id}][rev_enroll]" value="{$location['theme_values'][$link_id][$theme_id]['rev_enroll']}"
									id="revenroll[{$a}]"{$readonly} /></td>
EOT;
							}
							$content_array['content'] .= <<<EOT
						<td class="input table-header-theme-{$col_color}" title="Rotations"><input type="text" name="locations[{$id}][themelinks][{$link_id}][rotations]" value="{$location['theme_values'][$link_id][$theme_id]['rotations']}"
									id="rotations[{$a}]"{$readonly} /></td>
EOT;
						}


						$content_array['content'] .= <<<EOT
						<td class="input table-header-theme-{$col_color}" title="Instructors"><input type="text" name="locations[{$id}][themelinks][{$link_id}][instructors]"
									value="{$location['theme_values'][$link_id][$theme_id]['instructors']}"
									id="instructors[{$a}][{$link_id}]"{$readonly} /></td>
EOT;
						$content_array['content'] .= <<<EOT
						<td class="input table-header-theme-{$col_color}" title="Theme Weeks"><input type="text" name="locations[{$id}][themelinks][{$link_id}][theme_weeks]"
									value="{$location['theme_values'][$link_id][$theme_id]['theme_weeks']}"
									id="themeweeks[{$a}][{$link_id}]"{$readonly} /></td>
EOT;
						/* $content_array['content'] .= <<<EOT
						<td class="input table-header-theme-{$col_color}" title="Theme Total Classes (Disabled)"><input type="text" value="{$location['theme_values'][$link_id][$theme_id]['total_classes']}"
									class="form-control" id="total_classes[{$a}][{$link_id}]" readonly /></td>
EOT; */
						$content_array['content'] .= <<<EOT
						<td class="input table-header-theme-{$col_color}" title="Theme Camper Weeks (Disabled)"><input type="text" value="{$location['theme_values'][$link_id][$theme_id]['camper_weeks']}"
									id="camperweeks[{$a}][{$link_id}]" readonly />
						</td>
EOT;
						$camp_weeks[$id] = $camp_weeks[$id] + $location['theme_values'][$link_id][$theme_id]['camper_weeks'];
						$i++;
					}
					$content_array['content'] .= <<<EOT
						<!-- Classrooms -->
						<td class="input"><input type="text" name="locations[{$id}][num_classrooms]" value="{$location['location_values']['num_classrooms']}"
									id="numclassrooms[{$a}]"{$readonly} /></td>
						<!-- Kids/Class (SSs) (Disabled) -->
						<td class="input"><input type="text" name="locations[{$id}][kids_per_class]" value="{$location['location_values']['kids_per_class']}"
									id="kidsperclass[{$a}]"{$readonly} /></td>
						<!-- Class Totals (Disabled) -->
						<td class="input"><input type="text" value="{$location['location_values']['class_totals']}" id="classtotals[{$a}]" readonly /></td>
						<!-- Total Camper Weeks (Disabled) -->
						<td class="input"><input type="text" value="{$location['location_values']['total_camp_weeks']}" id="campweeks[{$a}]" readonly /></td>
					</tr>
EOT;
					$a++;
				}
			}
						$content_array['content'] .= <<<EOT
				</tbody>
			</table>
			<button type="submit" class="button small radius">Update Enrollment</button>
EOT;
			if($location_id != "") {
			$content_array['content'] .= <<<EOT
			<button class="toggle-rows button small radius">Toggle Hidden Rows</button>
EOT;
			}
			if($user_id == 1) {
				$content_array['content'] .= <<<EOT
			<p><input type="checkbox" name="debug" value="1" /> Debug: Check off box to check calculation.</p>
EOT;
			}
			$content_array['content'] .= <<<EOT
		</form>
EOT;
			$content_array['footer_js'] .= <<<EOT
		<script type="text/javascript">
			$(document).ready(function(){
				$(".toggle-rows").click(function(e){
					e.preventDefault();
					$(".toggle-row").toggleClass("row-hidden");
				});
			});
		</script>
EOT;
			$content_array['content'] .= gambaDebug::preformatted_arrays($enrollment['enrollment'][$grade], 'enrollment_by_grade', 'Enrollment');
			return  $content_array;
// 			echo "<pre>"; print_r($terms); echo "</pre>";
// 			echo "<pre>"; print_r($enrollment); echo "</pre>";

		}


		/**
		 * GSQ Camper Enrollment View
		 * @param unknown $term
		 */
		public static function gsq_campers_view($term) {
    		$url = url('/');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$enrollment = self::gsq_campers($term);
			$themes = gambaThemes::quick_themes_by_camp(2, $term);
			$content_array['page_title'] = "Galileo Summer Quest Enrollment $term";
// 			$content_array['header_css'] .= <<<EOT
// 	<link rel="stylesheet" href="css/responsive-tables.css" />
// EOT;
// 			$content_array['header_js'] .= <<<EOT
// 	<script src="js/responsive-tables.js"></script>
// EOT;

			$content_array['header_js'] = <<<EOT
			<script language="JavaScript" type="text/javascript" src="{$url}/js/jquery.fixedheadertable.js"></script>
			<script>
// 				$(document).ready(function() {
// 					$('#enrollment').fixedHeaderTable({ footer: true, cloneHeadToFoot: false, fixedColumn: false, height: 500 });
// 				});
			</script>


EOT;
// 			$content_array['content'] .= gambaDirections::getDirections('gsq_campers_view');
			foreach($enrollment['enrollment'] as $key => $values) {
				$content_array['content'] .= <<<EOT
		<p><a href="{$url}/enrollment/{$term}/gsq_campers_edit?grade={$key}" class="button small success radius">Edit {$values['grade']} Enrollment</a>
			<a href="{$url}/enrollment/{$term}/gsq_campers_csv_view?grade={$key}&sheet_id={$enrollment['sheet_id']}" class="button small success radius">CSV {$values['grade']} Download/Upload</a></p>
		<script>
// 		$(function(){
// 		    $("table").tablesorter({
// 				widgets: [ 'stickyHeaders' ],
// 				widgetOptions: { stickyHeaders_offset : 50, },
// 			});
// 			$("table").data("sorter", false);
// 		 });
		</script>
		<a name="{$key}"></a>
		<table id="enrollment" class="responsive" role="grid">
				<tr>
					<th colspan="2"></th>
EOT;
				$i = 1;
				foreach($themes as $theme_id => $theme_name) {
					if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
					$content_array['content'] .= <<<EOT
					<th colspan="6" class="center table-header-theme-{$col_color}" title="{$theme_id}">{$theme_name}</th>
EOT;
					$i++;
				}
				$content_array['content'] .= <<<EOT
					<th colspan="6" class="center">TOTALS</th>
				</tr>
				<tr>
					<th colspan="2"></th>
EOT;
				$i = 1;
				foreach($themes as $theme_id => $theme_name) {
					if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
					$content_array['content'] .= <<<EOT
					<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-enrollment-sessions.png" style="width: 35px !important; height: 82px !important;" title="Enrollments per Session" /></th>
					<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-num-weeks.png" style="width: 35px !important; height: 82px !important;" title="# Weeks" /></th>
					<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-total-enrollments.png" style="width: 35px !important; height: 82px !important;" title="Total Enrollments" /></th>
					<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-max-campers-per-class.png" style="width: 35px !important; height: 82px !important;" title="Max Campers/Class" /></th>
					<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-num-classrooms.png" style="width: 35px !important; height: 82px !important;" title="# Classrooms" /></th>
					<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-num-instructors.png" style="width: 35px !important; height: 82px !important;" title="# Instructors" /></th>
EOT;
					$i++;
				}
				$content_array['content'] .= <<<EOT
					<td><img src="{$url}/img/header-enrollment-sessions.png" style="width: 35px !important; height: 82px !important;" title="Enrollments per Session" /></td>
					<th><img src="{$url}/img/header-num-weeks.png" style="width: 35px !important; height: 82px !important;" title="# Weeks" /></th>
					<th><img src="{$url}/img/header-total-enrollments.png" style="width: 35px !important; height: 82px !important;" title="Total Enrollments" /></th>
					<th><img src="{$url}/img/header-max-campers-per-class.png" style="width: 35px !important; height: 82px !important;" title="Max Campers/Class" /></th>
					<th><img src="{$url}/img/header-num-classrooms.png" style="width: 35px !important; height: 82px !important;" title="# Classrooms" /></th>
					<th><img src="{$url}/img/header-num-instructors.png" style="width: 35px !important; height: 82px !important;" title="# Instructors" /></th>
				</tr>
EOT;
				foreach($values['locations'] as $id => $location) {
					$content_array['content'] .= <<<EOT
				<tr>
					<td><a href="{$url}/enrollment/{$term}/gsq_campers_edit?grade={$key}&location_id={$id}" class="button success radius small">Edit</a></td>
					<td title="{$id}">{$location['location']}</td>
EOT;
					$i = 1;
					foreach($themes as $theme_id => $theme_name) {
						if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
						$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center table-header-theme-{$col_color}">
						{$location['values'][$theme_id]['location_values']['enroll_per_session']}
					</td>
EOT;
						$col_total[$grade_id][$theme_id]['enroll_per_session'] += $location['values'][$theme_id]['location_values']['enroll_per_session'];
						$row_total[$grade_id][$id]['enroll_per_session'] += $location['values'][$theme_id]['location_values']['enroll_per_session'];
						$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center table-header-theme-{$col_color}">
						{$location['values'][$theme_id]['location_values']['sessions']}
					</td>
EOT;
						$col_total[$grade_id][$theme_id]['sessions'] += $location['values'][$theme_id]['location_values']['sessions'];
						$row_total[$grade_id][$id]['sessions'] += $location['values'][$theme_id]['location_values']['sessions'];
						$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center table-header-theme-{$col_color}">
						{$location['values'][$theme_id]['location_values']['tot_enrollments']}
					</td>
EOT;
						$col_total[$grade_id][$theme_id]['tot_enrollments'] += $location['values'][$theme_id]['location_values']['tot_enrollments'];
						$row_total[$grade_id][$id]['tot_enrollments'] += $location['values'][$theme_id]['location_values']['tot_enrollments'];
						$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center table-header-theme-{$col_color}">
						{$location['values'][$theme_id]['location_values']['campers']}
					</td>
EOT;
						$col_total[$grade_id][$theme_id]['campers'] += $location['values'][$theme_id]['location_values']['campers'];
						$row_total[$grade_id][$id]['campers'] += $location['values'][$theme_id]['location_values']['campers'];
						$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center table-header-theme-{$col_color}">
						{$location['values'][$theme_id]['location_values']['num_classrooms']}
					</td>
EOT;
						$col_total[$grade_id][$theme_id]['num_classrooms'] += $location['values'][$theme_id]['location_values']['num_classrooms'];
						$row_total[$grade_id][$id]['num_classrooms'] += $location['values'][$theme_id]['location_values']['num_classrooms'];
						$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center table-header-theme-{$col_color}">
						{$location['values'][$theme_id]['location_values']['instructors']}
					</td>
EOT;
						$col_total[$grade_id][$theme_id]['instructors'] += $location['values'][$theme_id]['location_values']['instructors'];
						$row_total[$grade_id][$id]['instructors'] += $location['values'][$theme_id]['location_values']['instructors'];
						$i++;
					}
					$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center">
						{$row_total[$grade_id][$id]['enroll_per_session']}
					</td>
EOT;
					$grade_col_total[$grade]['enroll_per_session'] += $row_total[$grade_id][$id]['enroll_per_session'];
					$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center">
						{$row_total[$grade_id][$id]['sessions']}
					</td>
EOT;
					$grade_col_total[$grade]['sessions'] += $row_total[$grade_id][$id]['sessions'];
					$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center">
						{$row_total[$grade_id][$id]['tot_enrollments']}
					</td>
EOT;
					$grade_col_total[$grade]['tot_enrollments'] += $row_total[$grade_id][$id]['tot_enrollments'];
					$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center">
						{$row_total[$grade_id][$id]['campers']}
					</td>
EOT;
					$grade_col_total[$grade]['campers'] += $row_total[$grade_id][$id]['campers'];
					$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center">
						{$row_total[$grade_id][$id]['num_classrooms']}
					</td>
EOT;
						$grade_col_total[$grade]['num_classrooms'] += $row_total[$grade_id][$id]['num_classrooms'];
					$content_array['content'] .= <<<EOT
					<td title="{$location['location']}" class="center">
						{$row_total[$grade_id][$id]['instructors']}
					</td>
				</tr>
EOT;
					$grade_col_total[$grade]['instructors'] += $row_total[$grade_id][$id]['instructors'];
				}
				$content_array['content'] .= <<<EOT
				<tr>
					<th />
					<th>Theme Totals</th>
EOT;
				$i = 1;
				foreach($themes as $theme_id => $theme_name) {
					if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
					$content_array['content'] .= <<<EOT
					<th class="center table-header-theme-{$col_color}">
						{$col_total[$grade_id][$theme_id]['enroll_per_session']}
					</th>
					<th class="center table-header-theme-{$col_color}">
						{$col_total[$grade_id][$theme_id]['sessions']}
					</th>
					<th class="center table-header-theme-{$col_color}">
						{$col_total[$grade_id][$theme_id]['tot_enrollments']}
					</th>
					<th class="center table-header-theme-{$col_color}">
						{$col_total[$grade_id][$theme_id]['campers']}
					</th>
					<th class="center table-header-theme-{$col_color}">
						{$col_total[$grade_id][$theme_id]['num_classrooms']}
					</th>
					<th class="center table-header-theme-{$col_color}">
						{$col_total[$grade_id][$theme_id]['instructors']}
					</th>
EOT;
					$i++;
				}
				$content_array['content'] .= <<<EOT
					<th class="center">{$grade_col_total[$grade]['enroll_per_session']}</th>
					<th class="center">{$grade_col_total[$grade]['sessions']}</th>
					<th class="center">{$grade_col_total[$grade]['tot_enrollments']}</th>
					<th class="center">{$grade_col_total[$grade]['campers']}</th>
					<th class="center">{$grade_col_total[$grade]['num_classrooms']}</th>
					<th class="center">{$grade_col_total[$grade]['instructors']}</th>
				</tr>
		</table>
EOT;
			}
			$content_array['content'] .= gambaDebug::preformatted_arrays($enrollment, 'enrollment', 'Enrollment');
			return $content_array;
 			//echo "<pre>"; print_r($enrollment); echo "</pre>";
		}


		/**
		 * GSQ Camper Enrollment Edit
		 * @param unknown $term
		 */
		public static function gsq_campers_view_edit($array, $csv_file_data_array) {
    		$url = url('/');
			$term = $array['term'];
			$grade = $array['grade'];
			$location_id = $array['location_id'];
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($array['csv_upload'] == "true") {
				$enrollment = self::gsq_campers_csv_upload($array, $csv_file_data_array);
			} else {
				$enrollment = self::gsq_campers($term);
			}
			$themes = gambaThemes::quick_themes_by_camp(2, $term);
			$content_array['page_title'] .= "Galileo Summer Quest Enrollment $term Edit";
// 			$content_array['content'] .= gambaDirections::getDirections('gsq_campers_edit');
			foreach($enrollment['enrollment'] as $key => $values) {
				$content_array['content'] .= <<<EOT
		<form method="post" action="{$url}/enrollment/{$term}/gsq_campers_update" name="update">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
			<button type="submit" class="button small radius">Update Enrollment</button>
EOT;
			if($location_id != "") {
			$content_array['content'] .= <<<EOT
			<button class="toggle-rows button small radius">Toggle Hidden Rows</button>
EOT;
			}
			$content_array['content'] .= <<<EOT
			<input type="hidden" name="action" value="gsq_campers_update" />
			<input type="hidden" name="term" value="{$term}" />
			<input type="hidden" name="grade" value="{$grade}" />
			<input type="hidden" name="sheet_id" value="{$enrollment['sheet_id']}" />
			<input type="hidden" name="camp" value="2" />
			<table class="form" role="grid">
				<thead>
					<tr>
						<th></th>
EOT;
				$i = 1;
				foreach($themes as $theme_id => $theme_name) {
					if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
					$content_array['content'] .= <<<EOT
						<th colspan="6" class="center table-header-theme-{$col_color}" title="{$theme_id}">{$theme_name}</th>
EOT;
					$i++;
				}
				$content_array['content'] .= <<<EOT
					</tr>
					<tr>
						<th></th>
EOT;
				$i = 1;
				foreach($themes as $theme_id => $theme_name) {
					if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
					$content_array['content'] .= <<<EOT
						<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-enrollment-sessions.png" style="width: 35px !important; height: 82px !important;" title="Enrollments per Session" /></th>
						<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-num-weeks.png" style="width: 35px !important; height: 82px !important;" title="# Weeks" /></th>
						<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-total-enrollments.png" style="width: 35px !important; height: 82px !important;" title="Total Enrollments" /></th>
						<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-max-campers-per-class.png" style="width: 35px !important; height: 82px !important;" title="Max Campers/Class" /></th>
						<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-num-classrooms.png" style="width: 35px !important; height: 82px !important;" title="# Classrooms" /></th>
						<th class="table-header-theme-{$col_color}"><img src="{$url}/img/header-num-instructors.png" style="width: 35px !important; height: 82px !important;" title="# Instructors" /></th>
EOT;
					$i++;
				}
				$content_array['content'] .= <<<EOT
					</tr>
				</thead>
				<tbody>
EOT;
				foreach($values['locations'] as $id => $location) {
					$row_css = ""; $readonly = "";
					if($location_id != "" && $id != $location_id) {
						$row_css = "toggle-row row-hidden";
						$readonly = " readonly";
					}
					if($location_id != "" && $id == $location_id) {
						$row_css = "row-shown";
					}
					$content_array['content'] .= <<<EOT
					<tr class="{$row_css}">
						<td title="{$id}" class="row-location">{$location['location']}</td>
EOT;
					$i = 1;
					foreach($themes as $theme_id => $theme_name) {
						//if($i == 6) { break; }
						if($i % 2 == 0) { $col_color = "even"; } else { $col_color = "odd"; }
						$content_array['content'] .= <<<EOT
						<td class="input table-header-theme-{$col_color}">
							<input type="text" id="enrollpersession{$id}{$theme_id}" name="locations[{$id}][{$theme_id}][location_values][enroll_per_session]" value="{$location['values'][$theme_id]['location_values']['enroll_per_session']}" size="4"{$readonly} />
						</td>
						<td class="input table-header-theme-{$col_color}">
							<input type="text" id="sessions{$id}{$theme_id}" name="locations[{$id}][{$theme_id}][location_values][sessions]" value="{$location['values'][$theme_id]['location_values']['sessions']}"{$readonly} />
						</td>
						<td class="input table-header-theme-{$col_color}">
							<script type="text/javascript">
							$(document).ready(function(){
								$('#enrollpersession{$id}{$theme_id}, #sessions{$id}{$theme_id}').change(function(){
									var enrollpersession = parseFloat($('#enrollpersession{$id}{$theme_id}').val()) || 0;
									var enrollsessions = parseFloat($('#sessions{$id}{$theme_id}').val()) || 0;
									$('#total{$id}{$theme_id}').val(enrollpersession * enrollsessions);
								 });
							 });
							 </script>
EOT;
						$orig_total = $location['values'][$theme_id]['location_values']['sessions'] * $location['values'][$theme_id]['location_values']['enroll_per_session'];
						if($location['values'][$theme_id]['location_values']['tot_enrollments'] == "" || $location['values'][$theme_id]['location_values']['tot_enrollments'] != $orig_total) {
							$location['values'][$theme_id]['location_values']['tot_enrollments'] = $location['values'][$theme_id]['location_values']['sessions'] * $location['values'][$theme_id]['location_values']['enroll_per_session'];
						}
						$instructors = intval($location['values'][$theme_id]['location_values']['instructors']);
						$content_array['content'] .= <<<EOT
							<input type="text" name="locations[{$id}][{$theme_id}][location_values][tot_enrollments]" value="{$location['values'][$theme_id]['location_values']['tot_enrollments']}" id="total{$id}{$theme_id}" readonly />
						</td>
						<td class="input table-header-theme-{$col_color}">
							<input type="text" name="locations[{$id}][{$theme_id}][location_values][campers]" value="{$location['values'][$theme_id]['location_values']['campers']}"{$readonly} />
						</td>
						<td class="input table-header-theme-{$col_color}">
							<input type="text" name="locations[{$id}][{$theme_id}][location_values][num_classrooms]" value="{$location['values'][$theme_id]['location_values']['num_classrooms']}"{$readonly} />
						</td>
						<td class="input table-header-theme-{$col_color}">
							<input type="text" name="locations[{$id}][{$theme_id}][location_values][instructors]" value="{$instructors}"{$readonly} />
						</td>
EOT;
						$i++;
					}
					$content_array['content'] .= <<<EOT
					</tr>
EOT;
				}
				$content_array['content'] .= <<<EOT

				</tbody>
			</table>
			<button type="submit" class="button small radius">Update Enrollment</button>
EOT;
			if($location_id != "") {
			$content_array['content'] .= <<<EOT
			<button class="toggle-rows button small radius">Toggle Hidden Rows</button>
EOT;
			}
			$content_array['content'] .= <<<EOT
		</form>
EOT;
			}
			$content_array['footer_js'] .= <<<EOT
		<script type="text/javascript">
			$(document).ready(function(){
				$(".toggle-rows").click(function(e){
					e.preventDefault();
					$(".toggle-row").toggleClass("row-hidden");
				});
			});
		</script>
EOT;
			return $content_array;
// 			echo "<pre>"; print_r($array); echo "</pre>";
// 			echo "<pre>"; print_r($enrollment); echo "</pre>";
		}





		public static function gsq_campers_csv_view($term, $grade, $sheet_id) {
    		$url = url('/');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($grade == "") { $grade = 11; }
			$grades = gambaGrades::grade_list();
// 			$cur_term = gambaTerm::year_by_status('C');
// 			$terms = gambaTerm::terms();

// 			$themes = gambaThemes::themes_by_camp(2, $term);
			$content_array['page_title'] = "GSQ Camper CSV Upload/Download $term {$grades[2]['grades'][11]['level']}";
			$content_array['sub_nav'] = <<<EOT
			<dl class="sub-nav">
				<dt>CSV View:</dt>
				<dd><a href="{$url}/enrollment/{$term}/gsq_campers">Return to {$term} GSQ Enrollment</a></dd>
			</dl>
EOT;
			$content_array['content'] .= gambaDirections::getDirections('gsq_campers_csv_view');
			$csv_view = self::gsq_campers_csv($term);
			// enroll_php?action=gsq_campers_edit&term=2016&grade=11&csv_upload=true
			// <input type="hidden" name="action" value="gsq_campers_csv_upload" />
			$content_array['content'] .= <<<EOT
		<div class="panel">
		<form method="post" action="{$url}/enrollment/{$term}/gsq_campers_edit/upload?action=grade=11&csv_upload=true" name="update" enctype="multipart/form-data">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
			<h4>Upload CSV File</h4>
			<input type="hidden" name="term" value="{$term}" />
			<input type="hidden" name="grade" value="{$grade}" />
			<input type="hidden" name="camp" value="2" />
			<input type="hidden" name="sheet_id" value="{$sheet_id}" />
			<p><input type="file" name="csv_file" required /></p>
			<p><input type="submit" name="submit" value="Upload File" class="button small radius" /></p>
		</form>
		</div>
		<div class="panel">
			<h4>Download Latest CSV File</h4>
			<a href="{$url}/enrollment/gsq_campers_csv_download?term={$term}" class="button small radius success">Download</a>
		</div>
		<div class="panel">
EOT;
			$content_array['content'] .= nl2br($csv_view);
			$content_array['content'] .= <<<EOT
		</div>
EOT;
			return $content_array;
// 			$grades = gambaGrades::grade_list();
// 			echo "<pre>"; print_r($csv_view); echo "</pre>";
		}

		public static function gsq_campers_csv_upload($array, $csv_file_data_array) {
			$grade = $array['grade'];
			$term = $array['term'];
			$grades = gambaGrades::grade_list();
			$terms = gambaTerm::terms();
// 			echo "<pre>"; print_r($terms); echo "</pre>";
			$theme_enroll_rotations = $terms[$term]['campg_enroll_rotations'];
			$pack_per_hide = $terms[$term]['campg_packper'];
// 			echo "<pre>"; print_r($array); echo "</pre>";

// 			exit; die();
			$enrollment = self::gsq_campers($term);
// 			echo "<pre>"; print_r($enrollment); echo "</pre>";
			$themes = gambaThemes::quick_themes_by_camp(2, $term);
// 			echo "<pre>"; print_r($themes); echo "</pre>";

// 			$return_array['enrollment'][$grade]['grade'] = $grades[2]['grades'][$grade]['level'];
// 			$return_array['camp_type'] = 2;
// 			$return_array['sheet_id'] = $array['sheet_id'];
// 			$return_array['term'] = $term;

// 			echo "<pre>"; print_r($new_array[36]); echo "</pre>";
			$return_array['term'] = $term;
			$return_array['sheet_id'] = $array['sheet_id'];
			$return_array['camp_type'] = 2;
			foreach($enrollment['enrollment'] as $key => $values) {
				foreach($values['locations'] as $id => $location) {
					$i = 0;
					// Location ID
					$i = 1;
					// Location Name

					// $return_array['enrollment'][$grade]['locations'][$id]['location'] = $location['location'];
					$return_array['enrollment'][$grade]['locations'][$id]['location'] = $csv_file_data_array[$id][1];
					$return_array['enrollment'][$grade]['locations'][$id]['enroll_id'] = $location['enroll_id'];

					$i = 2;
// 					$return_array['enrollment'][$grade]['locations'][$id]['row'] = $csv_file_data_array[$id];
					foreach($themes as $theme_id => $theme_name) {
						// CSV Theme ID
						$i = $i + 1;
						$return_array['enrollment'][$grade]['locations'][$id]['values'][$theme_id]['location_values']['enroll_per_session'] = $csv_file_data_array[$id][$i];
						$i = $i + 1;
						$return_array['enrollment'][$grade]['locations'][$id]['values'][$theme_id]['location_values']['sessions'] = $csv_file_data_array[$id][$i];
						$i = $i + 1;
						$return_array['enrollment'][$grade]['locations'][$id]['values'][$theme_id]['location_values']['campers'] = $csv_file_data_array[$id][$i];
						$i = $i + 1;
						$return_array['enrollment'][$grade]['locations'][$id]['values'][$theme_id]['location_values']['num_classrooms'] = $csv_file_data_array[$id][$i];
						$i = $i + 1;
						$return_array['enrollment'][$grade]['locations'][$id]['values'][$theme_id]['location_values']['instructors'] = $csv_file_data_array[$id][$i];
						$i = $i + 1;
					}
				}
			}


// 			echo "<p>Hello World</p>";
// 			echo "<pre>"; print_r($return_array); echo "</pre>";
// 			exit; die();
			return $return_array;
		}

		public static function gsq_campers_csv($term) {
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$enrollment = self::gsq_campers($term);
			$themes = gambaThemes::quick_themes_by_camp(2, $term);

			foreach($enrollment['enrollment'] as $key => $values) {
					$content .= <<<EOT
"",""
EOT;
				foreach($themes as $theme_id => $theme_name) {
					$content .= <<<EOT
,"{$theme_name}","Enrollments per Session","# Weeks","Max Campers/Class","# Classrooms","# Instructors"
EOT;
					$i++;
				}
				foreach($values['locations'] as $id => $location) {
					$content .= <<<EOT

"{$id}","{$location['location']}"
EOT;
					foreach($themes as $theme_id => $theme_name) {
						$content .= <<<EOT
,"$theme_id","{$location['values'][$theme_id]['location_values']['enroll_per_session']}",
EOT;
						$content .= <<<EOT
"{$location['values'][$theme_id]['location_values']['sessions']}",
EOT;
						$content .= <<<EOT
"{$location['values'][$theme_id]['location_values']['campers']}",
EOT;
						$content .= <<<EOT
"{$location['values'][$theme_id]['location_values']['num_classrooms']}",
EOT;
						$content .= <<<EOT
"{$location['values'][$theme_id]['location_values']['instructors']}"
EOT;
					}
				}
			}
			return $content;

		}

		public static function gsq_campers_csv_download($term) {
			$grades = gambaGrades::grade_list();
			$file = "GSQ-CSV_Season-{$term}_DownloadDate-" . date("Y-m-d-His").".csv";
			header("Content-Type: text/csv; charset=utf-8");
			header("Content-Disposition: attachment;filename=" . urlencode($file));
			header("Content-Description: File Transfer");
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");

			print self::gsq_campers_csv($term);
		}

		public static function cg_ext_view_edit($term) {
    		$url = url('/');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$ext_enrollment = self::ext_enrollment($term, 1);
			$content_array['page_title'] = "Camp Galileo PM Extended Care Enrollment $term";
			$locations = gambaLocations::locations_by_camp();
// 			$content_array['content'] .= gambaDirections::getDirections('cg_ext_edit');
			$camp_size = gambaAdmin::config_val('cg_campsize');
			$content_array['content'] .= <<<EOT
		<form method="post" action="{$url}/enrollment/ext_update" name="update">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
			<input type="hidden" name="term" value="{$term}" />
			<input type="hidden" name="camp" value="1" />
			<table class="table-hover table-bordered table-striped table-condensed table-small">
				<thead>
					<tr>
						<th></th>
						<th class="center">PM Enrollment/Session</th>
						<th class="center">Total Sessions</th>
						<th class="center">Total Enrollments</th>
					</tr>
				</thead>
				<tbody>
EOT;
			foreach($locations['locations'][1] as $location_id => $info) {
				if($info['terms'][$term]['active'] == "Yes") {
					if($ext_enrollment['locations'][$location_id]['location_values']['pm_enrollment_session'] < $camp_size) { $cg_tot_sm_camps = $cg_tot_sm_camps + 1; }
					if($ext_enrollment['locations'][$location_id]['location_values']['pm_enrollment_session'] >= $camp_size) { $cg_tot_lg_camps = $cg_tot_lg_camps + 1; }
					$camper_weeks = $num_campers * $camp_wks;
					$cg_pm_enroll_sess += $ext_enrollment['locations'][$location_id]['location_values']['pm_enrollment_session'];
					$cg_total_sessions += $ext_enrollment['locations'][$location_id]['location_values']['total_sessions'];
					$cg_total_enroll += $ext_enrollment['locations'][$location_id]['location_values']['total_enrollment'];
					$content_array['content'] .= <<<EOT
					<tr>
						<td>{$info['name']}</td>
						<td class="input"><input type="text" name="location[{$location_id}][pm_enrollment_session]" value="{$ext_enrollment['locations'][$location_id]['location_values']['pm_enrollment_session']}" /></td>
						<td class="input"><input type="text" name="location[{$location_id}][total_sessions]" value="{$ext_enrollment['locations'][$location_id]['location_values']['total_sessions']}" /></td>
						<td class="center">{$ext_enrollment['locations'][$location_id]['location_values']['total_enrollment']}</td>
					</tr>
EOT;
				}
			}
			$content_array['content'] .= <<<EOT
					<tr>
						<td><strong>Total</strong></td>
						<td align="center">{$cg_pm_enroll_sess}</td>
						<td align="center">{$cg_total_sessions}</td>
						<td align="center">{$cg_total_enroll}</td>
					</tr>
					<tr>
						<td>Large Camps ({$camp_size}+)</td>
						<td align="center">{$cg_tot_lg_camps}</td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td>Small Camps (&lt;{$camp_size})</td>
						<td align="center">{$cg_tot_sm_camps}</td>
						<td></td>
						<td></td>
					</tr>
				</tbody>
			</table>
			<p><button type="submit" class="button small radius">Update Enrollment</button></p>
		</form>
EOT;
			return $content_array;
// 			gambaDebug::preformatted_arrays($ext_enrollment, "ext_enrollment", "Camp G Ext Enrollment");
// 			gambaDebug::preformatted_arrays($locations['locations'][1], "locations", "Locations");

		}

		public static function gsq_ext_view_edit($term) {
    		$url = url('/');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$ext_enrollment = self::ext_enrollment($term, 2);
			$content_array['page_title'] = "Galileo Summer Quest PM Extended Care Enrollment $term";
			$locations = gambaLocations::locations_by_camp();
// 			$content_array['content'] .= gambaDirections::getDirections('cg_ext_edit');
			$camp_size = gambaAdmin::config_val('cg_campsize');
			$content_array['content'] .= <<<EOT
		<form method="post" action="{$url}/enrollment/ext_update" name="update">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
			<input type="hidden" name="action" value="ext_update" />
			<input type="hidden" name="term" value="{$term}" />
			<input type="hidden" name="camp" value="2" />
			<table class="table-hover table-bordered table-striped table-condensed table-small">
				<thead>
					<tr>
						<th></th>
						<th class="center">PM Enrollment/Session</th>
						<th class="center">Total Sessions</th>
						<th class="center">Total Enrollments</th>
					</tr>
				</thead>
				<tbody>
EOT;
			foreach($locations['locations'][2] as $location_id => $info) {
				if($info['terms'][$term]['active'] == "Yes") {
					if($ext_enrollment['locations'][$location_id]['location_values']['pm_enrollment_session'] < $camp_size) { $cg_tot_sm_camps = $cg_tot_sm_camps + 1; }
					if($ext_enrollment['locations'][$location_id]['location_values']['pm_enrollment_session'] >= $camp_size) { $cg_tot_lg_camps = $cg_tot_lg_camps + 1; }
					$camper_weeks = $num_campers * $camp_wks;
					$cg_pm_enroll_sess += $ext_enrollment['locations'][$location_id]['location_values']['pm_enrollment_session'];
					$cg_total_sessions += $ext_enrollment['locations'][$location_id]['location_values']['total_sessions'];
					$cg_total_enroll += $ext_enrollment['locations'][$location_id]['location_values']['total_enrollment'];
					$content_array['content'] .= <<<EOT
					<tr>
						<td>{$info['name']}</td>
						<td class="input">
							<input type="text" name="location[{$location_id}][pm_enrollment_session]" value="{$ext_enrollment['locations'][$location_id]['location_values']['pm_enrollment_session']}" />
						</td>
						<td class="input">
							<input type="text" name="location[{$location_id}][total_sessions]" value="{$ext_enrollment['locations'][$location_id]['location_values']['total_sessions']}" />
						</td>
						<td class="center">
							{$ext_enrollment['locations'][$location_id]['location_values']['total_enrollment']}
						</td>
					</tr>
EOT;
				}
			}
			$content_array['content'] .= <<<EOT
					<tr>
						<td><strong>Total</strong></td>
						<td align="center">{$cg_pm_enroll_sess}</td>
						<td align="center">{$cg_total_sessions}</td>
						<td align="center">{$cg_total_enroll}</td>
					</tr>
					<tr>
						<td>Large Camps ({$camp_size}+)</td>
						<td align="center">{$cg_tot_lg_camps}</td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td>Small Camps (&lt;{$camp_size})</td>
						<td align="center">{$cg_tot_sm_camps}</td>
						<td></td>
						<td></td>
					</tr>
				</tbody>
			</table>
			<p><button type="submit" class="button small radius">Update Enrollment</button></p>
		</form>
EOT;
			return $content_array;
// 			gambaDebug::preformatted_arrays($ext_enrollment, "ext_enrollment", "GSQ Ext Enrollment");
// 			gambaDebug::preformatted_arrays($locations['locations'][1], "locations", "Locations");
		}

	}
