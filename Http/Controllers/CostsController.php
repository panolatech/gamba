<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaCosts;
use App\Gamba\gambaCostsView;
use App\Gamba\gambaQuantityTypes;
use App\Gamba\gambaTerm;

use App\Models\Camps;
use App\Models\QuantityTypes;

use App\Jobs\CostsCalculate;

class CostsController extends Controller
{

    public function summaries(Request $array)
    {
    	$content = gambaCostsView::report_all($array);
    	return view('app.costs.home', ['content' => $content]);
    }

//     public function calculationSetup(Request $array)
//     {
//     	$content = gambaCosts::calculation_setup($array);
//     	return view('app.costs.settings', ['content' => $content]);
//     }
//     public function updateCalculation(Request $array)
//     {
//     	$url = url('/');
//     	$result = gambaCosts::calculation_update($_POST);
// 		return redirect("{$url}/costs/calculation_setup?camp={$array['camp']}&update=1");
//     }

    public function calculate(Request $array)
    {
    	$url = url('/');
    	//exec(php_path . " " . Site_path . "execute_php costs_calculate > /dev/null &");
    	$job = (new CostsCalculate($array['term']))->onQueue('calculate');
    	$this->dispatch($job);
		return redirect("{$url}/costs/calculate_material_costs?term={$array['term']}&view_logs=1");
    }


    public function quantity_type_setup(Request $array)
    {
    	$content = gambaCostsView::view_quantity_type_setup($array);
    	return view('app.costs.settings', ['content' => $content]);
    }

    public function quantity_types_update(Request $array)
    {
    	$url = url('/');
    	gambaCosts::quantity_types_update($array);
		return redirect("{$url}/costs/quantity_type_setup?camp={$array['camp']}&term={$array['term']}&update=1");
    }

    public function copy_previous_quantity_types(Request $array)
    {

    	$url = url('/');
    	gambaCosts::copy_previous_quantity_types($array);
		return redirect("{$url}/costs/quantity_type_setup?camp={$array['camp']}&term={$array['term']}&copied=1");
    }

    public function themes_setup(Request $array)
    {
        $group = Session::get('group'); $url = url('/');
        if($group > 1) { return redirect("{$url}/costs"); }
    	$content = gambaCostsView::view_themes_setup($array);
    	return view('app.costs.settings', ['content' => $content]);
    }

    public function themes_update(Request $array)
    {
    	$url = url('/');
    	gambaCosts::themes_update($array);
		return redirect("{$url}/costs/themes_setup?camp={$array['camp']}&term={$array['term']}&success=1");
    }

    public function camp_list(Request $array)
    {
        $group = Session::get('group'); $url = url('/');
        if($group > 1) { return redirect("{$url}/costs"); }
    	$content = gambaCostsView::camp_list($array);
    	return view('app.costs.settings', ['content' => $content]);
    }

    public function update_camps(Request $array)
    {
    	$url = url('/');
    	gambaCosts::camps_update($array);
		return redirect("{$url}/costs/camp_list?update=1");
    }

    public function summaries_campg(Request $array)
    {
    	$content = gambaCostsView::report_campg_by_grade($array);
    	return view('app.costs.home', ['content' => $content]);
    }

    public function summaries_camps(Request $array)
    {
    	$content = gambaCostsView::report_campg($array);
    	return view('app.costs.home', ['content' => $content]);
    }

    public function summaries_gsq(Request $array)
    {
    	$content = gambaCostsView::report_gsq($array);
    	return view('app.costs.home', ['content' => $content]);
    }

    public function summaries_noncurriculum(Request $array)
    {
    	$content = gambaCostsView::report_noncurriculum($array);
    	return view('app.costs.home', ['content' => $content]);
    }

    public function activities(Request $array)
    {
    	$content = gambaCostsView::activities($array);
    	return view('app.costs.home', ['content' => $content]);
    }

    public function activities_gsq(Request $array)
    {
    	$content = gambaCostsView::activities_gsq($array);
    	return view('app.costs.home', ['content' => $content]);
    }

    public function activities_camps(Request $array)
    {
    	$content = gambaCostsView::activities_camps($array);
    	return view('app.costs.home', ['content' => $content]);
    }

    public function calculate_material_costs(Request $array)
    {
    	$content = gambaCostsView::calculate_material_costs($array);
    	return view('app.costs.settings', ['content' => $content]);
    }



}