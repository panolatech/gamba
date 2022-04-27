<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaCustomers;
use App\Gamba\gambaFBSync;
use App\Gamba\gambaFishbowl;
use App\Gamba\gambaParts;
use App\Gamba\gambaVendors;

class SettingsFishbowlController extends Controller
{
    public function showSync(Request $array)
    {
        // $content = gambaFishbowl::view_fishbowl_control_sync($array);
        $content['view'] = $array['view'];
        $content['alert'] = $array['alert'];
        return view('app.settings.fishbowlsync', ['array' => $content]);
    }
    
    public function outputSyncAll(Request $array)
    {
        gambaFBSync::fishbowl_sync("flush");
    }
    
    public function outputSyncParts(Request $array)
    {
        gambaFBSync::sync_parts("flush");
    }
    
    public function outputSyncUoMs(Request $array)
    {
        gambaFBSync::sync_uoms("flush");
    }
    
    public function outputSyncVendors(Request $array)
    {
        gambaFBSync::sync_vendors("flush");
    }
    
    public function outputSyncCustomers(Request $array)
    {
        gambaFBSync::sync_customers("flush");
    }
    
    public function outputSyncProducts(Request $array)
    {
        gambaFBSync::sync_products("flush");
    }
    
    public function outputSyncVendorParts(Request $array)
    {
        gambaFBSync::sync_vendor_parts("flush");
    }
    
    public function schedule(Request $array)
    {
        // 		dd($array['schedule']);
        $url = url('/');
        gambaFishbowl::data_update_fishbowl_sync_schedule($array);
        return redirect("{$url}/settings/fishbowl");
    }
    
    public function partSearch(Request $array)
    {
        $content = gambaParts::view_fishbowl_part_search($array);
        return view('app.settings.home', ['content' => $content]);
    }
    
    public function customers(Request $array)
    {
        $content = gambaCustomers::view_fishbowl_customers();
        return view('app.settings.home', ['content' => $content]);
    }
    
    public function vendors(Request $array)
    {
        $content = gambaVendors::view_fishbowl_vendors();
        return view('app.settings.home', ['content' => $content]);
    }
}
