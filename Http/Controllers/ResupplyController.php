<?php

	namespace App\Http\Controllers;
	
	use Illuminate\Support\Facades\Session;
	use Illuminate\Http\Request;
	
	use App\Http\Requests;
	use App\Http\Controllers\Controller;
	
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
	use App\Gamba\gambaNavigation;
	use App\Gamba\gambaProducts;
	use App\Gamba\gambaResupplyOrders;
	use App\Gamba\gambaSupplies;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaUsers;
	
	class ResupplyController extends Controller
	{
		
		// Reorders Open and Editable or Reorder User View
	    public function index(Request $array)
	    {
	    	// Landing Page for All
	    	$url = url('/');
			$content['user_group'] = $user_group = Session::get('group');
			$content['user_locations'] = $user_locations = Session::get('locations');
			$content['session_data'] = $session_data = Session::get();
			$content['term'] = gambaTerm::year_by_status("C");
			$content['pagination'] = gambaNavigation::pagination("{$url}/resupply", $array['roList']['reorders'], $array['page']);
				
	    	// If a Reorder User
	    	if($user_group == 4) {
	    		//echo "<p>$user_group</p>";
	    		//echo "<pre>"; print_r($session_data); echo "</pre>";
				$content['roList'] = gambaResupplyOrders::userreorders($user_locations);
	    		return view('app.resupply.reorderusersview', ['array' => $content]);
	    	} else {
				$content['roList'] = gambaResupplyOrders::reorders($user_group, $user_locations, "");
	    		return view('app.resupply.reordersopeneditable', ['array' => $content]);
	    	}
	    }
		// Reorders to Push
	    public function reorders_to_push(Request $array) {
	    	$url = url('/');
			$content['user_group'] = $group = Session::get('group');
			$user_locations = Session::get('locations');
			$content['term'] = gambaTerm::year_by_status("C");
			$content['roList'] = gambaResupplyOrders::reorders($user_group, $user_locations, "pushfb");
			$content['pagination'] = gambaNavigation::pagination("{$url}/resupply/orders/pushtofishbowl", $array['roList']['reorders'], $array['page']);
	    	return view('app.resupply.reorderspush', ['array' => $content]);
	    }
		// Reorders In Production
	    public function reorders_in_production(Request $array) {
	    	$url = url('/');
			$content['user_group'] = $group = Session::get('group');
			$user_locations = Session::get('locations');
			$content['term'] = gambaTerm::year_by_status("C");
			$content['roList'] = gambaResupplyOrders::reorders($user_group, $user_locations, "production");
			$content['pagination'] = gambaNavigation::pagination("{$url}/resupply/orders/inproduction", $array['roList']['reorders'], $array['page']);
	    	return view('app.resupply.reordersinproduction', ['array' => $content]);
	    }
		// Reorders Partially Shipped
	    public function reorders_partially_shipped(Request $array) {
	    	$url = url('/');
			$content['user_group'] = $group = Session::get('group');
			$user_locations = Session::get('locations');
			$content['term'] = gambaTerm::year_by_status("C");
			$content['roList'] = gambaResupplyOrders::reorders($user_group, $user_locations, "shipping");
			$content['pagination'] = gambaNavigation::pagination("{$url}/resupply/orders/partiallyshipped", $array['roList']['reorders'], $array['page']);
	    	return view('app.resupply.reorderspartiallyshipped', ['array' => $content]);
	    }
		// Reorders Fully Shipped
	    public function reorders_fully_shipped(Request $array) {
	    	$url = url('/');
			$content['user_group'] = $group = Session::get('group');
			$user_locations = Session::get('locations');
			$content['term'] = gambaTerm::year_by_status("C");
			$content['roList'] = gambaResupplyOrders::reorders($user_group, $user_locations, "shipped");
			$content['pagination'] = gambaNavigation::pagination("{$url}/resupply/orders/fullyshipped", $array['roList']['reorders'], $array['page']);
	    	return view('app.resupply.reordersfullyshipped', ['array' => $content]);
	    }

	    // Reorder Edit Open and Editable
	    public function edit_open_editable($id, Request $array)
	    {
	    	$content['ship_status_override'] = $array['ship_status_override'];
			$content['user_group'] = Session::get('group');
			$content['user_id'] = Session::get('uid');
			$content['reorder_id'] = $id;
			$content['term'] = gambaTerm::year_by_status('C');
			$content['success'] = $array['success'];
			$content['unlocked'] = $array['unlocked'];
			$content['created'] = $array['created'];
			$content['item_delete'] = $array['item_delete'];
			$content['added'] = $array['added'];
			$content['customer_address_error'] = $array['customer_address_error'];
			$content['order_statuses'] = gambaResupplyOrders::order_status_array();
			$content['need_status_array'] = gambaResupplyOrders::need_status_array();
			$content['request_reason_array'] = gambaResupplyOrders::request_reason_array();
			$content['material_reorder'] = gambaResupplyOrders::materialReorderEdit($content['reorder_id']);
			$content['page_title'] .= "Resupply Order List for {$content['material_reorder']['camp_location']} - Open and Editable";
	    	return view('app.resupply.reordereditopeneditable', ['array' => $content]);
	    }
		// Update Resupply Order - Open and Editable
	    public function updateResupplyOrder(Request $array) {
// 	    	echo "<pre>"; print_r($array['values']); echo "</pre>"; exit; die();
	    	$url = url('/');
	    	$result = gambaResupplyOrders::resupplyorderupdate($array);
	    	// Add More Items
	    	if($array['submit'] == "Add More Items") {
				return redirect("{$url}/resupply/materiallists?location={$array['location_id']}&reorder_id={$array['reorder_id']}"); 
	    	}
	    	// Close Resupply Order
	    	if($array['submit'] == "Close Resupply Order") {
				return redirect("{$url}/resupply/edit/pushtofishbowl/{$array['reorder_id']}?submitted=1");
	    	}
	    	// Update Resupply Order
			return redirect("{$url}/resupply/edit/open/{$array['reorder_id']}?success=1");
	    }
	    // Reorder Edit to Push
	    public function edit_push($id, Request $array)
	    {
	    	$content['ship_status_override'] = $array['ship_status_override'];
	    	$content['connect'] = $array['connect'];
	    	$content['customer_address_error'] = $array['customer_address_error'];
	    	$content['pushtofishbowl'] = $array['pushtofishbowl'];
			$content['user_group'] = Session::get('group');
			$content['user_id'] = Session::get('uid');
			$content['reorder_id'] = $id;
			$content['term'] = gambaTerm::year_by_status('C');
			$content['order_statuses'] = gambaResupplyOrders::order_status_array();
			$content['need_status_array'] = gambaResupplyOrders::need_status_array();
			$content['material_reorder'] = gambaResupplyOrders::materialReorderEdit($content['reorder_id']);
			$content['page_title'] = "Resupply Order List for {$content['material_reorder']['camp_location']} - Push to Fishbowl";
	    	return view('app.resupply.reordereditpush', ['array' => $content]);
	    }
	    // Users View Reorders to Push
	    public function view_reorders_to_push($id, Request $array)
	    {
	    	$content['ship_status_override'] = $array['ship_status_override'];
			$content['user_group'] = Session::get('group');
			$content['user_id'] = Session::get('uid');
			$content['reorder_id'] = $id;
			$content['term'] = gambaTerm::year_by_status('C');
			$content['order_statuses'] = gambaResupplyOrders::order_status_array();
			$content['need_status_array'] = gambaResupplyOrders::need_status_array();
			$content['material_reorder'] = gambaResupplyOrders::materialReorderEdit($content['reorder_id']);
			$content['page_title'] = "Resupply Order List for {$content['material_reorder']['camp_location']} - Push to Fishbowl";
	    	return view('app.resupply.reorderviewpush', ['array' => $content]);
	    }
		// Update Resupply Order - Push to Fishbowl
	    public function change_push_to_fishbowl(Request $array) {
	    	//$result = gambaResupplyOrders::resupplyorderchange($array);
	    	
	    	foreach($array['values'] as $id => $values) {
	    		if($values['ship_date'] != "") {
	    			$ship_date = date("Y-m-d", strtotime($values['ship_date']));
	    		}
	    		$update = ReorderItems::where('id', $id)->update(['warh_notes' => $values['warh_notes'], 'ship_date' => $ship_date]);
	    	}
	    	
	    	if($array['submit'] == "Unlock for Editing") {
	    		$result = gambaResupplyOrders::unlock_for_editing($array);
	    		return redirect("{$url}/resupply/edit/open/{$array['reorder_id']}?unlocked=1");
	    		exit;
	    	}
	    	if($array['submit'] == "Push to Fishbowl") {
	    		$result = gambaResupplyOrders::push_to_fishbowl($array);
	    		//echo "<pre>"; print_r($result); echo "</pre>"; exit; die();
	    		if($result['connect_status_code'] == 1000) {
	    			if($result['customer_address_error'] == "true") {
	    				return redirect("{$url}/resupply/edit/pushtofishbowl/{$array['reorder_id']}?customer_address_error=true");
	    				exit;
	    			}
	    			if($result['push_status_code'] == "1000") {
	    				return redirect("{$url}/resupply/edit/inproduction/{$array['reorder_id']}?pushtofishbowl=success");
	    				exit;
	    			} else {
	    				return redirect("{$url}/resupply/edit/pushtofishbowl/{$array['reorder_id']}?fishbowl=2&pushtofishbowl=fail");
	    				exit;
	    			}
	    		} else {
	    			return redirect("{$url}/resupply/edit/pushtofishbowl/{$array['reorder_id']}?fishbowl=1&connect=fail");
	    			exit;
	    		}
	    	}
	    	if($array['submit'] == "Mark as Pushed") {
	    		$result = gambaResupplyOrders::mark_as_pushed($array);
	    		return redirect("{$url}/resupply/edit/inproduction/{$array['reorder_id']}?mark_pushed=1");
	    		exit;
	    	}
	    }
	    
	    // Reorder Edit In Production
	    public function edit_in_production($id, Request $array) {
	    	$content['ship_status_override'] = $array['ship_status_override'];
	    	$content['shipping_status'] = $array['shipping_status'];
	    	$content['mark_pushed'] = $array['mark_pushed'];
	    	$content['success'] = $array['success'];
			$content['user_group'] = Session::get('group');
			$content['user_id'] = Session::get('uid');
			$content['reorder_id'] = $id;
			$content['term'] = gambaTerm::year_by_status('C');
			$content['order_statuses'] = gambaResupplyOrders::order_status_array();
			$content['need_status_array'] = gambaResupplyOrders::need_status_array();
			$content['material_reorder'] = gambaResupplyOrders::materialReorderEdit($content['reorder_id']);
			$content['page_title'] .= "Resupply Order List for {$content['material_reorder']['camp_location']} - In Production";
	    	return view('app.resupply.reordereditinproduction', ['array' => $content]);
	    }
	    // Users View Reorders In Production
	    public function view_reorders_in_production($id, Request $array) {
	    	$content['ship_status_override'] = $array['ship_status_override'];
	    	$content['shipping_status'] = $array['shipping_status'];
	    	$content['mark_pushed'] = $array['mark_pushed'];
	    	$content['success'] = $array['success'];
			$content['user_group'] = Session::get('group');
			$content['user_id'] = Session::get('uid');
			$content['reorder_id'] = $id;
			$content['term'] = gambaTerm::year_by_status('C');
			$content['order_statuses'] = gambaResupplyOrders::order_status_array();
			$content['need_status_array'] = gambaResupplyOrders::need_status_array();
			$content['material_reorder'] = gambaResupplyOrders::materialReorderEdit($content['reorder_id']);
			$content['page_title'] = "Resupply Order List for {$content['material_reorder']['camp_location']} - In Production";
	    	return view('app.resupply.reorderviewinproduction', ['array' => $content]);
	    }
		// Update Resupply Order - In Production
	    public function change_in_production(Request $array) {
    		$fbpre = config('fishbowl.fbpre');
			$reorder_id = $array['reorder_id']; $request_id = $array['request_id'];
	    	//$result = gambaResupplyOrders::resupplyorderchange($array);
				$ship_total = 0; $item_total = 0;
				foreach($array['values'] as $key => $value) {
// 					if($value['ship_date'] == "" && $value['status'] == 1) { $ship_date = date("Y-m-d"); } else { $ship_date = $value['ship_date']; }
					if($value['status'] == "") { $value['status'] = 1; }
					if($value['ship_date'] != "") { 
						$ship_date = date("Y-m-d", strtotime($value['ship_date'])); 
					} else { 
						$ship_date = ""; 
					}
					if($value['status'] == 2) {
						$ship_total++;
					}
					$item_total++;
					$update = ReorderItems::find($key);
						$update->order_status = $value['order_status'];
						$update->warh_notes = $value['warh_notes'];
						$update->status = $value['status'];
						$update->ship_date = $ship_date;
						$update->save();
				}
				if($item_total == $ship_total) {
					$result['path'] = "fullyshipped";
					$result['msg'] = "fullyshipped=1";
					$update = Reorders::where('id', $reorder_id)->update(['status' => '3', 'shipping' => '2']);
				}
				if($ship_total == 0) {
					$result['path'] = "inproduction";
					$result['msg'] = "success=1";
					$update = Reorders::where('id', $reorder_id)->update(['status' => '2', 'shipping' => '0']);
				}
				if($ship_total > 0 && $ship_total < $item_total) {
					$result['path'] = "partiallyshipped";
					$result['msg'] = "partiallyshipped=1";
					$update = Reorders::where('id', $reorder_id)->update(['status' => '2', 'shipping' => '1']);
				}
				$update = Reorders::where('id', $reorder_id)->update(['fb_number' => $array['fb_number']]);
	    	return redirect("{$url}/resupply/edit/{$result['path']}/{$array['reorder_id']}?{$result['msg']}");
	    }
	    
	    // Reorder Edit Partially Shipped
	    public function edit_partially_shipped($id, Request $array)
	    {
	    	$content['ship_status_override'] = $array['ship_status_override'];
			$content['user_group'] = Session::get('group');
			$content['user_id'] = Session::get('uid');
			$content['reorder_id'] = $id;
			$content['term'] = gambaTerm::year_by_status('C');
			$content['order_statuses'] = gambaResupplyOrders::order_status_array();
			$content['need_status_array'] = gambaResupplyOrders::need_status_array();
			$content['material_reorder'] = gambaResupplyOrders::materialReorderEdit($content['reorder_id']);
			$content['page_title'] .= "Resupply Order List for {$content['material_reorder']['camp_location']} - Partially Shipped";
	    	return view('app.resupply.reordereditpartiallyshipped', ['array' => $content]);
	    }
	    // Users View Reorders Partially Shipped
	    public function view_reorders_partially_shipped($id, Request $array)
	    {
	    	$content['ship_status_override'] = $array['ship_status_override'];
			$content['user_group'] = Session::get('group');
			$content['user_id'] = Session::get('uid');
			$content['reorder_id'] = $id;
			$content['term'] = gambaTerm::year_by_status('C');
			$content['order_statuses'] = gambaResupplyOrders::order_status_array();
			$content['need_status_array'] = gambaResupplyOrders::need_status_array();
			$content['material_reorder'] = gambaResupplyOrders::materialReorderEdit($content['reorder_id']);
			$content['page_title'] .= "Resupply Order List for {$content['material_reorder']['camp_location']} - Partially Shipped";
	    	return view('app.resupply.reorderviewpartiallyshipped', ['array' => $content]);
	    }
		// Update Resupply Order - Partially Shipped
	    public function change_partially_shipped(Request $array) {
    		$fbpre = config('fishbowl.fbpre');
			$reorder_id = $array['reorder_id']; $request_id = $array['request_id'];
	    	//$result = gambaResupplyOrders::resupplyorderchange($array);
			$ship_total = 0; $item_total = 0;
			foreach($array['values'] as $key => $value) {
// 					if($value['ship_date'] == "" && $value['status'] == 1) { $ship_date = date("Y-m-d"); } else { $ship_date = $value['ship_date']; }
				if($value['status'] == "") { $value['status'] = 1; }
				if($value['ship_date'] != "") { 
					$ship_date = date("Y-m-d", strtotime($value['ship_date'])); 
				} else { 
					$ship_date = ""; 
				}
				if($value['status'] == 2) {
					$ship_total++;
				}
				$item_total++;
				$update = ReorderItems::find($key);
					$update->order_status = $value['order_status'];
					$update->warh_notes = $value['warh_notes'];
					$update->status = $value['status'];
					$update->ship_date = $ship_date;
					$update->save();
			}
			
			if($item_total == $ship_total) {
				$result['path'] = "fullyshipped";
				$result['msg'] = "fullyshipped=1";
				$update = Reorders::where('id', $reorder_id)->update(['status' => '3', 'shipping' => '2']);
			}
			if($ship_total == 0) {
				$result['path'] = "inproduction";
				$result['msg'] = "success=1";
				$update = Reorders::where('id', $reorder_id)->update(['status' => '2', 'shipping' => '0']);
			}
			if($ship_total > 0 && $ship_total < $item_total) {
				$result['path'] = "partiallyshipped";
				$result['msg'] = "partiallyshipped=1";
				$update = Reorders::where('id', $reorder_id)->update(['status' => '2', 'shipping' => '1']);
			}
			
			$update = Reorders::where('id', $reorder_id)->update(['fb_number' => $array['fb_number']]);
	    	return redirect("{$url}/resupply/edit/{$result['path']}/{$array['reorder_id']}?{$result['msg']}");
	    }
	    // Reorder Edit Fully Shipped
	    public function edit_fully_shipped($id, Request $array)
	    {
	    	$content['ship_status_override'] = $array['ship_status_override'];
			$content['user_group'] = Session::get('group');
			$content['user_id'] = Session::get('uid');
			$content['reorder_id'] = $id;
			$content['term'] = gambaTerm::year_by_status('C');
			$content['order_statuses'] = gambaResupplyOrders::order_status_array();
			$content['need_status_array'] = gambaResupplyOrders::need_status_array();
			$content['material_reorder'] = gambaResupplyOrders::materialReorderEdit($content['reorder_id']);
			$content['page_title'] .= "Resupply Order List for {$content['material_reorder']['camp_location']} - Fully Shipped";
	    	return view('app.resupply.reordereditfullyshipped', ['array' => $content]);
	    }
	    // Users View Reorders Fully Shipped
	    public function view_reorders_fully_shipped($id, Request $array)
	    {
	    	$content['ship_status_override'] = $array['ship_status_override'];
			$content['user_group'] = Session::get('group');
			$content['user_id'] = Session::get('uid');
			$content['reorder_id'] = $id;
			$content['term'] = gambaTerm::year_by_status('C');
			$content['order_statuses'] = gambaResupplyOrders::order_status_array();
			$content['need_status_array'] = gambaResupplyOrders::need_status_array();
			$content['material_reorder'] = gambaResupplyOrders::materialReorderEdit($content['reorder_id']);
			$content['page_title'] .= "Resupply Order List for {$content['material_reorder']['camp_location']} - Fully Shipped";
	    	return view('app.resupply.reorderviewfullyshipped', ['array' => $content]);
	    }
		// Update Resupply Order - Fully Shipped
	    public function change_fully_shipped(Request $array) {
	    	$result = gambaResupplyOrders::resupplyorderchange($array);
	    	//     	echo "<pre>"; print_r($result); echo "</pre>"; exit; die();
	    	if($array['submit'] == "Unlock for Editing") {
	    		return redirect("{$url}/resupply/materialReorderEdit?action=materialReorderEdit&request_id={$array['request_id']}&reorder_id={$array['reorder_id']}&unlocked=1");
	    		exit;
	    	}
	    	if($array['submit'] == "Push to Fishbowl") {
	    		//$result = gambaFishbowl::push_resupply_order($array);
	    		if($result['statusCode'] != 1000) {
	    			return redirect("{$url}/resupply/materialReorderEdit?reorder_id={$array['reorder_id']}&fishbowl=1&connect=fail");
	    			exit;
	    		} else {
	    			if($result['customer_address_error'] == "true") {
	    				return redirect("{$url}/resupply/materialReorderEdit?reorder_id={$array['reorder_id']}&customer_address_error=true");
	    				exit;
	    			}
	    			if($result['push_status_code'] != "1000") {
	    				return redirect("{$url}/resupply/materialReorderEdit?action=materialReorderEdit&reorder_id={$array['reorder_id']}&fishbowl=2&pushtofishbowl=fail");
	    				exit;
	    			} else {
	    				return redirect("{$url}/resupply?fishbowl=1&rso={$fbpre}RSO-{$array['reorder_id']}&pushtofishbowl=success");
	    				exit;
	    			}
	    				
	    		}
	    	}
	    	if($array['submit'] == "Mark as Pushed") {
	    		return redirect("{$url}/resupply/materialReorderEdit?action=materialReorderEdit&request_id={$array['request_id']}&reorder_id={$array['reorder_id']}&mark_pushed=1");
	    		exit;
	    	}
	    	if($array['submit'] == "Update My Order Status") {
	    		return redirect("{$url}/resupply/materialReorderEdit?action=materialReorderEdit&request_id={$array['request_id']}&reorder_id={$reorder_id}");
	    		exit;
	    	}
	    	if($array['submit'] == "Save Changes") {
	    		return redirect("{$url}/resupply/materialReorderEdit?action=materialReorderEdit&request_id={$array['request_id']}&reorder_id={$array['reorder_id']}&shipping_status=1");
	    		exit;
	    	}
	    	//return redirect("{$url}/resupply/supplylistview");
	    }
	    
	    
	    // Material Lists
	    public function material_lists(Request $array)
	    {
	    	//$content = gambaResupplyOrders::view_materiallists($array);
	    	$content['session_data'] = Session::get();
			$content['user_locations'] = $user_locations = Session::get('locations');
			$term = gambaTerm::year_by_status("C");
			$content['campactivities'] = gambaResupplyOrders::supplyactivities($term, $array['part_info'], $array['type'], $user_locations, $array['camp']);
			$content['location_id'] = $array['location'];
			$content['reorder_id'] = $array['reorder_id'];
			$content['camp'] = $array['camp'];
			$content['type'] = $array['type'];
			$content['request_reason_array'] = gambaResupplyOrders::request_reason_array();
			$content['autocomplete'] = gambaResupplyOrders::reorder_parts_autocomplete();
			$content['locations_with_camps'] = gambaLocations::locations_with_camps();
	    	return view('app.resupply.materiallists', ['array' => $content]);
	    }
	    // Material List Items, Select Location and Add Items
	    public function material_list_items($request_id, $activity_id, $camp, Request $array)
	    {
	    	//$content = gambaResupplyOrders::view_materialreorder($array);
			$url = url('/');
			$content['user_group'] = Session::get('group');
			$content['user_locations'] = json_decode(Session::get('locations'), true);
			$content['request_id'] = $request_id;  
			if($content['user_locations'] == "" || $content['user_locations'] == 0) { 
				$location = "";
			} else { 
				$locations = $user_locations; 
			}
			$content['location_id'] = $array['location_id'];
			$content['reorder_id'] = $array['reorder_id'];
			$content['activity_id'] = $activity_id;
			$content['camp'] = $camp;
// 			echo "<pre>"; print_r($content); echo "</pre>"; exit; die();
			$camps = gambaCampCategories::camps_list();
			$content['request_reason_array'] = gambaResupplyOrders::request_reason_array();
			$content['material_reorder'] = gambaResupplyOrders::materialReorder($request_id, $action, $array['reorder_id']);
			$content['materialListInfo'] = gambaActivities::activity_info($activity_id);
			$content['locations'] = $locations = gambaLocations::locations_with_camps();
			if($array['location_id'] != "") { 
				$content['location_info'] = gambaLocations::location_by_id($array['location_id']); 
				$content['camp_info'] = gambaCampCategories::camp_info($camp);
				$content['page_title'] = "Resupply Orders - Material Reorder Item Add";
				$content['submit_button'] = "Add to Existing Reorder Request";
			} else {
				$content['page_title'] = "Resupply Orders - Material Reorder List Create";
				$content['submit_button'] = "Create Reorder Request";
			}
			$content['materialSelected'] = gambaResupplyOrders::materialSelected($request_id, $locations, $camp);
	    	return view('app.resupply.materiallistitems', ['array' => $content]);
	    }
	    
	    
	    
	    // Reporting
	    public function reportResupply(Request $array)
	    {
	    	//$content = gambaResupplyOrders::view_reporting($array, $array['r']);
			$content['term'] = gambaTerm::year_by_status('C');
			$content['reorderitems'] = $reorderitems = gambaResupplyOrders::report_reorderitems($array);
			$content['locations'] = gambaResupplyOrders::report_reorder_locations();
			$content['num_items'] = count($reorderitems);
	    	return view('app.resupply.reporting', ['array' => $content]);
	    }
	    
	    // Mark as Shipped
	    public function markShipped(Request $array)
	    {
	    	$url = url('/');
			$update = Reorders::find($array['reorder_id']);
				$update->status = 3;
				$update->shipping = 2;
				$update->save();
			return redirect("{$url}/resupply/shipping");
	    }
		
	    // Delete Resupply Order
	    public function deleteResupplyOrder(Request $array)
	    {
	    	$url = url('/');
			$delete = Reorders::find($array['reorder_id'])->delete();
			$delete = ReorderItems::where('reorder_id', $array['reorder_id'])->delete();
			return redirect("{$url}/resupply?view={$array['view']}");
	    }
		
	    // Delete Reorder Item
	    public function reorder_item_delete(Request $array)
	    {
	    	$url = url('/');
	    	$reorder_id = $array['reorder_id']; 
	    	$reorderitem_id = $array['reorderitem_id'];
			$delete = ReorderItems::find($reorderitem_id)->delete();
			return redirect("{$url}/resupply/edit/open/{$array['reorder_id']}?item_delete=1");
	    }
		
	    // Create Resupply Order
	    public function insertResupplyOrder(Request $array)
	    {
	//     	echo "<p>{$array['location']}</p>"; exit; die();
	    	$url = url('/');
	    	if($array['location'] == "") {
				return redirect("{$url}/resupply/materiallistitems?request_id={$array['request_id']}&activity_id={$array['activity_id']}&camp={$array['camp']}&error=1");
	    		//exit;
	    	}
	    	$result = gambaResupplyOrders::resupplyordercreate($array);
			return redirect("{$url}/resupply/edit/open/{$result['reorder_id']}{$result['msg']}");
	    }
		
		public function update_reorder_item_need_by_date() {
			$query = ReorderItems::select('id', 'need_by')->where('need_by', '!=', '')->get();
			$number = count($query);
			foreach($query as $key => $value) {
				$need_by = date("Y-m-d", strtotime($value['need_by']));
				$update = ReorderItems::find($value['id']);
					$update->need_by = $need_by;
					$update->save();
			}
			$content['content'] = "<p>$number  dates converted.</p>";
	    	return view('app.resupply.home', ['content' => $content]);
		}
		
		// Resupply Admin - Locations Cut Off Day
		public function cut_off_locations() {
			$query = Locations::select('locations.id', 'locations.location', 'camps.name', 'locations.camp', 'locations.term_data', 'locations.cut_off_day')->leftjoin('camps', 'camps.id', '=', 'locations.camp')->orderBy('locations.camp')->orderBy('locations.location')->get();
			foreach($query as $value) {
				$content['locations_array'][$value['camp']]['camp_name'] = $value['name'];
				$content['locations_array'][$value['camp']]['locations'][$value['id']]['location_name'] = $value['location'];
				$content['locations_array'][$value['camp']]['locations'][$value['id']]['term_data'] = json_decode($value['term_data'], true);;
				$content['locations_array'][$value['camp']]['locations'][$value['id']]['cut_off_day'] = $value['cut_off_day'];
			}
			return view('app.resupply.locations', ['array' => $content]);
		}
		
		// Resupply Admin - Location Cut Off Day Update
		public function cut_off_location_update(Request $array) {
// 			echo "Hello"; exit; die();
// 			echo $array['location_id']; exit; die();
			$update = Locations::find($array['location_id']);
				$update->cut_off_day = $array['cut_off_day'];
				$update->save();
			$url = url('/');
			return redirect("{$url}/resupply/cut_off_locations");
		}
		
		// Resupply Admin - Location Modal
		public function location_modal($location_id) {
			$query = Locations::find($location_id);
			$content['location_id'] = $location_id;
			$content['location_name'] = $query['location'];
			$content['cut_off_day'] = $query['cut_off_day'];
			return view('app.resupply.locationmodal', ['array' => $content]);
		}
		
		// Material List Items - Location Cut Off Day Datepicker
		public function datepicker_cutoff(Request $array) {
			$user_group = Session::get('group');
			$query = Locations::find($array['location']);
			$cut_off_day = $query['cut_off_day'];
			if($user_group == 4) {
				if($cut_off_day > 0) {
					$offset = 3;
				} else {
					$offset = 0;
				}
			} else {
				$offset = 0;
			}
			echo $offset;
			/*print <<<EOT
<script>
	$(function() {
		// Location ID: {$array['location']} {$query['location']}
		$( ".datepicker" ).datepicker({
			minDate: {$offset},
			beforeShowDay: $.datepicker.noWeekends
		});
	});
</script>
EOT; */
			
		}
	
	}