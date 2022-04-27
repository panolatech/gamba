<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;
	
	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaGrades;
	use App\Gamba\gambaQuantityTypes;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaUsers;
	
	class gambaCostsNav {

		public static function theme_year_nav($camp, $term) {
			$url = url('/');
			$terms = gambaTerm::terms();
			$current_term = gambaTerm::year_by_status('C');
			if($term == "") { $term = $current_term; }
			$content .= "<dl class=\"sub-nav\"><dt>Select Term:</dt>";
			foreach($terms as $key => $value) {
				$active = ""; if($term == $key) { $active = ' class="active"'; }
				$content .= <<<EOT
				<dd{$active}><a href="{$url}/costs/themes_setup?camp={$camp}&term={$key}" title="{$value['year_status']}">{$key}</a></dd>
EOT;
			}
			$content .= '</dl>';
			return $content;
		}
		
		public static function theme_camp_nav($camp, $term) {
			$url = url('/');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($camp == "") { $camp = 1; }
			$camps = gambaCampCategories::camps_list();
			if(is_array($camps)) {
				$content .= "<dl class=\"sub-nav\"><dt>Select Category :</dt>";
				foreach($camps as $key => $value) {
					if($value['camp_values']['cost_analysis'] == "true") {
						$active = ""; if($camp == $key) { $active = ' class="active"'; }
						$content .= <<<EOT
				<dd{$active}><a href="{$url}/costs/themes_setup?camp={$key}&term={$term}">{$value['alt_name']}</a></dd>
EOT;
					}
				}
				$content .= '</dl>';
			}
			return $content;
		}
		
		public static function summaries_year_nav($array) {
			$url = url('/');
			$camp = $array['camp'];
			$term = $array['term'];
			$grade = $array['grade'];
			$terms = gambaTerm::terms();
			$current_term = gambaTerm::year_by_status('C');
			if($term == "") { $term = $current_term; }
			$content .= "<dl class=\"sub-nav\"><dt>Select Term:</dt>";
			foreach($terms as $key => $value) {
				$active = ""; if($term == $key) { $active = ' class="active"'; }
				$content .= <<<EOT
				<dd{$active}><a href="{$url}/costs/{$array['action']}?camp={$camp}&grade={$grade}&term={$key}" title="{$value['year_status']}">{$key}</a></dd>
EOT;
			}
			$content .= '</dl>';
			return $content;
		}
		
		public static function summaries_camp_nav($array) {
			$url = url('/');
			$camp = $array['camp'];
			$term = $array['term'];
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($camp == "") { $camp = 1; }
			$grades = gambaGrades::grade_list();
			$camps = gambaCampCategories::camps_list();
			if(is_array($camps)) {
				$content .= "<dl class=\"sub-nav\"><dt>Select Category :</dt>";
				// Camps with gambaGrades
				foreach($camps as $key => $value) {
					if($value['camp_values']['cost_analysis'] == "true" && $value['camp_values']['cost_grade_display'] == "true") {
						foreach($grades[$key]['grades'] as $grade_id => $grade_values) {
							$active = ""; if($camp == $key && $array['action'] == "summaries_campg" && $array['grade'] == $grade_id) { $active = ' class="active"'; }
							if($grade_values['enrollment'] == 1) {
								$content .= <<<EOT
								<dd{$active}><a href="{$url}/costs/summaries_campg?camp={$key}&grade={$grade_id}&term={$term}">{$value['alt_name']} {$grade_values['level']}</a></dd>
EOT;
							}
						}
					}
				}
				// Camps 
				foreach($camps as $key => $value) {
					if($value['camp_values']['cost_analysis'] == "true" && $value['camp_values']['cost_grade_display'] != "true" && $value['camp_values']['cost_non_curriculum'] != "true") {
						$active = ""; if($camp == $key) { $active = ' class="active"'; }
						$content .= <<<EOT
						<dd{$active}><a href="{$url}/costs/summaries_gsq?camp={$key}&term={$term}">{$value['alt_name']}</a></dd>
EOT;
					}
				}
				$active_all = ""; if($array['action'] == "summaries") { $active_all = ' class="active"'; }
				$active_non = ""; if($array['action'] == "summaries_noncurriculum") { $active_non = ' class="active"'; }
				$content .= <<<EOT
					<dd{$active_all}><a href="{$url}/costs/summaries?term={$term}">All (Summary)</a></dd>
					<dd{$active_non}><a href="{$url}/costs/summaries_noncurriculum?term={$term}">Non-Curriculum</a></dd>
				</dl>
EOT;
			}
			return $content;
		}
		
		/**
		 * Quantity Types Term Navigation
		 * @param unknown $camp
		 * @param unknown $term
		 * @return string
		 */
		public static function quantity_types_year_nav($camp, $term) {
			$url = url('/');
			$terms = gambaTerm::terms();
			$current_term = gambaTerm::year_by_status('C');
			if($term == "") { $term = $current_term; }
			$content .= "<dl class=\"sub-nav\"><dt>Select Term:</dt>";
			foreach($terms as $key => $value) {
				$active = ""; if($term == $key) { $active = ' class="active"'; }
				$content .= <<<EOT
				<dd{$active}><a href="{$url}/costs/quantity_type_setup?camp={$camp}&term={$key}" title="{$value['year_status']}">{$key}</a></dd>
EOT;
			}
			$content .= '</dl>';
			return $content;
		}

		/**
		 * Quantity Types Camp Navigation
		 * @param unknown $camp
		 * @param unknown $term
		 * @return string
		 */
		public static function quantity_types_camp_nav($camp, $term) {
			$url = url('/');
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			if($camp == "") { $camp = 1; }
			$camps_array = gambaQuantityTypes::camp_quantity_types();
			$camps = gambaCampCategories::camps_list();
			if(is_array($camps_array)) {
				$content .= "<dl class=\"sub-nav\"><dt>Select Category :</dt>";
				foreach($camps as $key => $value) {
					if($value['camp_values']['cost_analysis'] == "true") {
						$active = ""; if($camp == $key) { $active = ' class="active"'; }
						$content .= <<<EOT
				<dd{$active}><a href="{$url}/costs/quantity_type_setup?camp={$key}&term={$term}">{$value['alt_name']}</a></dd>
EOT;
					}
				}
				$content .= '</dl>';
			}
			return $content;
		}

		/**
		 * Year Navigation
		 * @param unknown $camp
		 * @param unknown $term
		 * @return string
		 */
		public static function year_nav($camp, $term) {
			$url = url('/');
			$terms = gambaTerm::terms();
			$current_term = gambaTerm::year_by_status('C');
			if($term == "") { $term = $current_term; }
			$content .= "<dl class=\"sub-nav\"><dt>Select Term:</dt>";
			foreach($terms as $key => $value) {
				$active = ""; if($term == $key) { $active = ' class="active"'; }
				$content .= <<<EOT
				<dd{$active}><a href="{$url}/costs/quantity_type_setup?camp={$camp}&term={$key}" title="{$value['year_status']}">{$key}</a></dd>
EOT;
			}
			$content .= '</dl>';
			return $content;
		}
		
		/**
		 * Camp Categories Nav
		 * @param unknown $array
		 * @return string
		 */
		public static function camps_list_nav($array) {
			$url = url('/');
			$content .= "<dl class=\"sub-nav\"><dt>Select Status:</dt>";
			
			$active = ""; if($array['status'] == "active" || $array['status'] == "") { $active = ' class="active"'; }
			$content .= <<<EOT
				<dd{$active}><a href="{$url}/costs/camp_list?status=active" title="Active">Active</a></dd>
EOT;
			$active = ""; if($array['status'] == "inactive") { $active = ' class="active"'; }
			$content .= <<<EOT
				<dd{$active}><a href="{$url}/costs/camp_list?status=inactive" title="Active">In-Active</a></dd>
EOT;
			
			$content .= '</dl>';
			return $content;
		}
		
	}