<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	
	use App\Models\Camps;
	use App\Models\CustomerAddresses;
	use App\Models\Customers;
	use App\Models\Grades;
	use App\Models\PackingLists;
	use App\Models\PackingTotals;
	use App\Models\Products;
	use App\Models\SalesOrders;
	use App\Models\Supplies;
	use App\Models\Themes;
	
	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaCustomers;
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaGrades;
	use App\Gamba\gambaFishbowl;
	use App\Gamba\gambaLocations;
	use App\Gamba\gambaPacking;
	use App\Gamba\gambaParts;
	use App\Gamba\gambaQuantityTypes;
	use App\Gamba\gambaSupplies;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaThemes;
	use App\Gamba\gambaUOMs;
	use App\Gamba\gambaUsers;
	
	use App\Jobs\ExportSalesOrders;
	
	class gambaSales {
		
		public static function soList($term) {
			$packinglists = gambaPacking::packing_lists();
			$query = SalesOrders::select(
					'salesorders.id', 
					'customers.Name as customer_name', 
					'salesorders.customer', 
					'salesorders.fulfillment_date', 
					'salesorders.fishbowl', 
					'salesorders.list', 
					'salesorders.term', 
					'salesorders.camp', 
					'camps.abbr', 
					'salesorders.theme', 
					'salesorders.grade', 
					'salesorders.location', 
					'locations.location as location_name', 
					'salesorders.dli', 
					'salesorders.date_created', 
					'salesorders.xmlstring', 
					'salesorders.fb_err_msg');
			$query = $query->leftjoin('customers', 'customers.CustomerID', '=', 'salesorders.customer');
			$query = $query->leftjoin('locations', 'locations.id', '=', 'salesorders.location');
			$query = $query->leftjoin('camps', 'camps.id', '=', 'locations.camp');
			$query = $query->where('salesorders.term', '=', $term);
			$query = $query->orderBy('salesorders.date_created', 'DESC');
			$query = $query->get()->toArray();
			if(!empty($query)) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$array['orders'][$id]['customer_id'] = $row['customer'];
					$array['orders'][$id]['customer'] = $row['customer_name'];
					$array['orders'][$id]['fulfillment_date'] = $row['fulfillment_date'];
					$array['orders'][$id]['fishbowl'] = $fishbowl = $row['fishbowl'];
					$array['orders'][$id]['list'] = $row['list'];
					$packingListName = self::packingListName($row['list'], $term);
					$array['orders'][$id]['list_values'] = $packinglists['packinglists'][$row['list']]['list_values'];
					$array['orders'][$id]['listname'] = $packingListName['alt'];
					$array['orders'][$id]['term'] = $row['term'];
					$array['orders'][$id]['fulfillmentdate'] = $row['fulfillment_date'];
					$array['orders'][$id]['camp'] = $row['camp'];
					$camp = self::campType($row['camp']);
					$array['orders'][$id]['camp_name'] = $camp['name'];
					$array['orders'][$id]['theme'] = $row['theme'];
					$theme = self::theme($row['theme'], $term);
					$array['orders'][$id]['theme_name'] = $theme['name'];
					$array['orders'][$id]['grade'] = $row['grade'];
					$grade = self::grade($row['grade']);
					$array['orders'][$id]['grade_level'] = $grade['level'];
					$array['orders'][$id]['camp_location'] = $row['location'];
					$array['orders'][$id]['location_name'] = $row['location_name'];
					$array['orders'][$id]['camp_abbr'] = $row['abbr'];
					$array['orders'][$id]['dli'] = $row['dli'];
					$array['orders'][$id]['date_created'] = $row['date_created'];
					if($row['xmlstring'] != "" && $fishbowl == "false") { $push_error = "true"; } else { $push_error = "false"; } 
					$array['orders'][$id]['push_error'] = $push_error;
					$array['orders'][$id]['fb_err_msg'] = json_decode($row['fb_err_msg'], true);
				}
			} else {
				$array['message'] = "There are no Sales Orders for this term.";
			}
			return $array;
		}
		
		/**
		 * Get the Grade Name and Info for Camp and Theme
		 * 
		 * @param unknown_type $id
		 */
		public static function grade($id) {
			$query = Grades::select(
					'id', 
					'level', 
					'camp_type', 
					'enrollment', 
					'altname');
				$query = $query->where('id', $id);
				$row = $query->first();
			$array['id'] = $row['id'];
			$array['level'] = $row['level'];
			$array['camp'] = $row['camp_type'];
			$array['enrollment'] = $row['enrollment'];
			$array['altname'] = $row['altname'];
			return $array;
		}
		
		/** 
		 * Get the Theme Name
		 * 
		 * @param unknown_type $id
		 * @param unknown_type $term
		 */
		public static function theme($id, $term) {
			$query = Themes::select(
					'id', 
					'name', 
					'camp_type', 
					'theme_type', 
					'cg_staff', 
					'link_id', 
					'minor', 
					'quantity_id');
				$query = $query->where('id', $id);
				$query = $query->where('term', $term);
				$row = $query->first();
			$array['id'] = $row['id'];
			$array['name'] = $row['name'];
			$array['camp'] = $row['camp_type'];
			$array['theme_type'] = $row['theme_type'];
			$array['cg_staff'] = $row['cg_staff'];
			$array['link_id'] = $row['link_id'];
			$array['minor'] = $row['minor'];
			$array['quantity_id'] = $row['quantity_id'];
			return $array;
		}
		
		public static function salesordercreate($array) {
			$customer = $array['customer']; $dli = $array['dli']; $list = $array['list']; $theme = $array['theme'];
			$term = $array['term']; $camp = $array['camp'];
			$location = $array['location']; $grade = $array['grade']; $date_created = date("Y-m-d H:i:s");
			$packby = $array['packby'];
			
			$fulfillment_date = date("Y-m-d");
			if($theme == "") { $theme = 0; }
			if($grade == "") { $grade = 0; }
			
			$soid = SalesOrders::insertGetId([
				'customer' => $customer, 
				'fishbowl' => 'false', 
				'list' => $list, 
				'term' => $term, 
				'camp' => $camp, 
				'theme' => $theme, 
				'grade' => $grade, 
				'location' => $location, 
				'dli' => $dli, 
				'date_created' => $date_created, 
				'fulfillment_date' => $fulfillment_date	
			]);
			return $soid;
		}
		
		public static function mark_pushed($array) {
			$soid = $array['soid'];
			$update = SalesOrders::find($soid);
				$update->fishbowl = 'true';
				$update->save();
		}
		
		public static function salesorderupdate($array) {
			
			$dli = $array['dli']; $list = $array['list']; $theme = $array['theme'];
			$term = $array['term']; $camp = $array['camp'];
			$location = $array['location']; $grade = $array['grade'];
			$soid = $array['soid']; $customer_id = $array['customer_id']; $fulfillment_date = $array['soFulfillmentDate'];
			$products = $array['products']; $customer_name = $array['customer_name']; $class = $array['class'];
			$packby = $array['packby'];
			
// 			echo "<pre>"; print_r($array); echo "</pre>";
// 			exit; die();
			
			if($fulfillment_date != "") { $fulfillment_date = date("Y-m-d", strtotime($fulfillment_date)); }
			// Pushed in Controller
// 			if($array['submit'] == "Push Sales Order to Fishbowl") {
// 				$fishbowl = 'true';
// 				$result = gambaFishbowl::push_sales_order($array);
// 				$fishbowl = $result['fishbowl'];
// 				$result = base64_encode(json_encode($result));
// 			} else {
// 				$fishbowl = 'false';
// 			}
			if($array['submit'] == "Save Sales Order") {
				$fishbowl = 'false';
			}
			
			if($customer_id != "") {
				$update = SalesOrders::find($soid);
					$update->customer = $customer_id;
					$update->fishbowl = $fishbowl;
					$update->fulfillment_date = $fulfillment_date;
					$update->save();
			}
		}
		
		public static function salesorderdelete($array) {
			$soid = $array['soid'];
			$delete = SalesOrders::find($soid)->delete();
		}

		
		/**
		 * Items for Sales Order. 
		 * 
		 * @param unknown_type $list
		 * @param unknown_type $term
		 * @param unknown_type $theme
		 * @param unknown_type $camp
		 * @param unknown_type $location
		 * @param unknown_type $dli
		 * @param unknown_type $grade
		 * @return Ambigous <string, unknown>
		 */
		public static function salesOrder($list, $term, $theme, $camp, $location, $dli, $grade, $soid, $packby = "theme-grade") {
			$packingListName = self::packingListName($list, $term);
			$campType = self::campType($camp);
			if($soid != "") {
				$array = self::salesOrderInfo($soid);
// 				$array['soinfo_sql'] = $salesOrderInfo['soinfo_sql'];
// 				$array['soNum'] = $soid;
// 				$array['soStatus'] = $salesOrderInfo['soStatus'];
// 				$array['soCustomer'] = $salesOrderInfo['soCustomer'];
// 				$array['soFulfillmentDate'] = $salesOrderInfo['soFulfillmentDate'];
// 				$array['xmlstring'] = $salesOrderInfo['xmlstring'];
			}
			$array['list'] = $list;
			$array['term'] = $term;
			if($theme != "") { $array['theme'] = $theme; }
			$array['camp'] = $camp;
			$array['location'] = $location;
			$array['dli'] = $dli;
			$array['grade'] = $grade;
			if($array['supplemental'] == 1) {
				foreach($array['supplemental_parts'] as $part => $values) {
					$array['orders'] = self::supplemental_order_locations($array['supplemental_parts']);
				}
			} else {
				if($packby == "grade") {
					$supplies = self::supply_grade_locations($term, $grade, $list);
					$array['sql'] = $supplies['sql'];
					$array['orders'] = $supplies['locations']['grade'][$grade]['location'][$location]['dli'][$dli];
				} 
				if($packby == "theme") {
					$supplies = self::supply_theme_locations($term, $theme, $list, $camp);
					$array['sql'] = $supplies['sql'];
					$array['orders'] = $supplies['locations']['grade'][$grade]['location'][$location]['dli'][$dli];
				}
				if($packby == "theme-grade") {
					$supplies = self::supply_theme_grade_locations($term, $theme, $grade, $list, $camp);
					$array['sql'] = $supplies['sql'];
					$array['orders'] = $supplies['locations']['grade'][$grade]['location'][$location]['dli'][$dli];
				}
			}
			return $array;
		}
		
		public static function supplemental_order_locations($array) {
			$uoms = gambaUOMs::uom_list();
			foreach($array as $part => $values) {
				$part_info = gambaParts::part_info($part);
				$content[$part]['part'] = $part;
				$content[$part]['part_desc'] = $values['part_desc'];
				$content[$part]['value'] = $values['qty'];
				$content[$part]['uom'] = $part_info['suom'];
				$content[$part]['fbuom'] = $part_info['fbuom'];
				$content[$part]['fbuomcode'] = $uoms['codes'][$part_info['fbuom']];
			}
			return $content;
			// "C4042":{"part_desc":"bag, plastic, ziploc, gallon","qty":"1","uom":"_36pk","price":"$5.80","date":"June 2, 2017"},
			//"C7345":{"part_desc":"ball, foam, popper, 1\"","qty":"25","uom":"ea","price":"$0.29","date":"June 2, 2017"}
		}

		public static function supply_theme_locations($term, $theme, $list, $camp) {
			$packing_lists = gambaPacking::packing_lists();
			$uoms = gambaUOMs::uom_list();
			$packing_list_info = $packing_lists['packinglists'][$list];
// 			if($packing_list_info['list_values']['highest'] == "true") { $sql .= " AND s.lowest = 0"; }
			$query = PackingTotals::select(
					'packingtotals.grade', 
					'packingtotals.part', 
					'packingtotals.converted_total', 
					'packingtotals.location_totals', 
					'parts.fbuom', 
					'parts.fbcost', 
					'parts.description', 
					'parts.conversion', 
					'parts.adminnotes');
				$query = $query->leftjoin('parts', 'parts.number', '=', 'packingtotals.part');
				$query = $query->where('packingtotals.term', $term);
				$query = $query->where('packingtotals.theme', $theme);
				$query = $query->where('packingtotals.packing_id', $list);
				$query = $query->where('parts.fishbowl', 'true');
				$query = $query->where('packingtotals.converted_total', '>', 0);
				$sql = $query->toSql();
				$query = $query->get();
			//echo $sql; exit; die();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$grade = $row['grade'];
					$id = $row['id'];
					$supplylist_id = $row['supplylist_id'];
					$part = $row['part'];
					$uom = $uoms['uoms'][$row['fbuom']]['name'];
					$fbuomcode = $row['fbuom'];
					$fbuom = $uoms['uoms'][$row['fbuom']]['code'];
					$cost = $row['fbcost'];
					$description = $row['description'];
					$conversion = $row['conversion'];
					$adminnotes = $row['adminnotes'];
					$packing_quantities = json_decode($row->location_totals, true);
// 					$packing_recalc_quantities = json_decode($row->packing_recalc_quantities, true);
// 					if(basic_calc_status == 1 && is_array($row['packing_recalc_quantities'])) { $packing_quantities = $packing_recalc_quantities; }
					$product = self::getProductInfo($part);
					$product_status = $product['product']['status'];
					if($product_status) {
						$product_price = str_replace('$', "", $product['product']['Price']);
					} 
					foreach($packing_quantities as $location => $dli_info) {
						$location_by_id = gambaLocations::location_by_id($location);
						foreach($dli_info as $dli => $info) {
							$location_name = $location_by_id['name']; if($dli == 1) { $location_name .= " 2"; }
							$total = $info['converted'];
							if($total > 0) {
								$array['locations']['grade'][$grade]['location'][$location]['location_name'] = $location_name;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['value'] = $total;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['uom'] = $uom;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['fbuom'] = $fbuomcode;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['fbuomcode'] = $fbuomcode;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['cost'] = $cost;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['part'] = $part;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['price'] = $product_price;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['part_desc'] = $description;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['product_status'] = $product_status;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['conversion'] = $conversion;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['notes'] = gambaSupplies::cw_notes($part, $term, $theme, '', $list);
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['adminnotes'] = $adminnotes;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['value'] = $total;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['part_desc'] = $description;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['supply_id'] = $id;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['supplylist_id'] = $supplylist_id;
							}
						}
					}
				}
			}
			return $array;
		}
		public static function supply_theme_grade_locations($term, $theme, $grade, $list, $camp) {
			$packing_lists = gambaPacking::packing_lists();
			$uoms = gambaUOMs::uom_list();
			$packing_list_info = $packing_lists['packinglists'][$list];
// 			if($packing_list_info['list_values']['highest'] == "true") { $sql .= " AND s.lowest = 0"; }

			$query = PackingTotals::select(
				'packingtotals.theme', 
				'packingtotals.grade', 
				'packingtotals.part', 
				'packingtotals.converted_total', 
				'packingtotals.location_totals', 
				'parts.fbuom', 
				'parts.fbcost', 
				'parts.description', 
				'parts.conversion', 
				'parts.adminnotes');
				$query = $query->leftjoin('parts', 'parts.number', '=', 'packingtotals.part');
				$query = $query->where('packingtotals.term', $term);
				$query = $query->where('packingtotals.theme', $theme);
				$query = $query->where('packingtotals.grade', $grade);
				$query = $query->where('packingtotals.packing_id', $list);
				$query = $query->where('parts.fishbowl', 'true');
				$query = $query->where('packingtotals.converted_total', '>', 0);
				$query = $query->get();
			$array['sql'] = \DB::last_query();
			if($query->count() > 0) {
				$i = 0;
				foreach($query as $key => $row) {
					$grade = $row['grade'];
// 					$id = $row['id'];
					$id = $i; $i++;
					$supplylist_id = $row['supplylist_id'];
					$part = $row['part'];
					$uom = $uoms['uoms'][$row['fbuom']]['name'];
					$fbuomcode = $row['fbuom'];
					$fbuom = $uoms['uoms'][$row['fbuom']]['code'];
					$cost = $row['fbcost'];
					$description = $row['description'];
					$conversion = $row['conversion'];
					$adminnotes = $row['adminnotes'];
					$packing_quantities = json_decode($row->location_totals, true);
// 					$packing_recalc_quantities = json_decode($row->packing_recalc_quantities, true);
// 					if(basic_calc_status == 1 && is_array($row['packing_recalc_quantities'])) { $packing_quantities = $packing_recalc_quantities; }
					$product = self::getProductInfo($part);
					$product_status = $product['product']['status'];
					if($product_status) {
						$product_price = str_replace('$', "", $product['product']['Price']);
					} 
					foreach($packing_quantities as $location => $dli_info) {
						$location_by_id = gambaLocations::location_by_id($location);
						foreach($dli_info as $dli => $info) {
							$total = $info['converted'];
							$location_name = $location_by_id['name']; if($dli == 1) { $location_name .= " 2"; }
							if($total > 0) {
								$array['locations']['grade'][$grade]['location'][$location]['location_name'] = $location_name;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['value'] = $total;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['uom'] = $uom;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['fbuom'] = $fbuomcode;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['fbuomcode'] = $fbuomcode;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['cost'] = $cost;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['part'] = $part;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['price'] = $product_price;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['part_desc'] = $description;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['product_status'] = $product_status;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['conversion'] = $conversion;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['notes'] = gambaSupplies::cw_notes($part, $term, $theme, $grade, $list);
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['adminnotes'] = $adminnotes;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['value'] = $total;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['part_desc'] = $description;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['supply_id'] = $id;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['supplylist_id'] = $supplylist_id;
							}
						}
					}
				}
			}
			return $array;
		}
		public static function supply_grade_locations($term, $grade, $list) {
			$packing_lists = gambaPacking::packing_lists();
			$uoms = gambaUOMs::uom_list();
			$packing_list_info = $packing_lists['packinglists'][$list];
// 			if($packing_list_info['list_values']['highest'] == "true") { $sql .= " AND s.lowest = 0"; }

			$query = PackingTotals::select(
					'packingtotals.part', 
					'packingtotals.converted_total', 
					'packingtotals.location_totals', 
					'parts.fbuom', 
					'parts.fbcost', 
					'parts.description', 
					'parts.conversion', 
					'packingtotals.camp', 
					'packingtotals.theme', 
					'packingtotals.packing_id', 
					'parts.adminnotes');
				$query = $query->leftjoin('parts', 'parts.number', '=', 'packingtotals.part');
				$query = $query->where('packingtotals.term', $term);
				$query = $query->where('packingtotals.grade', $grade);
				$query = $query->where('packingtotals.packing_id', $list);
				$query = $query->where('parts.fishbowl', 'true');
				$query = $query->where('packingtotals.converted_total', '>', 0);
				$query = $query->get();
// 			$array['sql'] = \DB::last_query();
			if($query->count() > 0) {
				$i = 0;
				foreach($query as $key => $row) {
// 					$id = $row['id'];
					$id = $i; $i++;
					$supplylist_id = $row['supplylist_id'];
					$part = $row['part'];
					$uom = $uoms['uoms'][$row['fbuom']]['name'];
					$fbuomcode = $row['fbuom'];
					$fbuom = $uoms['uoms'][$row['fbuom']]['code'];
					$cost = $row['cost'];
					$description = $row['description'];
					$conversion = $row['conversion'];
					$adminnotes = $row['adminnotes'];
					$camp = $row['camp_id'];
					$theme_id = $row['theme_id'];
					$packing_id = $row['packing_id'];
					$theme_by_id = gambaThemes::theme_by_id($theme_id);
					$theme_name = $theme_by_id['name'];
					$packing_recalc_quantities = "";
					$packing_quantities = json_decode($row->location_totals, true);
// 					$packing_recalc_quantities = json_decode($row->packing_recalc_quantities, true);
					if(basic_calc_status == 1 && is_array($packing_recalc_quantities)) { $packing_quantities = $packing_recalc_quantities; }
					$product = self::getProductInfo($part);
					$product_status = $product['product']['status'];
					if($product_status) {
						$product_price = str_replace('$', "", $product['product']['Price']);
					} 
					foreach($packing_quantities as $location => $dli_info) {
						$location_by_id = gambaLocations::location_by_id($location);
						foreach($dli_info as $dli => $info) {
							$location_name = $location_by_id['name']; if($dli == 1) { $location_name .= " 2"; }
							$total = $info['converted'];
							if($total > 0) {
								$array['locations']['grade'][$grade]['location'][$location]['location_name'] = $location_name;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['value'] = $total;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['uom'] = $uom;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['fbuom'] = $fbuomcode;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['fbuomcode'] = $fbuomcode;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['cost'] = $cost;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['part'] = $part;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['price'] = $product_price;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['part_desc'] = $description;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['product_status'] = $product_status;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['conversion'] = $conversion;
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['notes'] = gambaSupplies::cw_notes($part, $term, '', $grade, $list);
								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['adminnotes'] = $adminnotes;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['value'] = $total;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['part_desc'] = $description;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['supply_id'] = $id;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['supplylist_id'] = $supplylist_id;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['grade_id'] = $grade;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['camp'] = $camp;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['theme_id'] = $theme_id;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['theme_name'] = $theme_name;
// 								$array['locations']['grade'][$grade]['location'][$location]['dli'][$dli][$part]['values'][$id]['packing_id'] = $packing_id;
							}
						}
					}
// 					$array['supplies'][$supplylist_id][$part]['description'] = $description;
// 					$array['supplies'][$supplylist_id][$part]['packing_quantities'] = $packing_quantities;
				}
			}
			return $array;
		}
		
		public static function supplemental_orders($array) {
			$content['form_data'] = <<<EOT
			<tr>
				<td colspan="13">Camp:{$array['camp']} |
						Enrollment:{$array['enrollment']} |
						Location:{$array['location']} |
						Theme:{$array['theme']} |
						Grade:{$array['grade']} |
						Item Type:{$array['itemtype']} |
						Quantity Type:{$array['quantity_type']}
EOT;
			$content['timestamp'] = date("Y-m-d H:i:s");
			$content['term'] = $current_term = gambaTerm::year_by_status('C');
			$content['quantity_types'] = $quantity_types = gambaQuantityTypes::quantity_types_by_camp($array['camp'], $current_term);
			$content['packing_ids'] = $packing_ids = gambaPacking::packing_lists();
			$theme_info = gambaThemes::themes_by_camp($array['camp'], $current_term);
			$camps = gambaCampCategories::camps_list();
			$locations = gambaLocations::location_by_id($array['location']);
			$content['class'] = "{$camps[$array['camp']]['alt_name']}:{$locations['name']}";
			$content['customer_search'] = $locations['name'];
			// customer addresses
			$customers = Customers::select('CustomerID', 'Name')->where('Name', 'LIKE', "%{$locations['name']}%")->where('ActiveFlag', "true")->orderBy('Name');
			$content['customer_sql'] = $customers->toSql();
			$customers = $customers->get();
			if($customers->count() > 0) {
				foreach($customers as $key => $values) {
					$content['customers'][$key]['id'] = $values['CustomerID'];
					$content['customers'][$key]['name'] = $values['Name'];
				}
			} else {
				$customers = Customers::select('CustomerID', 'Name')->where('ActiveFlag', "true")->orderBy('Name');
				$content['customer_sql'] = $customers->toSql();
				$customers = $customers->get();
				foreach($customers as $key => $values) {
					$content['customers'][$key]['id'] = $values['CustomerID'];
					$content['customers'][$key]['name'] = $values['Name'];
				}
			}
			
			
			$kqd_quantity_types = self::kqd_quantity_types($array['camp']);
				//$kqd_quantity_types_array = print_r($kqd_quantity_types,true);
				//$content['kqd_quantity_types'] = "<tr><td colspan='13'><pre>" . $kqd_quantity_types_array . "</pre></td></tr>";
			
			$supplies = Supplies::select('supplies.part', 'supplies.request_quantities')->leftjoin('parts', 'parts.number', '=', 'supplies.part')->where('supplies.term', $current_term)->where('supplies.theme_id', $array['theme'])->where('supplies.camp_id', $array['camp'])->where('supplies.exclude', '0');
			if($array['grade'] != "") {
				$supplies = $supplies->where('supplies.grade_id', $array['grade']);
			}
			if($array['itemtype'] == "C") {
				$supplies = $supplies->where('supplies.itemtype', "C");
			}
			if($array['itemtype'] == "NC") {
				$supplies = $supplies->orWhere('supplies.itemtype', "NC")->orWhere('supplies.itemtype', "NCx3");
			}
			$sql = $supplies->toSql();
			$content['sql'] = "<tr><td colspan='13'>{$sql}</td></tr>";
			$supplies = $supplies->orderBy('parts.description')->get();
			
			foreach($supplies as $key => $values) {
				$request_quantities = json_decode($values['request_quantities'], true);
				$part_info = gambaParts::part_info($values['part']);
				$product = self::getProductInfo($values['part']);
				if($array['quantity_type'] == "all" || ($array['quantity_type'] == "campers" && array_key_exists($request_quantities['quantity_type_id'], $kqd_quantity_types['quantity_types']) == "true")) {
					$content['parts'][$values['part']]['description'] = $part_info['description'];
					$content['parts'][$values['part']]['suom'] = $part_info['suom'];
					$content['parts'][$values['part']]['fbuom'] = $part_info['fbuom'];
					$content['parts'][$values['part']]['conversion'] = $conversion = $part_info['conversion'];
					$content['parts'][$values['part']]['price'] = $product['product']['Price'];
					$content['parts'][$values['part']]['request_quantities'] = $request_quantities;
					
					if($array['quantity_type'] == "all") {
						// Dropdown
						$content['parts'][$values['part']]['qty'] += self::quantity_calc($array['enrollment'], $conversion, $request_quantities['quantity_val'], $quantity_types['dropdown'][$request_quantities['quantity_type_id']]['value']);
						// Static
						foreach($request_quantities['static'] as $quantity_type_id => $quantity_type_value) {
							$content['parts'][$values['part']]['qty'] += self::quantity_calc($array['enrollment'], $conversion, $quantity_type_value);
						}
					} else {
						// KQD
						$content['parts'][$values['part']]['qty'] += self::quantity_calc($array['enrollment'], $conversion, $request_quantities['quantity_val'], $kqd_quantity_types['quantity_types'][$request_quantities['quantity_type_id']]['kqd_value']);
					}
					
				}
			}
			$content['total'] = count($content['parts']);
			return $content;
		}
		
		public static function quantity_calc($enrollment, $conversion, $quantity_value, $kqd_value = 1) {
			if($kqd_value == 0) {
				$kqd_value = 1;
			}
			$quantity = ($quantity_value * $kqd_value) * $enrollment;
			if($conversion > 0) {
				$quantity = ceil($quantity / $conversion);
			} else {
				$quantity = ceil($quantity);
			}
			return $quantity;
		}
		
		public static function kqd_quantity_types($camp) {
			$current_term = gambaTerm::year_by_status('C');
			$quantity_types = gambaQuantityTypes::quantity_types_by_camp($camp, $current_term);
			foreach($quantity_types['dropdown'] as $id => $values) {
				if($values['qt_options']['kqd'] == "1") {
					$array['quantity_types'][$id]['name'] = $values['name'];
					$array['quantity_types'][$id]['kqd_value'] = $values['value'];
					$array['quantity_types'][$id]['qt_options'] = $values['qt_options'];
				}
			}
			return $array;
// 			return $quantity_types['dropdown'];
		}
		
		public static function salesOrderInfo($soid) {
			$query = SalesOrders::select(
					'salesorders.id', 
					'customers.Name as customer_name', 
					'salesorders.customer', 
					'salesorders.fulfillment_date', 
					'salesorders.fishbowl', 
					'salesorders.list', 
					'salesorders.term', 
					'salesorders.camp', 
					'camps.abbr', 
					'salesorders.theme', 
					'salesorders.grade', 
					'salesorders.location', 
					'locations.location as location_name', 
					'salesorders.dli', 
					'salesorders.date_created', 
					'salesorders.xmlstring', 
					'salesorders.fb_err_msg', 
					'salesorders.supplemental', 
					'salesorders.supplemental_parts'
				);
				$query = $query->leftjoin('customers', 'customers.CustomerID', '=', 'salesorders.customer');
				$query = $query->leftjoin('locations', 'locations.id', '=', 'salesorders.location');
				$query = $query->leftjoin('camps', 'camps.id', '=', 'locations.camp');
				$query = $query->where('salesorders.id', '=', $soid);
				$query = $query->orderBy('salesorders.date_created', 'DESC');
			$row = $query->first();
// 			$row = $query->get()->toArray();
			$sql = $query->toSql();

			$array['soid'] = $row['id'];
			$array['soCustomer'] = $row['customer_name'];
			$array['soCustomerID'] = $row['customer'];
			$array['soFulfillmentDate'] = $row['fulfillment_date'];
			$array['soStatus'] = $row['fishbowl'];
			$array['soList'] = $row['list'];
			$array['soTerm'] = $row['term'];
			$array['soCampID'] = $row['camp'];
			$array['soCampAbbr'] = $row['abbr'];
			$array['soThemeID'] = $row['theme'];
			$array['soGradeID'] = $row['grade'];
			$array['soLocationID'] = $row['location'];
			$array['soLocationName'] = $row['location_name'];
			$array['soDLI'] = $row['dli'];
			$array['soDateCreated'] = $row['date_created'];
			$array['fb_err_msg'] = json_decode($row['fb_err_msg'], true);
			$array['xmlstring'] = $row['xmlstring'];
			$array['soinfo_sql'] = $sql;
			if($row['supplemental'] == 1) {
				$array['supplemental'] = $row['supplemental'];
				$array['supplemental_parts'] = json_decode($row['supplemental_parts'], true);
			}
			return $array;
		}
		/**
		 * @param unknown $part_num
		 * @return Array <array> $array['product']['status'], $array['product']['Num'], $array['product']['Description'], $array['product']['Price'], $array['product']['UOM'], $array['product']['PartID']
		 */
		private static function getProductInfo($part_num) {
			$row = Products::find($part_num);
			if($row-count() > 0) {
				$array['product']['status'] = "true";
				$array['product']['Num'] = $row['Num'];
				$array['product']['Description'] = $row['Description'];
				$array['product']['Price'] = $row['Price'];
				$array['product']['UOM'] = $row['UOM'];
				$array['product']['PartID'] = $row['PartID'];
			} else {
				$array['product']['status'] = "false";
			}
			return $array;
		}
		
		/**
		 * Get the Camp Locations for the Camp and Theme
		 * 
		 * @param unknown_type $list
		 * @param unknown_type $term
		 * @param unknown_type $theme
		 * @param unknown_type $camp
		 * @return unknown
		 */
		public static function campLocations($list, $term, $theme, $camp) {
			$packingListName = self::packingListName($list, $term);
			$campType = self::campType($camp);
			if($theme == 0) {
				$array['description'] = $campType['name'] . " &gt; " . $packingListName['alt'];
			} else {
				$theme = self::theme($theme, $term);
				$array['description'] = $campType['name'] . " &gt; " . $packingListName['alt'] . " &gt; " . $theme['name'];
			}
			$sosGrades = self::sosGrades($list, $term, $theme, $camp);
			$array['num_grades'] = $sosGrades['num_grades'];
			$grades = $sosGrades['grades'];
			$array['grades'] = $grades;
			$array['list'] = $list;
			$array['term'] = $term;
			$array['theme'] = $theme;
			$array['camp'] = $camp;
//			if($array['num_grades'] == 1 && $theme == 0) {
//				
//			} 
			
			return $array;
		}
		
		private static function sosCamps($list, $grades, $term, $theme, $camp) {
			foreach($grades as $key => $value) {
				$sosCampInfo = self::sosCampInfo($list, $grade, $term, $theme, $camp);
			}
			return $sosCampInfo;
		}
		
		
		/**
		 * Get List of Camp Locations for Grade by Theme and Camp
		 * 
		 * @param unknown_type $list
		 * @param unknown_type $grade
		 * @param unknown_type $term
		 * @param unknown_type $theme
		 * @param unknown_type $camp
		 */
		private static function sosCampInfo($list, $grade, $term, $theme, $camp) {
			$query = SalesOrders::select(
					'salesordersupplies.location', 
					'camp_locations.location as name', 
					'camp_locations.abbreviation', 
					'camp_locations.dstar');
				$query = $query->leftjoin('camp_locations', 'camp_locations.id', '=', 'salesordersupplies.location');
				$query = $query->where('salesordersupplies.camp', $camp);
				$query = $query->where('salesordersupplies.term', $term);
				$query = $query->where('salesordersupplies.theme', $theme);
				$query = $query->where('salesordersupplies.grade', $grade);
				$query = $query->where('salesordersupplies.list', $list);
				$query = $query->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['location'];
//					echo "<pre>"; print_r($row); echo "</pre>"; 
					$array[$id]['name'] = $row['name'];
					$array[$id]['abbr'] = $row['abbreviation'];
					$dstar = $row['dstar'];
					if($dstar == 1 && ($camp == 1 || $camp == 6)) {
						$dli = array("1","2");
					} else {
						$dli = array("0");
					}
					$array[$id]['campdstar'] = $dstar;
					foreach($dli as $key) {
						$array[$id]['dli'][$key]['num'] = self::campRequests($id, $key, $list, $grade, $term, $theme, $camp);
						$soStatus = self::soStatus($id, $key, $list, $grade, $term, $theme, $camp);
	//					echo "<pre>"; print_r($soStatus); echo "</pre>";
						
						if($soStatus['soid'] != "") { 
							$array[$id]['dli'][$key]['fishbowl'] = $soStatus['fishbowl']; 
							$array[$id]['dli'][$key]['soid'] = $soStatus['soid'];
						}
					}
				}
			}
