<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Session;


use App\Models\AllParts;
use App\Models\Inventory;
use App\Models\PackingTotals;
use App\Models\Parts;
use App\Models\Products;
use App\Models\PurchaseOrderItem;
use App\Models\Supplies;
use App\Models\VendorParts;
use App\Models\ViewPartCosts;

use App\Gamba\gambaLogs;
use App\Gamba\gambaParts;
use App\Gamba\gambaSupplies;
use App\Gamba\gambaTerm;
use App\Gamba\gambaUOMs;
use App\Gamba\gambaVendors;

use App\Jobs\JoinPartCosts;

class PartsController extends Controller
{

    public function index(Request $array, $view, $page) {
        //$return = json_decode(base64_decode($array['r']), true);

    	if($view == "all") {
    	    $title = "All Parts";
    	    if($page == "") { $page = "a"; }
    	    $parts = gambaParts::part_search("all", "", "", $page);
    	    $partslistnav = gambaParts::alphalistnav ("/parts/all", $view, $page);
    	} elseif ($view == "approved") {
    	    $title = "Approved Fishbowl Parts";
    	    if($page == "") { $page = "a"; }
    	    $parts = gambaParts::part_search("approved", "", "", $page);
    	    $partslistnav = gambaParts::alphalistnav ("/parts/approved", $view, $page);
    	} elseif ($view == "awaiting") {
    	    $title = "Parts Awaiting Approval";
    	    $parts = gambaParts::part_search("awaiting", $page, $array['offset']);
    	} elseif ($view == "concept") {
    	    $title = "Concept Items";
    	    $parts = gambaParts::part_search("concept", $page, $array['offset']);
    	} elseif ($view == "gamba") {
    	    $title = "GAMBA Parts";
    	    if($page == "") { $page = "a"; }
    	    $parts = gambaParts::part_search("gamba", "", "", $page);
    	    $partslistnav = gambaParts::alphalistnav ("/parts/gamba", $view, $page);
    	} elseif ($view == "retired") {
    	    $title = "Retired Parts";
    	    if($page == "") { $page = "a"; }
    	    $parts = gambaParts::part_search("retired", "", "", $page);
    	    $partslistnav = gambaParts::alphalistnav ("/parts/retired", $view, $page);
    	} elseif ($view == "parts_retired_requests") {
    	    $parts = gambaParts::part_search("parts_retired_requests", $page, $array['offset']);
    	} elseif ($view == "lastupdated") {
    	    $title = "Last 50 Updated";
    	    $parts = gambaParts::part_search("lastupdated", "", "");
    	} else {
    	    $view = "awaiting";
    	    $title = "Parts Awaiting Approval";
    	    $parts = gambaParts::part_search("awaiting", $page, $array['offset']);
    	}

    	$uoms = gambaUOMs::uom_list ();
    	$term = gambaTerm::year_by_status('C');

    	return view('app.parts.partsview', [
    	    'array' => $array,
    	    'parts' => $parts,
    	    'uoms' => $uoms,
    	    'term' => $term,
    	    'partslistnav' => $partslistnav,
    	    'view_navigation' => $view_navigation,
    	    'page_title' => $title,
    	    'view' => $view,
    	    'page' => $page
    	]);
    }

    public function suppliesconceptitems(Request $array, $page) {
        //$return = json_decode(base64_decode($array['r']), true);


        $title = "Concept Items";
        $parts = gambaParts::part_search("concept", $page, $array['offset']);

        $uoms = gambaUOMs::uom_list ();
        $vendors = gambaVendors::vendor_list ();
        $terms = gambaTerm::terms ();
        $numbers = gambaParts::available_part_numbers ();

        return view('app.parts.conceptitems', [
            'array' => $array,
            'parts' => $parts,
            'uoms' => $uoms,
            'vendors' => $vendors,
            'terms' => $terms,
            'numbers' => $numbers,
            'partslistnav' => $partslistnav,
            'view_navigation' => $view_navigation,
            'page_title' => $title,
            'view' => $view,
            'page' => $page
        ]);
    }

    public function suppliesconceptitemsedit(Request $array, $part) {
        $return = json_decode(base64_decode($array['r']), true);
        $user_group = Session::get('group');
        $uoms = gambaUOMs::uom_list();
        $vendors = gambaVendors::vendor_list();
        $terms = gambaTerm::terms();
        $supply_requests = gambaParts::supply_requests ( $part );
        $current_term = gambaTerm::year_by_status ( 'C' );
        $part_data = AllParts::select('number', 'description', 'suom', 'cost', 'pq', 'url', 'purl', 'approved', 'inventory', 'cwnotes', 'adminnotes', 'fishbowl', 'vendor', 'created', 'updated', 'fbuom', 'fbcost', 'conversion', 'xmlstring', 'part_options', 'concept');
        $part_data = $part_data->where('number', '=', $part);
        $part_data = $part_data->first();
        return view('app.parts.conceptitemeditform', [
            'array' => $array,
            'part_num' => $part,
            'user_group' => $user_group,
            'uoms' => $uoms,
            'vendors' => $vendors,
            'terms' => $terms,
            'part_data' => $part_data,
            'supply_requests' => $supply_requests,
            'current_term' => $current_term,
            'return' => $return
        ]);
    }

