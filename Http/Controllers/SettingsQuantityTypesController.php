<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaCalc;
use App\Gamba\gambaDebug;
use App\Gamba\gambaDirections;
use App\Gamba\gambaQuantityTypes;

use App\Models\QuantityTypes;


class SettingsQuantityTypesController extends Controller
{

	public function index(Request $array)
	{
		$content = gambaQuantityTypes::view_quantity_types($array['camp'], $array['r']);
		return view('app.settings.home', ['content' => $content]);
	}
	public function index_test(Request $array)
	{
		if($array['camp'] == "") { $array['camp'] = 1; }
		$content['camp'] = $array['camp'];
		//$content = gambaQuantityTypes::view_quantity_types($array['camp'], $array['r']);
		$content['quantity_types_used'] = gambaQuantityTypes::quantity_types_used($camp);
		$content['quantity_types'] = gambaQuantityTypes::camp_quantity_types();
		$content['enrollment_data'] = gambaCalc::enrollment_data();
		$content['directions'] = gambaDirections::getDirections('quantity_types_edit');
		$content['camps_nav'] = gambaQuantityTypes::camps_nav($array['camp']);
		if($array['add_error'] == 1) {
			$content['alert'] .= gambaDebug::alert_box('Please check your entry and try again.', 'warning');
		}
		if($array['updated'] == 1) {
			$content['alert'] .= gambaDebug::alert_box($return['name'].' successfully updated.', 'success');
		}
		if($array['add'] > 0) {
			$content['alert'] .= gambaDebug::alert_box($return['name'].' successfully added.', 'success');
		}
		return view('app.settings.quantitytypes', ['array' => $content]);
	}
	
	public function ordering(Request $array) {
// 		echo "hello";
// 		$i = 1;
// 		$order = print_r($array['order'], true);
// 		echo $order;
		foreach($array['order'] as $quantity_type_id) {
			$update = QuantityTypes::find($quantity_type_id);
				$update->ordering = $i;
				$update->save();
			$result .= "$quantity_type_id=$i&";
			$i++;
		}
// 		echo $result;
	}

	public function formQtyType(Request $array)
	{
		$content = gambaQuantityTypes::form_data_all_quanitytypes($array['action'], $array['r'], $array);
		return view('app.settings.home', ['content' => $content]);
	}

	public function storeQtyType(Request $array)
	{
		$url = url('/');
		$result = gambaQuantityTypes::data_add_quantitytype($array);
		return redirect("{$url}/settings/quantity_types?camp=".$array['camp']."&r=$result");
	}

	public function updateQtyType(Request $array)
	{
		$url = url('/');
		$result = gambaQuantityTypes::data_update_quantitytype($array);
		return redirect("{$url}/settings/quantity_types?camp=".$array['camp']."&r=$result");
	}

	public function orderQtyType(Request $array)
	{
		$url = url('/');
		$result = gambaQuantityTypes::data_ordering_quantitytypes($array);
		return redirect("{$url}/settings/quantity_types?camp=".$array['camp']."&r=$result");
	}

}
