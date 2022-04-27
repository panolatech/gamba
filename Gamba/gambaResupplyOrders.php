<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	use Illuminate\Support\Facades\Auth;
	
	use App\Models\Camps;
	use App\Models\Locations;
	use App\Models\ReorderItems;
	use App\Models\Reorders;
	use App\Models\Supplies;
	use App\Models\SupplyLists;
	
	use App\Gamba\gambaActivities;
	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaFishbowl;
	use App\Gamba\gambaLocations;
	use App\Gamba\gambaProducts;
	use App\Gamba\gambaSupplies;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaUsers;
	
	class gambaResupplyOrders {
		
		/**
		 * reorders
		 * 
		 * @param unknown_type $term
		 * @param unknown_type $permission
		 * @param unknown_type $camp
		 * @return unknown
		 */
		public static function reorders($permission, $locations, $view = "editable", $limit_num = NULL, $page = 0) {
			$term = gambaTerm::year_by_status('C');
			self::marked_as_shipping();
			if($view == "shipped") {
				$status = 3;
				$shipping = 2;
			} elseif($view == "shipping") {
				$status = 2;
				$shipping = 1;
			} elseif ($view == "production") {
				$status = 2;
				$shipping = 0;
			} elseif ($view == "pushfb") {
				$status = 1;
				$shipping = 0;
			} else {
				$view = "editable";
				$status = 0;
				$shipping = 0;
			}
			
			// production, shipping shipped
// 			if(is_array($locations) && $permission == 4) {
// 				$string = "(camp = 0";
// 				foreach($locations as $camp => $location_array) {
// 					foreach($location_array as $key => $value) {
// 						$new_array[] = $value;
// 					}
// 				}
// 				$new_array = array_unique($new_array);
// 				foreach($new_array as $key => $value) {
// 					$string .= " OR camp = $value";
// 				}
// 				$string .= ")";
// 				$array['num_reorders'] = Reorders::select('id')->where('term', $term)->where('status', $status)->where('shipping', $shipping)->whereRaw($string)->count();
// 				$array['pages'] = self::reorder_pages($limit_num, $array['num_reorders']);
				
// 				$reorders = Reorders::select('id', 'term', 'status', 'camp', 'created', 'updated', 'user', 'xmlstring');
// 				$reorders = $reorders->where('term', $term);
// 				$reorders = $reorders->whereRaw($string);
// // 				if($limit_num != NULL) {
// // 					$reorders = $reorders->take($limit_num);
// // 					if($page > 1) {
// // 						$skip = $limit_num * ($page - 1);
// // 						$reorders = $reorders->skip($skip);
// // 					}
// // 				}
// 				$reorders = $reorders->orderBy('created', 'desc');
// 				$array['sql'] = $reorders->toSql();
// 				$array['term'] = $term;
// 				$reorders = $reorders->get();
// 			} else {
				
				$array['num_shipped'] = Reorders::select('id')->where('term', $term)->where('status', 3)->where('shipping', 2)->count();
				$array['num_shipping'] = Reorders::select('id')->where('term', $term)->where('status', 2)->where('shipping', 1)->count();
				$array['num_production'] = Reorders::select('id')->where('term', $term)->where('status', 2)->where('shipping', 0)->count();
				$array['num_pushfb'] = Reorders::select('id')->where('term', $term)->where('status', 1)->where('shipping', 0)->count();
				$array['num_editable'] = Reorders::select('id')->where('term', $term)->where('status', 0)->where('shipping', 0)->count();
				
				$array['num_reorders'] = Reorders::select('id')->where('term', $term)->where('status', $status)->where('shipping', $shipping)->count();
				$array['pages'] = self::reorder_pages($limit_num, $array['num_reorders']);
				
				$reorders = Reorders::select('reorders.id', 'reorders.term', 'reorders.status', 'reorders.camp', 'reorders.created', 'reorders.updated', 'reorders.user', 'reorders.xmlstring');
				$reorders = $reorders->selectRaw('MIN(gmb_reorderitems.need_by) AS early_need_by');
				$reorders = $reorders->leftjoin('reorderitems', 'reorderitems.reorder_id', '=', 'reorders.id');
				$reorders = $reorders->where('reorders.term', $term); $array['term'] = $term;
				$reorders = $reorders->where('reorders.status', $status); $array['status'] = $status;
				$reorders = $reorders->where('reorders.shipping', $shipping); $array['shipping'] = $shipping;
				//$reorders = $reorders->where('reorderitems.need_by', '!=', "");
				$reorders = $reorders->groupBy('reorderitems.reorder_id');
// 				if($limit_num != NULL) {
// 					$reorders = $reorders->take($limit_num);
// 					if($page > 1) {
// 						$skip = $limit_num * ($page - 1);
// 						$reorders = $reorders->skip($skip);
// 					}
// 				}
				$reorders = $reorders->orderBy('early_need_by');
				$array['sql'] = $reorders->toSql();
				$reorders = $reorders->get();
// 			}
			if(!empty($reorders)) {
				foreach($reorders as $key => $row) {
					$id = $row['id'];
					$campLocation = self::campLocation($row['camp']);
					$num_reorder_items = self::num_reorder_items($id);
					$num_items = $num_reorder_items['num_items'];
					$num_shipped = $num_reorder_items['num_shipped'];
					$array['reorders'][$id]['id'] = $id;
					$array['reorders'][$id]['term'] = $row['term'];
					$array['reorders'][$id]['status'] = $row['status'];
					$array['reorders'][$id]['request_id'] = $row['request_id'];
					$array['reorders'][$id]['location_id'] = $location_id = $row['camp'];
					$array['reorders'][$id]['camp'] = 	$camp;
					$array['reorders'][$id]['early_need_by'] = $row['early_need_by'];
					$array['reorders'][$id]['created'] = $row['created'];
					$array['reorders'][$id]['updated'] = $row['updated'];
					$array['reorders'][$id]['user'] = $row['user'];
					$userInfo = gambaUsers::user_info($row['user']);
					$array['reorders'][$id]['user_name'] = $userInfo['name'];
					$array['reorders'][$id]['email'] = $userInfo['email'];
					$array['reorders'][$id]['num_items'] = $num_items;
					$array['reorders'][$id]['num_shipped'] = $num_shipped;
					if($row['status'] == 1) {
						$array['reorders'][$id]['pushfb'] = "true";
					}
					if($row['status'] == 2) {
						$array['reorders'][$id]['quantity_short'] = self::check_quantity_short($id);
						$array['reorders'][$id]['shipping'] = "true";
					}
					if($row['status'] == 3) {
						$array['reorders'][$id]['shipped'] = "true";
					}
					$array['reorders'][$id]['xmlstring'] = $row['xmlstring'];
					$location_info = gambaLocations::location_by_id($location_id);
					$array['reorders'][$id]['cut_off_day'] = $location_info['cut_off_day'];
					$array['reorders'][$id]['location_name'] = $location_info['name'];
					$array['reorders'][$id]['camp_and_location'] = $campLocation['abbr'] . " " . $location_info['name'];
				}
			} else {
				$array['message'] == "There are currently no resupply orders in the database.";
			}
			return $array;
		}
		public static function userreorders($locations) {
			$locations = json_decode($locations, true);
			//echo "<pre>"; print_r($user_locations); echo "</pre>";  
			$term = gambaTerm::year_by_status('C');
			self::marked_as_shipping();
			$order_array[0]['type']= "Open and Editable";
			$order_array[0]['status']= 0;
			$order_array[0]['shipping']= 0;
			$order_array[0]['path']= "edit/open";
			$order_array[1]['type']= "In Production";
			$order_array[1]['status']= 1;
			$order_array[1]['shipping']= 0;
			$order_array[1]['path']= "view/pushtofishbowl";
			$order_array[2]['type']= "In Production";
			$order_array[2]['status']= 2;
			$order_array[2]['status2']= 1;
			$order_array[2]['shipping']= 0;
			$order_array[2]['path']= "view/inproduction";
			$order_array[3]['type']= "Partially Shipped";
			$order_array[3]['status']= 2;
			$order_array[3]['shipping']= 1;
			$order_array[3]['path']= "view/partiallyshipped";
			$order_array[4]['type']= "Fully Shipped";
			$order_array[4]['status']= 3;
			$order_array[4]['shipping']= 2;
			$order_array[4]['path']= "view/fullyshipped";
			
			
			
			$array['num_shipped'] = Reorders::select('id')->where('term', $term)->where('status', 3)->where('shipping', 2)->count();
			$array['num_shipping'] = Reorders::select('id')->where('term', $term)->where('status', 2)->where('shipping', 1)->count();
			$array['num_production'] = Reorders::select('id')->where('term', $term)->where('status', 2)->where('shipping', 0)->count();
			$array['num_pushfb'] = Reorders::select('id')->where('term', $term)->where('status', 1)->where('shipping', 0)->count();
			$array['num_editable'] = Reorders::select('id')->where('term', $term)->where('status', 0)->where('shipping', 0)->count();
			
			foreach($order_array as $order_key => $order_values) {
				$array['status_list'][$order_values['type']]['type'] = $order_values['type'];
				$array['status_list'][$order_values['type']]['status'] = $order_values['status'];
				$array['status_list'][$order_values['type']]['shipping'] = $order_values['shipping'];
				$array['status_list'][$order_values['type']]['path'] = $order_values['path'];
				$string = "(camp = 0";
				foreach($locations as $camp => $location_array) {
					foreach($location_array as $key => $value) {
						$new_array[] = $value;
					}
				}
				$new_array = array_unique($new_array);
				foreach($new_array as $key => $value) {
					$string .= " OR camp = $value";
				}
				$string .= ")";
				$array['num_reorders'] = Reorders::select('id')->where('term', $term)->where('status', $values['status'])->where('shipping', $values['shipping'])->whereRaw($string)->count();
				$array['pages'] = self::reorder_pages($limit_num, $array['num_reorders']);
				
				$reorders = Reorders::select('id', 'term', 'status', 'camp', 'created', 'updated', 'user', 'fb_number');
				$reorders = $reorders->where('status', $order_values['status']);
				$reorders = $reorders->where('shipping', $order_values['shipping']);
				$reorders = $reorders->where('term', $term);
				$reorders = $reorders->whereRaw($string);
				if($limit_num != NULL) {
					$reorders = $reorders->take($limit_num);
					if($page > 1) {
						$skip = $limit_num * ($page - 1);
						$reorders = $reorders->skip($skip);
					}
				}
				$reorders = $reorders->orderBy('created', 'desc');
				//$array['status_list'][$order_values['type']]['sql'] = $reorders->toSql();
				//echo "<p>{$array['sql']}</p>";
				$array['term'] = $term;
				$query = $reorders->get();
				foreach($query as $key => $row) {
					$id = $row['id'];
					$campLocation = self::campLocation($row['camp']);
					$num_reorder_items = self::num_reorder_items($id);
					$num_items = $num_reorder_items['num_items'];
					$num_shipped = $num_reorder_items['num_shipped'];
					$array['status_list'][$order_values['type']]['reorders'][$id]['id'] = $id;
					$array['status_list'][$order_values['type']]['reorders'][$id]['term'] = $row->term;
					$array['status_list'][$order_values['type']]['reorders'][$id]['status'] = $row['status'];
					$array['status_list'][$order_values['type']]['reorders'][$id]['request_id'] = $row['request_id'];
					$array['status_list'][$order_values['type']]['reorders'][$id]['location_id'] = $location_id = $row['camp'];
					$array['status_list'][$order_values['type']]['reorders'][$id]['camp'] = $camp;
					$array['status_list'][$order_values['type']]['reorders'][$id]['path'] = $order_values['path'];
					$array['status_list'][$order_values['type']]['reorders'][$id]['order_key'] = $order_key;
					$array['status_list'][$order_values['type']]['reorders'][$id]['created'] = $row['created'];
					$array['status_list'][$order_values['type']]['reorders'][$id]['updated'] = $row['updated'];
					$array['status_list'][$order_values['type']]['reorders'][$id]['fb_number'] = $row['fb_number'];
					$array['status_list'][$order_values['type']]['reorders'][$id]['user'] = $row['user'];
					$userInfo = gambaUsers::user_info($row['user']);
					$array['status_list'][$order_values['type']]['reorders'][$id]['user_name'] = $userInfo['name'];
					$array['status_list'][$order_values['type']]['reorders'][$id]['email'] = $userInfo['email'];
					$array['status_list'][$order_values['type']]['reorders'][$id]['num_items'] = $num_items;
					$array['status_list'][$order_values['type']]['reorders'][$id]['num_shipped'] = $num_shipped;
					if($row['status'] == 1) {
						$array['status_list'][$order_values['type']]['reorders'][$id]['pushfb'] = "true";
					}
					if($row['status'] == 2) {
						$array['status_list'][$order_values['type']]['reorders'][$id]['quantity_short'] = self::check_quantity_short($id);
						$array['status_list'][$order_values['type']]['reorders'][$id]['shipping'] = "true";
					}
					if($row['status'] == 3) {
						$array['status_list'][$order_values['type']]['reorders'][$id]['shipped'] = "true";
					}
					$array['status_list'][$order_values['type']]['reorders'][$id]['location_name'] = $location_name = gambaLocations::location_by_id($location_id);
					$array['status_list'][$order_values['type']]['reorders'][$id]['camp_and_location'] = $campLocation['abbr'] . " " . $location_name['name'];
					//echo "<pre>"; print_r($array[$key]); echo "</pre>";
				}
			}
			//exit; die();
			return $array;
		}
		
		public static function check_quantity_short($reorder_id) {
			$query = ReorderItems::select('qty', 'inventory.quantityonhand')->leftjoin('supplies', 'supplies.id', '=', 'supply_id')->leftjoin('inventory', 'inventory.number', '=', 'supplies.part')->where('reorder_id', $reorder_id)->get();
			$i = 0;
			foreach($query as $key => $row) {
				if($row['qty'] > $row['quantityonhand']) {
					$i = $i + 1;
				} else {
					$i = $i + 0;
				}
			}
			if($i > 0) { $result = "true"; } else { $result = "false"; }
			return $result;
		}
		
		public static function reorder_pages($limit_num, $num_reorders) {
			$pages = ceil($num_reorders / $limit_num);
			return $pages;
		}
		
		public static function marked_as_shipping() {
			$term = gambaTerm::year_by_status('C');
			$query = Reorders::select('id')->where('term', $term)->where('status', 2)->where('shipping', 0)->get();
			foreach($query as $row) {
				$num_reorder_items = self::num_reorder_items($row['id']);
				$num_shipped = $num_reorder_items['num_shipped']; $num_items = $num_reorder_items['num_items'];
				// Shipping
				if($num_items > 0 && $num_shipped > 0 && $num_shipped < $num_items) {
					$update = Reorders::find($row['id']);
						$update->status = 2;
						$update->shipping = 1;
						$update->save();
				}
				// Shipped
				if($num_items > 0 && $num_shipped > 0 && $num_shipped == $num_items) {
					$update = Reorders::find($row['id']);
						$update->status = 3;
						$update->shipping = 2;
						$update->save();
				}
			}
		}
		
		public static function num_reorder_items($reorder_id) {
			$array['num_items'] = ReorderItems::select('id')->where('reorder_id', $reorder_id)->count();
			$array['num_shipped'] = ReorderItems::select('id')->where('reorder_id', $reorder_id)->where('status', '2')->count();
			if($array['num_shipped'] == "") { $array['num_shipped'] = 0; }
			return $array;
		}
		
		/**
		 * userAccess
		 * 
		 * @param unknown_type $permission
		 * @param unknown_type $camp
		 */
		public static function userAccess($permission, $camp = 0) {
			if($camp > 0) { $camp_type = self::campType($camp); } else { $camp_type = ""; }
			
			if($camp_type == 1 && $permission == 4) {
				$action = "campg";
			}
			elseif($camp_type == 2 && $permission == 4) {
				$action = "gsq";
			} else {
				$action = "campggsq";
			}
			return $action;
		}
		
		/**
		 * campType
		 * 
		 * @param unknown_type $camp
		 */
		public static function campType($camp) {
			$row = Locations::select('camp')->where('id', $camp)->get();
			return $row['camp']; 
		}
		
		/**
		 * materialReorder
		 * 
		 * @param unknown_type $request_id
		 * @param unknown_type $action
		 * @param unknown_type $reorder_id
		 */
		public static function materialReorder($request_id, $action = NULL, $reorder_id = NULL) {
			$query = Supplies::select('supplies.id', 'parts.description', 'parts.suom', 'supplies.part', 'parts.fbcost', 'supplies.term', 'parts.cost', 'parts.inventory', 'inventory.availablesale', 'inventory.quantityonhand', 'parts.conversion', 'parts.fbuom', 'parts.adminnotes', 'parts.url')->leftjoin('parts', 'parts.number', '=', 'supplies.part')->leftjoin('inventory', 'inventory.number', '=', 'parts.number')->where('supplies.supplylist_id', $request_id)->orderBy('parts.description');
			$array['sql'] = $query->toSql();
			$query = $query->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$supply_id = $row['id'];
					$array['parts'][$supply_id]['part_desc'] = $row['description'];
					$array['parts'][$supply_id]['uom'] = $row['suom'];
					$array['parts'][$supply_id]['material_id'] = $row['material_id'];
					$array['parts'][$supply_id]['standardCost'] = $row['standardCost'];
// 					$array['parts'][$supply_id]['status'] = $row['status']; //ml.status
					$array['parts'][$supply_id]['term'] = $row['term'];
					$array['parts'][$supply_id]['cost'] = $row['cost'];
					$array['parts'][$supply_id]['adminnotes'] = $row['adminnotes'];
					$array['parts'][$supply_id]['part_num'] = $row['part'];
					$array['parts'][$supply_id]['url'] = $row['url'];
					$array['parts'][$supply_id]['inventoryStatus'] = $row['inventory'];
					$array['parts'][$supply_id]['availablesale'] = $row['availablesale'];
					$array['parts'][$supply_id]['quantityonhand'] = $row['quantityonhand'];
					$array['parts'][$supply_id]['part_flag'] = $row['conversion'];
					$array['parts'][$supply_id]['fbuom'] = $row['fbuom'];
					if($action == "materialReorderEdit") { 
						$row2 = ReorderItems::select('qty')->where('reorder_id', $reorder_id)->where('supply_id', $supply_id)->first();
						$array['parts'][$supply_id]['qty'] = $row2['qty'];
					}
				}
			}
			return $array;
		}
		
		/**
		 * materialReorderEdit
		 * 
		 * @param unknown_type $reorder_id
		 */
		public static function materialReorderEdit($reorder_id) {
			$row = Reorders::select('term', 'status', 'camp', 'created', 'updated', 'user', 'xmlstring', 'fb_number', 'fishbowl_response')->where('id', $reorder_id)->first();
			
			$array['reorder_id'] = $reorder_id;
			$array['term'] = $row['term'];
			$array['status'] = $row['status'];
			$array['fb_number'] = $row['fb_number'];
			$array['fishbowl_response'] = json_decode($row['fishbowl_response'], true);
			$array['camp'] = $row['camp'];
//     		echo "<pre>"; print_r($array); echo "</pre>"; exit; die(); 
			$campLocation = self::campLocation($row['camp']);
			$array['camp_location'] = $campLocation['abbr'] . " " . $campLocation['location'];
			$array['abbr'] = $campLocation['abbr'];
			$array['location'] = $campLocation['location'];
			$array['cut_off_day'] = $campLocation['cut_off_day'];
			$array['created'] = $row['created'];
			$array['updated'] = $row['updated'];
			$array['user'] = $row['user'];
			$userInfo = gambaUsers::user_info($row['user']);
//     		echo "<pre>"; print_r($userInfo); echo "</pre>"; exit; die(); 
			$array['user_name'] = $userInfo['name'];
			$array['user_email'] = $userInfo['email'];
			$num_reorder_items = self::num_reorder_items($reorder_id);
			$array['num_shipped'] = $num_shipped = $num_reorder_items['num_shipped'];
			$array['num_items'] = $num_items = $num_reorder_items['num_items'];
			if($num_shipped < $num_items) { $array['shipped'] = "false"; } else { $array['shipped'] = "true"; }

			$array['xmlstring'] = $row['xmlstring'];
			
			$query = ReorderItems::select(
					'reorderitems.id AS reorderitem_id', 
					'supplies.id', 
					'parts.description', 
					'parts.suom', 
					'reorderitems.notes', 
					'reorderitems.warh_notes', 
					'supplies.part', 
					'parts.fbcost', 
					'supplies.term', 
					'parts.cost', 
					'parts.number', 
					'parts.inventory', 
					'inventory.availablesale',
					 'inventory.quantityonhand', 'reorderitems.qty', 'reorderitems.request_id', 'parts.conversion', 'parts.fbuom', 'supplies.activity_id', 'reorderitems.status', 'reorderitems.ship_date', 'parts.adminnotes', 'reorderitems.need_by', 'reorderitems.need_status', 'reorderitems.order_status', 'parts.url', 'reorderitems.request_reason')->leftjoin('supplies', 'reorderitems.supply_id', '=', 'supplies.id')->leftjoin('parts', 'parts.number', '=', 'supplies.part')->leftjoin('inventory', 'inventory.number', '=', 'parts.number')->where('reorderitems.reorder_id', $reorder_id)->orderBy('reorderitems.request_id')->orderBy('parts.description');
			$array['sql'] = $query->toSql();
			$query = $query->get();
//     		echo "<pre>"; print_r($query); echo "</pre>"; exit; die(); 
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$reorderitem_id = $row['reorderitem_id'];
					$array['parts'][$reorderitem_id]['reorderitem_id'] = $reorderitem_id;
					$array['parts'][$reorderitem_id]['part_desc'] = $row['description'];
					$array['parts'][$reorderitem_id]['supply_id'] = $row['id'];
					$array['parts'][$reorderitem_id]['uom'] = $row['suom'];
					$array['parts'][$reorderitem_id]['notes'] = $row['notes'];
					$array['parts'][$reorderitem_id]['adminnotes'] = $row['adminnotes'];
					$array['parts'][$reorderitem_id]['warh_notes'] = $row['warh_notes'];
					if($row['need_by'] != "") { 
						$array['parts'][$reorderitem_id]['need_by'] = date("n/j/Y", strtotime($row['need_by']));
					}
					$array['parts'][$reorderitem_id]['need_status'] = $row['need_status'];
					$array['parts'][$reorderitem_id]['order_status'] = $row['order_status'];
					$array['parts'][$reorderitem_id]['request_reason'] = $row['request_reason'];
					if($row['order_status'] >= 1 && $row['order_status'] <= 4) {
						$i = $i + 1;
					} else {
						$i = $i + 0;
					}
					$array['parts'][$reorderitem_id]['url'] = $row['url'];
					if($row['ship_date'] != "") { 
						$array['parts'][$reorderitem_id]['ship_date'] = date("n/j/Y", strtotime($row['ship_date'])); 
					}
// 					$array['parts'][$reorderitem_id]['material_id'] = $row['material_id'];
					$array['parts'][$reorderitem_id]['standardCost'] = $row['cost'];
					$array['parts'][$reorderitem_id]['status'] = $row['status'];
					$array['parts'][$reorderitem_id]['term'] = $row['term'];
					$array['parts'][$reorderitem_id]['cost'] = $row['cost'];
					$array['parts'][$reorderitem_id]['part_num'] = $row['part'];
					$array['parts'][$reorderitem_id]['inventoryStatus'] = $row['inventory'];
					$array['parts'][$reorderitem_id]['availablesale'] = $row['availablesale'];
					$array['parts'][$reorderitem_id]['quantityonhand'] = $row['quantityonhand'];
					$array['parts'][$reorderitem_id]['qty'] = $row['qty'];
					$array['parts'][$reorderitem_id]['request_id'] = $row['request_id'];
					$array['parts'][$reorderitem_id]['conversion'] = $row['conversion']; // part_flag
					$array['parts'][$reorderitem_id]['fbuom'] = $row['fbuom'];
// 					$mlInfo = $materials->getMaterialListInfo($row['request_id']);
					$activity_info = gambaActivities::activity_info($row['activity_id']);
//     				echo "<pre>"; print_r($activity_info); echo "</pre>"; exit; die(); 
					$array['parts'][$reorderitem_id]['activity_name'] = $activity_info['name'];
					$array['parts'][$reorderitem_id]['theme'] = $activity_info['theme_name'];
					$array['parts'][$reorderitem_id]['grade_level'] = $activity_info['grade_name'];
//     				echo "<pre>"; print_r($array); echo "</pre>"; exit; die(); 
					$product = gambaProducts::partToProduct($row['part']);
//     				echo "<pre>"; print_r($product); echo "</pre>"; exit; die(); 
					$array['parts'][$reorderitem_id]['prod_num'] = $product['prod_num'];
				}
			}
			if($i > 0) { $array['need_status_alert'] = "true"; } else { $array['need_status_alert'] = "false"; }
			return $array;
		}
		
		/**
		 * materialSelected
		 * 
		 * @param unknown_type $request_id
		 * @param unknown_type $camp
		 */
		public static function materialSelected($request_id, $locations, $camp) {
			if(is_array($location)) {
				$query = ReorderItems::select('supply_id')->where('request_id', $request_id);
				foreach($locations[$camp] as $key => $location_id) {
					$query = $query->where('camp', $location);
				}
				$query = $query->where('status', 0)->get();
				if($query->count() > 0) {
					foreach($query as $key => $row) {
						$supply_id = $row['supply_id'];
						$array[$supply_id] = 1;
					}
				}
				return $array;
			}
		}
		
		
		
		/**
		 * campLocation
		 * 
		 * @param unknown_type $id
		 */
		public static function campLocation($id) {
			$row = Locations::select('locations.id', 'locations.location', 'camps.abbr', 'camps.id AS camp_type', 'locations.cut_off_day')->leftjoin('camps', 'camps.id', '=', 'locations.camp')->where('locations.id', $id)->first();
				$id = $row['id'];
				$camps['location'] = $row['location'];
				$camps['abbr'] = $row['abbr'];
				$camps['camp_type'] = $row['camp_type'];
				$camps['cut_off_day'] = $row['cut_off_day'];
			return $camps;
		}
		
		public static function resupply_email_alert($array) {
			$url = url('/');
			$reorder_id = $array['reorder_id'];
			$date = date("F j, Y g:i a");
			$fb_pre = config('fishbowl.fbpre');
			// Send E-mail if Pushed
			if($array['submit'] == "Mark as Pushed" || ($array['submit'] == "Push to Fishbowl" && $array['push_status_code'] == "true")) {
				$send_email = "true";
				$send_subj = "Gamba: Your Resupply Order has Been Pushed to Fishbowl";
				
			}
			if($array['submit'] == "Update My Order Status") {
				
			}
			if($array['submit'] == "Save Changes") {
				$material_reorder = self::materialReorderEdit($array['reorder_id']);
				$send_email = "true";
				$send_subj = "Gamba: There are Updates to Your Resupply Order";
				
				foreach($array['values'] as $key => $value) {
					if($value['order_status'] != $material_reorder['parts'][$key]['order_status']) {
						$order_status_change[$key] = ' style="color:blue;"';
					} else {
						$order_status_change[$key] = '';
					}
					
					if($value['warh_notes'] != $material_reorder['parts'][$key]['warh_notes']) {
						$admin_notes_change[$key] = ' style="color:blue;"';
					} else {
						$admin_notes_change[$key] = '';
					}
					
					if($value['status'] == 2) {
						$part_shipped[$key] = "<span style='color:green;font-weight:bold;'>&#x2713;</span>";
					} else {
						$part_shipped[$key] = "<span style='color:red;font-weight:bold;'>&#x2717;</span>";
					}
					
					if($value['ship_date'] != $material_reorder['parts'][$key]['ship_date']) {
						$ship_date_change[$key] = ' style="color:blue;"';
					} else {
						$ship_date_change[$key] = '';
					}
				}
			}
// 			echo "<pre>"; print($send_msg); echo "</pre>";
			if($send_email == "true") {
			$send_msg = <<<EOT
<html>
<body>
<h2>Gamba Resupply Order #: {$fb_pre}RSO-{$term}-{$reorder_id}&nbsp;&nbsp;&nbsp;
			Fishbowl Sales Order #: {$array['push_fb_number']}

<h3>Parts:</h3>
<p>Changes are in blue.</p>
<table border="0" cellpadding="4" cellspacing="1">
	<thead>
		<tr style="background: #CCC;">
			<th></th>
			<th>Part #</th>
			<th>Description</th>
			<th>UoM</th>
			<th>Qty<br />Requested</th>
			<th>Need By</th>
			<th>Need Status</th>
			<th>Order Status</th>
			<th>Estimated<br />Shipping Date</th>
			<th>Shipped</th>
		</tr>
	</thead>
	<tbody>
EOT;
				$order_statuses = self::order_status_array();
				$need_statuses = self::need_status_array();
				foreach($array['values'] as $key => $value) {
					$order_status = $order_statuses[$value['order_status']];
					if($value['order_status'] > 0) {
						if($value['need_status'] == 0) {
							$need_status = "<span style='color:red;font-weight:bold;'>Response Needed</span>";
						} else {
							$need_status = $need_statuses[$value['need_status']];
						}
					} else {
						$need_status = "";
					}
					$send_msg .= <<<EOT
		<tr style="background: #eee;">
			<td>{$value['list_name']}</td>
			<td>{$value['part_number']}</td>
			<td>{$value['part_description']}</td>
			<td>{$value['part_uom']}</td>
			<td>{$value['part_qty']}</td>
			<td>{$value['part_need_by']}</td>
			<td>{$need_status}</td>
			<td{$order_status_change[$key]}>{$order_status}</td>
			<td{$ship_date_change[$key]}>{$value['ship_date']}</td>
			<td>{$part_shipped[$key]}</td>
		</tr>
		<tr>
			<td></td>
			<td colspan="7">
			<span><strong>Admin Notes:</strong><br />
          {$value['part_admin_notes']}</span><br />
         <span><strong>Camp Notes:</strong><br />
          {$value['part_camp_notes']}</span><br />
         <span{$admin_notes_change[$key]}><strong>Warehouse Notes:</strong><br />
          {$value['warh_notes']}</span>
          </td>
		</tr>
EOT;
				}
				$send_msg .= <<<EOT
	</tbody>
</table>
</body>
</html>
EOT;
				// Change emails before going to production
				if($array['user_email'] == "admin") {
					$email = "john@panolatech.com";
				} else {
					
					$email = Session::get('email');
// 					$email = $array['user_email'];
				}
				
				$headers = "From: " . strip_tags($email) . "\r\n";
				$headers .= "Reply-To: ". strip_tags($email) . "\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
				mail($email, $send_subj . " - " . $date, $send_msg, $headers, "-fwarehouse@galileo-learning.com");
			}
		}

		/**
		 * Send Email of Items not shipped do to back-order to verify if still needed
		 * Triggered by cron job from 
		 * @param unknown $array
		 */
		public static function resupply_needs_email_alert($array) {
			$url = url('/');
			$reorder_id = $array['reorder_id'];
			$date = date("F j, Y g:i a");
			$fb_pre = config('fishbowl.fbpre');
			// Send E-mail if Pushed
			if($array['submit'] == "Mark as Pushed" || $array['submit'] == "Push to Fishbowl") {
				$send_email = "true";
				$send_subj = "Gamba: Your Resupply Order has Been Pushed to Fishbowl";
		
			}
			if($array['submit'] == "Save Changes") {
				$material_reorder = self::materialReorderEdit($array['reorder_id']);
				$send_email = "true";
				$send_subj = "Gamba: There are Updates to Your Resupply Order";
		
				foreach($array['values'] as $key => $value) {
					if($value['order_status'] != $material_reorder['parts'][$key]['order_status']) {
						$order_status_change[$key] = ' style="color:blue;"';
					} else {
						$order_status_change[$key] = '';
					}
						
					if($value['warh_notes'] != $material_reorder['parts'][$key]['warh_notes']) {
						$admin_notes_change[$key] = ' style="color:blue;"';
					} else {
						$admin_notes_change[$key] = '';
					}
						
					if($value['status'] == 2) {
						$part_shipped[$key] = "<span style='color:green;font-weight:bold;'>&#x2713;</span>";
					} else {
						$part_shipped[$key] = "<span style='color:red;font-weight:bold;'>&#x2717;</span>";
					}
						
					if($value['ship_date'] != $material_reorder['parts'][$key]['ship_date']) {
						$ship_date_change[$key] = ' style="color:blue;"';
					} else {
						$ship_date_change[$key] = '';
					}
				}
			}
			// 			echo "<pre>"; print($send_msg); echo "</pre>";
			if($send_email == "true") {
				$send_msg = <<<EOT
<html>
<body>
<h2>Gamba Resupply Order #: {$fb_pre}RSO-{$reorder_id}&nbsp;&nbsp;&nbsp;
			Fishbowl Sales Order #: {$array['push_fb_number']}
		
<h3>Parts:</h3>
<p>Changes are in blue.</p>
<table border="0" cellpadding="4" cellspacing="1">
	<thead>
		<tr style="background: #CCC;">
			<th></th>
			<th>Part #</th>
			<th>Description</th>
			<th>UoM</th>
			<th>Qty<br />Requested</th>
			<th>Need By</th>
			<th>Order Status</th>
			<th>Estimated<br />Shipping Date</th>
			<th>Shipped</th>
		</tr>
	</thead>
	<tbody>
EOT;
				$order_statuses = self::order_status_array();
				foreach($array['values'] as $key => $value) {
					$order_status = $order_statuses[$value['order_status']];
					$send_msg .= <<<EOT
		<tr style="background: #eee;">
			<td>{$value['list_name']}</td>
			<td>{$value['part_number']}</td>
			<td>{$value['part_description']}</td>
			<td>{$value['part_uom']}</td>
			<td>{$value['part_qty']}</td>
			<td>{$value['part_need_by']}</td>
			<td{$order_status_change[$key]}>{$order_status}</td>
			<td{$ship_date_change[$key]}>{$value['ship_date']}</td>
			<td>{$part_shipped[$key]}</td>
		</tr>
		<tr>
			<td></td>
			<td colspan="7">
			<span><strong>Admin Notes:</strong><br />
          {$value['part_admin_notes']}</span><br />
         <span><strong>Camp Notes:</strong><br />
          {$value['part_camp_notes']}</span><br />
         <span{$admin_notes_change[$key]}><strong>Warehouse Notes:</strong><br />
          {$value['warh_notes']}</span>
          </td>
		</tr>
EOT;
				}
				$send_msg .= <<<EOT
	</tbody>
</table>
</body>
</html>
EOT;
				// Change emails before going to production
				if($array['user_email'] == "admin") {
					$email = "john@panolatech.com";
				} else {
					$email = Session::get('email');
// 					$email = $array['user_email'];
				}
		
				$headers = "From: " . strip_tags($email) . "\r\n";
				$headers .= "Reply-To: ". strip_tags($email) . "\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
				mail($email, $send_subj . " - " . $date, $send_msg, $headers, "-fwarehouse@galileo-learning.com");
			}
		}
		
		public static function unlock_for_editing($array) {
    		$fbpre = config('fishbowl.fbpre');
			$reorder_id = $array['reorder_id']; $request_id = $array['request_id'];
			$date = date("Y-m-d H:i:s");
			
			$update = Reorders::find($reorder_id);
				$update->status = 0;
				$update->shipping = 0;
				$update->updated = $date;
				$update->save();
			$update = ReorderItems::where('reorder_id', $reorder_id)->update([
				'status' => 0,
				'updated' => $date
				]);
			
		}
		
		public static function push_to_fishbowl($array) {
    		$fbpre = config('fishbowl.fbpre');
			$reorder_id = $array['reorder_id']; $request_id = $array['request_id'];
			$result = gambaFishbowl::push_resupply_order($array);
			self::resupply_email_alert($array);
			return $result;
		}
		
		public static function mark_as_pushed($array) {
    		$fbpre = config('fishbowl.fbpre');
			$reorder_id = $array['reorder_id']; $request_id = $array['request_id'];
			$fishbowl_response['connect_status_code'] = 1000;
			$fishbowl_response['connect_status_message'] = "Success!";
			$fishbowl_response['push_status_code'] = 1000;
			$fishbowl_response['push_status_message'] = "Success! Resupply Order Marked as Pushed.";
			$update = Reorders::find($reorder_id);
				$update->status = 2;
				$update->shipping = 0;
				$update->updated = $date;
				$update->fishbowl_response = json_encode($fishbowl_response);
				$update->save();

			self::resupply_email_alert($array);
		}
		
		
		public static function resupplyorderchange($array) {
    		$url = url('/');
    		$fbpre = config('fishbowl.fbpre');
			$reorder_id = $array['reorder_id']; $request_id = $array['request_id'];
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			
			$date = date("Y-m-d H:i:s");
			if($array['submit'] == "Update My Order Status") {
				foreach($array['values'] as $key => $value) {
					$update = ReorderItems::find($key);
						$update->need_status = $value['need_status'];
						$update->save();
				}
				
			}
			if($array['submit'] == "Save Changes") {
				$ship_total = 0; $item_total = 0;
				foreach($array['values'] as $key => $value) {
// 					if($value['ship_date'] == "" && $value['status'] == 1) { $ship_date = date("Y-m-d"); } else { $ship_date = $value['ship_date']; }
					if($value['status'] == "") { $value['status'] = 1; }
					if($value['ship_date'] != "") { 
						$ship_date = date("Y-m-d", strtotime($value['ship_date'])); 
					} else { 
						$ship_date = ""; 
					}
					if($value['need_by'] != "") { 
						$need_by = date("Y-m-d", strtotime($value['need_by'])); 
					} else { 
						$need_by = ""; 
					}
					if($value['status'] == 2) {
						$ship_total++;
					}
					$item_total++;
					$update = ReorderItems::find($key);
						$update->status = $value['status'];
						$update->request_reason = $value['request_reason'];
						$update->ship_date = $ship_date;
						$update->notes = $value['notes'];
						$update->warh_notes = $value['warh_notes'];
						$update->need_by = $need_by;
						$update->order_status = $value['order_status'];
						$update->need_status = $value['need_status'];
						$update->save();
				}
				if($item_total == $ship_total) {
					$return['path'] = "fullyshipped";
					$return['msg'] = "fullyshipped=1";
					$update = Reorders::where('id', $reorder_id)->update(['status' => '3', 'shipping' => '2']);
				}
				if($ship_total == 0) {
					$return['path'] = "inproduction";
					$return['msg'] = "success=1";
					$update = Reorders::where('id', $reorder_id)->update(['status' => '2', 'shipping' => '0']);
				}
				if($ship_total > 0 && $ship_total < $item_total) {
					$return['path'] = "partiallyshipped";
					$return['msg'] = "partiallyshipped=1";
					$update = Reorders::where('id', $reorder_id)->update(['status' => '2', 'shipping' => '1']);
				}
				$update = Reorders::where('id', $reorder_id)->update(['fb_number' => $array['fb_number']]);
				//echo "<p>$item_total - $ship_total - {$return['path']} - {$return['msg']}</p>"; exit; die();
			}
			self::resupply_email_alert($array);
			return $return;
		}
		
		public static function resupplyorderupdate($array) {
			$reorder_id = $array['reorder_id'];
			$origQty = $array['orig'];
			$date = date("Y-m-d H:i:s");
			// No Longer Saving Fishbowl Number at this point
			//$update = Reorders::find($reorder_id);
			//	$update->fb_number = $array['fb_number'];
			//	$update->save();
			foreach($array['values'] as $key => $value) {
				if($value['ship_date'] != "") { 
					$ship_date = date("Y-m-d", strtotime($value['ship_date'])); 
				} else { 
					$ship_date = ""; 
				}
				if($value['need_by'] != "") { 
					$need_by = date("Y-m-d", strtotime($value['need_by'])); 
				} else { 
					$need_by = ""; 
				}
				$update = ReorderItems::find($key);
					$update->qty = $value['qty'];
					$update->updated = $date;
					$update->notes = $value['notes'];
					$update->warh_notes = $value['warh_notes'];
					$update->ship_date = $ship_date;
					$update->need_by = $need_by;
					$update->order_status = $value['order_status'];
					$update->need_status = $value['need_status'];
					$update->request_reason = $value['request_reason'];
					$update->save();
			}
			if($array['submit'] == "Close Resupply Order") {
				$update = Reorders::find($reorder_id);
					$update->status = 1;
					$update->shipping = 0;
					$update->updated = $date;
					$update->fb_number = $array['fb_number'];
					$update->save();
				$query = ReorderItems::select('id')->where('reorder_id', $reorder_id)->get();
				foreach($query as $row) {
					$update = ReorderItems::find($row['id']);
						$update->status = 1;
						$update->updated = $date;
						$update->save();
				}
			}
		}
		
		
		public static function resupplyordercreate($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$request_id = $array['request_id']; $activity_id = $array['activity_id'];
			$term = gambaTerm::year_by_status('C');
			$location = $array['location'];
			
			$date = date("Y-m-d H:i:s");
			$row = ReorderItems::select('reorder_id')->where('camp', $location)->where('status', 0)->first();
			$user_id = Session::get('uid');
			if($user_id == "") {
				$user_id = Auth::user()->id;
			}
			if($row['reorder_id'] != "") {
				$id = $result['reorder_id'] = $row['reorder_id'];
				$result['msg'] = "?added=1";
			} else {
				$id = $result['reorder_id'] = Reorders::insertGetId(['term' => $term, 'camp' => $array['location'], 'created' => $date, 'user' => $user_id]);
				$result['msg'] = "?created=1";
			}
// 			echo $id; exit; die();
			foreach($array['values'] as $key => $value) {
				if($value['qty'] > 0) {
					$insert = new ReorderItems;
						$insert->reorder_id = $id;
						$insert->request_id = $array['request_id'];
						$insert->term = $term;
						$insert->supply_id = $key;
						$insert->camp = $array['location'];
						$insert->qty = $value['qty'];
						$insert->created = $date;
						$insert->user = $user_id;
						$insert->notes = $value['notes'];
						if($value['need_by'] != "") {
							$need_by_date = date("Y-m-d", strtotime($value['need_by']));
						} else {
							$need_by_date = "";
						}
						$insert->need_by = $need_by_date;
						$insert->request_reason = $value['request_reason'];
						$insert->save();
				}
			}
			return $result;
		}
		
		public static function view_resupplyorders($array, $return) {
// 			echo "<pre>"; print_r($array['view']); echo "</pre>"; exit; die();
			$url = url('/');
			$user_group = Session::get('group');
			$user_locations = Session::get('locations');
			$term = gambaTerm::year_by_status("C");
			$roList = self::reorders($user_group, $user_locations, $array['view']);
			$content_array['page_title'] = "Resupply Orders";
			if($return['fishbowl'] == 1) { 
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box success radius">
						Your resupply order {$array['rso']} has successfully been pushed to fishbowl.
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			}
			$content_array['content'] .= <<<EOT
					<div class="directions"><strong>Directions:</strong> Below are all user reorders. If there are no reorders in the list click on the &quot;Material Lists&quot; tab above.<br />
					<strong>Key:</strong> <img src="{$url}/img/fishbowl_true_icon.png" width="12" height="12" /> = Open & Editable, <img src="{$url}/img/fishbowl_false_icon.png" width="12" height="12" /> = In Production, <img src="{$url}/img/fishbowl_edit_icon.png" width="12" height="12" /> = Partially Shipped, <img src="{$url}/img/fishbowl_shipped_icon.png" width="12" height="12" /> = Fully Shipped</div>
					
					<div class="row">
						<div class="small-12 medium-4 large-4 columns">
					<ul class="pagination">
						<li class="current"><a href="#">Resupply Orders</a></li>
						<li><a href="{$url}/resupply/materiallists">Material Lists</a></li>
EOT;
			if($user_group <= 1) {
				$content_array['content'] .= <<<EOT
						<li><a href="{$url}/resupply/resupply-reporting">Reporting</a></li>
EOT;
			}
			$content_array['content'] .= <<<EOT
					</ul>
					</div>
EOT;
			if($array['view'] == "shipped") {
				$disable_shipped = ' class="disabled"';
			} elseif($array['view'] == "shipping") {
				$disable_shipping = ' class="disabled"';
			} elseif ($array['view'] == "production") {
				$disable_production = ' class="disabled"';
			} elseif ($array['view'] == "pushfb") {
				$disable_pushfb = ' class="disabled"';
			} else {
				$disable_editable = ' class="disabled"';
			}
			if($user_group <= 1) {
				$content_array['content'] .= <<<EOT
						<div class="small-12 medium-8 large-8 columns">
					<ul class="pagination">
						<li{$disable_editable}><a href="{$url}/resupply?view=editable">Open & Editable <span class="label round">{$roList['num_editable']}</span></a></li>
						<li{$disable_pushfb}><a href="{$url}/resupply?view=pushfb">Push To Fishbowl <span class="label round">{$roList['num_pushfb']}</span></a></li>
						<li{$disable_production}><a href="{$url}/resupply?view=production">In Production <span class="label round">{$roList['num_production']}</span></a></li>
						<li{$disable_shipping}><a href="{$url}/resupply?view=shipping">Partially Shipped <span class="label round">{$roList['num_shipping']}</span></a></li>
						<li{$disable_shipped}><a href="{$url}/resupply?view=shipped">Fully Shipped <span class="label round">{$roList['num_shipped']}</span></a></li>
					</ul>
					</div>
					</div>
EOT;
			}
			if(is_array($roList['reorders'])) {
				$content_array['content'] .= gambaNavigation::pagination("{$url}/resupply?view={$array['view']}", $roList['reorders'], $array['page']);
				$content_array['content'] .= <<<EOT
		
		<script>
// 		$(function(){ 
// 		    $("table").tablesorter({
// 				widgets: [ 'stickyHeaders' ],
// 				widgetOptions: { stickyHeaders_offset : 50, },
// 			}); 
// 			$("table").data("sorter", false);
// 		 }); 
		</script>
					<table class="table table-striped table-bordered table-hover table-condensed table-small table-fixed-header tablesorter">
						<thead>
							<tr>
								<th></th>
								<th></th>
								<th>Term</th>
								<th>Number</th>
								<th>Camp</th>
								<th>Status</th>
								<th>Items Shipped</th>
								<th>Date Created</th>
								<th>Updated</th>
								<th>Created By</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
EOT;
				/*
				 * Green: open and editable 
					Red: In Production 
					Yellow: Partially shipped 
					Blue: Fully shipped
				 */
//				$reorder_list = gambaNavigation::array_slice_pagination($roList['reorders'], $array['page']);
				$i = 1;
				$fb_pre = config('fishbowl.fbpre');
				foreach($roList['reorders'] as $key => $value) {
					
					// Shipped - Green
					if($value['shipped'] == "true") { 
						$fbimg = "<img src=\"{$url}/img/fishbowl_true_icon.png\" width=\"25\" height=\"25\" title=\"Fully Shipped\" />"; 
					// Quantity Short - Blue
					} elseif($value['shipping'] == "true" && $value['quantity_short'] == "true") { 
						$fbimg = "<img src=\"{$url}/img/fishbowl_shipped_icon.png\" width=\"25\" height=\"25\" title=\"Fully Shipped\" />"; 
						
					// Partially shipped - Yellow (fishbowl_edit_icon.png)
					} elseif($value['shipping'] == "true" && $value['quantity_short'] == "false") { 
						$fbimg = "<img src=\"{$url}/img/fishbowl_false_icon.png\" width=\"25\" height=\"25\" title=\"Partially shipped\" />"; 
						
					// In Production - Red (fishbowl_false_icon.png)
					} elseif($value['fbpush'] == "true") { 
						$fbimg = "<img src=\"{$url}/img/fishbowl_false_icon.png\" width=\"25\" height=\"25\" title=\"In Production\" />"; 
						
					// Open and Editable & Push to Fishbowl - Green (fishbowl_true_icon.png)
					} else { 
						$fbimg = "<img src=\"{$url}/img/fishbowl_edit_icon.png\" width=\"25\" height=\"25\" title=\"In Production\" />"; 
					}
					
					$created = date("n/j/Y h:i a", strtotime($value['created']));
					if($value['updated'] != "") { 
						$updated = date("n/j/Y h:i a", strtotime($value['updated'])); 
					} else { 
						$updated = "N/A"; 
					}
					if($value['status'] > 0 || ($user_group == 4 && $value['status'] > 1)) { 
						$del_disable = " disabled"; 
					}
					
// 					if($user_group >= 3 || $value['status'] == 3) { $ship_disable = " disabled"; }
					$content_array['content'] .= <<<EOT
						<tr>
							<td><a href="{$url}/resupply/materialReorderEdit?action=materialReorderEdit&reorder_id={$value['id']}&view={$array['view']}" class="button small success">View</a></td>
							<td>{$i}.</td>
							<td>{$value['term']}</td>
							<td>{$fb_pre}RSO-{$term}-{$value['id']}</td>
							<td>{$value['camp_and_location']}</td>
							<td class="center">{$fbimg}</td>
							<td class="center">{$value['num_shipped']} of {$value['num_items']}</td>
							<td>{$created}</td>
							<td>{$updated}</td>
							<td><a href="mailto:{$value['email']}">{$value['user_name']}</a></td>
							<td class="right"><a href="{$url}/resupply/resupplyorderdelete?reorder_id={$value['id']}&view={$array['view']}" onClick="return confirm('Are you sure you want to delete this resupply order? This action can not be undone!');" class="button small alert{$del_disable}">Delete</a>
							<!--<a href="{$url}/resupply/markshipped?reorder_id={$key}" class="button small success{$ship_disable}">Marked Shipped</a>--></td>
						</tr>
EOT;
					$i++;
				}
				$content_array['content'] .= <<<EOT
				</tbody>
			</table>
EOT;
				$content_array['content'] .= gambaNavigation::pagination("{$url}/resupply?view={$array['view']}", $roList['reorders'], $array['page']);
			} else {
				$content_array['content'] .= "<p>".$roList['message']."</p>";
			}
			$content_array['content'] .= gambaDebug::preformatted_arrays($roList, "ro_list", "Resupply Order List");
			return $content_array;
// 			echo "<pre>"; print_r($_SESSION); echo "</pre>";
// 			echo "<pre>"; print_r($roList); echo "</pre>";
		}
		
		public static function oc_block_actions_unresolved($uid) {
			$term = gambaTerm::year_by_status('C');
			$query = Reorders::select('id')->where('term', $term)->where('user', $uid)->where('status', 2)->get();
			foreach($query as $row) {
				$query2 = ReorderItems::select();
			}
		}
		
		// Moved to ResupplyController - showMaterialLists and materiallists.blade.php
		public static function view_materiallists($array) {
			$url = url('/');
			$user_camps = Session::get('camps');
			$term = gambaTerm::year_by_status("C");
			$campactivities = self::supplyactivities($term, $array['part_info'], $array['type'], $user_camps);
			$locations_with_camps = gambaLocations::locations_with_camps();
			$content_array['page_title'] = "Resupply Orders - Material Lists";
			$content_array['content'] = gambaDirections::getDirections('resupplymateriallists');
			if($array['type'] != "filterbypart") {
				$resupply_filter_display = ' style="display:none;"';
			}
			$content_array['content'] .= <<<EOT
			<ul class="pagination">
				<li><a href="{$url}/resupply">Resupply Orders</a></li>
				<li class="current"><a href="#">Material Lists</a></li>
				<li><a href="#" id="filterlist">Filter List</a></li>
			</ul>
 			<script type="text/javascript">
 				$(document).ready(function(){
 					$('#filterlist').click(function(){
				        $('.resupply_filter').toggle();
					});
 				});
			</script> 
			<div class="resupply_filter"{$resupply_filter_display}>
				<form method="get" action="{$url}/resupply/materiallists" name="search">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
					<p><strong>Filter List by Part</strong>
					<input type="text" name="part_info" value="{$array['part_info']}" class="partlist form-control" style="max-width: 300px; min-width: 200px; display: inline;" />
					<input type="submit" name="submit" value="Filter" class="button small success" />
					<a href="{$url}/resupply/materiallists" class="button small success" />Clear</a>
					</p>	
					<input type="hidden" name="action" value="materiallists" />
					<input type="hidden" name="type" value="filterbypart" />
				</form>
			</div>
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
					<th>Grade Level</th>
					<th>Theme</th>
					<th>Activity</th>
					<th>Items</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
EOT;
			foreach($campactivities['theme_activities'] as $key => $value) {
				$content_array['content'] .= <<<EOT
				<tr class="row-theme">
					<td colspan="6" class="row-theme"><strong>{$value['camp_name']}</strong></td>
				</tr>
EOT;
				foreach($value['supply_lists'] as $theme_id => $theme_values) {
					if(is_array($theme_values['supplies'])) {
						foreach($theme_values['supplies'] as $list_id => $list_values) {
							$content_array['content'] .= <<<EOT
				<tr title="{$list_id}">
					<td>{$list_values['grade_level']}</td>
					<td>{$list_values['theme_name']}</td>
					<td>{$list_values['activity_name']}</td>
					<td>{$list_values['number_items']}</td>
					<td><a href="{$url}/resupply/materialReorder?request_id={$list_id}&activity_id={$list_values['activity_id']}&camp={$list_values['activity_info']['camp']}" class="button small success">View</a></td>
				</tr>
EOT;
						}
					} else {
						$content_array['content'] .= <<<EOT
				<tr>
					<td colspan="8">There are no supply lists created for this camp.</td>
				</tr>
EOT;
					}
				} 
			}
			$content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
			self::reorder_parts_autocomplete();
// 			gambaDebug::preformatted_arrays($campactivities, "campactivities", "Camp Activities");
// 			$content_array['content'] .= "<pre>"; print_r($locations_with_camps); echo "</pre>";
			return $content_array;
		}
		
		public static function resupply_camp_categories() {
			$camps = Camps::select('id', 'camp_values')->get();
			$i = 0;
			foreach($camps as $key => $values) {
				$camp_values = json_decode($values['camp_values'], true);
				if($camp_values['resupply'] == "true") {
					$array[$i] = $values['id'];
					$i++;
				}
			}
			return $array;
		}

		public static function supplyactivities($term, $part_info = NULL, $type = NULL, $camp_array = NULL, $camp = NULL) {
			$camps = gambaCampCategories::camps_list();
			$camp_array = json_decode($camp_array, true);
			if(is_array($camp_array)) {
				foreach($camp_array as $camp_id => $location_values) {
					$new_array[$camp_id] = $camp_id;
				}
				$camp_array = $new_array;
			}
			$activies = gambaActivities::activity_list_by_term($term);
			$resupply_camp_categories = self::resupply_camp_categories();
			if(is_array($camp_array)) {
				$i = 0;
				foreach($camp_array as $camp_id => $location_values) {
					if($camps[$camp_id]['camp_values']['resupply'] == "true") {
						$array['camps'][$i]['camp_id'] = $camp_id;
						$array['camps'][$i]['name'] = $camps[$camp_id]['name'];
						$i++;
					}
				}
			} else {
				$i = 0;
				foreach($camps as $camp_id => $value) {
					if($value['camp_values']['resupply'] == "true") {
						$array['camps'][$i]['camp_id'] = $camp_id;
						$array['camps'][$i]['name'] = $camps[$camp_id]['name'];
						$i++;
					}
				}
			}
			
			if($camp == NULL) {
				$camp = $array['camps'][0]['camp_id'];
			}
			
			$query = SupplyLists::select('supplylists.id', 
					'supplylists.activity_id', 
					'supplylists.term', 
					'supplylists.camp_type', 
					'supplylists.cg_staff', 
					'supplylists.user_id', 
					'supplylists.created', 
					'themes.name', 
					'themes.theme_options', 
					'grades.level', 
					'supplylists.budget', 
					'themes.id AS theme_id');
				$query = $query->leftjoin('activities', 'activities.id', '=', 'supplylists.activity_id');
				$query = $query->leftjoin('supplies', 'supplies.supplylist_id', '=', 'supplylists.id');
				$query = $query->leftjoin('themes', 'themes.id', '=', 'activities.theme_id');
				$query = $query->leftjoin('grades', 'grades.id', '=', 'activities.grade_id');
				if($part_info == "" && $type != "filterbypart") {
					$query = $query->where('supplylists.camp_type', $camp);
				}
				$query = $query->where('supplylists.term', $term);
				if($part_info != "" && $type == "filterbypart") { 
					list($number, $description) = explode("|", $part_info);
					$part = trim($number);
					$query = $query->where('supplies.part', $part);
				}
				$query = $query->orderBy('grades.level');
				$query = $query->orderBy('themes.name');
				$query = $query->orderBy('activities.activity_name');
				$array['sql'] = $query->toSql();
				$query = $query->get();
			
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					if($part_info != "" && $type == "filterbypart") {
						$camp = $row['camp_type'];
					}
					$supply_id = $row['id'];
					$theme_id = $row['theme_id'];
					$theme_name = $row['name'];
					$activity_id = $row['activity_id'];
					$array['camps'][$camp]['activities'][] = $activity_id;
					$number_items = gambaSupplies::supplies_per_list($supply_id);
					$array['bycamp'][$camp][$supply_id]['number_items'] = $num_items = $number_items['num_rows'];
// 					$array['bycamp'][$camp][$supply_id]['activity_id'] = $activity_id;
// 					$array['bycamp'][$camp][$supply_id]['last_user'] = $last_user = gambaSupplies::last_request_user($supply_id);
					$array['bycamp'][$camp][$supply_id]['activity_name'] = $activity_name = $activies[$activity_id]['name'];
					$array['bycamp'][$camp][$supply_id]['activity_info'] = $activity_info = $activies[$activity_id];
					$array['bycamp'][$camp][$supply_id]['cg_staff'] = $cg_staff = $row['cg_staff'];
					$array['bycamp'][$camp][$supply_id]['user_id'] = $user_id = $user_id = $row['user_id'];
					$array['bycamp'][$camp][$supply_id]['user_info'] = $user_info = gambaUsers::user_info($user_id);
					$array['bycamp'][$camp][$supply_id]['created'] = $created = $row['created'];
					$array['bycamp'][$camp][$supply_id]['theme_name'] = $theme_name;
					$array['bycamp'][$camp][$supply_id]['theme_id'] = $theme_id;
					$array['bycamp'][$camp][$supply_id]['theme_options'] = $theme_options = json_decode($row->theme_options, true);
					$array['bycamp'][$camp][$supply_id]['grade_level'] = $grade_level = $row['level'];
					$array['bycamp'][$camp][$supply_id]['budget'] = $budget = $row['budget'];
					$activities_array['bycamptheme'][$camp][$theme_id]['theme_name'] = $theme_name;
					$activities_array['bycamptheme'][$camp][$theme_id]['theme_options'] = $theme_options;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['activity_id'] = $activity_id;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['last_user'] = $last_user;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['number_items'] = $num_items;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['number_fbitems'] = $number_fbitems;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['activity_name'] = $activity_name;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['activity_info'] = $activity_info;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['cg_staff'] = $cg_staff;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['user_id'] = $user_id;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['user_info'] = $user_info;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['created'] = $created;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['theme_name'] = $theme_name;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['theme_id'] = $theme_id;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['theme_options'] = $theme_options;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['grade_level'] = $grade_level;
					$activities_array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['budget'] = $budget;
				}
			}
			
			if($term == "") {
				$term = gambaTerm::year_by_status('C');
			}
			if($part_info != "" && $type == "filterbypart") {
				$camps = gambaCampCategories::camps_list();
			}
			$group = Session::get('group');
			if($group < 4) {
				foreach($camps as $key => $value) {
					if($value['camp_values']['resupply'] == "true") {
						$array['theme_activities'][$key]['camp_name'] = $value['name'];
						$array['theme_activities'][$key]['camp_abbr'] = $value['abbr'];
						$array['theme_activities'][$key]['camp_alt_name'] = $value['alt_name'];
						$array['theme_activities'][$key]['supply_lists'] = $activities_array['bycamptheme'][$key];
					}
				}
			} else {
				foreach($camp_array as $key => $camp) {
					if($camps[$camp]['camp_values']['resupply'] != "false") {
						$array['theme_activities'][$camp]['camp_name'] = $camps[$camp]['name'];
						$array['theme_activities'][$camp]['camp_abbr'] = $camps[$camp]['abbr'];
						$array['theme_activities'][$camp]['camp_alt_name'] = $camps[$camp]['alt_name'];
						$array['theme_activities'][$camp]['supply_lists'] = $activities_array['bycamptheme'][$camp];
					}
				}
			}
			return $array;
		}
		
		public static function reorder_parts_autocomplete() {
			$term = gambaTerm::year_by_status('C');
			
			$query = Supplies::select('parts.number', 'parts.description', 'parts.suom', 'parts.cost', 'inventory.quantityonhand');
				$query = $query->leftjoin('parts', 'supplies.part', '=', 'parts.number')->leftjoin('inventory', 'inventory.number', '=', 'parts.number')->where('supplies.term', $term)->groupBy('supplies.part')->orderBy('parts.description')->get();
			
			if ($query->count() > 0) {
				$content .= '<script type="text/javascript">' . "\n";
				$content .= '$(".partlist").each(function(){$(this).autocomplete({' . "\n";
				// $content .= '$(".partlist").autocomplete({'."\n";
				$content .= 'source: [' . "\n";
				foreach($query as $key => $row) {
					$number = $row ['number'];
					$cost = $row ['cost'];
					$quantityonhand = $row ['quantityonhand'];
					$array [$number] ['description'] = $description = str_replace ( "&quot;", "''", $row ['description'] );
					$array [$number] ['suom'] = $suom = $row ['suom'];
					$content .= "\"$number | $description (";
					if ($cost != "") {
						$content .= "${$cost} ";
					}
					$content .= "$suom)";
					$content .= " QoH: $quantityonhand";
					$content .= "\", ";
				}
				$content .= ']' . "\n";
				$content .= "});" . "\n";
				$content .= "});" . "\n";
				$content .= "</script>" . "\n";
			}
			return $content;
	}
		
		/** Material Order Add
		 * Moved to materiallistitems.blade.php and ResupplyController - showMaterialReorder
		 * 
		 * @param unknown $array
		 */
		public static function view_materialreorder($array) {
			$url = url('/');
			$user_group = Session::get('group');
			$user_locations = Session::get('locations');
			$request_id = $array['request_id']; 
			$camp = $array['camp']; 
			if($user_locations == "" || $user_locations == 0) { 
				$location = "";
			} else { 
				$locations = $user_locations; 
			}
			$camps = gambaCampCategories::camps_list();
			$material_reorder = self::materialReorder($request_id, $action, $array['reorder_id']);
// 			$materialListInfo = $materials->getMaterialListInfo($request_id);
			$materialListInfo = gambaActivities::activity_info($array['activity_id']);
			$materialSelected = self::materialSelected($request_id, $locations, $camp);
			$content_array['page_title'] .= "Resupply Orders - Material Reorder Form Add";
			$content_array['content'] .= gambaDirections::getDirections('view_materialreorder');
			//<p class="directions"><strong>Directions:</strong> Input a quantity in the input next to the item you are reordering. Click the &quot;Create or Add to Existing Reorder Request&quot; button at the bottom. If you do not have an open resupply order one will be automatically created for you. Items added to the currently open list will not appear below. You will need to go the Reorder Edit to change amount or remove from reorder.</p>
			$content_array['content'] .= <<<EOT
			<ul class="pagination"> 
				<li><a href="{$url}/resupply/materiallists">Material Lists</a></li>
				<li><a href="{$url}/resupply">Resupply Orders</a></li>
			</ul>
EOT;
			if($array['error'] == 1) { 
				$content_array['content'] .= '<p class="error">You need to choose a camp.</p>'; 
			} 
			$content_array['content'] .= "<h2>Material List for ".$materialListInfo['camp_name']." &gt; ".$materialListInfo['theme_name']; 
			if($materialListInfo['grade_id'] > 0) { $content_array['content'] .= " &gt; " . $materialListInfo['grade_name']; } 
			$content_array['content'] .= "&gt; ". $materialListInfo['name'] ."</h2>";
			if(!empty($material_reorder)) {
				$content_array['content'] .= <<<EOT
				<form method="post" action="{$url}/resupply/resupplyordercreate" name="resupply">
EOT;
				$content_array['content'] .= csrf_field();
				
// 				if(!is_array($user_locations)) {
// 					$camps = self::campLocations($term, $array['camp']);

					$locations = gambaLocations::locations_with_camps();
//					echo "<pre>"; print_r($locations); echo "</pre>";
					$content_array['content'] .= <<<EOT
					<p><strong>Select Camp Location to Reorder:</strong> 
						<select name="location" required>
							<option value="">choose...</option>
EOT;
					foreach($locations['camps'][$camp]['locations'] as $key => $value) {
						if(!is_array($user_locations) || ($user_group == 4 && in_array($key, $user_locations[$camp]))) {
// 							$content_array['content'] .= "<option value='".$key."'>".$camps[$camp]['abbr']." ".$value['name']."</option>\n";
							$content_array['content'] .= "<option value='".$key."'";
							if(count($user_locations[$camp]) == 1) { $content_array['content'] .= " selected"; }
							$content_array['content'] .= ">".$value['camp_abbr']." ".$value['name']."</option>\n";
						}
					}
					$content_array['content'] .= "</select></p>";
// 				} else {
// 					$camp = self::campLocation($location);
// // 					$location = gambaLocations::location_by_id($location);
// 					$content_array['content'] .= "<h2>Reorder for Camp ".$camps[$camp]['abbr']." ".$camp['location']."</h2>\n";
// 					$content_array['content'] .= "\t\t\t<input type='hidden' name='location' value='".$location."' />\n";
// 				}
				$content_array['content'] .= <<<EOT
		<script>
		$(function(){ 
		    $("table").tablesorter({
				widgets: [ 'stickyHeaders' ],
				widgetOptions: { stickyHeaders_offset : 50, },
			}); 
			$("table").data("sorter", false);
		 }); 
		 $(function() {
			var BusinessDays = new Date();
			var adjustments = [0, 0, 2, 2, 2, 2, 1]; // Offsets by day of the week
			BusinessDays.setDate(BusinessDays.getDate() + 3 + adjustments[BusinessDays.getDay()]);
		    $( ".datepicker" ).datepicker({
				minDate: BusinessDays,    
				beforeShowDay: $.datepicker.noWeekends
			});
		  });
		</script>
				<table class="table table-striped table-bordered table-hover table-condensed table-small table-fixed-header">
					<thead>
						<tr>
							<th></th>
							<th>Part<br />Number</th>	
							<th>Part Description</th>	
							<th>Purchase Notes</th>
							<th align="center">Pack Size</th>
							<th align="center">Cost<br />Per<br />Unit</th>
							<th align="center">Part UoM</th>
							<th align="center">Quantity<br />Available</th>
							<th>Quantity<br />Requested</th>
							<th>Need By Date</th>
							<th>Notes</th>
						</tr>
					</thead>
					<tbody>
EOT;
				$i = 1;
				foreach($material_reorder['parts'] as $key => $value) {
					if($value['part_num'] != "") {
						if($materialSelected[$key] != 1) {
							if($value['conversion'] > 0) { $uom = $value['fbuom']; }
							if($value['url'] != "") { $link = " [<a href=\"{$value['url']}\" target=\"new\">URL</a>]"; } else { $link = ""; }
							$content_array['content'] .= <<<EOT
						<tr valign="top">
							<td>{$i}</td>
							<td>{$value['part_num']} {$materialSelected[$key]}</td>
							<td>{$value['part_desc']}{$link}</td>
							<td>{$value['adminnotes']}</td>
							<td>{$value['uom']}</td>
							<td align="right">{$value['cost']}</td>
							<td>{$value['fbuom']}</td>
							<!-- <td align="center">{$value['availablesale']}</td> -->
							<td align="center">{$value['quantityonhand']}</td>
							<input type="hidden" name="orig[{$key}]" value="{$value['qty']}" />
							<td><input type="text" name="values[{$key}][qty]" value="{$value['qty']}" class="form-control" /></td>
							<td><input type="text" name="values[{$key}][need_by]" value="{$value['need_by']}" class="form-control datepicker" /></td>
							<td><input type="text" name="values[{$key}][notes]" value="{$value['notes']}" class="form-control" /></td>
						</tr>
EOT;
							$i++;
						}
					}
				}
				$content_array['content'] .= <<<EOT
					<tbody>
				</table>
				<input type='hidden' name="camp" value="{$camp}" />
				<input type="hidden" name="action" value="resupplyordercreate" />
				<input type="hidden" name="request_id" value="{$array['request_id']}" />
				<input type="hidden" name="activity_id" value="{$array['activity_id']}" />
				<input type="hidden" name="reorder_id" value="{$array['reorder_id']}" />
				<input type="hidden" name="term" value="{$term}" />
				<p><input type="submit" name="submit" value="Create or Add To Existing Reorder Request" class="button small success" /></p>
			</form>
EOT;
			} else {
				$content_array['content'] .= '<p class="error">There are currently no materials for this list.</p>';

			}
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($materialSelected, "materialSelected", "Materials Selected");
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($materialListInfo, "materialListInfo", "Material List Info");
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($material_reorder, "material_reorder", "Materials Reorder");
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($camps, "camps", "Camps");
// 			$content_array['content'] .= gambaDebug::preformatted_arrays($locations, "Locations", "Locations");
			return $content_array;
		}
		
		/**
		 * Material Order Edit
		 * @param unknown $array
		 * @param unknown $return
		 */
		public static function view_materialreorderedit($array, $return) {
			$url = url('/');
			$user_group = Session::get('group');
			$user_id = Session::get('uid');
			if($user_id == "") {
				$user_id = Auth::user()->id;
			}
			$request_id = $array['request_id'];
			$term = gambaTerm::year_by_status('C');
			$material_reorder = self::materialReorderEdit($array['reorder_id']);
			$content_array['page_title'] .= "Resupply Order List for {$material_reorder['camp_location']}";
			$content_array['content'] .= gambaDirections::getDirections('view_materialreorderedit');
			//<p class="directions"><strong>Directions:</strong> Items are grouped by material list and then part description. Click on the &quot;Add More Items&quot; to return to the Material Lists. You can also click on the Material Request Name to return directly to that list to add more items. Click &quot;Delete&quot; to remove an item from your order. You can change the quantities requested and click the &quot;Update Order&quot; button to save the new amounts.</p>
			if($material_reorder['fishbowl_response']['push_status_code'] != "") {
				if($material_reorder['fishbowl_response']['push_status_code'] == 1000) { $alert_status = " success"; } else { $alert_status = " alert"; }
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box{$alert_status} radius">
						<strong>FB Code {$material_reorder['fishbowl_response']['push_status_code']}:</strong> {$material_reorder['fishbowl_response']['push_status_message']}
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			}
			if($array['success'] == 1) { 
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box success radius">
						The data has been updated.
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			} 
			if($array['submitted'] == 1) { 
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box success radius">
						Your resupply order has been submitted for approval.
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			}
			if($array['unlocked'] == 1) {
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box success radius">
						Your resupply order has been unlocked for editing.
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			}
			if($array['shipping_status'] == 1) {
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box success radius">
						The Shipping Status of your items has been updated.
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			}
			if($array['mark_pushed'] == 1) {
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box success radius">
						Admin Override: Marked Pushed to Fishbowl.
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			}
			if($array['added'] == 1) {
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box success radius">
						Your items have been added to resupply order.
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			}
			if($array['created'] == 1) { 
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box success radius">
						Your resupply order has been created.
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			}
// 			if($array['connect'] == "fail" && $material_reorder['fishbowl_response']) { 
// 				$content_array['content'] .= <<<EOT
// 					<div data-alert class="alert-box alert radius">
// 						Unknown Error. Could not connect to the Fishbowl Server to submit your Reorder.
// 						<a href="#" class="close">&times;</a>	
// 					</div>
// EOT;
// 			} 
			if($array['fb_number_status'] == "missing") { 
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box alert radius">
						You need to provide a Fishbowl Sales Number to Close Out the Order.
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			}
			if($array['pushtofishbowl'] == 2) { 
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box alert radius">
						Successfully connected but an Error occured Pushing your Reorder.
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			} 
			if($array['customer_address_error'] == "true") {
				$content_array['content'] .= <<<EOT
					<div data-alert class="alert-box alert radius">
						Gamba could not find the customer address for {$material_reorder['camp_location']}. Please make sure the Location name in Fishbowl matches the location in Gamba. Update the information in Fishbowl and then sync Gamba.
						<a href="#" class="close">&times;</a>	
					</div>
EOT;
			}
			$content_array['content'] .= <<<EOT
			<ul class="pagination">
				<li><a href="{$url}/resupply/materiallists">Material Lists</a></li>
				<li><a href="{$url}/resupply?view={$array['view']}">Resupply Orders</a></li>
			</ul>
EOT;
			if(!empty($material_reorder)) {
				if($material_reorder['status'] == 1 || $material_reorder['status'] == 2) { $hidden_action = "resupplyorderchange"; } 					
				if($material_reorder['status'] == 0) { $hidden_action = "resupplyorderupdate"; }
				$fb_pre = config('fishbowl.fbpre');
				$content_array['content'] .= <<<EOT
			<p><strong>GAMBA #:</strong> {$fb_pre}RSO-{$material_reorder['reorder_id']} &nbsp;&nbsp;&nbsp;
				<strong>Sales Order #:</strong> {$material_reorder['fb_number']} &nbsp;&nbsp;&nbsp;
				<strong>Submitter:</strong> <a href="mailto:{$material_reorder['user_email']}">{$material_reorder['user_name']}</a> 
				</p>
				
				
			<form method="post" action="{$url}/resupply/{$hidden_action}" name="resupply" id="resupplyEdit">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
				<input type="hidden" name="push_location" value="{$material_reorder['camp_location']}" />
				<input type="hidden" name="push_gamba_number" value="{$fb_pre}RSO-{$term}-{$material_reorder['reorder_id']}" />
				<input type="hidden" name="push_fb_number" value="{$material_reorder['fb_number']}" />
EOT;
			if($material_reorder['shipped'] == "false" && $material_reorder['status'] == 2 && $material_reorder['num_shipped'] <= $material_reorder['num_items']) {
				$content_array['content'] .= <<<EOT
				<script type="text/javascript">
					$(document).ready(function () {
					    $("#ckbCheckAll").click(function () {
					        $(".checkBoxClass").prop('checked', $(this).prop('checked'));
					    });
					});
				</script>
EOT;
				if($user_group <= 1) {
					$content_array['content'] .= <<<EOT
				<div style="float:right">
					<input type="checkbox" id="ckbCheckAll" /> Mark All Shipped
				</div>
EOT;
				}
			}
			if($material_reorder['status'] == 0) {
				$content_array['content'] .= <<<EOT
				<p><input type="submit" name="submit" value="Add More Items" class="button small success" /></p>
EOT;
			}
			if($material_reorder['status'] == 0) { $header_col = "<th></th>"; }
				$content_array['content'] .= <<<EOT
	
		<script>
		$(function(){ 
		    $("table").tablesorter({
				widgets: [ 'stickyHeaders' ],
				widgetOptions: { stickyHeaders_offset : 50, },
			}); 
			$("table").data("sorter", false);
		 }); 
		 $(function() {
			var BusinessDays = new Date();
			var adjustments = [0, 0, 2, 2, 2, 2, 1]; // Offsets by day of the week
			BusinessDays.setDate(BusinessDays.getDate() + 3 + adjustments[BusinessDays.getDay()]);
		    $( ".datepicker" ).datepicker({
				minDate: BusinessDays,    
				beforeShowDay: $.datepicker.noWeekends
			});
		  });
		</script>
					<table class="table table-striped table-bordered table-hover table-condensed table-small table-fixed-header">
						<thead>
							<tr>
								<th></th>
								<th>Material Request List</th>
								<th>Part<br />No.</th>	
								<th>Part Description</th>
								<th align="center">Part UoM</th>
								<th>Purchase Notes</th>
								<th align="center">Cost<br />Per<br />Unit</th>
								<th align="center">Pack Size</th>
								<th>Prod #</th>
								<th>Quantity<br />Requested</th>
								<th>Need By Date</th>
								<th align="center">Quantity<br />Available</th>
								<th>Camp Notes</th>
EOT;
					if($need_status == "true") {
						$content_array['content'] .= "<th>Need Status</th>";
					}
					$content_array['content'] .= <<<EOT
								<th>Order Status</th>
								<th>Warehouse Notes</th>
								<th>Shipped</th>
								<th>Est. Delivery Date</th>
								{$header_col}
							</tr>
						</thead>
						<tbody>
EOT;
				$i = 1;
				if($_GET['ship_status_override'] == "true") {
					$material_reorder['shipped'] = "false";
				}
				foreach($material_reorder['parts'] as $key => $value) {
					$standardcost = "$".$value['standardCost'];
					if($value['url'] != "") { $link = " [<a href=\"{$value['url']}\" target=\"new\">URL</a>]"; } else { $link = ""; }
					$content_array['content'] .= <<<EOT
							<tr valign="top">
								<td>{$i}</td>
								<td>
EOT;
					if($material_reorder['status'] == 0) {
						$content_array['content'] .= '<a href="'.$url.'/resupply/materialReorder?request_id='.$value['request_id'].'&camp='.$material_reorder['camp'].'">'; 
					} 
					$content_array['content'] .= $value['activity_name'] .' '. $value['grade_level'];
					if($material_reorder['status'] == 0) { $content_array['content'] .= '</a>'; } 
					$content_array['content'] .= <<<EOT
									<input type="hidden" name="values[{$key}][list_name]" value="{$value['activity_name']} {$value['grade_level']}" />
									<input type="hidden" name="values[{$key}][part_number]" value="{$value['part_num']}" />
									<input type="hidden" name="values[{$key}][part_description]" value="{$value['part_desc']}" />
									<input type="hidden" name="values[{$key}][part_uom]" value="{$value['uom']}" />
									<input type="hidden" name="values[{$key}][part_admin_notes]" value="{$value['adminnotes']}" />
									<input type="hidden" name="values[{$key}][part_qty]" value="{$value['qty']}" />
									<input type="hidden" name="values[{$key}][part_need_by]" value="{$value['need_by']}" />
									<input type="hidden" name="values[{$key}][part_camp_notes]" value="{$value['notes']}" />
								</td>
								<td>{$value['part_num']}</td>
								<td>{$value['part_desc']}{$link}</td>
								<td align="center">{$value['uom']}</td>
								<td>{$value['adminnotes']}</td>
								<td align="right">{$standardcost}</td>
								<td>{$value['fbuom']}</td>
								<td>{$value['part_num']}</td>
								<!--<td>{$value['prod_num']}</td>-->
								<td align="center">
EOT;
					// Quantity - 
					if($material_reorder['status'] == 0) { 
						$content_array['content'] .= '<input type="text" name="values['.$key.'][qty]" value="'.$value['qty'].'" class="form-control" required />'; 
					} else {
						$content_array['content'] .= $value['qty']; 
					} 
					$content_array['content'] .= <<<EOT
								</td>
								<td align="center">
EOT;
					// Quantity - 
					if($material_reorder['status'] == 0) { 
						$content_array['content'] .= '<input type="text" name="values['.$key.'][need_by]" value="'.$value['need_by'].'" class="form-control datepicker" required />'; 
					} else {
						$content_array['content'] .= $value['need_by']; 
					} 
					$content_array['content'] .= <<<EOT
								</td>
								<!--<td align="center">{$value['availablesale']}</td>-->
								<td align="center">{$value['quantityonhand']}</td>
								
EOT;
					// Camp Notes
					if($material_reorder['status'] == 0) {
						$content_array['content'] .= '<td><textarea  name="values['.$key.'][notes]" class="form-control">'.$value['notes'] .'</textarea></td>'; 
					} else { 
						$content_array['content'] .= $value['notes']; 
						$content_array['content'] .= '<input type="hidden" name="values['.$key.'][notes]" value="'.$value['notes'].'" />'; 
					} 
					$content_array['content'] .= <<<EOT
								<td>
EOT;
					if($value['order_status'] > 0 && $value['status'] < 2) {
						// Need Status Dropdown
						$need_statuses = self::need_status_array();
						if($material_reorder['status'] == 2 && $material_reorder['shipped'] == "false") {
							$need_status_required = ""; 
							if($value['order_status'] > 0 && $value['order_status'] <= 3) {
								$need_status_required = " required"; 
							}
							$content_array['content'] .= <<<EOT
									<select name="values[$key][need_status]"{$need_status_required} style="color:red;">
										<option value="0">Action Needed</option>
EOT;
							foreach($need_statuses as $status_key => $status_value) {
								if($value['need_status'] == $status_key) { $status_selected = " selected"; } else { $status_selected = ""; }
								$content_array['content'] .= "<option value=\"$status_key\"$status_selected>$status_value</option>";
							}
						}
						$content_array['content'] .= <<<EOT
									</select>
EOT;
					}
					$content_array['content'] .= <<<EOT
								</td>
EOT;
					// Status of Order
					if($value['status'] < 2) {
					$order_statuses = self::order_status_array();
					if($material_reorder['status'] == 2 && $material_reorder['shipped'] == "false" && $user_group < 4) {
						$content_array['content'] .= <<<EOT
								<td class="center" title="order status">
									<select name="values[$key][order_status]">
										<option value="0">------</option>
EOT;
						foreach($order_statuses as $status_key => $status_value) {
							if($value['order_status'] == $status_key) { $status_selected = " selected"; } else { $status_selected = ""; }
							$content_array['content'] .= "<option value=\"$status_key\"$status_selected>$status_value</option>";
						}
						$content_array['content'] .= <<<EOT
									</select>
								</td>
EOT;
					} else { 
						$content_array['content'] .= <<<EOT
									<input type="hidden" name="values[$key][order_status]" value="{$value['order_status']}" />
									{$order_statuses[$value['order_status']]}
EOT;
						 
					}
					}
					$content_array['content'] .= <<<EOT
								<td>
EOT;
					// Warehouse Notes
// 					if($material_reorder['status'] == 2 && $material_reorder['shipped'] == "false" && $user_group < 4) {
					if($material_reorder['status'] <= 2 && $user_group < 4 && $material_reorder['shipped'] == "false") {
						$content_array['content'] .= '<textarea name="values['.$key.'][warh_notes]">'.$value['warh_notes'].'</textarea>'; 
					} else { 
						$content_array['content'] .= $value['warh_notes']; 
						$content_array['content'] .= '<input type="hidden" name="values['.$key.'][warh_notes]" value="'.$value['warh_notes'].'" />'; 
					}
					$content_array['content'] .= <<<EOT
								</td>
								<td class="center">
EOT;
					// Set Shipping Status
					if($material_reorder['status'] == 2 && $material_reorder['shipped'] == "false" && $user_group < 4) {
						$content_array['content'] .= '<input type="checkbox" name="values['.$key.'][status]" value="2"'; if($value['status'] == 2) { $content_array['content'] .= " checked"; } $content_array['content'] .= ' class="checkBoxClass" />'; 
					} else { 
						if($value['status'] == 2) { $content_array['content'] .= "<img src='{$url}/img/van_icon.png' title='Shipped' />"; } else { $content_array['content'] .= ""; } 
					}
					$content_array['content'] .= <<<EOT
								</td>
								<td class="center">
EOT;
					// Set Shipping Date
					if($material_reorder['status'] <= 2 && $material_reorder['shipped'] == "false" && $user_group < 4) {
						$content_array['content'] .= '<input type="text" name="values['.$key.'][ship_date]" value="'.$value['ship_date'].'" class="form-control datepicker" />'; 
					} else { 
						$content_array['content'] .= $value['ship_date'];
						$content_array['content'] .= '<input type="hidden" name="values['.$key.'][ship_date]" value="'.$value['ship_date'].'" />'; 
					}
					$content_array['content'] .= <<<EOT
								</td>
								
EOT;
					// <td>{$value['ship_date']}<input type="hidden" name="values[{$key}][ship_date]" value="{$value['ship_date']}" /></td>
					
					if($material_reorder['status'] == 0) {
						$content_array['content'] .= <<<EOT
					
					<td><a href="{$url}/resupply/reorderitemdelete?reorder_id={$array['reorder_id']}&reorderitem_id={$key}" class="button small alert">Delete</a></td>
EOT;
					} 
					$content_array['content'] .= <<<EOT
							</tr>
EOT;
							$i++;
						}
					$content_array['content'] .= <<<EOT
						</tbody>
					</table>
EOT;
					if($material_reorder['status'] == 1 || $material_reorder['status'] == 2) { $hidden_action = "resupplyorderchange"; } 					
					if($material_reorder['status'] == 0) { $hidden_action = "resupplyorderupdate"; }
					$content_array['content'] .= <<<EOT
					<input type="hidden" name="action" value="{$hidden_action}" />
					<input type="hidden" name="reorder_id" value="{$array['reorder_id']}" />
					<input type="hidden" name="term" value="{$term}" />
EOT;
					if($material_reorder['status'] == 0) {
						$content_array['content'] .= <<<EOT
					<p><strong>You may continue to edit your resupply order until the time your resupply order is to be completed. The warehouse will close it and process it according to the resupply schedule.</strong></p>
EOT;
					}
					// Fishbowl Sales Order No. - To be entered on SO Closing
					if($material_reorder['status'] == 0 && $user_group < 4) {
						$content_array['content'] .= <<<EOT
					<p><strong>Fishbowl Sales Order Number</strong> <input type="text" name="fb_number" id="fbNumber" value="{$material_reorder['fb_number']}" /> <small>Required to Close Resupply Order. Obtain from Fishbowl.</small></p>
EOT;
					}
					$content_array['content'] .= <<<EOT
					<p>
EOT;
					if($material_reorder['status'] == 0) {
						$content_array['content'] .= <<<EOT
					<input type="submit" name="submit" value="Update Order" class="button small success" />
EOT;
						if($user_group < 4) {
							$content_array['content'] .= <<<EOT
					<input type="submit" name="submit" value="Close Resupply Order" class="button small success" />
					<script type="text/javascript">
						$(document).ready(function() {
							$('#resupplyEdit').submit(function(event) {
								if ($("input[type='submit]").val() == "Close Resupply Order" && $('#fbNumber').val == "") {
									if(!confirm("You need a Fishbowl Supply Order Number!")) {
										event.preventDefault();
									}
								}
							});
						});
					</script>
EOT;
						}
					}
					if($material_reorder['status'] == 1 && $user_group < 4) {
						$content_array['content'] .= <<<EOT
					<input type="submit" name="submit" value="Unlock for Editing" class="button small success" />
					<input type="submit" name="submit" value="Push to Fishbowl" class="button small success" />
EOT;
						if($user_id == 1) {
							$content_array['content'] .= <<<EOT
							<input type="submit" name="submit" value="Mark as Pushed" class="button small success" />
EOT;
						}
						if($user_group == 0) {
							$content_array['content'] .= <<<EOT
							Check to test XML Output <input type="checkbox" name="xmltest" value="1" />
EOT;
						}
					}
					if($material_reorder['status'] == 2 && $user_group == 4 && $material_reorder['shipped'] == "false") {
						$content_array['content'] .= <<<EOT
					<input type="submit" name="submit" value="Update My Order Status" class="button small success" />
EOT;
					}
					
					if($material_reorder['status'] == 2 && $user_group < 3 && $material_reorder['shipped'] == "false") {
						$content_array['content'] .= <<<EOT
					<input type="submit" name="submit" value="Save Changes" class="button small success" />
EOT;
					}
					if($material_reorder['shipped'] == "true" && $user_group < 4) {
						$content_array['content'] .= <<<EOT
					<a href="{$url}/resupply/materialReorderEdit?reorder_id={$array['reorder_id']}&ship_status_override=true" class="button small success">Reopen to Edit</a>
EOT;
					}
					$content_array['content'] .= <<<EOT
						</p>
				</form>
EOT;
			}
			$content_array['content'] .= gambaDebug::preformatted_arrays($material_reorder, "material_reorder", "Material Reorder");
			return $content_array;
		}
		
		public static function order_status_array() {
			$return = array(
				1 => "Out of Stock - Will Ship When in Stock",
				//2 => "Out of Stock - Item Not Crucial, Will Not Ship",
				//3 => "Out of Stock - Subbed With Alternative",
				//4 => "Please Clarify - Contact <a href='mailto:campsupport@galileo-learning.com'>campsupport@galileo-learning.com</a>"
// 				4 => "Need Clarification - Please Contact <a href='mailto:campsupport@galileo-learning.com'>campsupport@galileo-learning.com</a>",
				5 => "Out of stock for season",
				6 => "More info needed"
			);
			return $return;
		}
		
		public static function need_status_array() {
			$return = array(
				1 => "Still Need",
				2 => "Do Not Need"
			);
			return $return;
		}
		
		public static function request_reason_array() {
			$return = array(
				1 => "Increased Enrollment",
				2 => "Damaged",
				3 => "Received Short",
				4 => "Not Received"
			);
			return $return;
		}
		
		public static function report_reorder_locations() {
			$term = gambaTerm::year_by_status('C'); 
			$locations = ReorderItems::select('camp')->distinct();
			$locations = $locations->where('term', '=', $term);
			$locations = $locations->get()->toArray();
			foreach($locations as $key => $value) {
				$id = $value['camp'];
				$camp_location = self::campLocation($id);
				$array[$id] = "{$camp_location['abbr']} {$camp_location['location']}";
			}
			asort($array, SORT_NATURAL);
			return $array;
		}
		
		public static function report_reorderitems($array) {
			$term = gambaTerm::year_by_status('C'); 
// 			$term = "2015";
			$query = ReorderItems::select('reorderitems.reorder_id', 'reorderitems.request_id', 'supplies.part', 'parts.description', 'reorderitems.camp AS location_id', 'locations.location AS location_name', 'supplies.camp_id', 'camps.name AS camp_name', 'reorderitems.qty', 'reorderitems.status', 'partuoms.name AS uom', 'vendors.Name AS vendor', 'camps.abbr');
			$query = $query->from('reorderitems AS reorderitems');
			$query = $query->leftjoin('supplies', 'supplies.id', '=', 'reorderitems.supply_id');
			$query = $query->leftjoin('camps', 'camps.id', '=', 'supplies.camp_id');
			$query = $query->leftjoin('parts', 'parts.number', '=', 'supplies.part');
			$query = $query->leftjoin('locations', 'locations.id', '=', 'reorderitems.camp');
			$query = $query->leftjoin('partuoms', 'partuoms.code', '=', 'parts.fbuom');
			$query = $query->leftjoin('vendors', 'vendors.VendorID', '=', 'parts.vendor');
			$query = $query->where('reorderitems.term', $term);
			$query = $query->where('supplies.part', '!=', '');
			if($array['location_id'] != "") {
				$query = $query->where('reorderitems.camp', '=', $array['location_id']);
			}
			$query = $query->orderBy('parts.description');
			$query = $query->get();
			$reorder_items = $query->toArray(); // ->limit(60)
			foreach($reorder_items as $key => $values) {
				$part = $values['part']; 
				$reorder_id = $values['reorder_id']; 
				$return[$part]['part'] = $part; 
				$return[$part]['description'] = $values['description']; 
				$return[$part]['uom'] = strtolower($values['uom']); 
				$return[$part]['vendor'] = $values['vendor']; 
				$return[$part]['requests'][$reorder_id]['request_id'] = $values['request_id']; 
				$return[$part]['requests'][$reorder_id]['camp_id'] = $values['camp_id']; 
				$return[$part]['requests'][$reorder_id]['camp_name'] = $values['camp_name']; 
				$return[$part]['requests'][$reorder_id]['location_id'] = $values['location_id']; 
				$return[$part]['requests'][$reorder_id]['location_name'] = "{$values['abbr']} {$values['location_name']}";
				$return[$part]['requests'][$reorder_id]['qty'] = $values['qty']; 
				$return[$part]['requests'][$reorder_id]['status'] = $values['status']; 
			}
			return $return;
		}
		
		// Moved to ResupplyController.php and reporting.blade.php
		public static function view_reporting($array) {
			$url = url('/');
			$term = gambaTerm::year_by_status('C');
			$reorderitems = self::report_reorderitems($array);
			$locations = self::report_reorder_locations();
			$num_items = count($reorderitems);
			$content_array['page_title'] = "Resupply Order Reporting {$term}";
			$content_array['content'] .= <<<EOT
					<ul class="pagination">
						<li><a href="{$url}/resupply">Resupply Orders</a></li>
						<li><a href="{$url}/resupply/materiallists">Material Lists</a></li>
						<li class="current"><a href="#">Reporting</a></li>
					</ul>
				<p>Number of Parts in Search: $num_items</p>
EOT;
				$content_array['content'] .= <<<EOT
					<button href="#" data-dropdown="drop1" aria-controls="drop1" aria-expanded="false" class="button dropdown">
						Select Location ({$locations[$array['location_id']]})</button><br />
						<ul id="drop1" data-dropdown-content class="f-dropdown" aria-hidden="true">
EOT;
			foreach($locations as $location_id => $location_name) {
				$content_array['content'] .= <<<EOT
							<li><a href="{$url}/resupply/resupply-reporting?location_id={$location_id}">{$location_name}</a></li>
EOT;
			}
			$content_array['content'] .= <<<EOT
						</ul>
EOT;
// 			echo "<pre>"; print_r($locations); echo "</pre>";
			if(!empty($reorderitems)) {
				$content_array['content'] .= <<<EOT
				<table class="table table-striped table-bordered table-hover table-condensed table-small">
		 			<theader>
						<th></th>
		 				<th>Part Number</th>
		 				<th>Description</th>
		 				<th>UoM</th>
		 				<th>Vendor</th>
		 				<th>Qty Ordered</th>
		 				<th>Qty Shipped</th>
		 				<th>Locations</th>
		 				<th>Resupply Orders</th>
		 			</theader>
					<tbody>
EOT;
				$i = 1;
				foreach($reorderitems as $part => $values) {
					$qty = 0;
					$shipped = 0;
					$locations = "";
					$requests = "";
					$a = 1; $b = 1;
					foreach($values['requests'] as $request_id => $request_values) {
						$qty += $request_values['qty'];
						if($request_values['status'] == 2) {
							$shipped += $request_values['qty'];
						}
						$locations .= "{$request_values['location_name']}, ";;
						$requests .= "<a href=\"{$url}/resupply/materialReorderEdit?reorder_id=$request_id\">GMBSO-$request_id</a>, ";
						if($a == 5) {
							$locations .= "<br />";
							$a = 0;
						}
						if($b == 8) {
							$requests .= "<br />";
							$b = 0;
						}
						$a++; $b++;
					}
					if($qty != $shipped) {
						$shipped = "<span style=\"color:red\">$shipped</span>";
					} else {
						$shipped = "<span style=\"color:green\">$shipped</span>";
					}
					$locations = rtrim($locations, ", ");
					$requests = rtrim($requests, ", ");
					$content_array['content'] .= <<<EOT
						<tr>
							<td>$i.</td>
							<td>{$part}</td>
							<td>{$values['description']}</td>
							<td>{$values['uom']}</td>
							<td>{$values['vendor']}</td>
							<td>{$qty}</td>
							<td>{$shipped}</td>
							<td>{$locations}</td>
							<td>{$requests}</td>
						</tr>
EOT;
					$i++;
				}
				$content_array['content'] .= <<<EOT
					</tbody>
				</table>
EOT;
			}
			return $content_array;
		}
			
	}
