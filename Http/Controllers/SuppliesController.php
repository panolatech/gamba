<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Session;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaActivities;
use App\Gamba\gambaDirections;
use App\Gamba\gambaQuantityTypes;
use App\Gamba\gambaUOMs;
use App\Gamba\gambaCampCategories;
use App\Gamba\gambaParts;
use App\Gamba\gambaSupplies;
use App\Gamba\gambaLocations;
use App\Gamba\gambaTerm;

use App\Models\Seasons;

class SuppliesController extends Controller
{

    public function index(Request $array)
    {
    	//gambaSupplies::supplylists($array['term'], $array['action'], $array['r']);
    	$term = $array['term'];
    	if($term == "") {  $term = gambaTerm::year_by_status('C'); } else { $term = $array['term']; }
    	$current_term = gambaTerm::year_by_status('C');
    	$campactivities = gambaSupplies::campactivities($term); $directions = "supplylists";
    	$terms = gambaTerm::terms();
    	$theme_budget = gambaSupplies::theme_budget();
    	$cwsupplylists_term_dropdown = gambaSupplies::cwsupplylists_term_dropdown($term, $array['action']);
    	$directions = gambaDirections::getDirections('supplylists');
    	return view('app.supplies.cwmateriallists', [
    	    'array' => $array,
    	    'term' => $term,
    	    'current_term' => $current_term,
    	    'campactivities' => $campactivities,
    	    'terms' => $terms,
    	    'theme_budget' => $theme_budget,
    	    'cwsupplylists_term_dropdown' => $cwsupplylists_term_dropdown,
    	    'directions' => $directions
    	]);
    }

    public function showSupplyList(Request $array)
    {
//     	gambaSupplies::supplylistview($array, $array['r']);
    	$user_group = Session::get('group');
    	$supplyparts = gambaSupplies::supplyparts($array['id']);
    	$activity_info = gambaActivities::activity_info($array['activity_id'], $array['id']);
    	$qts = gambaQuantityTypes::quantity_types_by_camp($array['camp'], $array['term']);
    	$uoms = gambaUOMs::uom_list();
    	$camps = gambaCampCategories::camps_list();
    	$amt_cost_per = gambaSupplies::amt_cost_per();
    	$theme_budget_display = gambaSupplies::theme_budget();
//     	if($array['packtotalcalc'] == 1) {
//     	    $locations = gambaLocations::locations_by_camp();
//     	}
    	if(is_array($camps[$array['camp']]['camp_values']['request_locations'])) {
    	    $locations = gambaLocations::locations_by_camp();
    	}
    	$directions = gambaDirections::getDirections('supplylistview');
    	//$directions2 = gambaDirections::getDirections("material_add_".$array['camp']);
    	//$directions3 = gambaDirections::getDirections("create_materials_".$array['camp'],'direction');
    	$view_change_log = gambaSupplies::view_change_log($array['id']);
    	$data_inputs = gambaSupplies::data_inputs($array['camp'], $array['id'], $array['term'], $array['activity_id']);
    	$parts_autocomplete = gambaParts::parts_autocomplete();
    	if($user_group <= 1) {
    	    foreach($supplyparts['supplies'] as $key => $values) {
    	        $packing_list_array[] = $values['packing_id'];
    	    }
    	    $packing_list_array = array_unique($packing_list_array);
    	    $packing_list_ids = implode(',', $packing_list_array);
    	    $move_materials_change = gambaSupplies::move_materials_change($array['camp'], $array['id'], $array['term'], $array['activity_id'], $button_disabled, $supplyparts['locked'], $packing_list_ids);
    	}
    	$part_classes = gambaSupplies::material_classifications();
    	return view('app.supplies.cwlistview', [
    	    'array' => $array,
    	    'supplyparts' => $supplyparts,
    	    'user_group' => $user_group,
    	    'activity_info' => $activity_info,
    	    'qts' => $qts,
    	    'camps' => $camps,
    	    'amt_cost_per' => $amt_cost_per,
    	    'theme_budget_display' => $theme_budget_display,
    	    'locations' => $locations,
    	    'directions' => $directions,
    	    'view_change_log' => $view_change_log,
    	    'data_inputs' => $data_inputs,
    	    'directions2' => $directions2,
    	    'directions3' => $directions3,
    	    'parts_autocomplete' => $parts_autocomplete,
    	    'move_materials_change' => $move_materials_change,
    	    'uoms' => $uoms,
    	    'part_classes' => $part_classes
    	]);
    }

    public function editSupplyList(Request $array)
    {
        //gambaSupplies::supplylistedit($array);
        $user_id = Session::get('uid');
        $supplyparts = gambaSupplies::supplyparts($array['id']);
        $activity_info = gambaActivities::activity_info($array['activity_id']);
        $quantity_types_by_camp = gambaQuantityTypes::quantity_types_by_camp($array['camp'], $array['term']);
        $uoms = gambaUOMs::uom_list();
        $camps = gambaCampCategories::camps_list();
        $directions = gambaDirections::getDirections('supplylistview');
        if(is_array($camps[$array['camp']]['camp_values']['request_locations'])) {
            $locations = gambaLocations::locations_by_camp();
        }
        $part_classes = gambaSupplies::material_classifications();
    	return view('app.supplies.cwlistviewedit', [
    	    'array' => $array,
    	    'user_id' => $user_id,
    	    'supplyparts' => $supplyparts,
    	    'activity_info' => $activity_info,
    	    'quantity_types_by_camp' => $quantity_types_by_camp,
    	    'uoms' => $uoms,
    	    'camps' => $camps,
    	    'directions' => $directions,
    	    'locations' => $locations,
    	    'part_classes' => $part_classes
    	]);
    }

