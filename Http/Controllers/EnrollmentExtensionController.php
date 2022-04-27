<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Gamba\gambaTerm;
use App\Gamba\gambaDebug;
use App\Gamba\gambaEnroll;

class EnrollmentExtensionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showCampGCampers(Request $request) {
		$content = gambaEnroll::cg_ext_view_edit($array['term']);
		return view('app.enrollment.extcgedit', ['content' => $content]);
    }
    
    public Function showGSQCampers(Request $array) {
		$content = gambaEnroll::gsq_ext_view_edit($array['term']);
		return view('app.enrollment.extgsqedit', ['content' => $content]);
    }


    public Function update(Request $array) {
    	$url = url('/');
		$action = gambaEnroll::ext_update($array);
		return redirect("{$url}/enrollment/{$array['term']}/{$action}");		
    }
    
}