    public function suppliesconceptitemsupdate(Request $array) {
        if($array['concept'] == "") { $concept = 0; } else { $concept = 1; }
        $update = Parts::where('number', $array['part_num'])->update([
            'description' => $array['description'],
            'concept' => $concept,
            'url' => $array['url'],
            'suom' => $array['suom'],
            'cost' => $array['cost'],
            'cwnotes' => $array['cwnotes']
        ]);
        if($array['concept'] == "") {
            return redirect("supplies/conceptitems?part_confirmed=1&part_num={$array['part_num']}" );

        } else {
            return redirect("supplies/conceptitems/edit/{$array['part_num']}?update=1" );
        }
    }

    public function suppliesconceptitemsdelete($part) {

        $delete = Parts::where('number', $part)->delete();
        $delete = Supplies::where('part', $part)->delete();

        return redirect("supplies/conceptitems?part_deleted=1&part_num={$part}" );
    }

    public function suppliesmasterinventorylist(Request $array)
    {
        $parts = gambaParts::parts_list ( $array ['order'], $array ['part_num'], $array ['alpha'], "inventory" );
        $uoms = gambaUOMs::uom_list ();
        return view('app.supplies.masterinventorylist', [
            'parts' => $parts,
            'uoms' => $uoms
        ]);
    }

    public function suppliesparts(Request $array, $alpha) {
        //$return = json_decode(base64_decode($array['r']), true);

        if($alpha == "") { $alpha = "a"; }
        $parts = gambaParts::part_search("all", "", "", $alpha);
        $partslistnav = gambaParts::alphalistnav ("/supplies/parts", "all", $alpha);
        $uoms = gambaUOMs::uom_list ();
        $term = gambaTerm::year_by_status("C");

        return view('app.supplies.parts', [
            'array' => $array,
            'parts' => $parts,
            'uoms' => $uoms,
            'term' => $term,
            'partslistnav' => $partslistnav,
            'view_navigation' => $view_navigation,
            'page' => $alpha
        ]);
    }

    public function suppliespartsview($part) {
        $uoms = gambaUOMs::uom_list();
        $vendors = gambaVendors::vendor_list();
        $terms = gambaTerm::terms();
        $supply_requests = gambaParts::supply_requests ( $part );
        $current_term = gambaTerm::year_by_status ( 'C' );
        $part_data = AllParts::select('number', 'description', 'suom', 'cost', 'pq', 'url', 'purl', 'approved', 'inventory', 'cwnotes', 'adminnotes', 'fishbowl', 'vendor', 'created', 'updated', 'fbuom', 'fbcost', 'conversion', 'xmlstring', 'part_options', 'concept');
            $part_data = $part_data->where('number', $part);
            $part_data = $part_data->first();
        return view('app.supplies.partsview', [
            'part_num' => $part,
            'uoms' => $uoms,
            'vendors' => $vendors,
            'terms' => $terms,
            'part_data' => $part_data,
            'supply_requests' => $supply_requests,
            'current_term' => $current_term,
            'return' => $return

        ]);
    }

    public function formPartAdd(Request $array) {
    	$return = json_decode(base64_decode($array['r']), true);
    	$user_group = Session::get('group');
    	$uoms = gambaUOMs::uom_list();
    	$vendors = gambaVendors::vendor_list();
    	$current_term = gambaTerm::year_by_status ( 'C' );
    	return view('app.parts.parteditform', [
    	    'array' => $array,
    	    'part_num' => $part,
    	    'user_group' => $user_group,
    	    'uoms' => $uoms,
    	    'vendors' => $vendors,
    	    'terms' => $terms,
    	    'part_data' => $part_data,
    	    'supply_requests' => $supply_requests,
    	    'current_term' => $current_term,
    	    'return' => $return,
            'page_title' => "Add Part"
    	]);
    }

