<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Session;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaCalc;
use App\Gamba\gambaInventory;
use App\Gamba\gambaLogs;
use App\Gamba\gambaPacking;

use App\Jobs\CalcBasicSupplies;
use App\Jobs\CalcAllPackingLists;
use App\Jobs\CalcQuantityShort;
use App\Jobs\CalcPackingTotals;

use App\Models\ViewFailedJobs;
use App\Models\ViewJobs;

class SettingsCalculateController extends Controller
{

    public function calculatePacking(Request $array)
    {
		$content = gambaPacking::view_packing_list_calculation($array);
    	return view('app.settings.packing', ['content' => $content]);
    }

    public function calculateAll(Request $array)
    {
    	$url = url('/');
        $camp = $array['camp'];
		$term = $array['term'];
		//exec(php_path . " " . Site_path . "execute_php calculate_all $term $camp > /dev/null &");
		$job = (new CalcAllPackingLists($term, $camp))->onQueue('calculate');
		$this->dispatch($job);
		return redirect("{$url}/settings/packing_calc?camp={$array['camp']}&term={$array['term']}&calculation=1");

    }

    public function jobsFailed() {
    	$jobs_array = ViewFailedJobs::get();
    	return view('app.settings.failedjobs', ['jobs_array' => $jobs_array]);
    }

    public function jobs() {
    	$jobs_array = ViewJobs::get();
    	return view('app.settings.jobs', ['jobs_array' => $jobs_array]);
    }


    public function calculateBasic(Request $array)
    {
        $content = gambaCalc::view_basic_supplies_calculation($array);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function calculateBasicPacking(Request $array)
    {
    	$url = url('/');
        $term = $array['term'];
		//exec(php_path . " " . Site_path . "execute_php basic_calc $term > /dev/null &");
		$job = (new CalcBasicSupplies($term))->onQueue('calculate');
		$this->dispatch($job);
		return redirect("{$url}/settings/basic_calc");
    }

    public function logFiles(Request $array)
    {
    	$content = gambaLogs::admin_log_files($array['execute']);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function calcQtyShort(Request $array)
    {
    	$url = url('/');
        //exec(php_path . " " . Site_path . "execute_php quantity_short > /dev/null &");
        $job = (new CalcQuantityShort())->onQueue('calculate');
        $this->dispatch($job);
		return redirect("{$url}/settings/log_files?execute=quantityshort");
    }

    public function testCalcQtyShort(Request $array)
    {
    	$content = gambaInventory::quantity_short(1);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function calcPackingTotals(Request $array)
    {
    	$url = url('/');
        $term = $_GET['term'];
		//exec(php_path . " " . Site_path . "execute_php packing_totals_calc_all $term > /dev/null &");
		$job = (new CalcPackingTotals($term))->onQueue('calculate');
		$this->dispatch($job);
		return redirect("{$url}/settings/log_files?execute=packingtotals");
    }
}
