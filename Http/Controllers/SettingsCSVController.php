<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaCSVImport;
use App\Gamba\gambaLogs;

class SettingsCSVController extends Controller
{
    public function csvImport(Request $array)
    {
        $content = gambaCSVImport::view_csv_import($array);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function csvQtyOnHandImport(Request $array)
    {
        $content = gambaCSVImport::view_csv_qtyonhanddata_import($array);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function csvQtyShippedImport(Request $array)
    {
        $content = gambaCSVImport::view_csv_qtyshippeddata_import($array);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function csvOnOrderImport(Request $array)
    {
        $content = gambaCSVImport::view_csv_onorder_import($array);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function csvUpload(Request $array)
    {
    	gambaLogs::truncate_log('csvimport.log');
    	gambaLogs::data_log("Start CSV Import", 'csvimport.log');
		$url = url('/');
		$csv_array = array();
		$csv_file_data_array = array();
		// Type of File: Qty Shipped, Qty On Hand, Qty On Order
		$file_type = $array['type'];
		// File Name
		$file_name = $array->file('csvfile')->getClientOriginalName();
		// Get Temporary File Name
		$temp_file = $array->file('csvfile');
		// Open File
		$file_handle = fopen($temp_file, "r");
		// Read File Content
		$file_content = fread($file_handle, filesize($temp_file));
		// Explode File by Row
		$csv_array = explode("\r\n", trim($file_content));
		// Parse CSV by Row to New Array
		foreach($csv_array as $row) {
			$array_row = explode("\t", $row);
			// Exclude First Row
			if(is_numeric($array_row[0])) {
				$csv_file_data_array[$array_row[0]] = $array_row;
			}
		}
		// Close File
		fclose($file_handle);
		gambaLogs::data_log("$file_name - $temp_file - $file_type", 'csvimport.log');
        //gambaCSVImport::upload_file($array, $csv_file_data_array);
		gambaCSVImport::process_file($csv_array, $temp_file, $file_name, $file_type);
		return redirect("{$url}/settings/csvimport?processed={$file_type}");
    }
}