//			echo "<pre>"; print_r($array); echo "</pre>"; 
//			exit; die();
			return $array;
		}
		
		private static function soStatus($id, $dstar, $list, $grade, $term, $theme, $camp) {
			$query = SalesOrders::select(
					'id', 
					'fishbowl');
				$query = $query->where('list', $list);
				$query = $query->where('grade', $grade);
				$query = $query->where('term', $term);
				$query = $query->where('theme', $theme);
				$query = $query->where('camp', $camp);
				$query = $query->where('camp_location', $id);
				$query = $query->where('dli', $dstar);
				$row = $query->first();
			if($row->count() > 0) {
				$array['soid'] = $row['id'];
				$array['fishbowl'] = $row['fishbowl'];
			} else {
				$array['fishbowl'] = 'false';
			}
			return $array;
		}
		/**
		 * Number of Items for each Camp Location
		 * 
		 * @param $id
		 * @param $dstar
		 * @param $list
		 * @param $grade
		 * @param $term
		 * @param $theme
		 * @param $camp
		 */
		private static function campRequests($id, $dstar, $list, $grade = 0, $term, $theme = 0, $camp) {
// 			$numRows = SalesOrderSupplies::select('salesordersupplies.material')->leftjoin('materials_list', 'materials_list.id', '=', 'salesordersupplies.material')->where('salesordersupplies.list', $list)->where('salesordersupplies.grade', $grade)->where('salesordersupplies.term', $term)->where('salesordersupplies.theme', $theme)->where('salesordersupplies.camp', $camp)->where('salesordersupplies.location', $id)->where('salesordersupplies.dli', $dstar)->where('materials_list.fishbowl', 'true')->get();
// 			return $numRows->count();
		}
		
		/**
		 * Get the Grades for the Camp and Theme
		 * 
		 * @param unknown_type $list
		 * @param unknown_type $term
		 * @param unknown_type $theme
		 * @param unknown_type $camp
		 */
		private static function sosGrades($list, $term, $theme, $camp) {
// 			$query = SalesOrderSupplies::select(\DB::raw('DISTINCT grade'))->where('list', $list)->where('term', $term)->where('theme', $theme)->where('camp', $camp);
			/*if($query->count() > 1) {
				$array['num_grades'] = $query->count();
				$query = $query->get();
				foreach($query as $key => $row) {
					$id = $row['grade'];
					$grade = self::grade($id);
					$array['grades'][$id]['level'] = $grade['level'];
					$array['grades'][$id]['altname'] = $grade['altname'];
//					$array['grades'][$id]['list'] = $list;
//					$array['grades'][$id]['term'] = $term;
//					$array['grades'][$id]['theme'] = $theme;
//					$array['grades'][$id]['camp'] = $camp;
					$array['grades'][$id]['camps'] = self::sosCampInfo($list, $id, $term, $theme, $camp);
				}
			} else {
				$row = $query->first();
				$id = $row['grade'];
				$array['num_grades'] = 1;
				if($id == 0) {
					$array['grades'][0]['level'] = "";
					$array['grades'][0]['altname'] = "";
					$array['grades'][$id]['camps'] = self::sosCampInfo($list, $id, $term, $theme, $camp);
				} else {
					$grade = self::grade($id);
					$array['grades'][$id]['level'] = $grade['level'];
					$array['grades'][$id]['altname'] = $grade['altname'];
					$array['grades'][$id]['camps'] = self::sosCampInfo($list, $id, $term, $theme, $camp);
				}
			}
			return $array; */
		}
		
		/**
		 * Get the Name of the Camp (Camp Galileo, Galileo Summer Quest, etc...)
		 * 
		 * @param unknown_type $camp
		 */
		private static function campType($camp) {
			$row = Camps::find($camp);
			$array['name'] = $row['name'];
			$array['alt_name'] = $row['alt_name'];
			$array['abbr'] = $row['abbr'];
			return $array;
		}
		
		private static function themeNum($theme, $camp, $term, $list) {
// 			$numRows = SalesOrderSupplies::select('salesordersupplies.id')->leftjoin('parts', 'parts.number', '=', 'salesordersupplies.part')->where('salesordersupplies.camp', $camp)->where('salesordersupplies.theme', $theme)->where('salesordersupplies.term', $term)->where('salesordersupplies.list', $list)->where('parts.fishbowl', 'true')->get();
// 			return $numRows->count();
		}
		
		private static function themeName($theme) {
			$row = Themes::find($theme);
			return $row['name'];
		}
		
		/**
		 * Get the Packing List Name
		 * 
		 * @param $id
		 */
		private static function packingListName($id, $term) {
			$row = PackingLists::find($id);
			$array['list'] = $row['list'];
			$array['alt'] = $row['alt'];
// 			$num_themes = SalesOrderSupplies::select(\DB::raw('DISTINCT theme'))->wehre('term', $term)->where('list', $id)->get();
// 			$array['num_themes'] = $num_themes->count();
// 			$num_grades = SalesOrderSupplies::select(\DB::raw('DISTINCT grade'))->wehre('term', $term)->where('list', $id)->get();
// 			$array['num_grades'] = $num_grades->count();
// 			$num_locations = SalesOrderSupplies::select(\DB::raw('DISTINCT location'))->wehre('term', $term)->where('list', $id)->get();
// 			$array['num_locations'] = $num_locations->count();
			return $array;
		}
		
		private static function sosItems($term) {
// 			$query = SalesOrderSupplies::select(\DB::raw('DISTINCT salesordersupplies.list'))->select('salesordersupplies.camp', 'camps.name')->leftjoin('camps', 'camps.id', '=', 'salesordersupplies.camp')->where('salesordersupplies.term', $term)->orderBy('camps.name')->get();
// 			if($query->count() > 0) {
// 				foreach($query as $key => $row) {
// 					$list_name = $row['list'];
// 					$camp = $row['camp'];
// 					$camp_name = $row['name'];
// 					$numSOSitems = self::numSOSitems($list_name, $camp, $term);
// 					$list[$list_name]['camp_name'] = $camp_name;
// 					$list[$list_name]['camp'] = $camp;
// 					$list[$list_name]['num_items'] = $numSOSitems['numrows'];
// 					$list[$list_name]['query'] = $numSOSitems['query'];
// 				}
// 			}
// 			return $list;
//			echo "<pre>"; print_r($list); echo "</pre>"; 
//			exit; die();
		}
		
		private static function numSOSitems($list, $camp, $term) {
// 			$numrows = SalesOrderSupplies::select('salesordersupplies.id')->leftjoin('parts', 'parts.number', '=', 'salesordersupplies.part')->where('salesordersupplies.term', $term)->where('salesordersupplies.list', $list)->where('salesordersupplies.camp', $camp)->where('parts.fishbowl', 'true')->get();
// 			$array['query'] = \DB::last_query();
// 			$array['numrows'] = $numrows->count();
// 			return $array;
		}
		
		public static function numSalesOrderSupplies($camp, $term, $theme = 0, $grade = 0) {
// 			$numrows = SalesOrderSupplies::select('salesordersupplies.id')->leftjoin('materials_list', 'materials_list.id', '=', 'salesordersupplies.material')->where('salesordersupplies.camp', $camp)->where('salesordersupplies.term', $term)->where('salesordersupplies.theme', $theme)->where('salesordersupplies.grade', $grade)->where('materials_list.fishbowl', 'true')->get();
// 			return $numRows->count();
		}
		
		public static function customers() {
			$query = Customers::select(
					'CustomerID', 
					'Name');
				$query = $query->where('ActiveFlag', 'true');
				$query = $query->orderBy('Name');
				$query = $query->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['CustomerID'];
					$array[$id] = $row['Name'];
				}
			}
			return $array;
		}
		
		public static function customerInfo($name) {
			$query = CustomerAddresses::select(
					'attn', 
					'street', 
					'city', 
					'zip', 
					'state', 
					'country');
				$query = $query->where('name', $name);
				$row = $query->first();
			$array['attn'] = $row['attn'];
			$array['street'] = $row['street'];
			$array['city'] = $row['city'];
			$array['zip'] = $row['zip'];
			$array['state'] = $row['state'];
			$array['country'] = $row['country'];
			return $array;
		}
		
		/**
		 * View - Sales Orders - Packing Lists
		 * @param unknown $array
		 * @param unknown $return
		 */
		public static function view_packinglists($array, $return) {
			$url = url('/');
			if($array['term'] == "") { $term = gambaTerm::year_by_status('C'); } else { $term = $array['term']; }
			$grades = gambaGrades::grade_list();
			$packingList = gambaPacking::packing_lists($term);
			$themes_camps_all = gambaThemes::themes_camps_all($term);
			$content_array['page_title'] .= "Sales Orders - Packing Lists {$term}";
			$content_array['content'] .= <<<EOT
		<p class="directions"><strong>Directions:</strong> Select from the packing lists below to view by theme, grade and/or location.</p>
		<ul class="pagination">
			<li><a href="{$url}/sales">Sales Orders</a></li>
			<li class="disabled"><a href="#">Packing Lists</a></li>
		</ul>
EOT;
			if(!empty($packingList)) {
				$content_array['content'] .= <<<EOT
		<script>
		$(function(){ 
		    $("table").tablesorter({
				widgets: [ 'stickyHeaders' ],
				widgetOptions: { stickyHeaders_offset : 50, },
			}); 
			$("table").data("sorter", false);
		 }); 
		</script>
		<table class="table-bordered table-hover table-condensed table-small">
			<thead>
				<tr>
					<th>Packing List</th>
					<th>Themes/Grades</th>
					<th>Options</th>
				</tr>
			</thead>
			<tbody>
EOT;
				foreach($packingList['packinglists'] as $id => $values) {
					if($values['list_values']['active'] == "true") {
						$camp = $values['camp'];
						$sales_pack_by = $values['list_values']['sales_pack_by'];
	// 					$themes = gambaThemes::quick_themes_by_camp($camp, $term);
						$themes = $themes_camps_all[$camp];
						$content_array['content'] .= <<<EOT
				<tr>
					<th colspan="3" style="font-size:14px;" title="Packing List ID: {$id} - {$values['list_values']['sales_pack_by']}">{$values['alt']}</th>
				</tr>
EOT;
						if($sales_pack_by == "theme") {
						 	if(!empty($themes)) {
								foreach($themes as $theme => $theme_values) {
									if(($camp == $theme_values['theme_camp'] && $theme_values['this_camp'] == "true") 
									|| ($camp != $theme_values['theme_camp'] && $theme_values['this_camp'] == "true")
							) {
									$num_rows = PackingTotals::select('id')->where('packing_id', $id)->where('theme', $theme)->where('term', $term)->count();
// 									$num_rows = $query->count();
									if($num_rows > 0) { $view_disabled = ""; } else { $view_disabled = " disabled"; }
									$content_array['content'] .= <<<EOT
				<tr>
					<td></td>
					<td title="{$theme}">{$theme_values['name']}</td>
					<td align="center"><a href="{$url}/sales/camp_locations?list={$id}&theme={$theme}&term={$term}&camp={$camp}&packby=theme" class='button small{$view_disabled}'>View</a></td>
				</tr>
EOT;
									}
								}
							} 
						}
						elseif($sales_pack_by == "theme-grade") {
							if(!empty($themes)) {
								foreach($themes as $theme => $theme_values) {
									if((($camp == $theme_values['theme_camp'] && $theme_values['this_camp'] == "true") || 
										($camp != $theme_values['theme_camp'] && $theme_values['this_camp'] == "true")) && 
										($theme_values['theme_type'] == $values['list_values']['theme_type'])) {
										foreach($grades[$camp]['grades'] as $grade => $grade_values) {
											if($grade_values['grade_options']['exclude_packing'] != "true") {
												$num_rows = PackingTotals::select('id')->where('packing_id', $id)->where('grade', $grade)->where('theme', $theme)->where('term', $term)->count();
// 												$num_rows = $query->count();
												if($num_rows > 0) { $view_disabled = ""; } else { $view_disabled = " disabled"; }
												$content_array['content'] .= <<<EOT
				<tr>
					<td></td>
					<td title="{$theme}">{$theme_values['name']} &gt; {$grade_values['level']}</td>
					<td align="center"><a href="{$url}/sales/camp_locations?list={$id}&theme={$theme}&grade={$grade}&term={$term}&camp={$camp}&packby=theme-grade" class='button small{$view_disabled}'>View</a></td>
				</tr>
EOT;
											}
										}
									}
								}
							}
						} else {
							foreach($grades[$camp]['grades'] as $grade => $grade_values) {
								if($grade_values['grade_options']['exclude_packing'] != "true") {
									$num_rows = PackingTotals::select('id')->where('packing_id', $id)->where('grade', $grade)->where('term', $term)->count();
// 									$num_rows = $query->count();
									if($num_rows > 0) { $view_disabled = ""; } else { $view_disabled = " disabled"; }
									$content_array['content'] .= <<<EOT
				<tr>
					<td></td>
					<td title="{$grade}">{$grade_values['level']}</td>
					<td align="center"><a href="{$url}/sales/camp_locations?list={$id}&grade={$grade}&term={$term}&camp={$camp}&packby=grade" class='button small{$view_disabled}'>View</a></td>
				</tr>
EOT;
								}
							}
						}
					}
				}
				$content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
			}
//			$content_array['content'] .= gambaDebug::preformatted_arrays($packingList, 'packing_list_array', 'Packing Lists', 'true');
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($themes_camps_all, 'themes_camps_all_array', 'Themes Camps All');
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($grades, 'grades_list', 'Grades');
			return $content_array;
		}

		public static function sales_order_status($list, $term, $theme, $grade, $location, $dli) {
			$query = SalesOrders::select(
					'id', 
					'customer', 
					'fulfillment_date', 
					'fishbowl', 
					'date_created');
				$query = $query->where('list', $list);
				$query = $query->where('term', $term);
				$query = $query->where('location', $location);
				$query = $query->where('dli', $dli);
			if($theme != "" || $theme != 0) {
				$query = $query->where('theme', $theme);
			}
			if($grade != "" || $grade == 0) {
				$query = $query->where('grade', $grade);
			}
			$row = $query->first();
			$array['soid'] = $row['id'];
			$array['customer'] = $row['customer'];
			$array['fullfillment_date'] = $row['fullfillment_date'];
			$array['fishbowl'] = $row['fishbowl'];
			$array['date_created'] = $row['date_created'];
			return $array;
		}
		
		public static function view_camplocations($array, $return) {
			$url = url('/');
			$user_id = Session::get('uid');
			$packing_lists = gambaPacking::packing_lists();
			$grades = gambaGrades::grade_list();
			$term = $array['term'];
			$theme = $array['theme'];
			$grade = $array['grade'];
			$list = $array['list'];
			$packby = $array['packby'];
			$camp = $packing_lists['packinglists'][$list]['camp'];
			if($user_id == 1) { 
				$content_array['content'] = "<a href='{$url}/sales/camp_locationparts?list=$list&theme=$theme&grade=$grade&term=$term&camp=$camp&debug_override=true'>See Camps and Parts</a>";
			}
			// By Theme
			if($packby == "theme" && $theme != "" && $grade == "") {
				$themes = gambaThemes::theme_by_id($theme);
				$locations = self::supply_theme_locations($term, $theme, $list, $camp);
				$content_array['page_title'] .= "Sales Orders for ".$packing_lists['packinglists'][$list]['alt']." &gt; ".$themes['name'];
				$content_array['content'] .= gambaDirections::getDirections('camp_locations_theme');
				if(is_array($locations['locations'])) {
					$content_array['content'] .= '<div class="row">'."\n";
					foreach($locations['locations']['grade'] as $grade => $location_data) {
						$content_array['content'] .= '<div class="small-12 medium-4 large-4 columns">'."\n";
						$content_array['content'] .= "<h2>{$grades[$camp]['grades'][$grade]['level']}</h2>\n";
						foreach($location_data['location'] as $location => $values) {
							$location_info = gambaLocations::location_by_id($location);
							foreach($values['dli'] as $dli => $parts_info) {
								$number = count($parts_info);
								$fishbowl_status = "";
								$sales_order_status = self::sales_order_status($list, $term, $theme, $grade, $location, $dli);
								$content_array['content'] .= <<<EOT
										<div class="small-12 medium-6 large-6 columns sales-list">
										<a href="{$url}/sales/salesorder?soid={$sales_order_status['soid']}&dli={$dli}&list={$list}&theme={$theme}&term={$term}&camp={$camp}&location={$location}&grade={$grade}&packby={$packby}">{$location_info['name']}
EOT;
								if($dli == 1) { $content_array['content'] .= " 2"; } 
								$content_array['content'] .= " - ". $location_info['abbr'];
								if($sales_order_status['soid'] != "") {
									$fishbowl_status = ' fishbowl-pushed';
									if($sales_order_status['fishbowl'] == "false") { 
										$fishbowl_status = ' fishbowl-waiting'; 
									}
								} 
								$content_array['content'] .= <<<EOT
								<span class="label round{$fishbowl_status}">{$number}</span></a>
							</div>
EOT;
							}
						}
						$content_array['content'] .= "</div>\n";
					}
					$content_array['content'] .= "</div>\n";
				}
			}
			// By Theme > Grade

			if($packby == "theme-grade" && $theme != "" && $grade != "") {
				$themes = gambaThemes::theme_by_id($theme);
				$locations = self::supply_theme_grade_locations($term, $theme, $grade, $list);
				$content_array['page_title'] .= "Sales Orders for ".$packing_lists['packinglists'][$list]['alt']." &gt; Theme: ".$themes['name']." &gt; Grade: ".$grades[$camp]['grades'][$grade]['level'];
				$content_array['content'] .= gambaDirections::getDirections('camp_locations_theme_grade');
				if(is_array($locations['locations'])) {
					$content_array['content'] .= '<div class="row">'."\n";
					foreach($locations['locations']['grade'][$grade]['location'] as $location => $values) {
						$location_info = gambaLocations::location_by_id($location);
						foreach($values['dli'] as $dli => $parts_info) {
							$number = count($parts_info);
							$fishbowl_status = "";
							$soid = "";
							$sales_order_status = self::sales_order_status($list, $term, $theme, $grade, $location, $dli);
							$content_array['content'] .= <<<EOT
							<div class="small-12 medium-3 large-3 columns">
							<a href="{$url}/sales/salesorder?soid={$sales_order_status['soid']}&dli={$dli}&list={$list}&theme={$theme}&grade={$grade}&term={$term}&camp={$camp}&location={$location}&grade={$grade}&packby={$packby}">{$location_info['name']}
EOT;
							if($dli == 1) { $content_array['content'] .= " 2"; }
							$content_array['content'] .= " - ". $location_info['abbr'];
							if($sales_order_status['soid'] != "") {
								$fishbowl_status = ' fishbowl-pushed';
								if($sales_order_status['fishbowl'] == "false") { $fishbowl_status = ' fishbowl-waiting'; }
							}
							$content_array['content'] .= <<<EOT
							<span class="label round{$fishbowl_status}">{$number}</span></a>
						</div>
EOT;
						}
					}
					$content_array['content'] .= "</div>\n";
				}
			}
			// By Grade
			if($packby == "grade" && $theme == "" && $grade != "") {
				$locations = self::supply_grade_locations($term, $grade, $list);
				$content_array['page_title'] .= "Sales Orders for ".$packing_lists['packinglists'][$list]['alt']." &gt; Grade: ".$grades[$camp]['grades'][$grade]['level'];
				$content_array['content'] .= gambaDirections::getDirections('camp_locations_grade');
				if(is_array($locations['locations'])) {
					$content_array['content'] .= '<div class="row">'."\n";
					foreach($locations['locations']['grade'][$grade]['location'] as $location => $values) {
						$location_info = gambaLocations::location_by_id($location);
						foreach($values['dli'] as $dli => $parts_info) {
							$number = count($parts_info);
							$fishbowl_status = "";
							$soid = "";
							$sales_order_status = self::sales_order_status($list, $term, $theme, $grade, $location, $dli);
							$content_array['content'] .= <<<EOT
							<div class="small-12 medium-3 large-3 columns">
							<a href="{$url}/sales/salesorder?soid={$sales_order_status['soid']}&dli={$dli}&list={$list}&grade={$grade}&term={$term}&camp={$camp}&location={$location}&grade={$grade}&packby={$packby}">{$location_info['name']}
EOT;
							if($dli == 1) { $content_array['content'] .= " 2"; } 
							$content_array['content'] .= " - ". $location_info['abbr'];
							if($sales_order_status['soid'] != "") {
								$fishbowl_status = ' fishbowl-pushed';
								if($sales_order_status['fishbowl'] == "false") { $fishbowl_status = ' fishbowl-waiting'; }
							} 
							$content_array['content'] .= <<<EOT
							<span class="label round{$fishbowl_status}">{$number}</span></a>
						</div>
EOT;
						}
					}
					$content_array['content'] .= "</div>\n";
				}
			}
			$content_array['content'] .= gambaDebug::preformatted_arrays($locations, 'locations', 'Locations');
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($packing_lists, 'packing', 'Packing Lists');
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($grades, 'grades', 'Grades');
			return $content_array;
		}
		
		/* Debugging Purposes */
		public static function view_camplocationparts($array, $return) {
			$url = url('/');
			$packing_lists = gambaPacking::packing_lists();
			$grades = gambaGrades::grade_list();
			$term = $array['term'];
			$theme = $array['theme'];
			$grade = $array['grade'];
			$list = $array['list'];
			$packby = $array['packby'];
			$camp = $packing_lists['packinglists'][$list]['camp'];
			if($theme != "") {
				$themes = gambaThemes::theme_by_id($theme);
				$locations = self::supply_theme_locations($term, $theme, $list, $camp);
				$content_array['page_title'] = "Sales Orders for ".$packing_lists['packinglists'][$list]['alt']." &gt; ".$themes['name'];
				$content_array['content'] = gambaDirections::getDirections('camp_locations_theme');
				if(is_array($locations['locations'])) {
					$content_array['content'] .= '<div class="row">'."\n";
					foreach($locations['locations'] as $grade => $location_data) {
						$content_array['content'] .= <<<EOT
						<div class="small-12 medium-4 large-4 columns">
						<h2>{$grades[$camp]['grades'][$grade]['level']}</h2>
EOT;
						foreach($location_data as $location => $values) {
							$location_info = gambaLocations::location_by_id($location);
							foreach($values as $dli => $parts_info) {
								$number = count($parts_info);
								$fishbowl_status = "";
								$sales_order_status = self::sales_order_status($list, $term, $theme, $grade, $location, $dli);
								$content_array['content'] .= <<<EOT
							<div class="small-12 medium-6 large-6 columns sales-list">
								<a href="{$url}/sales/salesorder?soid={$sales_order_status['soid']}&dli={$dli}&list={$list}&theme={$theme}&term={$term}&camp={$camp}&location={$location}&grade={$grade}&packby={$packby}">{$location_info['name']} 
EOT;
								if($dli == 1) { $content_array['content'] .= " 2"; }
								if($sales_order_status['soid'] != "") {
									$fishbowl_status = ' fishbowl-pushed';
									if($sales_order_status['fishbowl'] == "false") {
										$fishbowl_status = ' fishbowl-waiting'; 
									}
								}
								$content_array['content'] .= <<<EOT
								<span class="label round{$fishbowl_status}">{$number}</span></a>
							</div>
EOT;
							}
						}
						$content_array['content'] .= "</div>\n";
					}
					$content_array['content'] .= "</div>\n";
				}
			}
			if($theme == "" && $grade != "") {
				$locations = self::supply_grade_locations($term, $grade, $list);
				$content_array['page_title'] = "Sales Orders for ".$packing_lists['packinglists'][$list]['alt']." &gt; Grade: ".$grades[$camp]['grades'][$grade]['level'];
				$content_array['content'] .= gambaDirections::getDirections('camp_locations_grade');
				if(is_array($locations['locations'])) {
					$content_array['content'] .= '<div class="row">'."\n";
					foreach($locations['locations']['grade'][$grade]['location'] as $location_id => $values) {
						$location_info = gambaLocations::location_by_id($location_id);
						$number = count($values['parts']);
						$fishbowl_status = "";
						$soid = "";
						$sales_order_status = self::sales_order_status($list, $term, $theme, $grade, $location, $dli);
						$content_array['content'] .= <<<EOT
						<div class="small-12 medium-3 large-3 columns">
							<a href="{$url}/sales/salesorder?soid={$sales_order_status['soid']}&dli={$dli}&list={$list}&grade={$grade}&term={$term}&camp={$camp}&location={$location}&grade={$grade}&packby={$packby}">{$location_info['name']} 
EOT;
						if($dli == 1) { $content_array['content'] .= " 2"; }
						if($sales_order_status['soid'] != "") {
							$fishbowl_status = ' fishbowl-pushed';
							if($sales_order_status['fishbowl'] == "false") { $fishbowl_status = ' fishbowl-waiting'; }
						}
						$content_array['content'] .= <<<EOT
						<span class="label round{$fishbowl_status}">{$number}</span></a><br />
EOT;
// 						foreach ($values['parts'] as $part_number => $part_info) {
// 							$content_array['content'] .= "$part_number - " . $part_info['part_desc'] . "<br />";
// 						}
						$content_array['content'] .= "</div>\n";
					}
					$content_array['content'] .= "</div>\n";
				}
			}
// 			gambaDebug::preformatted_arrays($locations, 'locations', 'Locations', $_REQUEST['debug_override']);
// 			gambaDebug::preformatted_arrays($packing_lists, 'packing', 'Packing Lists');
// 			gambaDebug::preformatted_arrays($grades, 'grades', 'Grades');
			return $content_array;
		}
		
		/**
		 * View - Sales Orders
		 * @param unknown $array
		 * @param unknown $return
		 */
		public static function view_salesorders($array, $return) {
			$url = url('/');
			//exec(php_path . " " . Site_path . "execute_export_php export_sales_orders > /dev/null &");
			$job = (new ExportSalesOrders())->onQueue('export');
			dispatch($job);
			$term = gambaTerm::year_by_status('C');
			$soList = self::soList($term);
			// <p class="directions"><strong>Directions:</strong> Items with the green fish have been pushed to Fishbowl. Items with a red fish are awaiting a push.</p>
			$content_array['page_title'] = "Sales Orders";
			$content_array['content'] .= gambaDirections::getDirections('view_salesorders');
			$content_array['content'] = <<<EOT
					
					<ul class="pagination">
						<li class="disabled"><a href="#">Sales Orders</a></li>
						<li><a href="{$url}/sales/packinglists">Packing Lists</a></li>
					</ul>				
EOT;
			if(is_array($soList['orders'])) {
				$content_array['content'] .= <<<EOT
			
		<script>
		$(function(){ 
		    $("table").tablesorter({
				widgets: [ 'stickyHeaders' ],
				widgetOptions: { stickyHeaders_offset : 50, },
			}); 
			$("table").data("sorter", false);
		 }); 
		</script>
					<table class="table table-striped table-bordered table-hover table-condensed table-small tablesorter">
						<thead>
						<tr>
							<th></th>
							<th>Term</th>
							<th>Number</th>
							<th>Packing Location</th>
							<th>Customer Name</th>
							<th>Camp/List</th>
							<th>Theme</th>
							<th>Grade</th>
							<th>Status</th>
							<th>Date Created</th>
							<th>Fulfillment Date</th>
							<th></th>
						</tr>
						<thead>
						<tbody>
EOT;
				$fb_pre = config('fishbowl.fbpre');
				foreach($soList['orders'] as $key => $value) {
					
					if($value['fishbowl'] == "true") { 
						$fishbowl_image = '<img src="img/fishbowl_true_icon.png" width="25" height="25" title="Pushed to Fishbowl" />'; 
					} elseif($value['fishbowl'] == "false" && $value['push_error'] == "true") { 
						$fishbowl_image = '<a href="'.$url.'/sales/so_mark_pushed?soid='.$key.'" onclick="return confirm(\'There has been an error in confirming data pushed to Fishbowl. Click OK to mark as pushed.\');"><img src="img/fishbowl_edit_icon.png" width="25" height="25" title="Push Error, Check Fishbowl" /></a>'; 
					} else { 
						$fishbowl_image = '<img src="img/fishbowl_false_icon.png" width="25" height="25" title="Awaiting Push" />'; 
					}
					$date_created = date("M j, Y", strtotime($value['date_created']));
					if($value['fulfillmentdate'] != "") { $fulfillmentdate = date("M j, Y", strtotime($value['fulfillmentdate'])); } else { $fulfillmentdate = "N/A"; }
					if($value['dli'] == 1) { $dli = " - 2"; } else { $dli = ""; }
					$content_array['content'] .= <<<EOT
						<a name="so_$key" />
						<tr>
							<td><a href="{$url}/sales/salesorder?soid={$key}&dli={$value['dli']}&list={$value['list']}&theme={$value['theme']}&term={$value['term']}&camp={$value['camp']}&location={$value['camp_location']}&grade={$value['grade']}&packby={$value['list_values']['sales_pack_by']}" class="button small">View</a></td>
							<td>{$value['term']}</td>
							<td>{$fb_pre}SO-{$key}</td>
							<td>[{$value['camp_location']}] {$value['camp_abbr']} {$value['location_name']}{$dli}</td>
							<td>[{$value['customer_id']}] {$value['customer']}</td>
							<td>{$value['camp_name']}/
								{$value['listname']}</td>
							<td>{$value['theme_name']}</td>
							<td>{$value['grade_level']}</td>
							<td class="center">{$fishbowl_image}</td>
							<td>{$date_created}</td>
							<td>{$fulfillmentdate}</td>
							<td><a href="{$url}/sales/salesorderdelete?soid={$key}" onClick="return confirm('Are you sure you want to delete this sales order? This action can not be undone!');" class="button small">Delete</a></td>
						</tr>
EOT;
				}
				$content_array['content'] .= <<<EOT
				</tbody>
					</table>
EOT;
			} else {
				$content_array['content'] .= "<p>".$soList['message']."</p>";
			}
// 			gambaDebug::preformatted_arrays($soList, 'solist', "Sales Order List");
			return $content_array;
		}
		
		public static function fb_so_push_error($id, $message) {
			$salesorder = SalesOrders::find($id);
			$salesorder->fb_err_msg = $message;
			$salesorder->fb_err_date = date("Y-m-d H:i:s");
			$salesorder->save();
		}
		
		/**
		 * View Sales Order
		 * @param unknown $array
		 * @param unknown $return
		 */
		public static function view_salesorder($array, $return) {
			$url = url('/');
			$user_id = Session::get('uid');
			$term = $array['term']; $list = $array['list']; $theme = $array['theme']; $camp = $array['camp'];
			$dli = $array['dli']; $location = $array['location']; $grade = $array['grade']; $soid = $array['soid'];
			$packby = $array['packby'];
			$uom_list = gambaUOMs::uom_list();
			$camps = gambaCampCategories::camps_list();
			$packinglists = gambaPacking::packing_lists();
			$locations = gambaLocations::location_by_id($location);
			if($grade != "") { $grades = gambaGrades::grade_list(); }
			if($theme != "") { $themes = gambaThemes::theme_by_id($theme); }
			$salesOrder = self::salesOrder($list, $term, $theme, $camp, $location, $dli, $grade, $soid, $packby);
			if($dli == 2) { $dstar = 2; }
			if($packby == "theme-grade" || $packby == "grade") {
				$grades = gambaGrades::grade_list();
				$grade_link = $grade;
				$header_description = "List: " . $packinglists['packinglists'][$list]['alt'] . " &gt; Grade: " . $grades[$camp]['grades'][$grade]['level'];
				if($packby == "grade") { $theme = ""; }
			} else {
				$themes = gambaThemes::theme_by_id($theme);
				$header_description = "List: " . $packinglists['packinglists'][$list]['alt'] . " &gt; Theme: " . $themes['name'];
				if($grade != "" && $grade != 0) { $header_description .= " &gt; Grade: " . $grades[$camp]['grades'][$grade]['level']; }
			}
			$content_array['page_title'] = "Sales Order &gt; Location: {$locations['name']} {$dstar} - {$locations['abbr']} &gt; {$header_description}";
			$content_array['content'] = <<<EOT
					<p class="directions"><strong>Directions:</strong> Please review the following products. When you are ready click the &quot;Push to Fishbowl&quot; button. Items in red have no corresponding product in Fishbowl and have to be added or excluded. Items come from the calculated packing list. If any items are missing they may be excluded from Fishbowl or need to be recalculated from the packing list.</p>
					<ul class="pagination">
						<li><a href="{$url}/sales">Sales Orders</a></li>
						<li><a href="{$url}/sales/packinglists">Packing Lists</a></li>
						<li><a href="{$url}/sales/camp_locations?list={$list}&theme={$theme}&grade={$grade_link}&term={$term}&camp={$camp}&packby={$packby}">Sales Orders by Camp</a></li>
					</ul>
EOT;

			$fishbowl_response = $salesOrder['fb_err_msg'];
			if($fishbowl_response['push_status_code'] == 1000) {
				$content_array['content'] .= '<div data-alert class="alert-box success radius">
			<strong>Status:</strong> Sales Order Pushed to Fishbowl! <a href="#" class="close">&times;</a></div>';
			}
			if($fishbowl_response['push_status_code'] != "" && $fishbowl_response['push_status_code'] != 1000) {
				$content_array['content'] .= '<div data-alert class="alert-box alert radius">
			<strong>FB Code '.$fishbowl_response['push_status_code'].':</strong> '.$fishbowl_response['push_status_message'].' <a href="#" class="close">&times;</a></div>';
			}
			
			$customers = gambaCustomers::customer_list();
			$customerList = '<select name="customer" class="form-control"';
			if($salesOrder['soStatus'] == "true") { $customerList .= " disabled"; }
			$customerList .= ' style="width:150px;">'."\n";
			$customerList .= '<option value="">select...</option>'."\n";
			foreach($customers['customers'] as $key => $value) {
				if($value['CustomerID'] == $salesOrder['soCustomerID']) { $customerListSelected = " selected"; } else { $customerListSelected = ""; }
				$customerList .= "<option value=\"{$value['CustomerID']}\"'{$customerListSelected}>[{$value['CustomerID']}] {$value['Name']}</option>\n";
// 					$customerName = $value;
// 				} 
			}
			$customerList .= "</select>\n";	
			if($array['error'] == "fishbowl") {
				$content_array['content'] .= '<p class="error">Fishbowl server was not accessed.</p>';
			}
			if($array['error'] == "push") {
				$content_array['content'] .= '<p class="error">Error pushing data to fishbowl.<br />'.$array['statusMessage'].'</p>';
				self::fb_so_push_error($soid, $array['statusMessage']);
			}
			
			// Create Sales Order
			if($soid == "") {	
				if($array['error'] == "customer") {
					$content_array['content'] .= '<p class="error">You need to select a Customer.</p>';
				}
				$content_array['content'] .= <<<EOT
					<form method="post" action="{$url}/sales/salesordercreate" name="create" class='form-inline'>	
EOT;
				$content_array['content'] .= csrf_field();
				$content_array['content'] .= <<<EOT
						<div>
							<label>Customer</label> {$customerList} <input type="submit" name="submit" value="Create Sales Order" class='button small' /></p>
						</div>
						<input type="hidden" name="action" value="salesordercreate" />
						<input type="hidden" name="term" value="{$term}" />
						<input type="hidden" name="list" value="{$list}" />
						<input type="hidden" name="theme" value="{$theme}" />
						<input type="hidden" name="camp" value="{$camp}" />
						<input type="hidden" name="dli" value="{$dli}" />
						<input type="hidden" name="location" value="{$location}" />
						<input type="hidden" name="grade" value="{$grade}" />
						<input type="hidden" name="packby" value="{$packby}" />
					</form>
EOT;
			// Live Sales Order
			} else {
				$customer_name = $customers['customers'][$salesOrder['soCustomer']]['Name'];
				$customerInfo = self::customerInfo($salesOrder['soCustomer']);
				$attn = $customerInfo['attn'];
				$street = $customerInfo['street'];
				$city = $customerInfo['city'];
				$zip = $customerInfo['zip'];
				$state = $customerInfo['state'];
				$country = $customerInfo['country'];
				$address = $attn . "<br />" . $street . "<br />" . $city . ", " . $state . " " . $zip . "<br />" . $country;
				if($salesOrder['soFulfillmentDate'] != "") { $soFulfillmentDate = date("n/j/Y", strtotime($salesOrder['soFulfillmentDate'])); }
				if($salesOrder['soStatus'] == "true") { $sostatus_disabled = "disabled "; } 
				$fb_pre = config('fishbowl.fbpre');
				$content_array['content'] .= <<<EOT
					<form method="post" action="{$url}/sales/salesorderupdate" name="save">
EOT;
				$content_array['content'] .= csrf_field();
				$content_array['content'] .= <<<EOT
						<div class="row sales-form">
							<div class="row">
								<div class="small-12 medium-2 large-2 columns">
									<label class="">Customer:</label>
								</div>
								<div class="small-12 medium-2 large-2 columns">{$customerList}</div>
								<div class="small-12 medium-2 large-2 columns">
									<label class="">SO No:</label>
								</div>
								<div class="small-12 medium-2 large-2 columns">
									<input type="text" name="tempSoNum" value="{$fb_pre}SO-{$soid}" class="form-control" disabled />
								</div>
								<div class="small-12 medium-2 large-2 columns">
									<label class="">Status:</label>
								</div>
								<div class="small-12 medium-2 large-2 columns">Estimate</div>
							</div>
							<div class="row">
								<div class="small-12 medium-2 large-2 columns">
									<label class="">Fulfillment Date:</label>
								</div>
								<div class="small-12 medium-2 large-2 columns">
									<input type="text" name="soFulfillmentDate" value="{$soFulfillmentDate}" class="form-control" {$sostatus_disabled}/>
								</div>
								<div class="small-12 medium-2 large-2 columns">
									<label class="">Customer Po:</label>
								</div>
								<div class="small-12 medium-2 large-2 columns">
									<input type="text" name="customerPo" value="" class="form-control" disabled />
								</div>
								<div class="small-12 medium-2 large-2 columns">
									<label class="">Vendor Po:</label>
								</div>
								<div class="small-12 medium-2 large-2 columns">
									<input type="text" name="vendorPo" value="" class="form-control" disabled />
								</div>
							</div>
							<div class="row">
								<div class="small-12 medium-6 large-6 columns">
									<div class="row">
									
									</div>
									<div class="row">
									
									</div>
									<div class="row">
										<div class="small-12 medium-12 large-12 columns address-box-header">Bill To</div>
									</div>
									<div class="row">
										<div class="small-12 medium-12 large-12 columns address-box">{$address}</div>
									</div>
								</div>
								<div class="small-12 medium-6 large-6 columns">
									<div class="row">
									
									</div>
									<div class="row">
									
									</div>
									<div class="row">
										<div class="small-12 medium-12 large-12 columns address-box-header">Ship To</div>
									</div>
									<div class="row">
										<div class="small-12 medium-12 large-12 columns address-box">{$address}</div>
									</div>
								</div>
							</div>
EOT;
				if($salesOrder['soStatus'] == "false") {
					if($salesOrder['incomplete'] == 'true' || $salesOrder['soFulfillmentDate'] == "") { 
						$push_disabled = "disabled "; 
					}
					$content_array['content'] .= <<<EOT
							<div class="row">
								<div class="small-12 medium-6 large-6 columns">
									<input type="submit" name="submit" value="Save Sales Order" class="button small" />
								</div>
								<div class="small-12 medium-6 large-6 columns">
									<input type="submit" name="submit" value="Push Sales Order to Fishbowl" class="button small" {$push_disabled}/>
EOT;
					if($salesOrder['soStatus'] == "false" && $salesOrder['xmlstring'] != "") {
						$content_array['content'] .= "&nbsp;<a href=\"{$url}/sales/so_mark_pushed?soid=$soid\" class=\"button small\">Mark as Pushed</a>";
					}
					$content_array['content'] .= <<<EOT
								</div>
							</div>
EOT;
				}
				$content_array['content'] .= "</div>";
				if($salesOrder['soStatus'] == "false") {
					$customer_name = $customers['customers'][$salesOrder['soCustomer']]['Name'];
					$content_array['content'] .= <<<EOT
						<input type="hidden" name="soid" value="{$soid}" />
						<input type="hidden" name="term" value="{$term}" />
						<input type="hidden" name="list" value="{$list}" />
						<input type="hidden" name="theme" value="{$theme}" />
						<input type="hidden" name="camp" value="{$camp}" />
						<input type="hidden" name="customer_id" value="{$salesOrder['soCustomerID']}" />
						<input type="hidden" name="customer_name" value="{$salesOrder['soCustomer']}" />
						<input type="hidden" name="dli" value="{$dli}" />
						<input type="hidden" name="location" value="{$location}" />
						<input type="hidden" name="grade" value="{$grade}" />
						<input type="hidden" name="action" value="salesorderupdate" />
						<input type="hidden" name="class" value="{$camps[$camp]['alt_name']}:{$locations['name']}" />
						<input type="hidden" name="packby" value="{$packby}" />
EOT;
				}

			}
// 			echo "<pre>"; print_r($salesOrder['orders']); echo "</pre>";

// 			if($user_id == 1) {
// 				$row_header_exclude_part = "<th></th>";
// 			}
			$content_array['content'] .= <<<EOT
		
		<script>
		$(function(){ 
		    $("table").tablesorter({
				widgets: [ 'stickyHeaders' ],
				widgetOptions: { stickyHeaders_offset : 50, },
			}); 
			$("table").data("sorter", false);
		 }); 
		</script>
			<table class="table table-striped table-bordered table-hover table-condensed table-small table-fixed-header">
				<thead>
					<tr>
						{$row_header_exclude_part}
						<th>#</th>
						<th class="right">Number</th>
						<th>Description</th>
						<th class="center">Qty</th>
						<th class="center">UoM</th>
						<th>Notes</th>
						<th>Purchase Notes</th>
						<th class="right">Unit Price</th>
						<th class="center">Total</th>
						<th class="center">Type</th>
						<th class="center">Status</th>
						<th>Date Scheduled</th>
						<th>Class</th>
					</tr>
				</thead>
				<tbody>
EOT;
			$i = 1;
			foreach($salesOrder['orders'] as $key => $value) {
// 				$qty = 0;
				$qty = ceil($value['value']);
// 				foreach($value['values'] as $sosid => $amount) {
// 					$qty = $qty + $amount['value'];
// 				}
				if($value['product_status'] == "false") {
					$highlight = ' class="notmatched"';
				} else {
					$highlight = "";
				}
				$alpha = strtolower(substr($value['part_desc'], 0, 1));
				if($value['conversion'] > 0) { $flag = " [F]"; } else { $flag = ""; }
				$total = $value['price'] * $qty;
				$total = number_format($total, 2);
				if($qty == 0) { $qty_red = ' style="color: red;"'; }
				$price = number_format($cost, 2);
				$date = date("M j, Y");
				$cw_notes = "";
				$cw_notes_display = "";
				if(is_array($value['notes'])) {
					foreach($value['notes'] as $supply_id => $supply_values) {
						$cw_notes_display .= "<strong>";
						if($supply_values['activity_info']['theme_name']) { 
							$cw_notes .= $supply_values['activity_info']['theme_name'] . " - "; 
							$cw_notes_display .= $supply_values['activity_info']['theme_name'] . " &gt; "; 
						}
						if($supply_values['activity_info']['grade_name']) { 
							$cw_notes .= $supply_values['activity_info']['grade_name'] . " - "; 
							$cw_notes_display .= $supply_values['activity_info']['grade_name'] . " &gt; "; 
						}
						$cw_notes .= $supply_values['activity_info']['name'] . ": " . $supply_values['notes'] . " | ";
						$cw_notes_display .= $supply_values['activity_info']['name'] . ":</strong> " . $supply_values['notes'] . "<br />\n";
					}
				}
				$search = array('"', "'", ","); $replace = array("", "", " ");
				$cw_notes = str_replace($search, $replace, $cw_notes);
// 				if($user_id == 1) {
// 					$row_exclude_part = "<td><input type=\"checkbox\" name=\"products[{$key}][part_exclude]\" value=\"{$value['part']}\" /></td>";
// 				}
				$content_array['content'] .= <<<EOT
							<tr{$highlight}>
								{$row_exclude_part}
								<td>{$i}</td>
								<td>{$value['part']}</td>
								<td>{$value['part_desc']} {$flag}<input type="hidden" name="products[{$key}][part_desc]" value="{$value['part_desc']}" /></td>
								<td class="center"{$qty_red}>{$qty}<input type="hidden" name="products[{$key}][qty]" value="{$qty}" /></td>
								<td class="center" title="{$value['fbuom']}">{$uom_list['codes'][$value['fbuom']]}<input type="hidden" name="products[{$key}][uom]" value="{$value['fbuom']}" /></td>
								<td>{$cw_notes_display}<input type="hidden" name="products[{$key}][cw_notes]" value="{$cw_notes}" /></td>
								<td>{$value['adminnotes']}</td>
								<td>&#36;{$value['price']}<input type="hidden" name="products[{$key}][price]" value="{$value['price']}" /></td>
								<td>&#36;{$total}<input type="hidden" name="products[{$key}][total]" value="{$total}" /></td>
								<td class="center">Sale</td>
								<td class="center">Entered</td>
								<td>{$date}</td>
								<td>{$camps[$camp]['name']}:{$locations['name']}</td>
							</tr>
EOT;
				$i++;
			}
			$content_array['content'] .= <<<EOT
						</tbody>
					</table>
				</form>
EOT;
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($customerInfo, 'customer_info', 'Customer Info');
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($customers, 'customers', 'Customers');
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($array, 'input_array', 'Input Array');
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($salesOrder, 'sales_order_array', 'Sales Orders');
			if($user_id == 1) {
				//$content_array['content'] .= "<pre>"; $content_array['content'] .= print_r($salesOrder, true); $content_array['content'] .= "</pre>";
			}
			return $content_array;
		}
		
	}
