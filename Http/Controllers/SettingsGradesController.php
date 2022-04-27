<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Gamba\gambaGrades;

class SettingsGradesController extends Controller
{
    public function index(Request $array)
    {
        $content = gambaGrades::view_grades($array['camp'], $array['r']);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function form(Request $array)
    {
        $content = gambaGrades::form_data_all_grades($array, $array['r']);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function store(Request $array)
    {
    	$url = url('/');
        $result = gambaGrades::data_add_grades($array);
		return redirect("{$url}/settings/grades?camp=".$array['camp']."&r=$result");
    }
    
    public function update(Request $array)
    {
    	$url = url('/');
        $result = gambaGrades::data_update_grades($array);
		return redirect("{$url}/settings/grades?camp=".$array['camp']."&r=$result");
    }
}
