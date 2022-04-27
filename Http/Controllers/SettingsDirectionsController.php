<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaDirections;

class SettingsDirectionsController extends Controller
{
    public function index()
    {
    	$content = gambaDirections::allDirections();
    	return view('app.settings.home', ['content' => $content]);
    }

    public function updateDirections(Request $array)
    {
    	$url = url('/');
		$result = gambaDirections::directions_update($array);
		return redirect("{$url}/settings/directions?r=$result");
    }

    public function updateDirection(Request $array)
    {
		gambaDirections::direction_update($array);
		return redirect($array['url']);
    }
}
