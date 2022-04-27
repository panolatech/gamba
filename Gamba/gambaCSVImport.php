<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\Inventory;

	use App\Gamba\gambaDirections;
	use App\Gamba\gambaInventory;
	use App\Gamba\gambaLogs;
	use App\Gamba\gambaNavigation;
	use App\Gamba\gambaUsers;

	use App\Jobs\CalcQuantityShort;

	Class gambaCSVImport {

		public static function view_csv_import() {
			$url = url('/');

			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$content_array['page_title'] = "Inventory Numbers - Tab Delimited Import";
			$content_array['content'] .= gambaDirections::getDirections('csvimport');


			$content_array['content'] .= <<<EOT
			<ul class="pagination">
				<li><a href="{$url}/settings/csv_onorder_import">Upload On Order</a></li>
				<li><a href="{$url}/settings/csv_qtyonhanddata_import">Upload Quantity On Hand</a></li>
				<li><a href="{$url}/settings/csv_qtyshippeddata_import">Upload Quantity Shipped</a></li>
				<li><a href="{$url}/settings/quantity_short" id="qty_short">Re-Compute Quantity Short</a></li>
			</ul>
			<script>
				 $(document).ready(function(){
				 	$("#qty_short").on("click", function unlock_progress(event){
				 		$.LoadingOverlay("show");
				 	});
				 });
			</script>
	<div class="row">
EOT;
			$date = date("YmdHis");
			$content_array['content'] .= <<<EOT
		<div class="small-12 medium-6 large-6 columns">

			<h3>Import Log</h3>
			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/logs/csvimport.log?{$date}', function(data) {
							var logfile = nl2br(data);
							//var logfile = data;

							$("#logfile").html("<p><a href='{$url}/logs/csvimport.log' target='csvimport'>View Complete Log</a> | <a href='{$url}/logs/qtyonhand.log' target='qtyonhand'>Qty On Hand (Text Tab Delimited)</a> | <a href='{$url}/logs/qtyshipped.log' target='qtyshipped'>Qty Shipped (Text Tab Delimited)</a> | <a href='{$url}/logs/qtyonorder.log' target='qtyonorder'>Qty On Order (Text Tab Delimited)</a></p><p>" + logfile + "</p>");
							setTimeout(functionToLoadFile, 1000);
						});
					}
					function nl2br (str) {
					  var breakTag = '<br />';
					  return (str + '').replace(/([^>\\r\\n]?)(\\r\\n|\\n\\r|\\r|\\n)/g, '$1'+ breakTag +'$2');
					};
					setTimeout(functionToLoadFile, 10);
				});
			</script>
			<div id="logfile"></div>
		</div>
EOT;
			$content_array['content'] .= <<<EOT
		<div class="small-12 medium-6 large-6 columns">

			<h3>Quantity Short Log</h3>
			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/logs/qtyshort.log?{$date}', function(data) {
							var logfile = nl2br(data);
							//var logfile = data;
							$("#logfile2").html("<p><a href='{$url}/logs/qtyshort.log' target='qtyshort'>View Log</a></p><p>" + logfile + "</p>");
							setTimeout(functionToLoadFile, 1000);
						});
					}
					function nl2br (str) {
					  var breakTag = '<br />';
					  return (str + '').replace(/([^>\\r\\n]?)(\\r\\n|\\n\\r|\\r|\\n)/g, '$1'+ breakTag +'$2');
					};
					setTimeout(functionToLoadFile, 10);
				});
			</script>
			<div id="logfile2"></div>
		</div>
	</div>