    public function formPartEdit(Request $array, $part) {
        if($array['part']) { $part = $array['part']; }
        $return = json_decode(base64_decode($array['r']), true);
        $user_group = Session::get('group');
        $uoms = gambaUOMs::uom_list();
        $vendors = gambaVendors::vendor_list();
        $terms = gambaTerm::terms();
        $supply_requests = gambaParts::supply_requests ( $part );
        $current_term = gambaTerm::year_by_status ( 'C' );
        $part_data = AllParts::select('number', 'description', 'suom', 'cost', 'pq', 'url', 'purl', 'approved',
                'inventory', 'cwnotes', 'adminnotes', 'fishbowl', 'vendor', 'created', 'updated', 'fbuom', 'fbcost',
                'conversion', 'xmlstring', 'part_options', 'concept');
            $part_data = $part_data->where('number', $part);
            $part_data = $part_data->first();
        $part_classes = gambaSupplies::material_classifications();
        return view('app.parts.parteditform', [
            'array' => $array,
            'part_num' => $part,
            'user_group' => $user_group,
            'uoms' => $uoms,
            'vendors' => $vendors,
            'terms' => $terms,
            'part_data' => $part_data,
            'supply_requests' => $supply_requests,
            'current_term' => $current_term,
            'return' => $return,
            'page_title' => "Edit Part {$part} {$part_data['description']}",
            'action' => "part_edit",
            'part_classes' => $part_classes
        ]);
    }

    public function formPartNumberEdit(Request $array, $part) {
        $part_data = AllParts::select('number', 'description', 'suom', 'cost', 'pq', 'url', 'purl', 'approved',
            'inventory', 'cwnotes', 'adminnotes', 'fishbowl', 'vendor', 'created', 'updated', 'fbuom', 'fbcost',
            'conversion', 'xmlstring', 'part_options', 'concept');
        $part_data = $part_data->where('number', $part);
        $part_data = $part_data->first();
        return view('app.parts.parteditnumberform', [
            'part_data' => $part_data,
            'page_title' => "Edit Part Number for {$part} {$part_data['description']}",
            'part_num' => $part
        ]);
    }

    public function processPartNumber(Request $array) {
        $errors = array();
        $data = array();
        $user_name = Session::get('name');
        if(empty($array['part_num'])) {
            $errors['part_num'] = "A Part Number is Required.";
        }

        if($array['part_num'] == $array['orig_part_num']) {
            $errors['part_num'] = "The Part Number has not changed.";
        } else {
            $part_exists = Parts::where('number', $array['part_num'])->first();
            if($part_exists) {
                $errors['part_exists'] = "The Part Number {$array['part_num']} Already Exists.";
            } else {
                $part_info = Parts::select('change_log')->where('number', $array['orig_part_num'])->first();
                $change_log .= $data['log'][0] = "Parts Database: Part Number {$array['orig_part_num']} changed to {$array['part_num']}. ({$user_name} - ". date("Y-m-d H:i:s") . ")\n";
                // Update Supplies Database
                $count = Supplies::where('part', $array['orig_part_num'])->get()->count();
                $supply = Supplies::where('part', $array['orig_part_num'])->update(['part' => $array['part_num']]);
                $change_log .= $data['log'][1] = "Supplies Database: Part Number {$array['orig_part_num']} changed to {$array['part_num']} in {$count} rows. ({$user_name} - ". date("Y-m-d H:i:s") . ")\n";
                // Update Inventory Database
                $count = Inventory::where('number', $array['orig_part_num'])->get()->count();
                $inventory = Inventory::where('number', $array['orig_part_num'])->update(['number' => $array['part_num']]);
                $change_log .= $data['log'][2] = "Inventory Database: Part Number {$array['orig_part_num']} changed to {$array['part_num']} in {$count} rows. ({$user_name} - ". date("Y-m-d H:i:s") . ")\n";                $i++;
                // Update Packing Totals Database
                $count = PackingTotals::where('part', $array['orig_part_num'])->get()->count();
                $packing = PackingTotals::where('part', $array['orig_part_num'])->update(['part' => $array['part_num']]);
                $change_log .= $data['log'][3] = "Packing Totals Database: Part Number {$array['orig_part_num']} changed to {$array['part_num']} in {$count} rows. ({$user_name} - ". date("Y-m-d H:i:s") . ")\n";                $i++;
                // Update Products Database
                $count = Products::where('Num', $array['orig_part_num'])->get()->count();
                $product = Products::where('Num', $array['orig_part_num'])->update(['Num' => $array['part_num'], 'PartID' => $array['part_num']]);
                $change_log .= $data['log'][4] = "Product Database: Part Number {$array['orig_part_num']} changed to {$array['part_num']} in {$count} rows. ({$user_name} - ". date("Y-m-d H:i:s") . ")\n";                $i++;
                // Update Purchase Order Items Database
                $count = PurchaseOrderItem::where('number', $array['orig_part_num'])->get()->count();
                $purchase = PurchaseOrderItem::where('number', $array['orig_part_num'])->update(['number' => $array['part_num']]);
                $change_log .= $data['log'][5] = "Purchase Order Items Database: Part Number {$array['orig_part_num']} changed to {$array['part_num']} in {$count} rows. ({$user_name} - ". date("Y-m-d H:i:s") . ")\n";                $i++;
                // Update Vendor Parts Database
                $count = VendorParts::where('partNumber', $array['orig_part_num'])->get()->count();
                $vendor = VendorParts::where('partNumber', $array['orig_part_num'])->update(['partNumber' => $array['part_num']]);
                $change_log .= $data['log'][6] = "Vendor Parts Database: Part Number {$array['orig_part_num']} changed to {$array['part_num']} in {$count} rows. ({$user_name} - ". date("Y-m-d H:i:s") . ")\n";
                // Update Parts Database
                $change_log .= $part_info['change_log'];
                $parts = Parts::where('number', $array['orig_part_num'])->update(['number' => $array['part_num'], 'change_log' => $change_log]);
                $data['part_num'] = $array['part_num'];
            }
        }

        // Response
        if(!empty($errors)) {
            $data['success'] = false;
            $data['errors'] = $errors;
        } else {
            $data['success'] = true;
            $data['message'] = 'Success!';
        }
//         echo "<pre>"; print_r($data); echo "</pre>"; exit; die();
        echo json_encode($data);
    }


