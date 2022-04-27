<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Enrollment;
use App\Models\EnrollmentExt;
use App\Models\EnrollSheets;
use App\Models\OfficeData;
use App\Models\ThemeLink;
use App\Models\Themes;

use App\Gamba\gambaAdmin;
use App\Gamba\gambaDebug;
use App\Gamba\gambaEnroll;
use App\Gamba\gambaTerm;
use App\Gamba\gambaLocations;
use App\Gamba\gambaGrades;
use App\Gamba\gambaThemes;
use App\Gamba\gambaCalc;
use App\Gamba\gambaDirections;

class EnrollmentCampGController extends Controller
{

    /**
     * View Camp Galileo Campers
     *
     * @param unknown $term
     * @param unknown $request
     */
    public function showCGCampers($term, Request $array) {
		if($term == "") { $term = gambaTerm::year_by_status('C'); }
		if($array['grade'] == "") { $array['grade'] = 1; }
		$array['cur_term'] = gambaTerm::year_by_status('C');
		$array['terms'] = gambaTerm::terms();
		$array['theme_enroll_rotations'] = $array['terms'][$term]['campg_enroll_rotations'];
		$array['pack_per_hide'] = $array['terms'][$term]['campg_packper'];
		$array['themes'] = gambaThemes::themes_by_camp(1, $term);
		$array['enrollment'] = gambaEnroll::cg_campers($term, $array['grade']);
		$array['cg_linked_themes'] = gambaEnroll::cg_linked_themes($term);
		$array['sub_nav'] = gambaEnroll::cg_campers_subnav(1, $array['grade'], $term);
		//$array['debug'] = gambaDebug::preformatted_arrays($enrollment, 'enrollment_array', 'Enrollment');
// 		echo "<pre>"; print_r($array['enrollment']); echo "</pre>"; exit; die();
    	return view('app.enrollment.cgcampers', [
    	    'term' => $term,
    	    'array' => $array
    	]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editCGCampers($term, $grade, Request $array) {
		$csv_array = array();
		$csv_file_data_array = array();
		// Get Temporary File Name
		$temp_file = $array->file('csv_file');
		// Open File
		$file_handle = fopen($temp_file, "r");
		// Read File Content
		$file_content = fread($file_handle, filesize($temp_file));
		// Explode File by Row
		$csv_array = explode("\r\n", trim($file_content));
		// Parse CSV by Row to New Array
		foreach($csv_array as $row) {
			$array_row = str_getcsv($row);
			// Exclude First Row
			if(is_numeric($array_row[0])) {
				$csv_file_data_array[$array_row[0]] = $array_row;
			}
		}
		// Close File
		fclose($file_handle);

    	$array['term'] = $term;
    	$array['grade'] = $grade;
		$content = gambaEnroll::cg_campers_view_edit($array, $csv_file_data_array);
    	return view('app.enrollment.cgcampersedit', ['content' => $content]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateCGCampers(Request $array, $term, $grade)
    {
    	$url = url('/');
    	$array['term'] = $term;
    	$array['grade'] = $grade;
        gambaEnroll::cg_campers_update($array);
		return redirect("{$url}/enrollment/$term/cg_campers?grade=$grade&r=$return");
    }

    /**
     * Calculate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function calculateCGCampers(Request $array)
    {
        gambaCalc::calculate_from_cg_enrollment($array['term'], $array['grade_id'], $array['camp']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function duplicateLocation(Request $array)
    {
        $url = url('/');
        $result = gambaEnroll::duplicate_location($array);
        return redirect("{$url}/enrollment/{$array['term']}/cg_campers?grade={$array['grade']}");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function removeDuplicateLocation(Request $array)
    {
        $url = url('/');
        $result = gambaEnroll::remove_duplicate_location($array);
        return redirect("{$url}/enrollment/{$array['term']}/cg_campers?grade={$array['grade']}");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showCSV($term, Request $array)
    {
		$content = gambaEnroll::cg_campers_csv_view($term, $array['grade']);
    	return view('app.enrollment.cgcamperscsv', ['content' => $content]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function downloadCSV($term, $grade)
    {
		gambaEnroll::cg_campers_csv_download($term, $grade);
    }
}
