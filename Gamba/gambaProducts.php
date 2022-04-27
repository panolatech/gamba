<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	
	use App\Models\Products;
	
	use App\Gamba\gambaUsers;
	
	class gambaProducts {
		
		public static function partToProduct($part_num) {
			$row = Products::select('Num', 'Description', 'Price', 'UOM', 'PartID')->where('PartID', "$part_num")->get();
			if($row->count() > 0) {
				$array['status'] = "true";
				$array['prod_num'] = $row['Num'];
				$array['prod_desc'] = $row['Description'];
				$array['price'] = $row['Price'];
				$array['uom'] = $row['UOM'];
				$array['partid'] = $row['PartID'];
				$array['sql'] = $sql;
			} else {
				$array['status'] = "false";
				$array['msg'] = "There is no product in the database for this part $part_num";
				$array['sql'] = $sql;
			}
			return $array;
		}
		
	}