EOT;
			return $content_array;
		}

		public static  function view_csv_qtyonhanddata_import($array) {
			$url = url('/');

			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$content_array['page_title'] = "Add Quantity On Hand Data";
			//$content_array['content'] .= gambaDirections::getDirections('csvimport');
			$content_array['content'] .= <<<EOT

			<form method="post" action="{$url}/settings/csv_upload" enctype="multipart/form-data" name="add_theme" class="form">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT

					<label for="csvfile">File input</label>
					<input type="file" name="csvfile" id="csvfile" class="form-upload" required />
					<p class="help-block">File should be of CSV format, tab delimited. Format: number, description, location, quantity, unit of measure.</p>


				<button type="submit" class="button small" id="add_data">Add Data</button>

				<input type="hidden" name="type" value="add_qtyonhanddata" />
			</form>
			<script>
				 $(document).ready(function(){
				 	$("#add_data").on("click", function unlock_progress(event){
				 		$.LoadingOverlay("show");
				 	});
				 });
			</script>
EOT;
			return $content_array;
		}

		public static  function view_csv_qtyshippeddata_import($array) {
			$url = url('/');

			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$content_array['page_title'] = "Add Quantity Shipped Data";
			//$content_array['content'] .= gambaDirections::getDirections('csvimport');
			$content_array['content'] .= <<<EOT

				<form method="post" action="{$url}/settings/csv_upload" enctype="multipart/form-data" name="add_theme" class="form">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT



								<label for="csvfile">File input</label>
								<input type="file" name="csvfile" id="csvfile" class="form-upload" required />
								<p class="help-block">File should be of CSV format, tab delimited. Format: part number, description, quantity, unit of measure.</p>

				<button type="submit" class="button small" id="add_data">Add Data</button>

					<input type="hidden" name="type" value="add_qtyshippeddata" />
				</form>
			<script>
				 $(document).ready(function(){
				 	$("#add_data").on("click", function unlock_progress(event){
				 		$.LoadingOverlay("show");
				 	});
				 });
			</script>
EOT;
			return $content_array;
		}

		public static  function view_csv_onorder_import($array) {
			$url = url('/');

			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$content_array['page_title'] = "Add On Order Data";
			//$content_array['content'] .= gambaDirections::getDirections('csvimport');
			$content_array['content'] .= <<<EOT
			<form method="post" action="{$url}/settings/csv_upload" enctype="multipart/form-data" name="add_theme" class="form">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT


								<label for="csvfile">File input</label>
								<input type="file" name="csvfile" id="csvfile" class="form-upload" required />
								<p class="help-block">File should be of CSV format, tab delimited. Format: part number, description, quantity, unit of measure.</p>


				<button type="submit" class="button small" id="add_data">Add Data</button>

				<input type="hidden" name="type" value="add_onorder" />
			</form>
			<script>
				 $(document).ready(function(){
				 	$("#add_data").on("click", function unlock_progress(event){
				 		$.LoadingOverlay("show");
				 	});
				 });
			</script>

EOT;
			return $content_array;
		}

		public static function upload_file($array, $files) {
// 			echo "<pre>"; print_r($array); echo "</pre>";
// 			echo "<pre>"; print_r($files); echo "</pre>";
// 			exit; die();
			$temp_file = $files['csvfile']['tmp_name'];
			$file_name = $files['csvfile']['name'];
			$file_type = $array['type'];
// 			$cmd = php_path . " " . Site_path . "execute_php csv_process_file $temp_file $file_name $file_type > /dev/null &";
// 			echo "<p>$cmd</p>"; exit; die();
// 			exec($cmd);
			self::process_file($temp_file, $file_name, $file_type);
		}

		public static function process_file($csv_array, $temp_file, $file_name, $file_type) {
			$url = url('/');
			$date = date("Y-m-d H:i:s");
			// Quantity Shipped
			if($file_type == "add_qtyshippeddata") {
				$update = Inventory::select('number')->update(['quantityshipped' => '0', 'quantityshippeduom' => '']);
				gambaLogs::truncate_log('qtyshipped.log', "false");
				foreach($csv_array as $row) {
					gambaLogs::data_log($row, 'qtyshipped.log', "false");
					list($number, $description, $qty, $uom) = explode("\t", $row);

					$qty = str_replace(",", "", trim($qty, '"'));
					$qs_array[$number]['description'] = $description;
					$qs_array[$number]['uom'] = trim(str_replace('"', "", $uom));
					$qs_array[$number]['qty'] += trim(str_replace(',', "", $qty), '"');
				}
				foreach($qs_array as $part => $values) {
					$qty = $values['qty'];
					$uom = $values['uom'];
					if($number != "" && $qty != "") {
						$part = trim($part);
						$number = Inventory::select('number')->where('number', $part)->count();
						if($number > 0) {
							$query = Inventory::where('number', $part)->update(['quantityshipped' => $qty, 'quantityshippeduom' => $uom]);
							$sql = "UPDATE gmb_inventory SET quantityshipped = $qty, quantityshippeduom = '$uom' WHERE number = '$part'";
						} else {
							$query = new Inventory;
								$query->number = $part;
								$query->quantityshipped = $qty;
								$query->quantityshippeduom = $uom;
								$query->save();
							$sql = "INSERT INTO gmb_inventory ('number', 'quantityshipped', 'quantityshippeduom') VALUES ('$part', $qty, '$uom')";
						}
						gambaLogs::data_log($sql, 'csvimport.log');
					}
				}
			}
			// Quantity On Hand
			if($file_type == "add_qtyonhanddata") {
				$update = Inventory::select('number')->update(['quantityonhand' => '0']);
				gambaLogs::truncate_log('qtyonhand.log', "false");
				foreach($csv_array as $row) {
					gambaLogs::data_log($row, 'qtyonhand.log', "false");
					list($number, $description, $location, $qty, $uom) = explode("\t", $row);

					$qty = str_replace(",", "", trim($qty, '"'));
					$qoh_array[$number]['description'] = $description;
					$qoh_array[$number]['uom'] = trim(str_replace('"', "", $uom));
					$qoh_array[$number]['qty'] += str_replace('"', "", $qty);
				}
				foreach($qoh_array as $part => $values) {
					$qty = $values['qty'];
					if($part != "" && $qty != "") {
						$part = trim($part);
						$number = Inventory::select('number')->where('number', $part)->count();
						if($number > 0) {
							$query = Inventory::where('number', $part)->update(['quantityonhand' => $qty]);
							$sql = "UPDATE gmb_inventory SET quantityonhand = $qty WHERE number = '$part'";
						} else {
							$query = new Inventory;
								$query->number = $part;
								$query->quantityonhand = $qty;
								$sql = $query->toSql();
								$query->save();
							$sql = "INSERT INTO gmb_inventory ('number', 'quantityonhand') VALUES ('$part', $qty)";
						}
						gambaLogs::data_log($sql, 'csvimport.log');
					}
				}
			}
			// Quantity On Order
			if($file_type == "add_onorder") {
				$update = Inventory::select('number')->update(['quantityonhand' => '0', 'onorderuom' => '']);
				gambaLogs::truncate_log('qtyonorder.log', "false");
				foreach($csv_array as $row) {
					gambaLogs::data_log($row, 'qtyonorder.log', "false");
					list($number, $description, $qty, $uom) = explode("\t", $row);

					$qty = str_replace(",", "", trim($qty, '"'));
					$oo_array[$number]['description'] = $description;
					$oo_array[$number]['uom'] = trim(str_replace('"', "", $uom));
					$oo_array[$number]['qty'] += str_replace('"', "", $qty);
				}
				foreach($oo_array as $part => $values) {
					$qty = $values['qty'];
					$uom = $values['uom'];
					if($part != "" && $qty != "") {
						$part = trim($part);
						$number = Inventory::select('number')->where('number', $part)->count();
						if($number > 0) {
							$query = Inventory::where('number', $part)->update(['onorder' => $qty, 'onorderuom' => $uom]);
							$sql = "UPDATE gmb_inventory SET onorder = $qty, onorderuom = '$uom' WHERE number = '$part'";
						} else {
							$query = new Inventory;
								$query->number = $part;
								$query->onorder = $qty;
								$query->onorderuom = $uom;
								$query->save();
							$sql = "INSERT INTO gmb_inventory ('number', 'onorder', 'onorderuom') VALUES ('$part', $qty, '$uom')";
						}
						gambaLogs::data_log($sql, 'csvimport.log');
					}
				}
			}

			// Job: Calculate Quantity Short
			$job = (new CalcQuantityShort())->onQueue('calculate');
			dispatch($job);
			gambaLogs::data_log("End CSV Import", 'csvimport.log');
// 			gambaInventory::quantity_short();
		}
	}
