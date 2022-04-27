<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Gamba\gambaPacking;

class SettingsPackingController extends Controller
{
    public function index(Request $array)
    {
        $content = gambaPacking::view_packing_lists();
    	return view('app.settings.home', ['content' => $content]);
    }
    
    public function formPackingList(Request $array)
    {
        $content = gambaPacking::form_data_all_packing_list($array, $array['r']);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function storePackingList(Request $array)
    {
    	$url = url('/');
        $result = gambaPacking::packing_add($array);
		return redirect("{$url}/settings/packing_lists?r=$result");
    }

    public function updatePackingList(Request $array)
    {
    	$url = url('/');
        $result = gambaPacking::packing_update($array);
		return redirect("{$url}/settings/packing_lists?r=$result");
    }
}
