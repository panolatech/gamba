<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	
	use App\Models\PurchaseOrders;
	use App\Models\PurchaseOrderItem;
	
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaFishbowl;
	use App\Gamba\gambaSupplies;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaUsers;
	
	use App\Jobs\ExportPurchaseOrders;
	
	class gambaPurchase {
		
		private static function po_list() {
			$term = gambaTerm::year_by_status('C');
			$query = PurchaseOrders::select('purchaseorders.id', 'purchaseorders.number', 'purchaseorders.vendorid', 'purchaseorders.notes', 'purchaseorders.fulfillmentdate', 'purchaseorders.datecreated', 'locationgroup.LocationGroup', 'vendors.DefPaymentTerms', 'vendors.DefShipTerms', 'vendors.Name')->leftjoin('vendors', 'vendors.VendorID', '=', 'purchaseorders.vendorid')->leftjoin('locationgroup', 'locationgroup.id', '=', 'purchaseorders.locationgroup')->where('purchaseorders.term', $term)->orderBy('purchaseorders.number')->orderBy('purchaseorders.datecreated', 'DESC')->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$pos[$id]['number'] = $row['number'];
					$pos[$id]['vendorid'] = $row['vendorid'];
					$pos[$id]['vendorname'] = $row['Name'];
					$pos[$id]['numitems'] = self::po_num_items($id);
					$pos[$id]['notes'] = $row['notes'];
					$pos[$id]['fulfillmentdate'] = $fulfillmentdate = $row['fulfillmentdate'];
					$pos[$id]['datecreated'] = $datecreated = $row['datecreated'];
					$pos[$id]['formatted_fulfillmentdate'] = date("n/j/Y", strtotime($fulfillmentdate));
					$pos[$id]['formatted_datecreated'] = date("n/j/Y", strtotime($datecreated));
					$pos[$id]['shippingterms'] = $row['DefShipTerms'];
					$pos[$id]['paymentterms'] = $row['DefPaymentTerms'];
					$pos[$id]['locationgroup'] = $row['LocationGroup'];
				}
			} else {
				$pos['msg'] = "The database is currently empty";
			}
			return $pos;
		}
		
		public static function po_list_view($poid) {
			$row = PurchaseOrders::select('purchaseorders.id', 'purchaseorders.number', 'purchaseorders.vendorid', 'purchaseorders.notes', 'purchaseorders.fulfillmentdate', 'purchaseorders.datecreated', 'locationgroup.LocationGroup', 'vendors.DefPaymentTerms', 'vendors.DefShipTerms', 'vendors.Name', 'purchaseorders.xmlstring', 'purchaseorders.fishbowl_response')->leftjoin('vendors', 'vendors.VendorID', '=', 'purchaseorders.vendorid')->leftjoin('locationgroup', 'locationgroup.id', '=', 'purchaseorders.locationgroup')->where('purchaseorders.id', $poid)->first();
			$id = $row['id'];
			$pos['poid'] = $row['id'];
			$pos['number'] = $row['number'];
			$pos['vendorid'] = $row['vendorid'];
			$pos['vendorname'] = $row['Name'];
			$pos['numitems'] = self::po_num_items($id);
			$pos['notes'] = $row['notes'];
			$pos['fulfillmentdate'] = $row['fulfillmentdate'];
			$pos['datecreated'] = $row['datecreated'];
			$pos['shippingterms'] = $row['DefShipTerms'];
			$pos['paymentterms'] = $row['DefPaymentTerms'];
			$pos['locationgroup'] = $row['LocationGroup'];
			$pos['xmlstring'] = json_decode($row['xmlstring'], true);
			$pos['fishbowl_response'] = json_decode($row['fishbowl_response'], true);
			return $pos;
		}
		
		public static function po_list_items($poid) {
			$query = PurchaseOrderItem::select('purchaseorderitem.id', 'purchaseorderitem.number', 'parts.description', 'partuoms.name', 'purchaseorderitem.qty', 'purchaseorderitem.notes', 'parts.fbcost', 'parts.fbuom', 'vendorparts.vendor', 'vendorparts.vendorPartNumber', 'purchaseorderitem.term', 'parts.adminnotes')->leftjoin('parts', 'parts.number', '=', 'purchaseorderitem.number')->leftjoin('partuoms', 'partuoms.code', '=', 'parts.fbuom')->leftjoin('vendorparts', 'vendorparts.partNumber', '=', 'purchaseorderitem.number')->where('purchaseorderitem.poid', $poid)->orderBy('parts.description')->get();
			$array['sql'] = \DB::last_query();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
					$array['items'][$id]['number'] = $row['number'];
					$array['items'][$id]['partDesc'] = $row['description'];
					$array['items'][$id]['partCode'] = $row['name'];
					$array['items'][$id]['partUOM'] = $row['fbuom'];
					$array['items'][$id]['qty'] = $row['qty'];
					$array['items'][$id]['notes'] = $row['notes'];
					$array['items'][$id]['adminnotes'] = $row['adminnotes'];
					$array['items'][$id]['cwnotes'] = gambaSupplies::cw_notes($row['number'], $row['term']);
					$array['items'][$id]['standardCost'] = $row['fbcost'];
					$array['items'][$id]['vendor'] = $row['vendor'];
					$array['items'][$id]['vendorPartNumber'] = $row['vendorPartNumber'];
				}
			} else {
				$array['msg'] = "The database is currently empty";
			}
			return $array;
		}
		
		public static function po_item($poitemid) {
			$row = PurchaseOrderItem::select('purchaseorderitem.id', 'purchaseorderitem.number', 'parts.description', 'purchaseorderitem.qty', 'purchaseorderitem.notes', 'parts.fbcost', 'parts.suom')->leftjoin('parts', 'parts.number', '=', 'purchaseorderitem.number')->leftjoin('partuoms', 'partuoms.id', '=', 'parts.fbuom')->where('purchaseorderitem.id', $poitemid)->first();
			$array['number'] = $row['number'];
			$array['partDesc'] = $row['description'];
			$array['partUOM'] = $row['suom'];
			$array['qty'] = $row['qty'];
			$array['notes'] = $row['notes'];
			$array['standardCost'] = $row['fbcost'];
			return $array;
		}
		public static function po_num_items($poid) {
			$numItems = PurchaseOrderItem::select('id')->where('poid', $poid)->get();
			return $numItems->count();
		}
		
		public static function pocreate($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$date = date("Y-m-d H:i:s");
			$vendor = $array['vendor'];
			$term = $array['term'];
			if(is_array($array['supplies'])) {
				$poid = PurchaseOrders::insertGetId(['vendorid' => $vendor, 'fulfillmentdate' => $date, 'datecreated' => $date, 'term' => $term]);
				foreach($array['supplies'] as $part => $values) {
					if($values['include'] == 1) {
						$quantityshort = $values['quantityshort'];
						$insert = new PurchaseOrderItem;
							$insert->poid = $poid;
							$insert->number = $part;
							$insert->qty = $quantityshort;
							$insert->term = $term;
							$insert->save();
					}
				}
			}
			return $poid;
		}
		
		public static function poiteminsert($array) {
			$poid = $array['poid'];
			$number = $array['number'];
			$qty = $array['qty'];
			$notes = htmlspecialchars($array['notes']);
			$insert = new PurchaseOrderItem;
				$insert->poid = $poid;
				$insert->number = $part;
				$insert->qty = $quantityshort;
				$insert->notes = $notes;
				$insert->save();
		}
		
		public static function poitemupdate($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$poid = $array['poid'];
			$item = $array['item'];
			$qty = $array['qty'];
			$notes = htmlspecialchars($array['notes']);
			$update = PurchaseOrderItem::find($item);
				$update->qty = $qty;
				$update->notes = $notes;
				$update->save();
		}
		
		public static function poitemdelete($array) {
			$id = $array['item'];
			$poid = $array['poid'];
			$delete = PurchaseOrderItem::find($id)->delete();
		}
		
		public static function view_po($array, $return) {
			$url = url('/');
			$content_array['page_title'] = "Purchase Order View";
			$content_array['content'] .= gambaDirections::getDirections('poview');
			if($return['statuscode'] != "") {
				if($return['statuscode'] == 1000) { $alert_status = " success"; } else { $alert_status = " alert"; }
				$content_array['content'] .= <<<EOT
				<div data-alert class="alert-box{$alert_status} radius">
					<strong>FB Code {$return['statusCode']}:</strong> {$return['statusmsg']}
					<a href="#" class="close">&times;</a>
				</div>
EOT;
			}
			$pos = self::po_list_view($array['id']);
			if($pos['number'] == "") { 
				$content_array['content'] .= <<<EOT
				<form method="post" action="{$url}/purchase/posave" name="push" class="form form-inline">
EOT;
				$content_array['content'] .= csrf_field();
			}
			$content_array['content'] .= '<p><strong>Vendor:</strong> '.$pos['vendorname']."&nbsp;&nbsp;&nbsp;&nbsp;<strong>Purchase Order Number:</strong> ";
			if($pos['number'] == "") { 
				$content_array['content'] .= "Will be created when submitted to Fishbowl"; 
			} else { 
				$content_array['content'] .= $pos['number']; 
			} 
			$content_array['content'] .= "</p>\n";
			$content_array['content'] .= '<p><strong>Fulfillment Date:</strong> ';
			if($pos['number'] == "") {
				$content_array['content'] .= '<input type="text" name="fulfillmentdate" value="'.date("m/d/Y", strtotime($pos['fulfillmentdate'])).'" style="width: 150px;" class="form-control" />';
			} else {
				$content_array['content'] .= date("m/d/Y", strtotime($pos['fulfillmentdate']));
			}
			$content_array['content'] .= " (MM/DD/YYYY)\n ";
			$content_array['content'] .= '&nbsp;&nbsp;&nbsp;&nbsp;<strong>Created On:</strong> '.date("m/d/Y", strtotime($pos['datecreated'])).'&nbsp;&nbsp;&nbsp;&nbsp;';
			if($pos['number'] == "") {
				$content_array['content'] .= '<input type="submit" name="submit" value="Save" class="button small success" />';
			}
			$content_array['content'] .= "</p>\n";
			if($pos['number'] == "") {
				$content_array['content'] .= '<input type="hidden" name="action" value="posave" />'."\n";
				$content_array['content'] .= '<input type="hidden" name="poid" value="'.$array['id'].'" />'."\n";
				$content_array['content'] .= "</form>\n";
			}
			
			$poItems = self::po_list_items($array['id']);
			$content_array['content'] .= "<p><strong>Number of Items:</strong> ".$pos['numitems']."</p>\n";
			if(is_array($poItems)) {
				$content_array['content'] .= <<<EOT
			<script>
			$(function(){ 
			    $("table").tablesorter({
					widgets: [ 'stickyHeaders' ],
					widgetOptions: { stickyHeaders_offset : 50, },
				}); 
			 }); 
			</script>
				<table class="table table-striped table-bordered table-hover table-condensed table-small">
					<thead>
						<tr>
							<th></th>
							<th>Number</th>
							<th>Description</th>
							<th>Default Vendor</th>
							<th>Vendor Part Number</th>
							<th class="center">Qty</th>
							<th class="center">UOM</th>
							<th class="center">Unit Cost</th>
							<th class="center">Total</th>
							<th>Notes</th>
							<th title="Curriculum Writer Notes">CW Notes</th>
							<th>Purchase Notes</th>
EOT;
				if($pos['number'] == "") {
					$content_array['content'] .= '<th class="center" colspan="2">Options</th>'."\n";
					
				}
				$content_array['content'] .= <<<EOT
						</tr>
					</thead>
					<tbody>
EOT;
				$i=1;
				foreach($poItems['items'] as $key => $value) {
					if($value['qty'] == "" || $value['qty'] == 0) { 
						$quantity = '<span style="color: red;">0</span>'; 
					} else { 
						$quantity = $value['qty']; 
					}
					$cost = "$".number_format($value['standardCost'],2);
					$total = "$".number_format($value['standardCost'] * $value['qty'],2);
					$cw_notes = "";
					if(is_array($value['cwnotes'])) {
						foreach($value['cwnotes'] as $supply_id => $supply_values) {
							$cw_notes .= "<strong>";
							if($supply_values['activity_info']['theme_name']) { $cw_notes .= $supply_values['activity_info']['theme_name'] . " &gt; "; }
							if($supply_values['activity_info']['grade_name']) { $cw_notes .= $supply_values['activity_info']['grade_name'] . " &gt; "; }
							$cw_notes .= $supply_values['activity_info']['name'] . ":</strong> " . $supply_values['notes'] . "\n";
						}
						$cw_notes = nl2br($cw_notes);
					}
					$content_array['content'] .= <<<EOT
						<tr>
							<td>$i</td>
							<td>{$value['number']}</td>
							<td>{$value['partDesc']}</td>
							<td>{$value['vendor']}</td>
							<td>{$value['vendorPartNumber']}</td>
							<td align='center'>{$quantity}</td>
							<td class="center">{$value['partCode']}</td>
							<td class="center">{$cost}</td>
							<td class="center">{$total}</td>
							<td>{$value['notes']}</td>
							<td>{$cw_notes}</td>
							<td>{$value['adminnotes']}</td>
EOT;
					if($pos['number'] == "") {
						$content_array['content'] .= <<<EOT
							<td class="center"><a href="#" class="button small success" data-reveal-id="part{$key}">Edit</a></td>
							<td class="center"><a href="{$url}/purchase/poitemdelete?poid={$array['id']}&item={$key}" class="button small success">Delete</a></td>
EOT;
					}
					$content_array['content'] .= "</tr>\n";
EOT;
					$i++;
					if($quantity == "" || $quantity == 0) { $quantities = 1; }
				}
				$content_array['content'] .= "</tbody>\n";
				$content_array['content'] .= "</table>\n";
				if($pos['number'] == "" && $quantities == 0 && ($pos['vendorid'] > 0 || $pos['vendorid'] != "") && $pos['fulfillmentdate'] != "") {
					$content_array['content'] .= <<<EOT
					<form method="post" action="{$url}/purchase/popushtofb" name="push">
EOT;
				$content_array['content'] .= csrf_field();
				$content_array['content'] .= <<<EOT
					<p><input type="submit" name="submit" value="Push to Fishbowl" class="button small success" /></p>
					<input type="hidden" name="action" value="popushtofb" />
					<input type="hidden" name="poid" value="{$array['id']}" />
				</form>
EOT;
					
				} else {
					$content_array['content'] .= '<p class="error">Make sure that you have entered in all part quantities and entered a fulfillment date.</p>'."\n";
				}
			} else {
				$content_array['content'] .= <<<EOT
				<p><a href="{$url}/purchase/poitemadd?poid=$poid">Add Item</a></p>
EOT;
			}
			$content_array['content'] .= "
		<!-- Start Modal Items -->";
			foreach($poItems['items'] as $key => $value) {
				$content_array['content'] .= <<<EOT
		<div id="part{$key}" class="reveal-modal" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">

				<form method="post" action="{$url}/purchase/poitemupdate" name="item">
EOT;
				$content_array['content'] .= csrf_field();
				$content_array['content'] .= <<<EOT
							<a class="close-reveal-modal" aria-label="Close">&#215;</a>
							<h2 id="modalTitle">Edit {$value['number']} - {$value['partDesc']}</h2>
							
							<p><strong>Part</strong>
							{$value['number']} - {$value['partDesc']}</p>
				
							<div>
								<label>Quantity to Order:</label>
								<input type="text" name="qty" value="{$value['qty']}" class="form-control" />
							</div>

							<div>
								<label>Notes</label>
								<textarea name="notes" class="form-control">{$value['notes']}</textarea>
							</div>


							<p><button type="submit" class="button small success radius">Update Item</button></p>
					<input type="hidden" name="action" value="poitemupdate" />
					<input type="hidden" name="item" value="{$key}" />
					<input type="hidden" name="poid" value="{$array['id']}" />
				</form>
		</div><!-- /.modal -->


EOT;
			}
			$fishbowl_response['fishbowl_response'] = $pos['fishbowl_response'];
			$fishbowl_response['xmlstring'] = $pos['xmlstring'];
			$content_array['content'] .= gambaDebug::preformatted_arrays($fishbowl_response, 'fishbowl_response', 'PO Fishbowl Response');
// 			$content_array['main_nav'] = gambaNavigation::bootstrap_navigation_static();
// 			BootStrapFullScreen::template($content_array);
			return $content_array;
// 			gambaDebug::preformatted_arrays($pos, 'pos', 'PO Info');
// 			gambaDebug::preformatted_arrays($poItems, 'pos_items', 'PO List Items');
		}
		
		public static function posave($array) {
			$poid = $array['poid'];
			$fulfillmentdate = date("Y-m-d H:i:s", strtotime($array['fulfillmentdate']));
			$notes = htmlspecialchars($array['notes']);
			$update = PurchaseOrders::find($poid);
				$update->fulfillmentdate = $fulfillmentdate;
				$update->save();
		}
		
		public static function podelete($array) {
			$info = json_decode(base64_decode($array['info']), true);
			$id = $array['id'];
			$return['number'] = $info['number'];
			$return['vendorname'] = $info['vendorname'];
			$delete = PurchaseOrders::where('id', $id)->delete();
			$delete = PurchaseOrderItem::where('poid', $id)->delete();
			$return['delete'] = 1;
			$return = base64_encode(json_encode($return));
			return $return;
		}
		
		public static function view_pologfile($array) {
			$url = url('/');
			$content_array['page_title'] = "Purchase Order Export List";
			$content_array['content'] .= <<<EOT

			<ul class="pagination">
				<li><a href="{$url}/purchase/pos">PurchaseOrders</a></li>
				<li><a href="{$url}/purchase/mastersupply">Master Supply List</a></li>
				<li class="disabled"><a href="{$url}/purchase/pologfile">Purchase Order Export List</a></li>
			</ul>

			<script type="text/javascript"> 
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/logs/purchaseorders.log?{$date}', function(data) {
							var logfile = data;
							$("#logfile").html("<pre>" + logfile + "</pre>");
							setTimeout(functionToLoadFile, 1000);
						});
					}
					setTimeout(functionToLoadFile, 10);
				});
			</script>
			<div id="logfile"></div>

