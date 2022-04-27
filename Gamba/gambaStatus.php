<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;

	use App\Models\Activities;
	use App\Models\Customers;
	use App\Models\Locations;
	use App\Models\Parts;
	use App\Models\Products;
	use App\Models\Supplies;
	use App\Models\ThemeLink;
	use App\Models\Themes;
	use App\Models\Vendors;

	use App\Gamba\gambaUsers;
	
	class gambaStatus {
		
		public static function camps_with_themes($term) {
			$query = Themes::select(\DB::raw('DISTINCT gmb_themes.camp_type, gmb_camps.name'))->leftjoin('camps', 'camps.id', '=', 'camp_type')->where('term', $term)->orderBy('camps.name')->get();
// 			$query =\DB::raw("SELECT DISTINCT t.camp_type, c.name FROM gmb_themes AS t LEFT JOIN gmb_camps AS c ON c.id = t.camp_type WHERE t.term = $term ORDER BY c.name");
			if($query->count() > 0) {
				$camp_list = "";
				foreach($query as $key => $row) {
					$name = $row['name'];
					$camp_list .= $name . ", ";
				}
				$content .= trim($camp_list, ", ");
			} else {
				$content .= '<span style="color:red;font-weight:bold;">WARNING: There are currently no themes added.</span>';	 
			}
			return $content;
		}
		
		public static function camps_with_locations($term) {
// 			$sql = "SELECT DISTINCT term.camp_type, camps.name FROM ".tbpre."locations l LEFT JOIN ".tbpre."camps c ON camps.id = term.camp_type WHERE term = $term ORDER BY camps.name";
			
			$query = Locations::select(DB::raw('DISTINCT gmb_locations.camp_type, gmb_camps.name'))->leftjoin('camps', 'camps.id', '=', 'locations.camp_type')->where('term', $term)->orderBy('camps.name')->get();
			if($query->count() > 0) {
				$camp_list = "";
				foreach($query as $key => $row) {
					$name = $row['name'];
					$camp_list .= $name . ", ";
				}
				$content .= trim($camp_list, ", ");
			} else {
				$content .= '<span style="color:red;font-weight:bold;">WARNING: There are currently no themes added.</span>';	 
			}
			return $content;
		}
		
		public static function num_supplies($term) {
			$query = Supplies::select('id')->where('term', $term)->get();
			$num_rows = $query->count();
			if($num_rows > 0) {
				$content .= $num_rows;
			} else {
				$content .= '<span style="color:red;font-weight:bold;">WARNING: There are currently no CW Supply Requests added.</span>';	 
			}
			return $content;
		}
		
		public static function campg_themes_linked($term) {
			$query = ThemeLink::select('id')->where('term', $term)->where('camp_type', 1)->get();
			$num_rows = $query->count();
			if($num_rows > 0) {
				$content .= "There are " . 2 * $num_rows . " themes linked.";
			} else {
				$content .= '<span style="color:red;font-weight:bold;">WARNING: There are currently no Themes Linked.</span>';	 
			}
			return $content;
		}
		
		public static function num_themes($term) {
			$query = Themes::select('id')->where('term', $term)->get();
			$num_rows = $query->count();
			if($num_rows > 0) {
				$content .= $num_rows;
			} else {
				$content .= '<span style="color:red;font-weight:bold;">WARNING: There are currently no themes added.</span>';	 
			}
			return $content;
		}
		
		public static function num_activities($term) {
			$query = Activities::select('id')->where('term', $term)->get();
			$num_rows = $query->count();
			if($num_rows > 0) {
				$content .= $num_rows;
			} else {
				$content .= '<span style="color:red;font-weight:bold;">WARNING: There are currently no activities added.</span>';	 
			}
			return $content;
		}
		
		public static function num_parts() {
			$num_rows = Parts::count();
			if($num_rows > 0) {
				$content .= $num_rows;
			} else {
				$content .= '<span style="color:red;font-weight:bold;">WARNING: There are currently no parts.</span>';	 
			}
			return $content;
		}
		
		public static function num_products() {
			$num_rows = Products::count();
			if($num_rows > 0) {
				$content .= $num_rows;
			} else {
				$content .= '<span style="color:red;font-weight:bold;">WARNING: There are currently no products.</span>';	 
			}
			return $content;
		}
		
		public static function num_customers() {
			$num_rows = Customers::count();
			if($num_rows > 0) {
				$content .= $num_rows;
			} else {
				$content .= '<span style="color:red;font-weight:bold;">WARNING: There are currently no customers.</span>';	 
			}
			return $content;
		}
		
		public static function num_vendors() {
			$num_rows = Vendors::count();
			if($num_rows > 0) {
				$content .= $num_rows;
			} else {
				$content .= '<span style="color:red;font-weight:bold;">WARNING: There are currently no vendors.</span>';	 
			}
			return $content;
		}
	}
