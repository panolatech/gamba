<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	
	use App\Models\StaffTypes;
	
	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaUsers;
	
	class gambaStaff {
		public static function staff_types() {
			$camps = gambaCampCategories::camps_list();
			$query = StaffTypes::select('id', 'name', 'camp_type', 'grade', 'ordering')->orderBy('camp_type')->orderBy('ordering')->get();
			$array['sql'] = \DB::last_query();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$camp = $row['camp_type'];
					$query2 = StaffTypes::select('id')->where('camp_type', $camp)->get();
					$array['staff'][$camp]['num_rows'] = $query2->count();
					$array['staff'][$camp]['camp_name'] = $camps[$camp]['name'];
					$array['staff'][$camp]['types'][$id]['name'] = $row['name'];
					$array['staff'][$camp]['types'][$id]['grade'] = $row['grade'];
					$array['staff'][$camp]['types'][$id]['ordering'] = $row['ordering'];
				}
			}
// 			echo "<pre>"; print_r($array); echo "</pre>"; 
			return $array;
		}

		public static function camps_with_stafftypes() {
			$query = StaffTypes::select(\DB::raw('DISTINCT gmb_stafftypes.camp_type AS camp, gmb_camps.alt_name AS name'))->leftjoin('camps', 'stafftypes.camp_type', '=', 'camps.id')->orderBy('camps.name')->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['camp'];
					$array[$id] = $row['name'];
				}
			}
			return $array;
