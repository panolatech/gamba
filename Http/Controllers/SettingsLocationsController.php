<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Gamba\gambaLocations;

class SettingsLocationsController extends Controller
{
    public function index(Request $array)
    {
        $content = gambaLocations::view_locations($array['camp'], $array['r']);
    	return view('app.settings.home', ['content' => $content]);
    }
    
    public function form(Request $array)
    {
        $content = gambaLocations::form_data_all_location($array, $array['r']);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function store(Request $array)
    {
    	$url = url('/');
        $result = gambaLocations::add_location($array);
		return redirect("{$url}/settings/locations?camp=".$array['camp']."&r=$result");
    }

    public function update(Request $array, $id)
    {
    	$url = url('/');
        $result = gambaLocations::update_location($array);
		return redirect("{$url}/settings/locations?camp=".$array['camp']."&r=$result");
    }
}
