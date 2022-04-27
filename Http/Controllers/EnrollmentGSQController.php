<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Gamba\gambaTerm;
use App\Gamba\gambaDebug;
use App\Gamba\gambaEnroll;

class EnrollmentGSQController extends Controller
{
    
    public Function showCampers($term) {
		$content = gambaEnroll::gsq_campers_view($term);
		if($term == "") { $term = gambaTerm::year_by_status('C'); }
    	return view('app.enrollment.gsqcampers', ['content' => $content]);
    }

    public Function editCampers(Request $array) {
    	
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
			
		$content = gambaEnroll::gsq_campers_view_edit($array, $csv_file_data_array);
    	return view('app.enrollment.gsqcampersedit', ['content' => $content]);
    }

    public Function updateCampers(Request $array, $term) {
    	$url = url('/');
    	$array['term'] = $term;
		gambaEnroll::gsq_campers_update($array);
		return redirect("{$url}/enrollment/{$array['term']}/gsq_campers?r={$result}#{$array['grade']}");	
    }
    
    public function showCSV(Request $array, $term)
    {
    	$array['term'] = $term;
		$content = gambaEnroll::gsq_campers_csv_view($array['term'], $array['grade'], $array['sheet_id']);
    	return view('app.enrollment.gsqcamperscsv', ['content' => $content]);
    }
    
    public function uploadCSV(Request $array)
    {
		$content = gambaEnroll::gsq_campers_csv_upload($array, $array->file);
    }

    public function downloadCSV(Request $array)
    {
		gambaEnroll::gsq_campers_csv_download($array['term']);
    }
    
    
}