    public function createSupplyList(Request $array)
    {
    	$content = gambaSupplies::createsupplylists($array['term'], $array['action']);
    	return view('app.supplies.home', ['content' => $content]);
    }

    public function copySupplyList(Request $array)
    {
    	//$content = gambaSupplies::listcopy($array);
    	$terms = Seasons::select('year')->where('status', 'C')->orWhere('status', 'N')->orWhere('status', 'F')->orderBy('year', 'DESC')->get();
    	$directions = gambaDirections::getDirections('listcopy');
    	$camps = gambaCampCategories::camps_list();
    	$themesandactivities = gambaSupplies::themesandactivities($array['term']);
    	$activity_info = gambaActivities::activity_info($array['activity_id']);
    	return view('app.supplies.cwlistcopy', [
    	    'term' => $array['term'],
    	    'camp' => $array['camp'],
    	    'directions' => $directions,
    	    'camps' => $camps,
    	    'themesandactivities' => $themesandactivities,
    	    'activity_info' => $activity_info,
    	    'terms' => $terms,
    	    'id' => $array['id'],
    	    'from_activity_id' => $array['activity_id']
    	]);
    }

    public function add_form_field(Request $array) {
    	// RETURN IF NEEDED IN FUTURE - js/add_form_field.js.php
    	//return view('app.supplies.add_form_field', ['array' => $array]);
    }


    public function storeList(Request $array)
    {
    	$result = gambaSupplies::createlist($array);
		return redirect("supplies/supplylistview?id={$result['id']}&camp={$array['camp']}&term={$array['term']}&activity_id={$array['activity_id']}&packtotalcalc=1&newlist=1");
    }

    public function deleteSupply(Request $array)
    {
//     	dd($array);
    	$result = gambaSupplies::delete_material_request($array);
		return redirect("supplies/supplylistview?id={$array['id']}&term={$array['term']}&camp={$array['camp']}&activity_id={$array['activity_id']}&packtotalcalc=1&r=$result");
    }

    public function moveMaterial(Request $array)
    {
    	$result = gambaSupplies::move_materials($array);
		return redirect("supplies/supplylistview?id={$array['id']}&term={$array['term']}&activity_id={$array['activity_id']}&packtotalcalc=1&camp={$array['new_camp']}");
    }

    public function deleteList(Request $array)
    {
    	$result = gambaSupplies::delete_list($array);
		return redirect("supplies/supplyrequests?action=supplyrequests&term={$array['$term']}&r=$result");
    }

    public function insertList(Request $array)
    {
    	$result = gambaSupplies::listinsert($array);
		return redirect("supplies/supplylistview?id={$array['supplylist_id']}&camp={$array['camp']}&term={$array['term']}&activity_id={$array['activity_id']}&packtotalcalc=1&newlist=1");
    }

    public function listCreateInsert(Request $array)
    {
    	$result = gambaSupplies::listcreateinsert($array);
		return redirect("supplies/supplylistview?id={$array['supplylist_id']}&camp={$array['camp']}&term={$array['term']}&activity_id={$array['activity_id']}&packtotalcalc=1&newlist=1");
    }

    public function storeSupplies(Request $array)
    {
    	$result = gambaSupplies::add_supply_requests($array);
		return redirect("supplies/supplylistview?id={$array['id']}&term={$array['term']}&camp={$array['camp']}&activity_id={$array['activity_id']}&packtotalcalc=1&r=$result");
    }

    public function updateSupplies(Request $array)
    {
    	$result = gambaSupplies::supplies_update($array);
		return redirect("supplies/supplylistview?id={$array['id']}&term={$array['term']}&camp={$array['camp']}&activity_id={$array['activity_id']}&packtotalcalc=1&r=$result");
    }

    public function updateDataInputs(Request $array)
    {
    	$result = gambaSupplies::update_data_inputs($array);
		return redirect("supplies/supplylistedit?id={$array['id']}&term={$array['term']}&camp={$array['camp']}&activity_id={$array['activity_id']}&data_inputs_updated=1");
    }

    public function adminLock(Request $array)
    {
    	$result = gambaSupplies::admin_lock($array);
    	return redirect("supplies/supplylistview?id={$array['id']}&term={$array['term']}&camp={$array['camp']}&activity_id={$array['activity_id']}&packtotalcalc=1&list=locked");
    }

    public function adminLockDebug(Request $array)
    {
        $result = gambaSupplies::admin_lock($array);
        $content['content'] = $result;
        return view('app.supplies.home', ['content' => $content]);
    }

    public function adminUnlock(Request $array)
    {
    	$result = gambaSupplies::admin_unlock($array);
		return redirect("supplies/supplylistview?id={$array['id']}&term={$array['term']}&camp={$array['camp']}&activity_id={$array['activity_id']}&packtotalcalc=1&list=unlocked");
    }

}