// 			echo "<pre>"; print_r($array); echo "</pre>"; 
		}

		public static function camps_nav($camp) {
			if($camp == "") { $camp = 1; }
			$camps_array = self::camps_with_stafftypes();
				
			if(is_array($camps_array)) {
				$content .= '<dl class="sub-nav">';
				foreach($camps_array as $key => $value) {
					if(is_int($key)) {
						$content .= '<dd';
						if($camp == $key) { $content .= ' class="active"';  $return_camp = $camp; }
						$content .= '><a href="'.$url.'/settings/staff?action=staff&camp='.$key.'">'. $value. '</a></dd>';
					}
				}
				$content .= '</dl>';
			}
			return $content;
		}
		
		public static function term_dropdown($action, $term) {
			$url = url('/');
			$terms = gambaTerm::terms();	
			$current_term = gambaTerm::year_by_status('C');
			if($term == "") { $term = $current_term; }
			$content = <<<EOT

		  <button href="#" data-dropdown="drop1" aria-controls="drop1" aria-expanded="false" class="button dropdown">
		    Select Term ({$term})
		  </button><br />
		  <ul id="drop1" data-dropdown-content class="f-dropdown" aria-hidden="true">
EOT;
			foreach($terms as $year => $values) {
				$content .= <<<EOT
		    <li><a href="{$url}/staff/{$action}?action={$action}&term={$year}">{$year}</a></li>
EOT;
		    }
		    $content .= <<<EOT
		  </ul>
EOT;
		    return $content;
		}
		
		private static function staff_navigation($action, $term, $title) {
			if($term == "") { $term = gambaTerm::year_by_status('C'); }
			$content = <<<EOT
			<h1>{$title}</h1>
			<div class="row">
				<div class="small-12 medium-2 large-2 columns">
EOT;
			self::term_dropdown($action, $term);
			$content .= <<<EOT
				</div>
				<div class="small-12 medium-10 large-10 columns">
						<ul class="pagination pagination-lg">
							<li><a href="{$url}/staff?action=cg_staffing&term={$term}">Camp G Staffing</a></li>
							<li><a href="{$url}/staff?action=cg_assumptions&term={$term}">Camp G Staff Assumptions Training</a></li>
							<li><a href="{$url}/staff?action=cg_position&term={$term}">Camp G Staff Position Training</a></li>
							<li><a href="{$url}/staff?action=gsq_assumptions&term={$term}">GSQ Staffing</a></li>
							<li><a href="{$url}/staff?action=gsq_training&term={$term}">GSQ Curr Training Assumptions</a></li>		
							<li><a href="{$url}/staff?action=allstaff&term={$term}">All Staff Position Training</a></li>
						</ul>
					</div>
				</div>
EOT;
			return $content;
		}
		
		public static function staff_assumptions_array() {
			$array = array(
				'alt_kinder_lis', 
				'alt_grade1_lis', 
				'alt_grade3_lis', 
				'alt_tls',
				'artk_classrooms', 
				'art1_classrooms', 
				'art3_classrooms',
				'scik_classrooms', 
				'sci1_classrooms', 
				'sci3_classrooms',
				'outk_classrooms', 
				'out1_classrooms', 
				'out3_classrooms',
				'art_kinder_lis', 
				'sci_kinder_lis', 
				'out_kinder_lis',
				'art_grade1_lis', 
				'sci_grade1_lis', 
				'out_grade1_lis',
				'art_grade3_lis', 
				'sci_grade3_lis', 
				'out_grade3_lis'
			);
			return $array;
		}
		
		public static function default_page($array) {
			self::staff_navigation($array['action'], $array['term'], "Staff");
			$content_array['content'] .= "<p>Please select a term and a camp enrollment sheet.</p>";
			return $content_array;
		}
		
		private static function cg_staffing($array) {
			
		}
		
		public static function cg_staffing_edit($array) {
			self::staff_navigation($array['action'], $array['term'], "Camp Galileo Staffing ".$array['term']);
			$term = $array['term'];
		}
		
		public static function cg_assumptions_edit($array) {
			self::staff_navigation($array['action'], $array['term'], "Camp Galileo Staff Assumptions for Training Supplies ".$array['term']);
			
		}
		
		public static function cg_position_edit($array) {
			self::staff_navigation($array['action'], $array['term'], "Camp Galileo Staff Position Training ".$array['term']);
			
		}
		
		public static function gsq_assumptions_edit($array) {
			self::staff_navigation($array['action'], $array['term'], "Galileo Summer Quest Staffing Assumptions ".$array['term']);
			
		}
		
		public static function gsq_training_edit($array) {
			self::staff_navigation($array['action'], $array['term'], "Galileo Summer Quest Curriculum Training Assumptions ".$array['term']);
			
		}
		
		public static function allstaff_edit($array) {
			self::staff_navigation($array['action'], $array['term'], "All Staff Position Training ".$array['term']);
			
		}
		
		public static function data_ordering_staff_types($array) {
			$id = $array['id']; $camp = $array['camp']; $order = $array['order'];
			if($array['movement'] == "up") { $array['move'] = $move = $order - 1; }
			if($array['movement'] == "down") { $array['move'] = $move = $order + 1; }
			$displace = StaffTypes::where('ordering', $move)->where('camp_type', $camp)->update([
				'ordering' => $order
			]);
				$array['displace_sql'] = \DB::last_query();
			$update = StaffTypes::find($id);
				$update->ordering = $move;
				$update->save();
				$array['update_sql'] = \DB::last_query();
			$return['reorder'] = 1;
			return base64_encode(json_encode($return));
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
		}
		
		public static function data_update_staff_type($array) {
			$name = htmlspecialchars($array['name']);
			$id = $array['id'];
			$update = StaffTypes::find($id);
				$update->name = $name;
				$update->save();
				$return['sql'] = \DB::last_query();
			$return['updated'] = 1;
			$return['row_updated'] = $id;
			return base64_encode(json_encode($return));
		}
		
		public static function data_add_staff_type($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$name = htmlspecialchars($array['name']);
			$camp = $array['camp'];
			$ordering = $array['num_rows'] + self::num_rows($camp);
			$return['add_id'] = StaffTypes::insertGetId(['name' => $name, 'camp_type' => $camp, 'grade' => 0, 'ordering' => $ordering]);
			$return['sql'] = \DB::last_query();
			return base64_encode(json_encode($return));
		}
		
		public static function num_rows($camp) {
			$num_rows = StaffTypes::select('id')->where('camp_type', $camp)->get();
			return $num_rows->count();
		}
		
		public static function view_staff_types($camp, $return) {
			$url = url('/');
			$content_array['page_title'] = "Staff Types";
			if($camp == "") { $camp = 1; }
			$content_array['content'] .= gambaDirections::getDirections('staff_types_edit');
			$content_array['content'] .= self::camps_nav($camp);
			$camps = gambaCampCategories::camps_list();
			$staff_types = self::staff_types();
			if($return['reorder'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box('Data successfully reordered.', 'success');
			}
			if($return['row_updated'] != "") {
				$content_array['content'] .= gambaDebug::alert_box('Data successfully updated.', 'success');
			}
			if($return['add_id'] > 0) {
				$content_array['content'] .= gambaDebug::alert_box('Data successfully added.', 'success');
			}
			$num_rows = $staff_types['staff'][$camp]['num_rows'];
			$content_array['content'] .= <<<EOT
		<table>
			<thead>
				<tr>
					<th><a data-toggle="modal" href="{$url}/settings/staff/edit?action=staff_edit" class="button small success">Add</a></th>
					<th>Location</th>
					<th colspan="2">Ordering</th>
				</tr>
			</thead>
			<tbody>
EOT;
			$i = 1;
			foreach($staff_types['staff'][$camp]['types'] as $key => $values) {
				$row_update = ""; if($return['row_updated'] == $key || $return['add_id'] == $key) { $row_update = ' class="success"'; }
				$content_array['content'] .= <<<EOT
				<tr{$row_update}>
					<td><a data-toggle="modal" href="{$url}/settings/staff/edit?action=staff_edit&id={$key}" class="button small">Edit</a></td>
					<td>{$values['name']}</td>
					<td>
EOT;
				if($i > 1) { 
				$content_array['content'] .= <<<EOT
					<a href="{$url}/settings/staff/types_ordering?action=staff_types_ordering&id={$key}&movement=up&camp={$camp}&order={$values['ordering']}" class="button small">&#x25B2;</a>
EOT;
				}
				$content_array['content'] .= <<<EOT
					</td>
					<td>
EOT;
				if($i != $num_rows) {
				$content_array['content'] .= <<<EOT
					<a href="{$url}/settings/staff/types_ordering?action=staff_types_ordering&id={$key}&movement=down&camp={$camp}&order={$values['ordering']}" class="button small">&#x25BC;</a>
EOT;
				} 
				$content_array['content'] .= <<<EOT
							</td>
					
				</tr>
EOT;
				$i++;
			}
			$content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
			FixedWidthTwoColumn::template($content_array);
		}
		public static function data_form_all_staff($array, $return) {
			$action = $array['action'];
			$url = url('/');
			if($action == "staff_edit") {
				$values = StaffTypes::find($array['id'])->toArray();
				$content_array['page_title'] = "Edit Staff Type";
				$form_action = "update_staff_type";
				$form_button = "Save Changes";
			}
			if($action == "staff_add") {
				$content_array['page_title'] = "Add Staff Type";
				$form_action = "add_staff_type";
				$form_button = "Add Staff Type";
			}
			$content_array['content'] .= <<<EOT
				<form name="form-camp{$key}" class="form" action="{$url}/settings/staff/update_staff_type">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
							<label for="name">Staff Title</label>
							<input type="text" name="name" id="name" value="{$values['name']}" required />
						
					<p><button type="submit" class="button small">Save Changes</button><p>
							
					<input type="hidden" name="action" value="update_staff_type" />
					<input type="hidden" name="camp" value="{$values['camp_type']}" />
					<input type="hidden" name="id" value="{$array['id']}" />
				</form>
EOT;
			FixedWidthTwoColumn::template($content_array);
		}
		
	}
