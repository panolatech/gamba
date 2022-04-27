<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Gamba\gambaInventory;

use App\Jobs\CalcQuantityShort;

class CalculateController extends Controller
{
    public function cg_all_grades_total() {
    
	}
	
    public function calculate_all() {
    
	}
	
    public function basic_calc() {
    
	}
	
    public function from_cg_enrollment() {
    
	}
	
    public function cg_office_data() {
    
	}
	
    public function gsq_office_data() {
    
	}
	
    public function packing_totals_calc_all() {
    
	}
	
    public function quantity_short() {
    	$url = url('/');
    	//exec(php_path . " " . Site_path . "execute_php quantity_short > /dev/null &");
    	$job = (new CalcQuantityShort())->onQueue('calculate');
    	$this->dispatch($job);
// 		gambaInventory::quantity_short();
    	return redirect("{$url}/settings/csvimport");
	}
	
    public function costs_calculate() {
    
	}
}
