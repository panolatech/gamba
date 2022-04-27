<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Gamba\gambaAdmin;
use App\Gamba\gambaPacking;

class SettingsController extends Controller
{
	
    public function index(Request $array)
    {
    	$content = gambaAdmin::dashboard($array['camp'], $array['term']);
    	return view('app.settings.home', ['content' => $content]);
    }
	
    public function showConfig(Request $array)
    {
    	$content = gambaAdmin::form_data_all_config($array['r']);
    	return view('app.settings.home', ['content' => $content]);
    }
	
    public function updateConfig(Request $array)
    {
    	$url = url('/');
    	$result = gambaAdmin::data_update_config($array);
		return redirect("{$url}/settings/config?r=$result");
    }

}