<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Session;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaCampCategories;
use App\Gamba\gambaCosts;
use App\Gamba\gambaDebug;
use App\Gamba\gambaDirections;
use App\Gamba\gambaGrades;
use App\Gamba\gambaLocations;

use App\Models\Camps;

class SettingsCampsController extends Controller
{
    public function index(Request $array)
    {
        //$content = gambaCampCategories::view_camps($array);
        $return = json_decode(base64_decode($array['r']), true);
        if($return['row_updated'] != "") {
            $alert = gambaDebug::alert_box($return['name'].' successfully updated.', 'success');
        }
        if($return['add_id'] > 0) {
            $alert = gambaDebug::alert_box('Data successfully added.', 'success');
        }
        $camps = gambaCampCategories::camps_list("all");
        $directions = gambaDirections::getDirections('camps_edit');
        $camps_with_grades = gambaGrades::camps_with_grades();
        $camps_with_locations = gambaLocations::camps_with_locations();
        $summary_report_list = gambaCosts::summary_report_list();
        return view('app.settings.campcategories', [
            'camps' => $camps,
            'directions' => $directions,
            'camps_with_grades' => $camps_with_grades,
            'camps_with_locations' => $camps_with_locations,
            'summary_report_list' => $summary_report_list,
            'return' => $return,
            'alert' => $alert
        ]);
    }

    public function createCamp(Request $array)
    {
        $content = gambaCampCategories::form_data_all_camp($array);
        $action = $array['action'];
        $camps_with_grades = gambaGrades::camps_with_grades();
        $camps_with_locations = gambaLocations::camps_with_locations();
        $summary_report_list = gambaCosts::summary_report_list();
        $camps = gambaCampCategories::camps_list("all");
        if($action == "camp_edit") {
            $row = Camps::select('id', 'abbr', 'name', 'alt_name', 'camp_values', 'data_inputs')->where('id', $array['id'])->first();
        }
    	return view('app.settings.campcategoriesedit', [
    	    'action' => $action,
    	    'camps' => $camps,
    	    'camps_with_grades' => $camps_with_grades,
    	    'camps_with_locations' => $camps_with_locations,
    	    'summary_report_list' => $summary_report_list,
    	    'row' => $row
    	]);
    }

    public function dataUpdateCamp(Request $array)
    {
    	$url = url('/');
		$result = gambaCampCategories::data_camp_update($array);
		return redirect("{$url}/settings/camps?r=$result");
    }

    public function dataAddCamp(Request $array)
    {
    	$url = url('/');
		$result = gambaCampCategories::data_camp_add($array);
		return redirect("{$url}/settings/camps?r=$result");
    }

    public function categoryInputUpdateCamp(Request $array)
    {
		$camp = gambaCampCategories::data_update_camp_category_input($array);
		$url = url('/');
		return redirect("{$url}/settings/camp_category_input?camp=$camp&updated=success");

    }

    public function categoryInputCamp(Request $array)
    {
		$content = gambaCampCategories::view_camp_category_input_edit($array, $return);
    	return view('app.settings.home', ['content' => $content]);
    }
}