EOT;
			return $content_array;
// 			$content_array['main_nav'] = gambaNavigation::bootstrap_navigation_static();
// 			BootStrapFullScreen::template($content_array);
		}
		
		public static function view_pos($return) {
			$url = url('/');
			$user_id = Session::get('uid');
			//exec(php_path . " " . Site_path . "execute_export_php export_purchase_orders > /dev/null &");
			$job = (new ExportPurchaseOrders())->onQueue('export');
			dispatch($job);
			$term = gambaTerm::year_by_status('C');
			$pos = self::po_list();
			$content_array['page_title'] = "$term Purchase Orders";
			$content_array['content'] .= gambaDirections::getDirections('pos');
			if($return['delete'] == 1) {
				$content_array['content'] .= <<<EOT
						<div data-alert class="alert-box success radius">
							{$return['number']} - {$return['vendorname']} successfully deleted.
							<a href="#" class="close">&times;</a>
						</div>
EOT;
			}
			
			$content_array['content'] .= <<<EOT
					<ul class="pagination">
			<li class="disabled"><a href="{$url}/purchase/pos">PurchaseOrders</a></li>
			<li><a href="{$url}/purchase/mastersupplylist">Master Supply List</a></li>
EOT;
			if($user_id == 1) {
				$content_array['content'] .= <<<EOT
						<li><a href="{$url}/purchase/pologfile">Purchase Order Export List</a></li>
EOT;
			}
			$content_array['content'] .= '</ul>';
			if(is_array($pos)) {
				$content_array['content'] .= <<<EOT
			<script type="text/javascript">

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
					<th><a href="{$url}/purchase/mastersupplylist" class="button small success">Create PO</a></th>
					<th>Number</th>
					<th>Fishbowl</th>
					<th>Vendor Name</th>
					<th># PO Items</th>
					<th>Date Created</th>
					<th>Fullfillment Date</th>
					<th>Shipping Terms</th>
					<th>Location Group</th>
					<th></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
EOT;
				foreach($pos as $key => $value) {
					if($value['number'] == "") { 
						$fbimg = "<img src=\"{$url}/img/fishbowl_false_icon.png\" width=\"25\" height=\"25\" />";
					} else {
						$edit_disable = " disabled"; 
						$fbimg = "<img src=\"{$url}/img/fishbowl_true_icon.png\" width=\"25\" height=\"25\" />";
					}
					$data['number'] = $value['number'];
					$data['vendorname'] = $value['vendorname'];
					$json_info = base64_encode(json_encode($data));
					$content_array['content'] .= <<<EOT
				<tr>
 					<td><a href="{$url}/purchase/poview?id={$key}" class="button small success">View</a></td>
					<td>{$value['number']}</td>
					<td class="center">{$fbimg}</td>
					<td>{$value['vendorname']}</td>
					<td align="center">{$value['numitems']}</td>
					<td>{$value['formatted_datecreated']}</td>
					<td>{$value['formatted_fulfillmentdate']}</td>
					<td>{$value['shippingterms']}</td>
					<td>{$value['locationgroup']}</td>
					<td align="center"><a href="{$url}/purchase/poview?id={$key}" class="button small success{$edit_disable}">Edit</a></td>
					<td align="center"><a href="{$url}/purchase/podelete?id={$key}&info={$json_info}" class="button small success">Delete</a></td>
				</tr>
EOT;
					
				}
				$content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
				return $content_array;
// 				$content_array['main_nav'] = gambaNavigation::bootstrap_navigation_static();
// 				BootStrapFullScreen::template($content_array);
// 				gambaDebug::preformatted_arrays($pos, 'pos_list', 'Purchase Order List');
			}
		}
		
		public static function view_poitem($array) {
			$url = url('/');
			$poid = $array['poid'];
			if($array['action'] == "poitemadd") {
				$submit = "Add Item";
				$action = "poiteminsert";
				$header = "Add Purchase Order Item";
			}
			if($array['action'] == "poitemedit") {
				$poItem = self::po_item($array['item']);
				$submit = "Update Item";
				$action = "poitemupdate";
				$header = "Edit Purchase Order Item";
			}
			$content_array['page_title'] = "{$header}";
			$content_array['content'] .= <<<EOT
			<form method="post" action="{$url}/purchase/{$action}" name="items">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT
				<div class="row">
					<div class="small-12 medium-2 large-2 columns">
						<label class="">Part</label>
					</div>
					<div class="small-12 medium-2 large-2 columns">{$poItem['number']} - {$poItem['partDesc']}</div>
				</div>
				<p><strong>Quantity to Order:</strong> <input type="text" name="qty" value="{$poItem['qty']}" size="15" /></p>
				<p><strong>Notes:</strong> <textarea name="notes">{$poItem['notes']}</textarea></p>
				<p><input type="submit" name="submit" value="{$submit}" /></p>
				<input type="hidden" name="action" value="{$action}" />
				<input type="hidden" name="item" value="{$array['item']}" />
				<input type="hidden" name="poid" value="{$poid}" />
			</form>
EOT;
			return $content_array;
// 			$content_array['main_nav'] = gambaNavigation::bootstrap_navigation_static();
// 			BootStrapFullScreen::template($content_array);
		}
		
		public static function popushtofb($array) {
			gambaFishbowl::push_purchase_order($array);
		}
	}
