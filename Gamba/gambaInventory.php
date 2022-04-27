<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\Inventory;
	use App\Models\ViewSupplyParts;

	use App\Gamba\gambaTerm;
	use App\Gamba\gambaMasterSupply;
	use App\Gamba\gambaLogs;
	use App\Gamba\gambaUsers;

	class gambaInventory {

		public static function part_inventory($number) {
			$row = Inventory::select('fb_partid', 'fb_vendorid', 'fb_active', 'availablesale', 'quantityonhand', 'onorder', 'quantityshipped', 'quantityshippeduom', 'quantityshort', 'updated')->where('number', "$number")->first();
			$array['fb_partid'] = $row['fb_partid'];
			$array['fb_vendorid'] = $row['fb_vendorid'];
			$array['fb_active'] = $row['fb_active'];
			$array['availablesale'] = $row['availablesale'];
			$array['quantityonhand'] = $row['quantityonhand'];
			$array['onorder'] = $row['onorder'];
			$array['quantityshipped'] = $row['quantityshipped'];
			$array['quantityshippeduom'] = $row['quantityshippeduom'];
			$array['quantityshort'] = $row['quantityshort'];
			$array['updated'] = $row['updated'];

			return $array;
		}

		/**
		 * Calculate Quantity Short
		 */
		public static function quantity_short($action = '') {
			$term = gambaTerm::year_by_status('C');
			//$supply_parts = gambaMasterSupply::supply_parts($term, '', 0);
			gambaLogs::truncate_log('qtyshort.log');
			gambaLogs::data_log("Start Quantity Short Calc", 'qtyshort.log');
			if($action == 1) { $content = "<p>Start Quantity Short Calc</p>"; }
			$date = date("Y-m-d H:i:s");
// 			$parts = Inventory::select('number', 'quantityshort')->where('quantityshort', '>', 0)->orderBy('quantityshort', 'DESC')->get();
// 			foreach($parts as $values) {
// 				gambaLogs::data_log("Set to Zero: {$values['number']} | {$values['quantityshort']}", 'qtyshort.log');
// 				$update = Inventory::find($values['number']);
// 					$update->quantityshort = 0;
// 					$update->quantityshort_updated = $date;
// 					$update->save();
// 			}
			$update = Inventory::select('number')->update([
					'quantityshort' => '0',
					'quantityshort_updated' => $date
			]);
			if($action == 1) { $content .= "<p>Reset Quantity Shorts to Zero - $date</p>"; }
			$supply_parts = ViewSupplyParts::get();
			foreach($supply_parts as $values) {
				$part = $values['part'];
				$total_amount = $values['converted_total'];
				$quantityonhand = $values['quantityonhand'];
				$onorder = $values['onorder'];
				$quantityshipped = $values['quantityshipped'];
				$quantityshort = $total_amount - ($quantityonhand + $onorder + $quantityshipped);
				if($quantityshort < 0) { $quantityshort = 0; }
				gambaLogs::data_log("$part: $quantityshort = $total_amount - ($quantityonhand + $onorder + $quantityshipped)", 'qtyshort.log');
				if($action == 1) { $content .= "<p>$part: $quantityshort = $total_amount - ($quantityonhand + $onorder + $quantityshipped) - $date</p>"; }
				$date = date("Y-m-d H:i:s");
				$update_part = Inventory::find($part);
					$update_part->quantityshort = $quantityshort;
					$update_part->quantityshort_updated = $date;
					$update_part->save();
			}
			gambaLogs::data_log("End Quantity Short Calc", 'qtyshort.log');
			if($action == 1) { $content .= "<p>End Quantity Short Calc</p>"; }
			if($action == 1) { return $content; }
		}

		public static function quantity_short_by_part($parts) {
			$term = gambaTerm::year_by_status('C');
			$supply_parts = gambaMasterSupply::supply_parts($term, '', 0);
			gambaLogs::truncate_log('qtyshort.log');
			gambaLogs::data_log("Start Quantity Short Calc", 'qtyshort.log');
			foreach($parts as $part) {
				$update = Inventory::find($part)->update(['quantityshort' => 0]);
				$values = $supply_parts['parts'][$part];
				$total_amount = $values['total_amount'];
				$quantityonhand = $values['inventory']['quantityonhand'];
				$onorder = $values['inventory']['onorder'];
				$quantityshipped = $values['inventory']['quantityshipped'];
				$quantityshort = $total_amount - ($quantityonhand + $onorder + $quantityshipped);
				if($quantityshort < 0) { $quantityshort = 0; }
				gambaLogs::data_log("$part: $quantityshort = $total_amount - ($quantityonhand + $onorder + $quantityshipped)", 'qtyshort.log');
				$update = Inventory::find($part)->update(['quantityshort' => $quantityshort]);
			}
			gambaLogs::data_log("End Quantity Short Calc", 'qtyshort.log');
		}

		public static function quantity_short_part($part) {
			// $quantityShort = $grandTotalQuantity - ($quantityOnHand + $onOrder + $quantityShipped);
			$term = gambaTerm::year_by_status('C');
			$supply_parts = gambaMasterSupply::supply_parts($term, '', 0);
			gambaLogs::truncate_log('qtyshort.log');
			gambaLogs::data_log("Start Quantity Short Calc", 'qtyshort.log');
			$update = Inventory::update(['quantityshort' => 0]);
			foreach($supply_parts['parts'] as $part => $values) {
				$total_amount = $values['total_amount'];
				$quantityonhand = $values['inventory']['quantityonhand'];
				$onorder = $values['inventory']['onorder'];
				$quantityshipped = $values['inventory']['quantityshipped'];
				$quantityshort = $total_amount - ($quantityonhand + $onorder + $quantityshipped);
				if($quantityshort < 0) { $quantityshort = 0; }
				gambaLogs::data_log("$part: $quantityshort = $total_amount - ($quantityonhand + $onorder + $quantityshipped)", 'qtyshort.log');
				$update = Inventory::find($part)->update(['quantityshort' => $quantityshort]);
			}
			gambaLogs::data_log("End Quantity Short Calc", 'qtyshort.log');
		}
	}
