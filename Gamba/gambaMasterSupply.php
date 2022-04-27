<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\Supplies;
	use App\Models\SupplyParts;
	use App\Models\PackingLists;
	use App\Models\PackingTotals;
	use App\Models\PartPackingTotals;
	use App\Models\ViewCurrentCWNotes;
	use App\Models\ViewSupplyParts;

	use App\Gamba\gambaDebug;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaInventory;
	use App\Gamba\gambaPacking;
	use App\Gamba\gambaParts;
	use App\Gamba\gambaSupplies;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaUsers;
	use App\Gamba\gambaVendors;

	class gambaMasterSupply {

		/**
		 * Hide/Show Packing Lists in Master Supply List
		 * @param unknown $array
		 */
		public static function master_supply_update($array) {
			foreach($array['packinglist'] as $key => $value) {
				$packinglists = PackingLists::find($key);
				$packinglists->hide = $value;
				$packinglists->save();
			}
		}

		/**
		 * Part Amounts - Original Total Amount, Packing Subtracted and Total Filtered Amount
		 * @param unknown $term
		 * @param unknown $part
		 * @return number
		 */
		public static function part_amounts($term, $part) {
			$basic_calc_status = config('gamba.basic_calc_status');
			if($basic_calc_status == 1) { $total_amount_field = "packing_total"; } else { $total_amount_field = "total_amount"; }
			// Total Quantity with Excluded
			$part_info = gambaParts::part_info($part);

			$total_amount = Supplies::where('part', '=', "$part")->where('term', '=', "$term")->sum('total_amount');

			if(($part_info['conversion'] > 0 || $part_info['conversion'] != "") && ($part_info['suom'] != $part_info['fbuom'])) {
				$array['total_qty'] = ceil($total_amount / $part_info['conversion']);
			} else {
				$array['total_qty'] = $total_amount;
			}
			$array['total_qty_nonconverted'] = $total_amount;


			// Total Quantity without Excluded
			$total = PackingTotals::where('part', '=', "$part")->where('term', '=', "$term")->sum('total');
			$converted_total = PackingTotals::where('part', '=', "$part")->where('term', '=', "$term")->sum('converted_total');
			if($converted_total > 0) { $row_total = $converted_total; } else { $row_total = $total; }
			$array['total_amount'] = $row_total;
			$array['orig_total_amount'] = $orig_total_amount = $row_total;
			$array['packing_subtracted'] = $packing_subtracted = $row['packing_subtracted'];

			// Converted Total and Filtered Total

			$filtered_total = PackingTotals::where('packingtotals.part', '=', "$part")->where('packingtotals.term', '=', "$term")->where('packinglists.hide', '=', "1")->leftjoin('packinglists', 'packinglists.id', '=', 'packingtotals.packing_id')->sum('packingtotals.total');
			$filtered_converted_total = PackingTotals::where('packingtotals.part', '=', "$part")->where('packingtotals.term', '=', "$term")->where('packinglists.hide', '=', "1")->leftjoin('packinglists', 'packinglists.id', '=', 'packingtotals.packing_id')->sum('packingtotals.converted_total');


			if($filtered_converted_total > 0) { $total_filtered_amount = $filtered_converted_total; } else { $total_filtered_amount = $filtered_total; }
// 			$total_filtered_amount = $row_total;
			if($total_filtered_amount == "") { $total_filtered_amount = 0; }
			$array['total_filtered_amount'] = $total_filtered_amount;


			return $array;
		}

		public static function master_supply_parts($vendor = NULL, $hide = 1) {
		    $supplyparts = ViewSupplyParts::select('part', 'description', 'term', 'total', 'fbuom', 'suom', 'converted_total', 'filtered_total', 'availablesale', 'quantityonhand', 'onorder', 'quantityshipped', 'quantityshort', 'conversion', 'hide', 'vendorid', 'vendorname', 'adminnotes');
		    if($vendor != "") { $supplyparts = $supplyparts->where('vendorid', '=', $vendor); }
		    $supplyparts = $supplyparts->get();


		    if($supplyparts->count() > 0) {
		        foreach($supplyparts as $key => $values) {
		            $part = $values['part'];
		            $partarray['parts'][$part]['description'] = $values['description'];
		            $partarray['parts'][$part]['total'] = $values['total'];
		            $partarray['parts'][$part]['fbuom'] = $values['fbuom'];
		            $partarray['parts'][$part]['suom'] = $values['suom'];
		            $partarray['parts'][$part]['converted_total'] = $values['converted_total'];
		            $partarray['parts'][$part]['total_filtered_amount'] = $values['filtered_total'];
		            $partarray['parts'][$part]['availablesale'] = $values['availablesale'];
		            $partarray['parts'][$part]['quantityonhand'] = $values['quantityonhand'];
		            $partarray['parts'][$part]['onorder'] = $values['onorder'];
		            $partarray['parts'][$part]['quantityshipped'] = $values['quantityshipped'];
		            $partarray['parts'][$part]['quantityshort'] = $values['quantityshort'];
		            $partarray['parts'][$part]['vendorname'] = $values['vendorname'];
		            $partarray['parts'][$part]['adminnotes'] = $values['adminnotes'];
		            $partarray['parts'][$part]['conversion'] = $values['conversion'];

		            $notes = ViewCurrentCWNotes::where('part', $part)->get();
		            //if($notes->count() > 0) {
		                foreach($notes as $note_key => $note_values) {
		                    $supply_id = $note_values['supply_id'];
		                    $partarray['parts'][$part]['cw_notes'][$supply_id]['abbr'] = $note_values['abbr'];
		                    $partarray['parts'][$part]['cw_notes'][$supply_id]['theme_name'] = $note_values['theme_name'];
		                    $partarray['parts'][$part]['cw_notes'][$supply_id]['activity_name'] = $note_values['activity_name'];
		                    $partarray['parts'][$part]['cw_notes'][$supply_id]['notes'] = $note_values['notes'];
		                    $partarray['parts'][$part]['cw_notes'][$supply_id]['part_class'] = $note_values['part_class'];
		                }
		            //}
		        }

		    }
		    return $partarray;
		}

		/**
		 * Supply Array for Master Supply List
		 * @param unknown $term
		 * @param string $vendor
		 * @param number $hide
		 * @return Ambigous <string, unknown>
		 */
		public static function supply_parts($term, $vendor = "", $hide = 1) {
		    $partarray['start_time'] = date("H:i:s");
			$basic_master_supply_parts = gambaPacking::basic_master_supply_parts($term);
			$basic_calc_status = config('gamba.basic_calc_status');
			if($basic_calc_status == 1) { $total_amount_field = "packing_total"; } else { $total_amount_field = "total_amount"; }
			$packingtotals = PackingTotals::select(
					'packingtotals.part',
					'packingtotals.total',
					'packingtotals.converted_total',
					'parts.conversion',
					'packinglists.hide');
			$packingtotals = $packingtotals->leftjoin('packinglists', 'packinglists.id', '=', 'packingtotals.packing_id');
			$packingtotals = $packingtotals->leftjoin('parts', 'parts.number', '=', 'packingtotals.part');
			$packingtotals = $packingtotals->where('packingtotals.term', '=', $term);
			//$packingtotals = $packingtotals->where('packinglists.hide', '=', 1);
			$packingtotals = $packingtotals->where('packingtotals.total', '>', 0);
			$packingtotals = $packingtotals->where('packingtotals.part', 'NOT LIKE', 'GMB%');
			if($vendor != "") { $packingtotals = $packingtotals->where('parts.vendor', '=', $vendor); }
			$packingtotals = $packingtotals->groupBy('packingtotals.part');
			$packingtotals = $packingtotals->orderBy('parts.number');
			$partarray['count_all'] = $packingtotals->count();
			//$packingtotals = $packingtotals->limit('100');
			$partarray['sql'] = $packingtotals->toSql();
			$packingtotals = $packingtotals->get();

			/*$packingtotals = SupplyParts::select('part', 'description', 'term', 'total', 'converted_total', 'conversion', 'hide', 'vendor');
			if($vendor != "") { $packingtotals = $packingtotals->where('vendor', '=', $vendor); }
			$partarray['count_all'] = $packingtotals->count();
			//$partarray['sql'] = $packingtotals->toSql();
			$packingtotals = $packingtotals->get(); */

			$partarray['count'] = $packingtotals->count();
			if($packingtotals->count() > 0) {
				foreach($packingtotals as $key => $row) {
					$part = $row['part'];
					$array['parts'][$part]['total'] += $row['total'];
					$array['parts'][$part]['converted_total'] += $row['converted_total'];
					if($row['hide'] == 1) {
						$array['parts'][$part]['filtered_total'] += $row['converted_total'];
					}
				}
			}
			if($packingtotals->count() > 0) {
				$i = 1;
				//foreach($array['parts'] as $part => $values) {
				foreach($packingtotals as $key => $row) {
					$part = $row['part'];
					$part_info = gambaParts::part_info($part);
					$part_supply_numbers = self::part_supply_numbers($part, $term, $vendor);
					if($part != "" && $part_info['description'] != "" && $part_supply_numbers['filtered_total'] > 0) {
						$bmsp = $basic_master_supply_parts['parts'][$part];
						$inventory = gambaInventory::part_inventory($part);
						$part_amounts = self::part_amounts($term, $part);
						$partarray['parts'][$part]['description'] = $part_info['description'];
						$partarray['parts'][$part]['suom'] = $part_info['suom'];
						$partarray['parts'][$part]['fbuom'] = $part_info['fbuom'];
						$partarray['parts'][$part]['conversion'] = $part_info['conversion'];
						$partarray['parts'][$part]['vendor'] = $part_info['vendor'];
						$partarray['parts'][$part]['adminnotes'] = $part_info['adminnotes'];
						$cw_notes = gambaSupplies::cw_notes_bypart_filtered($part, $term);
						if(!empty($cw_notes)) {
							$partarray['parts'][$part]['cw_notes'] = $cw_notes;
						}
						$partarray['parts'][$part]['total_qty'] = $part_amounts['total_qty'];
						$partarray['parts'][$part]['total_qty_nonconverted'] = $part_amounts['total_qty_nonconverted'];
						$partarray['parts'][$part]['total'] = $part_supply_numbers['total'];
						$partarray['parts'][$part]['converted_total'] = $part_supply_numbers['converted_total'];
						$partarray['parts'][$part]['orig_total_amount'] = $part_amounts['orig_total_amount'];
						$partarray['parts'][$part]['packing_subtracted'] = $part_amounts['packing_subtracted'];
						$partarray['parts'][$part]['total_filtered_amount'] = $part_supply_numbers['filtered_total'];
						$partarray['parts'][$part]['supplies'] = $part_amounts['supplies'];
						$partarray['parts'][$part]['sql'] = $part_amounts['sql'];
						$partarray['parts'][$part]['sql1'] = $part_amounts['sql1'];
						$partarray['parts'][$part]['sql2'] = $part_amounts['sql2'];
						$partarray['parts'][$part]['sql3'] = $part_amounts['sql3'];
						$bmsp_total = $bmsp['total'];
						if($bmsp_total == 0) { $bmsp_total = 0; }
						$partarray['parts'][$part]['basic_amount'] = $bmsp_total;
						$partarray['parts'][$part]['inventory'] = $inventory;
						$i++;
					}
				}
				$partarray['count_parts'] = $i;
			}
			$partarray['end_time'] = date("H:i:s");
			return $partarray;
		}

		/**
		 * Create a view
		 * @param unknown $part
		 * @param unknown $term
		 * @param unknown $vendor
		 * @return unknown
		 */
		private static function part_supply_numbers($part, $term, $vendor) {
		    /*
SELECT `gmb_packingtotals`.`term`, `gmb_packingtotals`.`part`, `gmb_packingtotals`.`total`, `gmb_packingtotals`.`converted_total`, `gmb_packinglists`.`hide`, `gmb_parts`.`vendor`
FROM `gmb_packingtotals`
LEFT JOIN `gmb_packinglists` ON `gmb_packinglists`.`id` = `gmb_packingtotals`.`packing_id`
LEFT JOIN `gmb_parts` ON `gmb_parts`.`number` = `gmb_packingtotals`.`part`
LEFT JOIN `CURRENTSEASON` ON `gmb_packingtotals`.`term` = `CURRENTSEASON`.`YEAR`
WHERE `gmb_packingtotals`.`term` = `CURRENTSEASON`.`YEAR` AND `gmb_packingtotals`.`total` > 0
order by `gmb_parts`.`number`
		     */
			$packingtotals = PackingTotals::select('packingtotals.total', 'packingtotals.converted_total', 'packinglists.hide');
			$packingtotals = $packingtotals->leftjoin('packinglists', 'packinglists.id', '=', 'packingtotals.packing_id');
			$packingtotals = $packingtotals->leftjoin('parts', 'parts.number', '=', 'packingtotals.part');
			$packingtotals = $packingtotals->where('packingtotals.term', '=', $term);
			$packingtotals = $packingtotals->where('packingtotals.total', '>', 0);
			$packingtotals = $packingtotals->where('packingtotals.part', '=', $part);
			if($vendor != "") { $packingtotals = $packingtotals->where('parts.vendor', '=', $vendor); }
			$packingtotals = $packingtotals->get();
			if($packingtotals->count() > 0) {
				foreach($packingtotals as $key => $row) {
					$array['total'] += $row['total'];
					$array['converted_total'] += $row['converted_total'];
					if($row['hide'] == 1) {
						$array['filtered_total'] += $row['converted_total'];
					}
				}
			}
			return $array;
		}

		/**
		 * Moved to Purchase Controller and mastersupplylist.blade.php
		 * Packing List Modal for Master Supply List
		 * @param unknown $term
		 */
		public static function packing_lists_modal($term) {
			$url = url('/');
			$packing_lists = gambaPacking::packing_lists();
			$content = <<<EOT
		<p><a data-reveal-id="PackingListsModal" href="#" class="button small radius success">Filter Packing Lists</a></p>
			<script>
				 $(document).ready(function(){
				 	$("#update_filter").on("click", function unlock_progress(event){
				 		$.LoadingOverlay("show");
				 	});
				 });
			</script>

		<div id="PackingListsModal" class="reveal-modal" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
			<form method="post" action="{$url}/purchase/master_supply_update" name="packing_lists">
EOT;
			$content .= csrf_field();
			$content .= <<<EOT
				<h2 class="modalTitle">Packing Lists</h2>

				<div class="directions">
					<strong>Directions:</strong> Check off the packing lists you wish to include in the Master Supply List.
				</div>
				<div class="row">
EOT;
			foreach($packing_lists['packinglists'] as $id => $values) {
				if($values['list_values']['active'] == "true") {
					if($values['hide'] == 1) { $packinglist_hidden = " checked"; } else { $packinglist_hidden = ""; }
					$content .= <<<EOT
					<div class="small-12 medium-6 large-6 columns">
						<input type="hidden" name="packinglist[{$id}]" value="0" />
						<input type="checkbox" name="packinglist[{$id}]" value="1"{$packinglist_hidden} /> {$values['list']} [{$id}]
					</div>
EOT;
				}
			}
			$content .= ''."\n";

			$content .= <<<EOT
				</div>
				<input type="submit" class="button large radius success" value="Filter Packing Lists" id="update_filter" />
				<input type="hidden" name="action" value="master_supply_update" />
				<input type="hidden" name="term" value="{$term}" />
			</form>
			<a class="close-reveal-modal" aria-label="Close">&#215;</a>
		</div>
EOT;
			return $content;
		}

		/**
		 * Moved to PurchaseController.php and app/purchase/mastersupplylist.blade.php
		 * Master Supply List
		 * @param unknown $term
		 * @param unknown $vendor
		 */
		public static function view_master_supply_list($term, $vendor) {
			$url = url('/');
			$user_id = Session::get('uid');
			if($term == "") { $term = gambaTerm::year_by_status("C"); }
			$content_array['page_title'] = "Master Supply List $term";
			$vendors = gambaVendors::vendor_parts_term($term);
			$content_array['content'] .= gambaDirections::getDirections('master_supply_list_view');
// 			$basic_master_supply_parts = gambaPacking::basic_master_supply_parts($term);
			$content_array['content'] .= <<<EOT
			<ul class="pagination">
				<li><a href="{$url}/purchase/pos">PurchaseOrders</a></li>
				<li class="disabled"><a href="{$url}/purchase/mastersupplylist">Master Supply List</a></li>
EOT;
			if($user_id == 1) {
				$content_array['content'] .= '<li><a href="'.$url.'/purchase/pologfile">Purchase Order Export List</a></li>';
			}
			$content_array['content'] .= <<<EOT
			</ul>
			<div class="row">
				<div class="small-12 medium-4 large-4 columns">
EOT;
			$content_array['content'] .= self::packing_lists_modal($term);
			$content_array['content'] .= <<<EOT
				</div>
				<div class="small-12 medium-8 large-8 columns">
					<form method="get" action="{$url}/purchase/mastersupplylist" name="vendor" role="form">
						<div class="row">
							<div class="small-4 columns">
								<label for="vendordrop" class="right">Filter by Vendor</label>
							</div>
							<div class="small-8 columns">
								<select name="vendor" id="vendordrop" style="width:200px;">
									<option value="">------------------------------------</option>
EOT;
			foreach($vendors['vendors'] as $id => $values) {
				$content_array['content'] .= '<option value="'.$id.'"';
				if($vendor == $id) { $content_array['content'] .= " selected"; }
				$content_array['content'] .= '>'.$values['name'];
// 				$content_array['content'] .= ' ('.$values['supply_requests'].')';
				$content_array['content'] .= '</option>';
			}
			$content_array['content'] .= <<<EOT
								</select>
								<button type="submit" class="button small radius success">Filter</button>
							</div>
						</div>
						<input type="hidden" name="action" value="mastersupply" />
					</form>
				</div>
			</div>
EOT;
			$supply_parts = self::supply_parts($term, $vendor);
			if($vendor != "") {
				$content_array['content'] .= '<form method="post" action="'.$url.'/purchase/pocreate" name="purchase">'."\n";
				$content_array['content'] .= csrf_field();
			}
			$content_array['content'] .= <<<EOT
		<script type="text/javascript">
			// call the tablesorter plugin
			$(function(){
			    $("table").tablesorter({
					widgets: [ 'stickyHeaders' ],
					widgetOptions: { stickyHeaders_offset : 50, },
				});
			 });
		</script>

		<table class="table table-striped table-bordered table-hover table-condensed table-small tablesorter">
			<thead>
				<tr>
EOT;
			$content_array['content'] .= "<th></th>";
			if($vendor != "") { $content_array['content'] .= '<th><button type="submit" class="button small radius success">Create PO</button></th>'; }
			$content_array['content'] .= <<<EOT
						<th>Part</th>
						<th>Description</th>
						<th>Total Qty<br />w/ Excl.</th>
						<th>UoM</th>
						<th>Total Qty<br />Converted</th>
						<th>Filtered Qty</th>
						<!-- <th>Quantity on Hand</th> -->
						<th>Available Sale</th>
						<th>On Order</th>
						<th>Quantity Shipped</th>
						<th>Quantity Short</th>
						<th>Vendor</th>
					<!--	<th>Status</th>
						<th>POs</th> -->
						<th>Purchaser/Curriculum Notes</th>
					<!--	<th>GAMBA Notes</th> -->
					</tr>
				</thead>
				<tbody>

EOT;
			$i = 1;
			foreach($supply_parts['parts'] as $part => $values) {
				if($values['total_filtered_amount'] == 0) {
					$lowest = ' class="lowest"';
				} else {
					$lowest = "";
				}
				$content_array['content'] .= "<tr{$lowest}>\n";
				$content_array['content'] .= "<td>{$i}</td>\n";

				if($vendor != "") {
					$content_array['content'] .= "<td class=\"center\"><input type=\"checkbox\" name=\"supplies[{$part}][include]\" value=\"1\" /></td>";
				}
				$content_array['content'] .= <<<EOT
				<td>{$part}</td>
				<td>{$values['description']}</td>
				<td class="center">{$values['total']}</td>
EOT;
				if($values['conversion'] > 0) { $uom = $values['fbuom']; } else { $uom = $values['suom']; }
				$content_array['content'] .= "<td>$uom</td>\n";
				$inventory_total = $values['inventory']['availablesale'] + $values['inventory']['onorder'] + $values['inventory']['quantityshipped'];
				if($inventory_total <= $values['converted_total']) {
					$new_total = $values['inventory']['availablesale'] + $values['inventory']['onorder'] + $values['inventory']['quantityshipped'] + $values['inventory']['quantityshort'];
					if($new_total != $values['converted_total']) {
						$total_error = ' style="color:red;"';
					} else {
						$total_error = "";
					}
				} else {
					$total_error = "";
				}
				$content_array['content'] .= '<td class="center"'.$total_error.'>'.$values['converted_total']."</td>\n";
				$content_array['content'] .= '<td class="center">'.$values['total_filtered_amount']."</td>";
				//$content_array['content'] .= "<td class='center'>".$values['inventory']['quantityonhand']."</td>\n";
				$content_array['content'] .= "<td class='center'>".$values['inventory']['availablesale']."</td>\n";
				$content_array['content'] .= "<td class='center'>".$values['inventory']['onorder']."</td>\n";
				$content_array['content'] .= "<td class='center'>".$values['inventory']['quantityshipped'];
				$content_array['content'] .= "<td class='center'>".$values['inventory']['quantityshort'];
				if($vendor != "") { $content_array['content'] .= '<input type="hidden" name="supplies['.$part.'][quantityshort]" value="'.$values['inventory']['quantityshort'].'" />'; }
				$content_array['content'] .= "</td>\n";
				$content_array['content'] .= "<td>".$vendor_parts_term['vendors'][$values['vendor']]['name']."</td>\n";
				$content_array['content'] .= "<!--	<td></td>\n";
				$content_array['content'] .= "<td></td> -->\n";
				if(!empty($values['cw_notes'])) {
					$cw_notes = "";
					if($values['adminnotes']) {
						$cw_notes .= " | ";
					}
					foreach($values['cw_notes'] as $supply_id => $note_values) {
						$cw_notes .= "<strong>".$note_values['activity_info']['abbr']." &gt; ".$note_values['activity_info']['theme_name']." &gt; ".$note_values['activity_info']['name']."</strong>: ".$note_values['notes'] . " | ";
					}
					$cw_notes = rtrim($cw_notes, " | ");
				} else {
					$cw_notes = "";
				}
				if(!empty($values['adminnotes'])) {
					$adminnotes = "<strong>Purchaser:</strong> ". $values['adminnotes'];
				} else {
					$adminnotes = "";
				}
				$content_array['content'] .= <<<EOT
						<td>{$adminnotes}{$cw_notes}</td>
						<!--	<td></td> -->
					</tr>
EOT;
				$i++;
			}
			$content_array['content'] .= <<<EOT
				</tbody>
			</table>
EOT;
			if($vendor != "") {
				$content_array['content'] .= <<<EOT
			<input type="hidden" name="action" value="pocreate" />
			<input type="hidden" name="term" value="{$term}" />
			<input type="hidden" name="vendor" value="{$vendor}" />
		</form>
EOT;
			}
// 			$content_array['content'] .= "<pre>" . print_r($supply_parts, true) . "</pre>";
			return $content_array;
// 			$content_array['main_nav'] = gambaNavigation::bootstrap_navigation_static();
// 			BootStrapFullScreen::template($content_array);
// 			echo "<pre>"; print_r($supply_parts); echo "</pre>";
//			gambaDebug::preformatted_arrays($supply_parts, 'supply_parts', 'Supply Parts By Term');
// 			gambaDebug::preformatted_arrays($vendor_parts_term, 'vendor_parts_term', 'Vendor Parts By Term');
// 			gambaDebug::preformatted_arrays($basic_master_supply_parts, 'basic_master_supply_parts', 'Basic Master Supply Parts');
		}
	}
