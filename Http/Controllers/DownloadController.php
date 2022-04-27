<?php

	namespace App\Http\Controllers;
	
	use Illuminate\Http\Request;
	
	use App\Http\Requests;
	
	use App\Models\Parts;
	use App\Models\Supplies;
	
	use App\Gamba\gambaLogs;
	use App\Gamba\gambaPacking;
	use App\Gamba\gambaParts;
	use App\Gamba\gambaSupplies;
	
	class DownloadController extends Controller {
	
		private function file_headers($array) {
	
			$file = $array['file_name'] . "_".date("YmdHis").".csv";
			header("Content-Type: text/csv; charset=utf-8");
			header("Content-Disposition: attachment;filename=" . urlencode($file));
			header("Content-Description: File Transfer");
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
		}
		
		public function materiallistcsv(Request $array) {
	    	self::file_headers($array);
	    	gambaSupplies::list_download($array);
		}
	
		public function packinglistcsv(Request $array) {
			self::file_headers($array);
			gambaPacking::packing_download($array);
		}
	
		public function usedmaterialscsv($term) {
			//echo "Hello - $term"; exit; die();
			$array['term'] = $term;
			$array['file_name'] = "Used_Materials_{$term}";
			self::file_headers($array);
			$query = Parts::select('supplies.part', 'parts.description')->leftjoin('supplies', 'supplies.part', '=', 'parts.number')->where('supplies.term', $array['term'])->orderBy('parts.description')->groupBy('supplies.part')->get();
			echo "\"Part Number\",";
			echo "\"Part Description\",";
			echo "\"# of Item Selected\",";
			echo "\"Quantity Calculated\"\r";
			foreach($query as $key => $values) {
				$items = Supplies::where('part', $values['part'])->where('term', $array['term'])->count();
				$qty_calc = Supplies::where('part', $values['part'])->where('term', $array['term'])->sum('packing_total');
				echo "\"{$values['part']}\",";
				echo "\"{$values['description']}\",";
				echo "\"{$items}\",";
				echo "\"{$qty_calc}\"";
				echo "\r";
			}
		}

	}
