<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaPacking;

class PackingController extends Controller
{
	
    public function index(Request $array) {
    	$content = gambaPacking::view_packing_supply_lists($array);
    	return view('app.packing.home', ['content' => $content]);
    }
	
    public function showPackingSupplies($term, $key, Request $array) {
    	$array['term'] = $term; $array['list'] = $key;
    	$content = gambaPacking::packing_supplies($array);
    	return view('app.packing.home', ['content' => $content]);
    }
    
    public function downloadPackingList(Request $array) {
    	$file = $array['file_name'] . "_".date("YmdHis").".csv";
    	header("Content-Type: text/csv; charset=utf-8");
    	header("Content-Disposition: attachment;filename=" . urlencode($file));
    	header("Content-Description: File Transfer");
    	header("Content-Type: application/force-download");
    	header("Content-Type: application/octet-stream");
    	header("Content-Type: application/download");
    	gambaPacking::packing_download($array);
    }
    
}
