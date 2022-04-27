<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use Illuminate\Support\Facades\Session;

	use App\Http\Requests;
	use App\Http\Controllers\Controller;

	use App\Gamba\gambaTerm;
	use App\Gamba\gambaParts;

	use App\Models\Parts;
	use App\Models\Supplies;
	use App\Models\SupplyLists;

	class UsedMaterialsController extends Controller {

		public function index(Request $array) {
			if($array['term'] == "") {
				$array['term'] = gambaTerm::year_by_status('C');
			}
			$query = Parts::select('supplies.part', 'parts.description')->leftjoin('supplies', 'supplies.part', '=', 'parts.number')->where('supplies.term', $array['term'])->orderBy('parts.description')->groupBy('supplies.part')->get();
			foreach($query as $key => $values) {
				$supplies['parts'][$values['part']]['part'] = $values['part'];
				$supplies['parts'][$values['part']]['description'] = $values['description'];
				$supplies['parts'][$values['part']]['supplies'] = Supplies::where('part', $values['part'])->where('term', $array['term'])->count();
				$supplies['parts'][$values['part']]['total'] = Supplies::where('part', $values['part'])->where('term', $array['term'])->sum('packing_total');
			}
			$content['page_title'] = "Used Materials";
			$content['supplies'] = $supplies;
			$content['term'] = $array['term'];
			$content['terms'] = gambaTerm::terms();
			//echo $content;
			return view('app.supplies.usedmaterials', ['array' => $content]);
		}

		public function supplylists($term, $part_num) {
			$array['supply_requests'] = gambaParts::supply_requests($part_num);
			$array['part_info'] = gambaParts::part_info($part_num);
			$array['term'] = $term;
			return view('app.supplies.usedpartsupplyrequests', ['array' => $array]);
		}

	}