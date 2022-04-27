<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaDebug;
use App\Gamba\gambaDirections;
use App\Gamba\gambaTerm;

use App\Models\Seasons;

class SettingsSeasonsController extends Controller
{

	public function index(Request $array)
	{
		$content['directions'] = gambaDirections::getDirections('terms_edit');
		$content['year_status'] = gambaTerm::year_status();

		//$content['terms'] = gambaTerm::view_terms($array['r']);
		$query = Seasons::select('year', 'status', 'json_array')->orderBy('year', 'desc')->get();
		foreach($query as $key => $value) {
			$year = $value['year'];
			$content['seasons'][$year]['year_status'] = $value['status'];
			$json_array = json_decode($value['json_array'], true);
			$content['seasons'][$year]['gsq'] = $json_array['gsq'];
			$content['seasons'][$year]['access'] = $json_array['access'];
// 			$content['seasons'][$year]['campg_themes_linked'] = $json_array['campg_themes_linked'];
			$content['seasons'][$year]['campg_packper'] = $json_array['campg_packper'];
			$content['seasons'][$year]['campg_enroll_rotations'] = $json_array['campg_enroll_rotations'];
		}
		if($return['add_error'] == 1) {
			$content['alert'] .= gambaDebug::alert_box('Please check your entry and try again.', 'warning');
		}
		if($return['row_updated'] == 1) {
			$content['alert'] .= gambaDebug::alert_box('Term successfully updated.', 'success');
		}
		if($return['add_id'] > 0) {
			$content['alert'] .= gambaDebug::alert_box('Term successfully added.', 'success');
		}
		return view('app.settings.seasons', ['array' => $content]);
	}

	public function update(Request $array)
	{
		//echo "<pre>"; print_r($array['update']); echo "</pre>"; exit; die();
		if($array['action'] == "update_years") {
			$url = url('/');
			//$result = gambaTerm::year_update($array);
			foreach($array['update'] as $year => $values) {
				$json_array['gsq'] = $values['gsq'];
				$json_array['access'] = $values['access'];
				$json_array['campg_packper'] = $values['campg_packper'];
				$json_array['campg_enroll_rotations'] = $values['campg_enroll_rotations'];
				$update = Seasons::find($year);
					$update->status = $values['year_status'];
					$update->json_array = json_encode($json_array);
					$update->save();
			}
			if($array['add_year'] != "" || $array['add_gsq'] != "") {
				$json_array['gsq'] = $array['add_gsq'];
				$json_array['access'] = $array['add_access'];
				$json_array['campg_packper'] = $array['add_campg_packper'];
				$json_array['campg_enroll_rotations'] = $array['add_campg_enroll_rotations'];
				$add = Seasons::insertGetId([
						'year' => $array['add_year'],
						'status' => $array['add_year_status'],
						'json_array' => json_encode($json_array)
				]);
			}
			return redirect("{$url}/settings/seasons?return=$result");
		}
	}

	public function delete(Request $array)
	{
		$url = url('/');
		//$result = gambaTerm::year_delete($array);
		return redirect("{$url}/settings/seasons?return=$result");
	}
}
