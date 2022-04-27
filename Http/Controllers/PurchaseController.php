<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Session;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaDebug;
use App\Gamba\gambaDirections;
use App\Gamba\gambaFishbowl;
use App\Gamba\gambaInventory;
use App\Gamba\gambaMasterSupply;
use App\Gamba\gambaPacking;
use App\Gamba\gambaParts;
use App\Gamba\gambaPurchase;
use App\Gamba\gambaSupplies;
use App\Gamba\gambaTerm;
use App\Gamba\gambaUOMs;
use App\Gamba\gambaUsers;
use App\Gamba\gambaVendors;

class PurchaseController extends Controller
{

    public function index(Request $array) {
    	$content = gambaPurchase::view_pos($array['r']);
    	return view('app.purchase.home', ['content' => $content]);
    }

    public function showPurchaseOrder(Request $array) {
    	$content = gambaPurchase::view_po($array, $array['r']);
    	return view('app.purchase.home', ['content' => $content]);
    }

    public function formPurchaseOrderItem(Request $array) {
    	$content = gambaPurchase::view_poitem($array);
    	return view('app.purchase.home', ['content' => $content]);
    }

    public function showPurchaseOrderLogs(Request $array) {
    	$content = gambaPurchase::view_pologfile($array);
    	return view('app.purchase.home', ['content' => $content]);
    }

    public function showPackingTotalOrphans(Request $array) {
		$term = gambaTerm::year_by_status('C');
    	$content = gambaPacking::packing_totals_orphaned_view($term, $array['packing_id']);
    	return view('app.purchase.home', ['content' => $content]);
    }

    public function showMasterSupply(Request $array) {
        $content = gambaMasterSupply::view_master_supply_list($array['term'], $array['vendor']);
        return view('app.purchase.home', ['content' => $content]);
    }

    public function masterSupplyList(Request $array) {
        $user_id = Session::get('uid');
        if($array['term'] == "") {
            $array['term'] = gambaTerm::year_by_status("C"); // Good
        }
        $vendors = gambaVendors::vendor_parts_term($array['term']); // Come Back To
        $directions =  gambaDirections::getDirections('master_supply_list_view'); // Good
        $packing_lists = gambaPacking::packing_lists();
        $supply_parts = gambaMasterSupply::master_supply_parts($array['vendor']);
        $uoms = gambaUOMs::uom_list();
        $part_classes = gambaSupplies::material_classifications();

        return view('app.purchase.mastersupplylist', [
            'array' => $array,
            'user_id' => $user_id,
            'vendors' => $vendors,
            'directions' => $directions,
            'packing_lists' => $packing_lists,
            'supply_parts' => $supply_parts,
            'uoms' => $uoms,
            'part_classes' => $part_classes
        ]);
    }


    public function updateMasterSupply(Request $array) {
    	$url = url('/');
    	$result = gambaMasterSupply::master_supply_update($array);
    	return redirect("{$url}/purchase/mastersupply");
    }

    public function insertPurchaseOrder(Request $array) {
    	$url = url('/');
    	$poid = gambaPurchase::pocreate($array);
    	return redirect("{$url}/purchase/poview?id=$poid");
    }

    public function deletePurchaseOrder(Request $array) {
    	$url = url('/');
    	$result = gambaPurchase::podelete($array);
    	return redirect("{$url}/purchase/pos?r={$result['return']}");
    }

    public function deletePurchaseOrderItem(Request $array) {
    	$url = url('/');
    	$result = gambaPurchase::poitemdelete($array);
    	return redirect("{$url}/purchase/poview?id={$array['poid']}");
    }

    public function updatePurchaseOrder(Request $array) {
    	$url = url('/');
    	$result = gambaPurchase::posave($array);
    	return redirect("{$url}/purchase/poview?id={$array['poid']}");
    }

    public function insertPurchaseOrderItem(Request $array) {
    	$url = url('/');
    	$result = gambaPurchase::poiteminsert($array);
    	return redirect("{$url}/purchase/poview?id={$array['poid']}");
    }

    public function updatePurchaseOrderItem(Request $array) {
    	$url = url('/');
    	$result = gambaPurchase::poitemupdate($array);
    	return redirect("{$url}/purchase/poview?id={$array['poid']}");
    }

    public function pushPurchaseOrder(Request $array) {
    	$url = url('/');
    	$result = gambaFishbowl::push_purchase_order($array);
    	if($result['connection'] == "fail") {
    		return redirect("{$url}/purchase/poview?id=$poid&fbconnection=fail");
    		exit;
    	} else {
	    	if($result['push_status_code'] == 1000) {
	    		return redirect("{$url}/purchase/pos?push=success");
	    		exit;
	    	} else {
	    		return redirect("{$url}/purchase/poview?id={$array['poid']}&push=error");
	    		exit;
	    	}
    	}
    }

    public function deleteAllOrphans(Request $array) {
    	$url = url('/');
    	$result = gambaPacking::delete_all_orphans($array['term'], $array['packing_id']);
    	return redirect("{$url}/purchase/orphans?packing_id={$array['packing_id']}&status=delete_all&deleted={$result['deleted']}");
    }

    public function deleteOrphans(Request $array) {
    	$url = url('/');
    	$result = gambaPacking::delete_orphan($array['term'], $array['packing_id'], $array['part'], $array['grade'], $array['theme']);
    	return redirect("{$url}/purchase/orphans?packing_id=".$array['packing_id']."&status=delete_one");
    }

}
