<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Config;

use App\Gamba\gambaTerm;
use App\Gamba\gambaDebug;
use App\Gamba\gambaEnroll;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
    	$array = $request->input();
    	$array['current_term'] = gambaTerm::year_by_status('C');
    	$array['terms'] = gambaTerm::terms();
		if($array['gsq_themes'] == "false") {
			$array['gsq_themes_false'] = gambaDebug::alert_box("Unable to create {$array['term']} GSQ Enrollment Sheet. There are currently no themes for the {$array['term']} Season.", 'warning');
		}
        return view('app.enrollment.home', ['array' => $array]);
    }
    
    public Function sheetLocations(Request $array, $term) {
    	$array['term'] = $term;
    	$url = url('/');
    	if($array['camp'] == 1) {
    		$result = gambaEnroll::campg_sheet_locations($array);
			return redirect("{$url}/enrollment/{$term}/cg_campers?grade=&{$array['grade']}&r={$result}");
    	}
    	if($array['camp'] == 2) {
    		$result = gambaEnroll::gsq_sheet_locations($array);
    		if($result['no_themes'] == 1) {
    			return redirect("{$url}/enrollment?gsq_themes=false&term={$term}");
    		} else {
    			return redirect("{$url}/enrollment/{$term}/gsq_campers");
    		}
    	}
    }


    public Function resetSheetLocations(Request $array) {
		$camp_action = gambaEnroll::reset_enrollment_sheet($array);
		return redirect("{$url}/enrollment/{$array['term']}/$camp_action");
    }
    
}
