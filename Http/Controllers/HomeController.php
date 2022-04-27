<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

use App\Gamba\gambaDirections;
use App\Gamba\gambaParts;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    	$user_group = Session::get('group');
    	if($user_group == 2 || $user_group == 3) {
    		return redirect('/supplies?action=supplyrequests');
    	}
    	if($user_group == 4) {
    		return redirect('/resupply');
    	}
    	$directions_home_parts = gambaDirections::getDirections('home_parts');
    	$newest_materials = gambaParts::parts_list('', '', '', "awaiting", 25);
    	$directions_home_sync = gambaDirections::getDirections('home_sync');
        return view('app.home', [
        	'directions_home_parts' => $directions_home_parts,
        	'newest_materials' => $newest_materials,
        	'directions_home_sync' => $directions_home_sync
        ]);
    }
}
