<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	
	use App\Models\Inventory;
	use App\Models\Parts;
	use App\Models\PartUoMs;
	use App\Models\VendorParts;
	
	use App\Gamba\FishbowlAPI;
	use App\Gamba\FBErrorCodes;
	use App\Gamba\gambaFishbowl;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaUsers;
	
	class gambaSync {

		// Get UoMs, Standard Costs, Customers and Addresses, Vendors
		public static function get_misc() {
			$fishbowlapi = new FishbowlAPI(FB_SERVER, FB_PORT);
			$fberror = new FBErrorCodes();

			
			// Unit of Measures
			$fishbowlapi->Login(FB_LOGIN, FB_PASS);
			$code = $fberror->checkCode($fishbowlapi->statusCode);
			
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('UoMs Checking Status', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
				// Call to Fishbowl Server
				$fishbowlapi->getUOMs();
				if($fishbowlapi->statusCode == 1000) {
					gambaFishbowl::connection_log('getUOMs Call', "Code: ".$code. " | Message: ".$fishbowlapi->statusMsg);
				}
				// Wade throug to the uoms array
				$uoms = $fishbowlapi->result['FbiMsgsRs']['UOMRs']['UOM'];
				// If the server returns an array
				if(is_array($uoms)) {
					// Set all active status to false in case status has changed in Fishbowl
					
					$update = PartUoMs;
						$update->active = 'false';
						$update->save();
					foreach($uoms as $key => $value) {
						$uomid = $value->UOMID;
						$name = $value->Name;
						$code = $value->Code;
						$active = $value->Active;
						$uomids["$code"] = "$uomid";
						// Add/Update in Database
						$insert = new PartUoMs;
							$insert->id = $uomid;
							$insert->name = $name;
							$insert->code = $code;
							$insert->active = $active;
							$insert->save();
						$log_data = "Code: $code | ID: $uomid | Name: $name | Active: $active";
						gambaFishbowl::data_log('uoms.log', $log_data);
					}
				}
				gambaFishbowl::connection_log('getUOMs Call', "End: Getting UOMs");
			} else {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('UoMs Checking Status Failed', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
			}
			$fishbowlapi->closeConnection();

			// Standard Costs
			$fishbowlapi->Login(FB_LOGIN, FB_PASS);
			$code = $fberror->checkCode($fishbowlapi->statusCode);
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Standard Costs Status', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
				// Call to Fishbowl Server
				$fishbowlapi->exportRq('ExportPartStandardCost');
				if ($fishbowlapi->statusCode == 1000) {
					gambaFishbowl::connection_log('ExportPartStandardCost Call', "Message: ".$fishbowlapi->statusMsg);
				}
				$costs = $fishbowlapi->result['FbiMsgsRs']['ExportRs']['Rows']['Row'];
				// Changed this so that standard costs are added to parts already in system
				gambaFishbowl::standardCost($costs);
			} else {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Standard Costs Status Failed', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
			}
			$fishbowlapi->closeConnection();

			// Customers and Addresses (Camp Locations)
			$fishbowlapi->Login(FB_LOGIN, FB_PASS);
			$code = $fberror->checkCode($fishbowlapi->statusCode);
			
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Customers and Addresses Checking Status', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
				// Call to Fishbowl Server
				$fishbowlapi->getCustomer('List');
				$customers = $fishbowlapi->result['FbiMsgsRs']['CustomerListRs']['Customer'];
				// Call to Fishbowl Server
				$fishbowlapi->exportRq('ExportCustomers');
				$customer_addresses = $fishbowlapi->result['FbiMsgsRs']['ExportRs'];
				// Process and put data in customer and customer address tables
				gambaFishbowl::customers_list_add($customers, $customer_addresses);
				gambaFishbowl::connection_log('ExportCustomers Call', "End: Getting Customers");
			} else {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Customers and Addresses Checking Status Failed', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
			}
			$fishbowlapi->closeConnection();
				
				
			// Vendors
			$fishbowlapi->Login(FB_LOGIN, FB_PASS);
			$code = $fberror->checkCode($fishbowlapi->statusCode);
			
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Vendors Status', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
				// Call to Fishbowl Server
				$fishbowlapi->exportRq('ExportVendors');
				if ($fishbowlapi->statusCode == 1000) {
					if (!empty($fishbowlapi->statusMsg)) {
						gambaFishbowl::connection_log('ExportVendors', "Export: ".$fishbowlapi->statusMsg);
					}
				}
				$vendor_addresses = $fishbowlapi->result['FbiMsgsRs']['ExportRs']['Rows']['Row'];
				// Call to Fishbowl Server
				$fishbowlapi->getVendor('List');
				$vendors = $fishbowlapi->result['FbiMsgsRs']['VendorListRs']['Vendor'];
				if(is_array($vendors)) {
					// Process and put data in vendor and vendor address tables
					gambaFishbowl::vendorslistadd($vendors, $vendor_addresses);
				}
				gambaFishbowl::connection_log('Get Vendors', "End: Getting Vendors");
			} else {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Vendors Status Failed', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
			}
			$fishbowlapi->closeConnection();
				
		}

		// Get Parts - Get Available Sale and Quantity on Hand
		public static function get_parts() {
			$fishbowlapi = new FishbowlAPI(FB_SERVER, FB_PORT);
			$fberror = new FBErrorCodes();
			$fishbowlapi->Login(FB_LOGIN, FB_PASS);
			$code = $fberror->checkCode($fishbowlapi->statusCode);
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Parts Status', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
			
				// Call to Fishbowl Server
				$fishbowlapi->exportRq('ExportPart');
				if ($fishbowlapi->statusCode == 1000) {
					gambaFishbowl::connection_log('ExportPart Call', "Export: ".$fishbowlapi->statusMsg);
				}
				$parts = $fishbowlapi->result['FbiMsgsRs']['ExportRs']['Rows']['Row'];
				$partNumbers = gambaFishbowl::process_parts($parts);
				gambaFishbowl::connection_log('gambaFishbowl::parts', "Processed Parts");
				$parts = $partNumbers['partNumbers'];
			
				// Materials
				// Begin Processing Parts into Inventory Material List.
				if(is_array($parts)) {
					foreach($parts as $key => $value) {
						$partNum = trim($key);
						$cost = $value['cost'];
						$description = htmlspecialchars($value['description']);
						$uom = $value['uom'];
						// Call to Fishbowl Server
						$fishbowlapi->getInvQty($partNum);
						$quantities = $fishbowlapi->result;
						// Quantity on Hand and Available Sale
						// Because of Inventory in more than one location in the location group treat InvQty as an array with multiple values that need to be summed for QtyAvailable and QtyOnHand.
						$availableSale = 0; $quantityOnHand = 0; $quantityCommitted = 0;
						
						// Run through the array from Fishbowl Inventory Quantity and Calculate Available Sale and Quantity on Hand
						if(is_array($quantities['FbiMsgsRs']['InvQtyRs']['InvQty'])) {
			
							if(array_key_exists('0', $quantities['FbiMsgsRs']['InvQtyRs']['InvQty'])) {
			
								foreach($quantities['FbiMsgsRs']['InvQtyRs']['InvQty'] as $key => $value) {
									$availSale = $quantities['FbiMsgsRs']['InvQtyRs']['InvQty'][$key]->QtyAvailable;
									$fbpartid = $quantities['FbiMsgsRs']['InvQtyRs']['InvQty'][$key]->Part->PartID;
									$availableSale = $availableSale + $availSale;
									$qtyOnHand = $quantities['FbiMsgsRs']['InvQtyRs']['InvQty'][$key]->QtyOnHand;
									$quantityOnHand = $quantityOnHand + $qtyOnHand;
									$quantityCommitted = $quantityCommitted + $quantities['FbiMsgsRs']['InvQtyRs']['InvQty'][$key]->QtyCommitted;
								}
							} else {
								$availableSale = $quantities['FbiMsgsRs']['InvQtyRs']['InvQty']['QtyAvailable'];
								$fbpartid = $quantities['FbiMsgsRs']['InvQtyRs']['InvQty']->Part->PartID;
								$fb_active = $quantities['FbiMsgsRs']['InvQtyRs']['InvQty']->Part->ActiveFlag;
								$quantityOnHand = $quantities['FbiMsgsRs']['InvQtyRs']['InvQty']['QtyOnHand'];
								$quantityCommitted = $quantities['FbiMsgsRs']['InvQtyRs']['InvQty']['QtyCommitted'];
							}
			
						}
						$log_data = "Part: $partNum | Available Sale: $availableSale | Quantity On Hand: $quantityOnHand";
						gambaFishbowl::data_log('inventory_quantity.log', $log_data);
						// Description, UoM and Cost set in GAMBA
						// Add/Update Parts Table
						$query = Parts::find($partNum);
						if($query->count() > 0) {
							$update = Parts::find($partNum);
								$update->fbuom = $uom;
								$update->fbcost = $cost;
								$update->save();
						} else {
							$insert = new Parts;
								$insert->number = $partNum;
								$insert->description = $description;
								$insert->suom = $uom;
								$insert->fbuom = $uom;
								$insert->cost = $cost;
								$insert->fbcost = $cost;
								$insert->inventory = 'true';
								$insert->fishbowl = 'true';
								$insert->save();
						}
						// Add/Update Inventory Table for Quantity on Hand and Available Sale
						$query = Inventory::find($partNum);
						if($query->count() > 0) {
							$update = Inventory::find($partNum);
								$update->quantityonhand = $quantityOnHand;
								$update->availablesale = $availableSale;
								$update->updated = date("Y-m-d H:i:s");
								$update->save();
						} else {
							$insert = new Inventory;
								$insert->number = $partNum;
								$insert->fb_partid = $partNum;
								$insert->fb_vendorid = $fbpartid;
								$insert->fb_active = $fb_active;
								$insert->availablesale = $availableSale;
								$insert->quantityonhand = $quantityOnHand;
								$insert->updated = date("Y-m-d H:i:s");
								$insert->save();
						}
						
						$materials['queries']["$partNum"] = "Part: $partNum - $description (".$query.")";
					}
					gambaFishbowl::connection_log('get_inventory_quantities', "Parts Returned to Process");
					gambaFishbowl::connection_log('gambaFishbowl::parts', "End: Processing Parts");
				} else {
					gambaFishbowl::connection_log('get_inventory_quantities', "No Parts Returned to Process");
				}
				gambaFishbowl::connection_log('get_parts', "End: Getting Parts");
			} else {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Parts Status Failed', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
			}

			$fishbowlapi->closeConnection();
		}

		// Get Products
		public static function get_products() {
			$fishbowlapi = new FishbowlAPI(FB_SERVER, FB_PORT);
			$fberror = new FBErrorCodes();
			$fishbowlapi->Login(FB_LOGIN, FB_PASS);
			$code = $fberror->checkCode($fishbowlapi->statusCode);
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Products Status', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
				// Call to Fishbowl Server
				$fishbowlapi->exportRq('ExportProduct');
				if ($fishbowlapi->statusCode == 1000) {
					gambaFishbowl::connection_log('ExportProduct Call', "Message: ".$fishbowlapi->statusMsg);
				}
				$products = $fishbowlapi->result['FbiMsgsRs']['ExportRs']['Rows']['Row'];
				// Process Products from Fishbowl
				$products = gambaFishbowl::process_products($products);
			} else {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Products Status Failed', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
			}

			$fishbowlapi->closeConnection();
		}

		// Get Vendor Part Numbers
		public static function get_vendor_parts() {
			$fishbowlapi = new FishbowlAPI(FB_SERVER, FB_PORT);
			$fberror = new FBErrorCodes();
			$fishbowlapi->Login(FB_LOGIN, FB_PASS);
			$code = $fberror->checkCode($fishbowlapi->statusCode);
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Vendor Part Numbers Status', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
				// Call to Fishbowl Server
				$fishbowlapi->exportRq('ExportPartProductAndVendorPricing');
				if ($fishbowlapi->statusCode == 1000) {
					gambaFishbowl::connection_log('ExportPartProductAndVendorPricing Call', "Message: ".$fishbowlapi->statusMsg);
				}
				$vendor_parts = $fishbowlapi->result['FbiMsgsRs']['ExportRs']['Rows']['Row'];
				foreach($vendor_parts as $key => $value) {
					$string = $value;
					$temp = fopen("php://memory", "rw");
					fwrite($temp, $string);
					fseek($temp, 0);
					$array = fgetcsv($temp);
					$part_num = $array[0]; $part_desc = $array[1]; $partTypeID = $array[5]; $vendor = $array[34]; $defaultVendorFlag = $array[35]; $vendorPartNumber = $array[36];
					if($partTypeID == 10 && $vendorPartNumber != "") {
						$query = VendorParts::find($part_num);
						if($query->count() > 0) {
							$update = VendorParts::find($part_num);
								$update->vendor = $vendor;
								$update->vendorPartNumber = $vendorPartNumber;
								$update->defaultVendorFlag = $defaultVendorFlag;
								$update->save();
						} else {
							$insert = new VendorParts;
								$insert->partNumber = $part_num;
								$insert->vendor = $vendor;
								$insert->vendorPartNumber = $vendorPartNumber;
								$insert->defaultVendorFlag = $defaultVendorFlag;
								$insert->save();
						}
						$log_data = "Part: $part_num | Vendor: $vendor | Vendor Part: $vendorPartNumber";
						gambaFishbowl::data_log('vendorparts.log', $log_data);
					}
				}
			} else {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Vendor Part Numbers Status Failed', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
			}

			$fishbowlapi->closeConnection();
		}

		// Get Purchase Orders - Get Quantity on Order
		public static function get_purchase_orders() {
			$fishbowlapi = new FishbowlAPI(FB_SERVER, FB_PORT);
			$fberror = new FBErrorCodes();
			$fishbowlapi->Login(FB_LOGIN, FB_PASS);
			$code = $fberror->checkCode($fishbowlapi->statusCode);
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Purchase Orders Status', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
				// Call to Fishbowl Server
				$fishbowlapi->exportRq('ExportPurchaseOrder');
				$onorder_parts = $fishbowlapi->result['FbiMsgsRs']['ExportRs']['Rows']['Row'];
					
				$statusArray = array(20 => "Issued", 40 => "Partial");
				$clearOnOrder = Inventory::update(['onorder' => 0]);
				foreach($onorder_parts as $key => $value) {
					$string = $value;
					$temp = fopen("php://memory", "rw");
					fwrite($temp, $string);
					fseek($temp, 0);
					$array = fgetcsv($temp);
					$flag = $array[0];
					if($flag == "PO") {
						$poNum = $array[1];
						$poStatus = $array[2];
						$poVendor = $array[3];
						$log_data = "Part: $poNum | Status: $poStatus | Vendor: $poVendor";
						gambaFishbowl::data_log('onorder.log', $log_data);
					}
					if($flag == "Item" && $array[1] == 10) {
						$itemNum = $array[2];
						$partQuantity = $array[4];
						$fulfilledQuantity = $array[5];
						$pickedQuantity = $array[6];
						$uom = $array[7];
						if($poStatus == 20 || $poStatus == 40) {
							$preTotal = $poArray['Calculation']["$itemNum"]['value'];
							$poArray['Calculation']["$itemNum"]['value'] = $poArray['Calculation']["$itemNum"]['value'] + ($partQuantity - $fulfilledQuantity);
							$postTotal = $poArray['Calculation']["$itemNum"]['value'];
							if($postTotal > 0) {
								$poArray['Calculation']["$itemNum"]['uom'] = $uom;
							}
						}
					}
					sleep(1);
				}
				foreach($poArray['Calculation'] as $key => $value) {
					$date = date("Y-m-d H:i:s");
					$update = Inventory::find($key);
						$update->onorder = $value['value'];
						$update->save();
				}
				gambaFishbowl::connection_log('get_onorder', "End: Getting On Orders");
			} else {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Purchase Orders Status Failed', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
			}

			$fishbowlapi->closeConnection();
		}

		// Get Sales Orders
		/**
		 * Get Sales Orders from Fishbowl and Write current term to logs/salesorders.txt
		 */
		public static function get_sales_orders() {
			$fishbowlapi = new FishbowlAPI(FB_SERVER, FB_PORT);
			$fberror = new FBErrorCodes();
			$fishbowlapi->Login(FB_LOGIN, FB_PASS);
			$code = $fberror->checkCode($fishbowlapi->statusCode);
			$term = gambaTerm::year_by_status('C');
			if($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode($fishbowlapi->statusCode);
				gambaFishbowl::connection_log('Quantity Shipped Status', "Code: ".$code. " | Message: " .$fishbowlapi->statusMsg);
				$year = gambaTerm::year_by_status('C');
				fwrite($open_file, "Start - ".date("His")."\n");
				$fishbowlapi->exportRq('ExportSalesOrder');
				if ($fishbowlapi->statusCode == 1000) {
					gambaFishbowl::connection_log('ExportSalesOrder Call', "Message: ".$fishbowlapi->statusMsg);
				}
				$statusArray = array(10 => "Estimate", 20 => "Issued", 25 => "In Progress", 60 => "Fulfilled", 70 => "Closed Short", 80 => "Void");
				$salesOrders = $fishbowlapi->result['FbiMsgsRs']['ExportRs']['Rows']['Row'];
				$log_path = config('gamba.log_path');
				$open_file = fopen($log_path . "salesorders.txt", "w+");
				fwrite($open_file, "Begin Write - ".date("His")."\n");
				foreach($salesOrders as $key => $value) {
					$string = $value;
					$array = fgetcsv($string);
					$flag = $array[0];
					if($flag == "SO") {
					fwrite($open_file, $string."\n");
// 						$soNum = $array[1];
// 						$soStatus = $array[2];
// 						$FulfillmentDate = date("Y", strtotime($array[30]));
// 						if($soStatus == 60) {
// 							$soArray["$soStatus"]["$FulfillmentDate"]["$soNum"]['Status'] = "$soStatus - " . $statusArray[$soStatus] . " - " . $FulfillmentDate;
// 						}
					}
					if($flag == "Item") {
					fwrite($open_file, $string."\n");
// 						if($soStatus == 60) {
// 							$SOItemTypeID = $array[1]; $ProductNumber = $array[2]; $ProductQuantity = $array[3]; $UOM = $array[4]; $ProductPrice = $array[5];
// 							$soArray["$soStatus"]["$FulfillmentDate"]["$soNum"]['Products']["$ProductNumber"]['SOItemTypeID'] = $SOItemTypeID;
// 							$soArray["$soStatus"]["$FulfillmentDate"]["$soNum"]['Products']["$ProductNumber"]['ProductQuantity'] = $ProductQuantity;
// 							$soArray["$soStatus"]["$FulfillmentDate"]["$soNum"]['Products']["$ProductNumber"]['UOM'] = $UOM;
// 							$soArray["$soStatus"]["$FulfillmentDate"]["$soNum"]['Products']["$ProductNumber"]['ProductPrice'] = $ProductPrice;
// 						}
					}
				}
				fwrite($open_file, "End Write - ".date("His")."\n");
				fclose($open_file);
			}
			$fishbowlapi->closeConnection();
		}
		
		
		public static function view_get_sos() {
			$url = url('/');
			// Located in Routes/logs.php and LogsController@enroll_calc_log
			$content = <<<EOT
			<p><a href="testing.php?action=test_get_sos">Get Sales Orders from Fishbowl</a>
			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('/{$url}/enroll_calc_log?logfile=salesorders.txt&{$date}', function(data) {
							var logfile = data;
							$("#view_sos").html("<p><a href='{$url}/logs/salesorders.txt' target='salesorders'>Sales Orders Write File</a></p><pre>" + logfile + "</pre>");
							setTimeout(functionToLoadFile, 500);
						});
					}
					setTimeout(functionToLoadFile, 10);
				});
			</script>
			<div class="small-12 medium-12 large-12 columns" id="view_sos"></div>
EOT;
			return $content;
		}
	}
