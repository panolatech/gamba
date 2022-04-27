<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\PartUoMs;

	use App\Gamba\gambaUsers;

	class gambaUOMs {

		public static function uom_list() {
			$query = PartUoMs::select('id', 'name', 'code')->where('active', 'true')->orderBy('name')->get();
			//$array['sql'] = \DB::last_query();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$array['uoms'][$id]['name'] = $row['name'];
					$array['uoms'][$id]['code'] = $row['code'];
					$array['codes'][$row['code']] = $row['name'];
				}
			}
			return $array;
		}

	}