    public function partsLog(Request $array) {
    	$content = gambaParts::view_partslogfile();
    	return view('app.parts.home', ['content' => $content]);
    }

    public function productsLog(Request $array) {
    	$content = gambaParts::view_productslogfile();
    	return view('app.parts.home', ['content' => $content]);
    }


    public function updatePart(Request $array) {
    	$result = gambaParts::part_update($array);
    	return redirect("parts/part_edit/{$result['number']}?update=1&alpha={$result['alpha']}&gamba_part={$result['gamba_part']}&view={$array['view']}");
    }
    public function backTo(Request $array) {
        if (preg_match ( '/GMB/i', $array['number'] )) {
            $return ['gamba_part'] = $gamba_part = 1;
        } else {
            $return ['gamba_part'] = $gamba_part = "false";
        }
        // Approved
        if($array['approved'] == 0 && $array['fishbowl'] == "true" && $array['inventory'] == "true" && $array['concept'] == 0) {
            $view = "approved";
        }
        // All
        elseif($array['approved'] == 0 && ($array['fishbowl'] == "false" || $array['fishbowl'] == "") && $array['inventory'] == "true" && $array['concept'] == 0) {
            $view = "all";
        }
        // Retired
        elseif($array['inventory'] == "false") {
            $view = "retired";
        }
        // Awaiting Approval
        elseif($array['inventory'] == "true" && $array['approved'] == 1 && ($array['fishbowl'] == "false" || $array['fishbowl'] == "") && $array['concept'] == 0) {
            $view = "awaiting";
        }
        // Concept Items
        elseif($array['inventory'] == "true" && $array['approved'] == 1 && $array['fishbowl'] == "false" && $array['concept'] == 1) {
            $view = "concept";
        }
        // Gamba Parts
        elseif($array['inventory'] == "true" && $array['approved'] == 1 && $array['fishbowl'] == "false" && $array['concept'] == 0 && $return['gamba_part'] == 1) {
            $view = "gamba";
        }
        else {
            $view = "all";
        }
        if($array['page']) { $page = $array['page']; } else { $page = $array['alpha']; }
        return redirect("parts/{$view}/{$page}");
    }

    public function addPart(Request $array) {
    	$result = gambaParts::part_add($array);
    	if($result['part_exists'] == "true") {
    		return redirect ("parts/add_part?view=approved&alpha={$result['alpha']}&r={$result['return']}");
    		exit;
    	}
		return redirect("parts/approved/{$result['alpha']}?r={$result['return']}" );
    }

    public function deletePart(Request $array) {
    	$result = gambaParts::part_delete($array);
		return redirect("parts/all");
    }

    public function viewPartCosts(Request $array, $page) {
        if($page == "") { $page = "a"; }
        $parts = ViewPartCosts::where('part_desc', 'LIKE', "$page%")->get();
        $partslistnav = gambaParts::alphalistnav ("/parts/fbcosts", "fbcosts", $page);
        return view('app.parts.fbcosts', [
            'parts' => $parts,
            'partslistnav' => $partslistnav
        ]);
    }
    public function viewJoinPartCosts(Request $array) {
        $job = (new JoinPartCosts())->onQueue('sync');
        $this->dispatch($job);
        return view('app.parts.fbcostsupdated');
    }


}
