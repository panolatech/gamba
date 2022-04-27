<?php

namespace App\Gamba;

use Illuminate\Support\Facades\Session;
use App\Models\Config;
use App\Models\Customers;
use App\Models\CustomerAddresses;
use App\Models\Inventory;
use App\Models\Parts;
use App\Models\PartUoMs;
use App\Models\Products;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrders;
use App\Models\Reorders;
use App\Models\SalesOrders;
use App\Models\VendorParts;
use App\Models\Vendors;
use App\Models\VendorAddresses;
use App\Gamba\FishbowlAPI;
use App\Gamba\FBErrorCodes;
use App\Gamba\gambaTerm;
use App\Gamba\gambaResupplyOrders;
use App\Gamba\gambaCustomers;
use App\Gamba\gambaNavigation;
use App\Gamba\gambaProducts;
use App\Gamba\gambaSupplies;
use App\Gamba\gambaUsers;

class gambaFishbowl {
	
	// CSV Formats: https://www.fishbowlinventory.com/wiki/CSV_Imports_and_Exports
	public static function push_part($array) {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowl_test = config ( 'fishbowl.fishbowl_test' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$return ['LoginStatusCode'] = $statuscode = $fishbowlapi->statusCode;
		$return ['LoginStatusMsg'] = $fberror->checkCode ( $statuscode );
		$return ['connect_fishbowl'] = "Connect {$statuscode} - {$return['LoginStatusMsg']}";
		if ($fishbowlapi->statusCode != 1000 || $fishbowlapi->statusCode == "") {
			$return ['statusmsg'] = $fberror->checkCode ( $fishbowlapi->statusCode );
			$return ['statuscode'] = $fishbowlapi->statusCode;
			$return = base64_encode ( json_encode ( $return ) );
			return $return;
			exit ();
		} else {
			// echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$partNum = $array ['number_edit'];
			if ($partNum == "") {
				$partNum = $array ['number'];
			}
			$search = array (
					"&quot;",
					'"' 
			);
			$partDescription = stripslashes ( str_replace ( $search, "''", $array ['description'] ) );
			// $partDescription = $array['description'];
			$partUOM = $array ['fbuom'];
			if ($partUOM == "") {
				$partUOM = $array ['suom'];
			}
			$vendorid = $array ['vendor'];
			$cost = $array ['fbcost'];
			if ($cost == "") {
				$cost = "0.00";
			}
			$active = strtoupper ( $array ['fishbowl'] );
			$uom = $array ['fbuom'];
			if ($uom == "") {
				$uom = $partUOM;
			}
			// Updated 2/9/17
			// PartNumber, PartDescription, PartDetails, UOM, UPC, PartType, Active, ABCCode, Weight, WeightUOM, Width, Height, Length, SizeUOM, AlertNote, PictureUrl, PrimaryTracking, Tracks-, CF-
			// New (12/5/14) - Note: 'A' in the ABCCode Position is what has changed.
			// PartNumber, PartDescription, PartDetails, UOM, UPC, PartType, Active, ABCCode, Weight, WeightUOM, Width, Height, Length, SizeUOM, PrimaryTracking, AlertNote, PictureUrl, Tracks-Serial Number, Tracks-Lot Number, Tracks-Expiration Date, CF-Custom
			// $string = '"NC7000","toy, straws and connectors, 705 piece set",,"ea",,"Inventory","true","A","0","lbs","0","0","0","ft",,,,"false","false","false",';
			// Old
			// PartNumber, PartDescription, PartDetails, UOM, UPC, PartType, Active, ABCCode, Weight, WeightUOM, Width, Height, Length, SizeUOM, PrimaryTracking, AlertNote, PictureUrl, Tracks-Serial Number, Tracks-Lot Number, Tracks-Expiration Date, CF-Custom
			// $string = 'NC7000,"toy, straws and connectors, 705 piece set",,ea,,Inventory,TRUE,,0,lbs,0,0,0,ft,,,,FALSE,FALSE,FALSE,';
			if ($fishbowl_test == "test") {
				$partNum = "TST" . $partNum;
				$partDetails = "This part was uploaded for testing purposes. Please Delete.";
			}
			$string = '<Row>';
			$string .= "\"{$partNum}\","; // Part Number - REQUIRED
			$string .= "\"{$partDescription}\","; // Part Description - REQUIRED
			$string .= "\"{$partDetails}\","; // Part Details
			$string .= "\"{$partUOM}\","; // UOM - REQUIRED
			$string .= '"",'; // UPC
			$string .= '"Inventory",'; // Part Type - REQUIRED
			$string .= "\"{$active}\","; // Active
			$string .= '"A",'; // ABC Code
			$string .= '"0",'; // Weight
			$string .= '"lbs",'; // Weight UOM
			$string .= '"0",'; // Width
			$string .= '"0",'; // Height
			$string .= '"0",'; // Length
			$string .= '"ft",'; // Size UOM
			$string .= '"",'; // Alert Note
			$string .= '"",'; // Picture Url
			$string .= '"",'; // Primary Tracking
			$string .= '"",'; // Tracks-
			$string .= '""'; // CF-
			$string .= '</Row>';
			$return ['part_string'] = $part_string = $string;
			// echo "<pre>"; print_r($string); echo "</pre>"; exit; die();
			$fishbowlapi->importRq ( 'ImportPart', $string );
			$result = $fishbowlapi->result ['FbiMsgsRs'];
			$PartXMLString = $fishbowlapi->XMLstring;
			
			$return ['part_cmd'] = "ImportPart";
			$return ['part_statuscode'] = $statuscode = $result ['ImportRs'] ['@attributes'] ['statusCode'];
			$return ['part_statusmsg'] = $statusmsg = $result ['ImportRs'] ['@attributes'] ['statusMessage'];
			$return ['part_push_status'] = "Part Push {$return['part_statuscode']} - {$return['part_statusmsg']}";
			$PartXMLString .= $statuscode . ": " . $statusmsg . "\n";
			if ($return ['part_statusmsg'] == "null") {
				$return ['part_statusmsg'] = $fberror->checkCode ( $statuscode );
			}
			$return ['part_results'] = $result;
			
			// PartNumber, ProductNumber, ProductDescription, ProductDetails, UOM, Price, Class, Active, Taxable, ComboBox, AllowUOM, ProductURL, ProductPictureURL, ProductUPC, ProductSKU, ProductSOItemType, IncomeAccount, Weight, WeightUOM, Width, Height, Length, sizeUOM, DefaultFlag, AlertNote, CF-Custom"
			// "C7010","C7010","Playdough, orange, 3lb",,"ea","$6.95",,"true","true","true","true",,,,,"Sale","Use Default","0","lbs","0","0","0","ft","true",,
			
			$return ['product_string'] = $string = '<Row>"' . $partNum . '","' . $partNum . '","' . $partDescription . '","","' . $partUOM . '","' . $cost . '","","true","true","true","true","","","","","Sale","Use Default","0","","0","0","0","","true","",""</Row>';
			$fishbowlapi->importRq ( 'ImportProduct', $string );
			$result = $fishbowlapi->result ['FbiMsgsRs'];
			$PartXMLString .= $fishbowlapi->XMLstring;
			
			$update = Parts::find ( $partNum );
			$update->xmlstring = "Part String: $part_string | Product String: $string";
			$update->save ();
			
			$return ['product_cmd'] = "ImportProduct";
			$return ['product_statuscode'] = $statuscode = $result ['ImportRs'] ['@attributes'] ['statusCode'];
			$return ['product_statusmsg'] = $statusmsg = $result ['ImportRs'] ['@attributes'] ['statusMessage'];
			if ($return ['product_statusmsg'] == "null") {
				$return ['product_statusmsg'] = $fberror->checkCode ( $statuscode );
			}
			$return ['product_results'] = $result;
		}
		$fishbowlapi->closeConnection ();
		return $return;
	}
	public static function push_sales_order($array) {
		$url = url ( '/' );
		// echo "<pre>"; print_r($array['customer_name']); echo "</pre>"; exit; die();
		$term = $array ['term'];
		$list = $array ['list'];
		$theme = $array ['theme'];
		$camp = $array ['camp'];
		$dli = $array ['dli'];
		$location = $array ['location'];
		$grade = $array ['grade'];
		$soid = $array ['soid'];
		$packby = $array ['packby'];
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fbpre = config ( 'fishbowl.fbpre' );
		$fishbowl_test = config ( 'fishbowl.fishbowl_test' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$products = $array ['products'];
		if ($fishbowlapi->statusCode != 1000) {
			$return ['statusmsg'] = $fberror->checkCode ( $fishbowlapi->statusCode );
			$return ['statuscode'] = $fishbowlapi->statusCode;
			$return = base64_encode ( json_encode ( $return ) );
			
			return $return;
		} else {
			$customer_id = $array ['customer_id'];
			// $row = "SELECT attn, street, city, zip, state, country FROM ".tbpre."customeraddresses WHERE id = '$customer_id'";
			$row = CustomerAddresses::where ( 'id', $array ['customer_id'] );
			$customer_address_sql = $row->toSql ();
			$row = $row->first ();
			$attn = $row ['attn'];
			$street = preg_replace ( "/\r|\n/", " ", $row ['street'] );
			$city = $row ['city'];
			$zip = $row ['zip'];
			$state = $row ['state'];
			$country = $row ['country'];
			$class = "None";
			$date = date ( "n/j/Y" );
			if ($array ['soFulfillmentDate'] == "") {
				$fulfillment_date = date ( "n/j/Y" );
			} else {
				$fulfillment_date = date ( "n/j/Y", strtotime ( $array ['soFulfillmentDate'] ) );
			}
			
			/**
			 * "Flag","SONum","Status","CustomerName","CustomerContact","BillToName","BillToAddress","BillToCity","BillToState","BillToZip","BillToCountry","ShipToName","ShipToAddress","ShipToCity","ShipToState","ShipToZip","ShipToCountry","CarrierName","TaxRateName","PriorityId","PONum","VendorPONum","Date","Salesman","ShippingTerms","PaymentTerms","FOB","Note","QuickBooksClassName","LocationGroupName","FulfillmentDate","URL","ShipService","CF-Custom"
			 * "SO","HO-0634","10","Burlingame CG","Burlingame CG","Burlingame CG","2385 Trousdale Rd.","Burlingame","CA","94010","UNITED STATES","Burlingame CG","2385 Trousdale Rd.","Burlingame","CA","94010","UNITED STATES","Will Call","None","30",,,"10/4/12","gamba","Prepaid","COD","Origin",,"Camp Galileo:Burlingame","Main","10/4/12",,,
			 */
			$fbsoid = $fbpre . "SO-" . $array ['soid'];
			$array ['class'] = "";
			$search = array (
					'"',
					"'",
					"," 
			);
			$replace = array (
					"",
					"",
					" " 
			);
			$notes = str_replace ( $search, $replace, $notes );
			if ($fishbowl_test == "test") {
				$notes = "This Sales Order was uploaded for testing purposes. Please Delete.";
			}
			$string .= '<Row>';
			$string .= '"SO",'; // Flag - REQUIRED
			$string .= "\"{$fbsoid}\","; // SO Number - REQUIRED
			$string .= '"10",'; // Status - 10 is Estimate - REQUIRED
			$string .= "\"{$array['customer_name']}\","; // Customer Name - REQUIRED
			$string .= "\"{$array['customer_name']}\","; // Customer Contact - REQUIRED
			$string .= "\"{$attn}\","; // Bill to Name - REQUIRED
			$string .= "\"{$street}\","; // Bill to Address - REQUIRED
			$string .= "\"{$city}\","; // Bill to City - REQUIRED
			$string .= "\"{$state}\","; // Bill to State - REQUIRED
			$string .= "\"{$zip}\","; // Bill to Zip - REQUIRED
			$string .= "\"{$country}\","; // Bill to Country - REQUIRED
			$string .= "\"{$attn}\","; // Ship to Name - REQUIRED
			$string .= "\"{$street}\","; // Ship to Address - REQUIRED
			$string .= "\"{$city}\","; // Ship to City - REQUIRED
			$string .= "\"{$state}\","; // Ship to State - REQUIRED
			$string .= "\"{$zip}\","; // Ship to Zip - REQUIRED
			$string .= "\"{$country}\","; // Ship to Country - REQUIRED
			$string .= '"Will Call",'; // Carrier Name - REQUIRED
			$string .= '"None",'; // Tax Rate Name - REQUIRED
			$string .= '"30",'; // Priority ID - 30 is Normal - REQUIRED
			$string .= '"",'; // PO Number
			$string .= '"",'; // Vendor PO Number
			$string .= "\"{$date}\","; // Creation Date
			$string .= '"gamba",'; // Salesman
			$string .= '"Prepaid",'; // Shipping Terms
			$string .= '"COD",'; // Payment Terms
			$string .= '"Origin",'; // FOB Point
			$notes = addcslashes ( $notes );
			$string .= "\"{$notes}\","; // SO Notes
			$string .= "\"{$array['class']}\","; // Quick Books Class Name
			$string .= '"Main",'; // Location Group Name
			$string .= "\"{$fulfillment_date}\","; // Fulfillment Date
			$string .= '"",'; // URL
			$string .= '"",'; // Shipping Service
			$string .= '</Row>';
			// Check URL if there is an issue. There may be an issue with format change.
			// Format URL: https://www.fishbowlinventory.com/w/files/csv/importSO.html
			// "Flag","SOItemTypeID","ProductNumber","ProductDescription","ProductQuantity","UOM","ProductPrice","Taxable","TaxCode","Note","QuickBooksClassName","FulfillmentDate","ShowItem","KitItem","RevievisionLevel"
			// "Item","10","C0004","1","ea","2.00","true",,"Camp Galileo:Burlingame","10/4/12","true","false",""
			$i = 1;
			foreach ( $products as $prod_num => $product ) {
				if ($prod_num != $product ['part_exclude']) {
					$cw_notes = $product ['cw_notes'];
					$search = '"';
					$replace = "''";
					$product ['part_desc'] = str_replace ( $search, $replace, $product ['part_desc'] );
					// if($product['price'] == "") { $product['price'] = "0.00"; }
					// Blank out Product UoM
					
					$string .= "\n";
					$string .= '<Row>';
					$string .= '"Item",'; // Flag - REQUIRED
					$string .= '"10",'; // SOItemTypeID - REQUIRED - 10 is Sale
					$string .= "\"{$prod_num}\","; // ProductNumber - REQUIRED
					$string .= "\"\","; // ProductDescription {$product['part_desc']}
					$string .= "\"{$product['qty']}\","; // ProductQuantity - REQUIRED
					$string .= "\"\","; // UOM {$product['uom']}
					$string .= "\"{$product['price']}\","; // ProductPrice
					$string .= '"true",'; // Taxable
					$string .= '"",'; // TaxCode (BLANK)
					$cw_notes = addcslashes ( $cw_notes );
					$string .= "\"{$cw_notes}\","; // Note
					$string .= "\"{$class}\","; // QuickBooksClassName
					$string .= "\"{$fulfillment_date}\","; // FulfillmentDate
					$string .= '"true",'; // ShowItem
					$string .= '"false",'; // KitItem
					$string .= '""'; // RevisionLevel (BLANK)
					$string .= '</Row>';
					// $string .= "\n" . '<Row>"Item","10","'.$prod_num.'","'.$product['qty'].'","'.$product['uom'].'","'.$product['price'].'","true","'.addcslashes($cw_notes).'","'.$class.'","' . $fulfillment_date . '","true","false"'.',""</Row>';
					// if($i == 49) { break; }
					$i ++;
				}
			}
			$fishbowlapi->importRq ( 'ImportSalesOrder', $string );
			$fishbowlapi->closeConnection ();
			
			$result = self::process_status_message ( $fishbowlapi->result ['FbiMsgsRs'] );
			$result ['customer_address_sql'] = $customer_address_sql;
			$SalesOrderXMLString = $fishbowlapi->XMLstring;
			$date_time = date ( "Y-m-d H:i:s" );
			
			// mysql_query("UPDATE ".tbpre."salesorders SET xmlstring = \"{$SalesOrderXMLString}\", fb_err_msg = \"{$fb_err_msg}\", fb_err_date = \"{$date_time}\" WHERE id = $soid"); // or die("Error xmlstring: ".mysql_error());
			$update = SalesOrders::find ( $soid );
			$update->xmlstring = $SalesOrderXMLString;
			$update->fb_err_msg = json_encode ( $result );
			$update->fb_err_date = $date_time;
			$update->save ();
			
			/*
			 * if($result['push_status_code'] != "1000") {
			 * $return = base64_encode(json_encode($return));
			 * return redirect("{$url}/sales/salesorder?statuscode={$result['push_status_code']}&action=salesorder&soid={$soid}&dli={$dli}&list={$list}&theme={$theme}&term={$term}&camp={$camp}&location={$location}&grade={$grade}&packby={$packby}&r={$return}");
			 * exit;
			 * } else {
			 * // $update = "UPDATE ".tbpre."salesorders SET fishbowl = 'true' WHERE id = $soid";
			 * $return['fishbowl'] = 'true';
			 * }
			 */
		}
		return $result;
	}
	public static function push_resupply_order($array) {
		$url = url ( '/' );
		$reorder_id = $array ['reorder_id'];
		$request_id = $array ['request_id'];
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fbpre = config ( 'fishbowl.fbpre' );
		$fishbowl_test = config ( 'fishbowl.fishbowl_test' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$term = gambaTerm::year_by_status ( 'C' );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$return ['connect_status_code'] = $fishbowlapi->statusCode;
		if ($return ['connect_status_code'] != 1000) {
			$return ['connect_status_message'] = $fberror->checkCode ( $return ['connect_status_code'] );
			// echo "<pre>"; print_r($return); echo "</pre>"; exit; die();
			$update = Reorders::find ( $reorder_id );
			$update->fishbowl_response = json_encode ( $return );
			$update->save ();
			return $return;
			exit ();
		} else {
			// $return['connect_status_msg'] = $fberror->checkCode($return['connect_status_code']);
			$reorderInfo = gambaResupplyOrders::materialReorderEdit ( $reorder_id );
			
			// The Camp Location Name needs to be the same as the Customer Name to Work 6/18/13
			$customer_name = trim ( $reorderInfo ['location'] ) . " " . $reorderInfo ['abbr'];
			// echo "<pre>"; print_r($reorderInfo); echo "</pre>"; exit; die();
			$customerAddress = gambaCustomers::addresses ( $customer_name, $reorderInfo ['abbr'] );
			// echo "<pre>"; print_r($customerAddress); echo "</pre>"; exit; die();
			$customer_name = $customerAddress ['name'];
			if ($customerAddress ['status'] == "false") {
				$return ['customer_address_error'] = "true";
				$return ['customer_address_sql'] = $customerAddress ['sql'];
				$return ['reorder_id'] = $reorder_id;
				$update = Reorders::find ( $reorder_id );
				$update->fishbowl_response = json_encode ( $return );
				$update->save ();
				// echo "<pre>"; print_r($return); echo "</pre>"; exit; die();
				
				// echo "<pre>"; print_r($return); echo "</pre>"; exit; die();
				return $return;
				exit ();
			}
			$attn = $customerAddress ['attn'];
			$street = $customerAddress ['street'];
			$city = $customerAddress ['city'];
			$zip = $customerAddress ['zip'];
			$state = $customerAddress ['state'];
			$country = $customerAddress ['country'];
			
			$class = "None";
			$date = date ( "n/j/Y" );
			$fulfillment_date = date ( "n/j/Y" );
			
			$so_number = $fbpre . 'RSO-' . $term . '-' . $reorder_id;
			
			// Up to Date 2/8/17
			// "SO","HO-0634","10","Burlingame CG","Burlingame CG","Burlingame CG","2385 Trousdale Rd.","Burlingame","CA","94010","UNITED STATES","Burlingame CG","2385 Trousdale Rd.","Burlingame","CA","94010","UNITED STATES","Will Call","None","30",,,"10/4/12","gamba","Prepaid","COD","Origin",,"Camp Galileo:Burlingame","Main","10/4/12",,,
			if ($fishbowl_test == "test") {
				$notes = "This Resupply Order was uploaded for testing purposes. Please Delete.";
			}
			$string .= '<Row>';
			$string .= '"SO",'; // Flag - REQUIRED
			$string .= "\"{$so_number}\","; // SONum - REQUIRED
			$string .= '"10",'; // Status - REQUIRED
			$string .= "\"{$customer_name}\","; // Customer Name - REQUIRED
			$string .= "\"{$customer_name}\","; // Customer Contact - REQUIRED
			$string .= "\"{$attn}\","; // Bill To Name - REQUIRED
			$string .= "\"{$street}\","; // Bill To Address - REQUIRED
			$string .= "\"{$city}\","; // Bill To City - REQUIRED
			$string .= "\"{$state}\","; // Bill To State - REQUIRED
			$string .= "\"{$zip}\","; // Bill To Zip - REQUIRED
			$string .= "\"{$country}\","; // Bill To Country - REQUIRED
			$string .= "\"{$attn}\","; // Ship To Name - REQUIRED
			$string .= "\"{$street}\","; // Ship To Address - REQUIRED
			$string .= "\"{$city}\","; // Ship To City - REQUIRED
			$string .= "\"{$state}\","; // Ship To State - REQUIRED
			$string .= "\"{$zip}\","; // Ship To Zip - REQUIRED
			$string .= "\"{$country}\","; // Ship To Country - REQUIRED
			$string .= '"Will Call",'; // Carrier Name - REQUIRED
			$string .= '"None",'; // Tax Rate Name - REQUIRED
			$string .= '"30",'; // Priority Id
			$string .= '"",'; // PO Num
			$string .= '"",'; // Vendor PO Num
			$string .= '"' . date ( "n/j/Y" ) . '",'; // Date
			$string .= '"gamba",'; // Salesman
			$string .= '"Prepaid",'; // Shipping Terms
			$string .= '"COD",'; // Payment Terms
			$string .= '"Origin",'; // FOB (Free On Board) Shipping Point
			$string .= "\"{$notes}\","; // Notes
			$string .= "\"{$class}\","; // Quick Books Class Name
			$string .= '"Main",'; // Location Group Name
			$string .= "\"{$fulfillment_date}\","; // Fulfillment Date
			$string .= '"",'; // URL
			$string .= '""'; // Ship Service
			$string .= '</Row>';
			
			// "Flag","SOItemTypeID","ProductNumber","ProductQuantity","UOM","ProductPrice","Taxable","Note","QuickBooksClassName","FulfillmentDate","ShowItem","KitItem"
			// "Item","10","C0004","1","ea","2.00","true",,"Camp Galileo:Burlingame","10/4/12","true","false"
			$i = 1;
			$search = array (
					'"',
					"'",
					",",
					"&" 
			);
			$replace = array (
					"",
					"",
					" ",
					"and" 
			);
			foreach ( $reorderInfo ['parts'] as $key => $value ) {
				$product = gambaProducts::partToProduct ( $value ['part_num'] );
				$note = "Part Info: " . $value ['part_num'] . " " . $value ['part_desc'] . " - " . $value ['uom'] . " - " . $value ['qty'];
				$note = str_replace ( $search, $replace, $note );
				// echo "<p>$note</p>";
				// echo "<pre>"; print_r($product); echo "</pre>";
				$string .= "\n<Row>";
				$string .= '"Item",'; // Flag - REQUIRED
				$string .= '"10",'; // SO Item Type ID - 10: Sale - REQUIRED
				$string .= "\"{$value['part_num']}\","; // Product Number - REQUIRED
				$string .= '"",'; // Product Description
				$string .= " \"{$value['qty']}\","; // Product Quantity - REQUIRED
				$string .= '"",'; // UOM - REQUIRED (Optional)
				$string .= "\"{$product['price']}\","; // Product Price - REQUIRED
				$string .= '"true",'; // Taxable - true OR false
				$string .= '"",'; // Tax Code
				$notes = str_replace ( $search, $replace, $value ['notes'] );
				$string .= "\"{$notes}\","; // Note
				$string .= '"None",'; // Quick Books Class Name
				$string .= '"",'; // Fulfillment Date
				$string .= '"true",'; // Show Item - true OR false
				$string .= '"false",'; // Kit Item
				$string .= '""'; // Revision Level
				$string .= "</Row>";
				$i ++;
			}
			// exit; die();
			$fishbowlapi->importRq ( 'ImportSalesOrder', $string );
			$fishbowlapi->closeConnection ();
			// $result = $fishbowlapi->result['FbiMsgsRs'];
			$result = self::process_status_message ( $fishbowlapi->result ['FbiMsgsRs'] );
			
			$ResupplyOrderXMLString = $fishbowlapi->XMLstring;
			if ($result ['push_status_code'] != "1000") {
				// echo "<pre>"; print_r($result); echo "</pre>"; exit; die();
				// $update = "UPDATE ".tbpre."reorders SET updated = '$date', xmlstring = \"".$xmlstring."\" WHERE id = $reorder_id"
				$update = Reorders::find ( $reorder_id );
				$update->updated = $date;
				$update->xmlstring = $string;
				$update->fishbowl_response = json_encode ( $result );
				$update->save ();
				
				return $result;
			} else {
				// echo "<pre>"; print_r($result); echo "</pre>"; exit; die();
				$fishbowl = 'true';
				// $update = "UPDATE ".tbpre."reorders SET status = '2', updated = '$date', xmlstring = \"".$error_string."\" WHERE id = $reorder_id"
				
				$update = Reorders::find ( $reorder_id );
				$update->status = '2';
				$update->updated = $date;
				$update->xmlstring = $string;
				$update->fishbowl_response = json_encode ( $result );
				$update->save ();
				
				// Removed for shipped checkbox
				// $update = "UPDATE ".tbpre."reorderitems SET status = 2, updated = '$date' WHERE reorder_id = $reorder_id"
				
				return $result;
			}
		}
	}
	
	/**
	 * PUSH PURCHASE ORDER
	 * 
	 * @param unknown $array
	 */
	public static function push_purchase_order($array) {
		$poid = $array ['poid'];
		$url = url ( '/' );
		$term = gambaTerm::year_by_status ( 'C' );
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fbpre = config ( 'fishbowl.fbpre' );
		$fishbowl_test = config ( 'fishbowl.fishbowl_test' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		// echo $fishbowlapi->statusCode; exit; die();
		if ($fishbowlapi->statusCode == 1000) {
			// $row = "SELECT vendors.Name, purchaseorders.notes, purchaseorders.fulfillmentdate, purchaseorders.datecreated, locationgroup.LocationGroup FROM ".tbpre."purchaseorders po LEFT JOIN ".tbpre."vendors v on vendors.VendorID = purchaseorders.vendorid LEFT JOIN ".tbpre."locationgroup lg on locationgroup.id = 2 WHERE purchaseorders.id = $poid"
			$row = PurchaseOrders::select ( 'vendors.Name', 'purchaseorders.notes', 'purchaseorders.fulfillmentdate', 'purchaseorders.datecreated', 'locationgroup.LocationGroup' );
			$row = $row->leftjoin ( 'vendors', 'vendors.VendorID', '=', 'purchaseorders.vendorid' );
			$row = $row->leftjoin ( 'locationgroup', 'locationgroup.id', '=', 'purchaseorders.locationgroup' );
			$row = $row->where ( 'purchaseorders.id', $array ['poid'] );
			$po_info_sql = $row->toSql ();
			$row = $row->first ();
			// echo "<pre>"; print_r($row); echo "</pre>"; exit; die();
			$ponumber = $fbpre . "PO-" . $term . '-' . $poid;
			// echo $ponumber;
			$fulfillmentdate = date ( "n/j/Y", strtotime ( $row ['fulfillmentdate'] ) );
			$datecreated = date ( "n/j/Y", strtotime ( $row ['datecreated'] ) );
			$locationgroup = $row ['LocationGroup'];
			// Updated 2/9/17
			// "Flag","PONum","Status","VendorName","VendorContact","RemitToName","RemitToAddress","RemitToCity","RemitToState","RemitToZip","RemitToCountry","ShipToName","DeliverToName","ShipToAddress","ShipToCity","ShipToState","ShipToZip","ShipToCountry","CarrierName","VendorSONum","CustomerSONum","CreatedDate","CompletedDate","ConfirmedDate","FulfillmentDate","IssuedDate","Buyer","ShippingTerms","PaymentTerms","FOB","Note","QuickBooksClassName","LocationGroupName","URL","CF-Custom"
			// "Flag","POItemTypeID","PartNumber","VendorPartNumber","PartQuantity","FulfilledQuantity","PickedQuantity","UOM","PartPrice","FulfillmentDate","LastFulfillmentDate","RevisionLevel","Note","QuickBooksClassName"
			// "PO","GMB4","10","Amazon","Amazon","Amazon","None","None","ID","None","UNITED STATES","Galileo Learning",,"1509 Zephyr Ave.","Hayward","CA","94544","UNITED STATES","Will Call",,,"10/3/2012",,,"10/3/2012",,"gamba","Prepaid","COD","Origin","","None","Main",,
			// "Item",10,"D5005","D5005","5",,,"ea","37","10/3/2012",,,"","None"
			// "Item",10,"C2468","C2468","10",,,"ea","2.52","10/3/2012",,,"","None"
			
			$search = array (
					'"',
					"'",
					"," 
			);
			$replace = array (
					"",
					"",
					" " 
			);
			$notes = str_replace ( $search, $replace, $row ['notes'] );
			if ($fishbowl_test == "test") {
				$notes = "This Resupply Order was uploaded for testing purposes. Please Delete.";
			}
			$string = '<Row>';
			$string .= '"PO",'; // Flag - REQUIRED
			$string .= "\"{$ponumber}\","; // PONum - REQUIRED
			$string .= '"10",'; // Status - 10: Bid Request - REQUIRED
			$string .= "\"{$row['Name']}\","; // Vendor Name - REQUIRED
			$string .= "\"{$row['Name']}\","; // Vendor Contact - REQUIRED
			$string .= "\"{$row['Name']}\","; // Remit To Name - REQUIRED
			$string .= '"None",'; // Remit To Address - REQUIRED
			$string .= '"None",'; // Remit To City - REQUIRED
			$string .= '"ID",'; // Remit To State - REQUIRED
			$string .= '"None",'; // Remit To Zip - REQUIRED
			$string .= '"UNITED STATES",'; // Remit To Country - REQUIRED
			$string .= '"Galileo Learning",'; // Ship To Name - REQUIRED
			$string .= '"",'; // Deliver To Name - REQUIRED (Optional)
			$string .= '"1509 Zephyr Ave.",'; // Ship To Address - REQUIRED
			$string .= '"Hayward",'; // Ship To City - REQUIRED
			$string .= '"CA",'; // Ship To State - REQUIRED
			$string .= '"94544",'; // Ship To Zip - REQUIRED
			$string .= '"UNITED STATES",'; // Ship To Country - REQUIRED
			$string .= '"Will Call",'; // Carrier Name - REQUIRED
			$string .= '"",'; // Vendor SO Num
			$string .= '"",'; // Customer SO Num
			$string .= "\"{$datecreated}\","; // Created Date
			$string .= '"",'; // Completed Date
			$string .= '"",'; // Confirmed Date
			$string .= "\"{$fulfillmentdate}\","; // Fulfillment Date
			$string .= '"",'; // Issued Date
			$string .= '"gamba",'; // Buyer
			$string .= '"Prepaid and Billed",'; // Shipping Terms
			$string .= '"COD",'; // Payment Terms
			$string .= '"Origin",'; // FOB (Free On Board) Shipping Point
			$string .= "\"{$notes}\","; // Note
			$string .= '"None",'; // Quick Books Class Name
			$string .= "\"Davis\","; // Location Group Name - Removed {$locationgroup} and Hardcoded Davis
			$string .= '""'; // URL
			$string .= "</Row>";
			$sql = "SELECT purchaseorderitem.id, purchaseorderitem.number, purchaseorderitem.qty, purchaseorderitem.notes, parts.fbcost, parts.fbuom, vendorparts.vendor, vendorparts.vendorPartNumber FROM " . tbpre . "purchaseorderitem poi LEFT JOIN " . tbpre . "parts p ON parts.number = purchaseorderitem.number LEFT JOIN " . tbpre . "vendorparts vp ON vendorparts.partNumber = purchaseorderitem.number WHERE purchaseorderitem.poid = $poid";
			// "SELECT purchaseorderitem.number, fbu.code, purchaseorderitem.qty, purchaseorderitem.notes, fb.standardCost, parts.suom, vendorparts.vendorPartNumber FROM ".tbpre."purchaseorderitem poi LEFT JOIN fbParts fb ON fb.partNum = purchaseorderitem.number LEFT JOIN ".tbpre."partuoms fbu ON fbu.id = fb.partUOM LEFT JOIN ".tbpre."parts p ON ml.part_num = fb.partNum LEFT JOIN ".tbpre."vendorparts vp ON vendorparts.partNumber = purchaseorderitem.number WHERE purchaseorderitem.poid = $poid"
			
			$query = PurchaseOrderItem::select ( 'purchaseorderitem.id', 'purchaseorderitem.number', 'purchaseorderitem.qty', 'purchaseorderitem.notes', 'parts.fbcost', 'parts.fbuom', 'vendorparts.vendor', 'vendorparts.vendorPartNumber' );
			$query = $query->leftjoin ( 'parts', 'parts.number', '=', 'purchaseorderitem.number' );
			$query = $query->leftjoin ( 'vendorparts', 'vendorparts.partNumber', '=', 'purchaseorderitem.number' );
			$query = $query->where ( 'purchaseorderitem.poid', $poid );
			$query = $query->get ();
			if ($query->count () > 0) {
				foreach ( $query as $key => $row ) {
					$partnumber = $row ['number'];
					$uom = $row ['fbuom'];
					$qty = $row ['qty'];
					$notes = $row ['notes'];
					$cw_notes = gambaSupplies::cw_notes ( $partnumber, $term );
					if (is_array ( $cw_notes )) {
						$cwnotes = "";
						foreach ( $cw_notes as $supply_id => $supply_values ) {
							if ($supply_values ['activity_info'] ['theme_name']) {
								$cwnotes .= $supply_values ['activity_info'] ['theme_name'] . " - ";
							}
							if ($supply_values ['activity_info'] ['grade_name']) {
								$cwnotes .= $supply_values ['activity_info'] ['grade_name'] . " - ";
							}
							$cwnotes .= $supply_values ['activity_info'] ['name'] . ": " . $supply_values ['notes'] . "\n";
						}
						$notes .= "\n" . $cwnotes;
					}
					$notes = str_replace ( $search, $replace, $notes );
					$string .= "\n<Row>";
					$string .= '"Item",'; // Flag - REQUIRED
					$string .= '"10",'; // PO Item Type ID - REQUIRED
					$string .= "\"{$partnumber}\","; // Part Number - REQUIRED
					$string .= "\"{$row['vendorPartNumber']}\","; // Vendor Part Number - REQUIRED
					$string .= "\"{$qty}\","; // Part Quantity - REQUIRED
					$string .= '"",'; // Fulfilled Quantity - REQUIRED
					$string .= '"",'; // Picked Quantity - REQUIRED
					$string .= "\"{$uom}\","; // UOM - REQUIRED -
					$string .= "\"{$row['fbcost']}\","; // PartPrice - REQUIRED
					$string .= "\"{$fulfillmentdate}\","; // Fulfillment Date - REQUIRED
					$string .= '"",'; // Last Fulfillment Date - REQUIRED
					$string .= '"",'; // Revision Level
					$string .= "\"{$notes}\","; // Note
					$string .= '"None"'; // Quick Books Class Name
					$string .= "</Row>";
				}
			}
			$fishbowlapi->importRq ( 'ImportPurchaseOrder', $string );
			$result = self::process_status_message ( $fishbowlapi->result ['FbiMsgsRs'] );
			$result ['po_info_sql'] = $po_info_sql;
			$PurchaseOrderXMLString = $fishbowlapi->XMLstring;
			
			// "UPDATE ".tbpre."purchaseorders SET xmlstring = \"".$PurchaseOrderXMLString."\" WHERE id = $poid"
			$update = PurchaseOrders::find ( $poid );
			$update->xmlstring = json_encode ( $PurchaseOrderXMLString );
			$update->fishbowl_response = json_encode ( $result );
			$update->save ();
			
			// echo "<pre>"; print_r($result); echo "</pre>"; exit; die();
			// $return['statusCode'] =
			// $statuscode = $result['ImportRs']['@attributes']['statusCode'];
			// $return['statusmsg'] =
			// $result['ImportRs']['@attributes']['statusMessage'];
			// if($return['statusmsg'] == NULL) {
			// $return['statusmsg'] = $fberror->checkCode($statuscode);
			// }
			// $return['results'] = $result;
			// $return = base64_encode(json_encode($return));
			
			// echo "<pre>"; print_r($result); echo "</pre>"; exit; die();
			
			// Purchase Order Pushed Successfully
			if ($result ['push_status_code'] == 1000) {
				// $update = "UPDATE ".tbpre."purchaseorders SET number = '".$fbpre."PO"."-".$term."-".$poid."' WHERE id = $poid"
				$update = PurchaseOrders::find ( $poid );
				$update->number = $fbpre . "PO" . "-" . $term . "-" . $poid;
				$update->save ();
				
				// print_r($fishbowlapi->result['FbiMsgsRs']); exit; die();
				$fishbowlapi->closeConnection ();
				
				return $result;
			} else {
				$fishbowlapi->closeConnection ();
			}
		} else {
			// $return['statusmsg'] = $fberror->checkCode($fishbowlapi->statusCode);
			// $return['statuscode'] = $fishbowlapi->statusCode;
			// $return = base64_encode(json_encode($return));
			$update = PurchaseOrders::find ( $poid );
			$update->fishbowl_response = "Can Not Connect to Fishbowl";
			$update->save ();
			$fishbowlapi->closeConnection ();
			$return ['connection'] = "fail";
			return $return;
		}
	}
	public static function process_status_message($results) {
		// echo "<pre>"; print_r($results); echo "</pre>"; exit; die();
		$fberror = new FBErrorCodes ();
		$fb_result_array ['datetime'] = date ( "n/j/Y h:i a" );
		$fb_result_array ['connect_status_code'] = $results ['@attributes'] ['statusCode'];
		if ($results ['@attributes'] ['statusMessage'] == "") {
			$fb_result_array ['connect_status_message'] = $fberror->checkCode ( $results ['@attributes'] ['statusCode'] );
		} else {
			$fb_result_array ['connect_status_message'] = $results ['@attributes'] ['statusMessage'];
		}
		if (isset ( $results [0] )) {
			$fb_result_array ['push_status_code'] = ( string ) $results [0]->attributes ()->statusCode;
			$pushStatusMessage = ( string ) $results [0]->attributes ()->statusMessage;
		} else {
			$pushStatusMessage = "No Response from Fishbowl.";
		}
		if ($pushStatusMessage == "") {
			$fb_result_array ['push_status_message'] = $fberror->checkCode ( $fb_result_array ['push_status_code'] );
		} else {
			$fb_result_array ['push_status_message'] = $pushStatusMessage;
		}
		return $fb_result_array;
	}
	public static function export_sales_orders() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->exportRq ( 'ExportSalesOrder' );
		$so_export_list = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
		$reversed_so_export_list = array_reverse ( $so_export_list );
		$output = array_slice ( $reversed_so_export_list, 0, 150 );
		self::truncate_log ( 'salesorders.log', 1 );
		self::data_log ( 'salesorders.log', print_r ( $output, TRUE ), 1 );
		
		$fishbowlapi->closeConnection ();
	}
	public static function export_list() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$xml = "<ExportListRq/>\n";
		$fishbowlapi->openRq ( $xml );
		$result = $fishbowlapi->result;
		$fishbowlapi->closeConnection ();
		return $result;
	}
	public static function export_inventory_quantities() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->exportRq ( 'ExportInventoryQuantities' );
		$result = $fishbowlapi->result;
		$fishbowlapi->closeConnection ();
		return $result;
	}
	public static function export_custom_field_lists() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->exportRq ( 'ExportCustomFieldLists' );
		$result = $fishbowlapi->result;
		$fishbowlapi->closeConnection ();
		return $result;
	}
	public static function export_parts() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->exportRq ( 'ExportPart' );
		$part_export_list = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
		// $reversed_part_export_list = array_reverse($part_export_list);
		// $output = array_slice($reversed_part_export_list, 0, 150);
		// self::truncate_log('export_parts.log', 1);
		// self::data_log('export_parts.log', print_r($part_export_list, TRUE), 1);
		
		$fishbowlapi->closeConnection ();
		return $part_export_list;
	}
	public static function export_products() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->exportRq ( 'ExportProduct' );
		$product_export_list = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
		// $reversed_product_export_list = array_reverse($product_export_list);
		// $output = array_slice($reversed_product_export_list, 0, 150);
		self::truncate_log ( 'export_products.log', 1 );
		self::data_log ( 'export_products.log', print_r ( $product_export_list, TRUE ), 1 );
		
		$fishbowlapi->closeConnection ();
	}
	public static function get_sales_orders($locationgroup, $status, $datebegin, $dateend) {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->getSalesOrderList ( $locationgroup, $status, $datebegin, $dateend );
		$solist = $fishbowlapi->result;
		$fishbowlapi->closeConnection ();
		return $solist;
	}
	public static function part_query($part) {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->getPartQuery ( $part );
		$part_info = $fishbowlapi->result;
		$fishbowlapi->closeConnection ();
		return $part_info;
	}
	public static function part_inventory($part, $location) {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->getTotalInventory ( $part, $location );
		$part_info = $fishbowlapi->result;
		$fishbowlapi->closeConnection ();
		return $part_info;
	}
	public static function inventory_quantity($part, $fromdate, $todate) {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->getInvQty ( $part, $fromdate, $todate );
		$inventory = $fishbowlapi->result;
		$fishbowlapi->closeConnection ();
		return $inventory;
	}
	public static function product_query($part) {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->getProducts ( 'Query', $part );
		$part_info = $fishbowlapi->result;
		$fishbowlapi->closeConnection ();
		return $part_info;
	}
	public static function sales_by_count() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->getReportSalesByCount ();
		$part_info = $fishbowlapi->result;
		$fishbowlapi->closeConnection ();
		return $part_info;
	}
	public static function export_purchase_orders() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$fishbowlapi->exportRq ( 'ExportPurchaseOrder' );
		$po_export_list = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
		$reversed_po_export_list = array_reverse ( $po_export_list );
		$output = array_slice ( $reversed_po_export_list, 0, 150 );
		self::truncate_log ( 'purchaseorders.log', 1 );
		self::data_log ( 'purchaseorders.log', print_r ( $output, TRUE ), 1 );
		
		$fishbowlapi->closeConnection ();
	}
	public static function uoms_sync() {
		self::truncate_log ( 'connection.log' );
		self::truncate_log ( 'mysql_error.log' );
		self::truncate_log ( 'uoms.log' );
		
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		self::connection_log ( 'FishbowlAPI Login', "Code: $code" );
		
		self::connection_log ( 'UoMs Sync', "Code: $code" );
		
		// Units of Measure
		if ($fishbowlapi->statusCode == 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'UoMs Checking Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			
			$fishbowlapi->getUOMs ();
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'getUOMs Call', "Code: {$fishbowlapi->statusCode} | Message: {$fishbowlapi->statusMsg}" );
			}
			// $uoms = $fishbowlapi->result['FbiMsgsRs']['UOMRs']['UOM'];
			$json = json_encode ( $fishbowlapi->result ['FbiMsgsRs'] ['UOMRs'] ['UOM'] );
			$uoms = json_decode ( $json, true );
			if (is_array ( $uoms )) {
				// $update = "UPDATE ".tbpre."partuoms SET active = 'false'"
				$update = PartUoMs::where ( 'active', 'true' )->update ( [ 
						'active' => 'false' 
				] );
				
				foreach ( $uoms as $key => $uom_values ) {
					$uomid = $uom_values ['UOMID'];
					$name = $uom_values ['Name'];
					$code = $uom_values ['Code'];
					$active = $uom_values ['Active'];
					$uomids [$code] = $uomid;
					if ($active == "true") {
						// $add = "INSERT INTO ".tbpre."partuoms (id, name, code, active) VALUES (\"$uomid\", \"$name\", \"$code\", '$active') ON DUPLICATE KEY UPDATE name = \"$name\", code = \"$code\", active = '$active'";
						$query = PartUoMs::where ( 'id', $uomid );
						$date = date ( "Y-m-d H:i:s" );
						if ($query->count () > 0) {
							$update = PartUoMs::where ( 'id', $uomid )->update ( [ 
									'name' => $name,
									'code' => $code,
									'active' => $active,
									'date_updated' => $date 
							] );
							self::data_log ( 'uoms.log', "Update UoM | Code: $code | ID: $uomid | Name: $name | Active: $active" );
						} else {
							$insert = new PartUoMs ();
							$insert->id = $uomid;
							$insert->name = $name;
							$insert->code = $code;
							$insert->active = $active;
							$insert->date_added = $date;
							$insert->date_updated = $date;
							$insert->save ();
							self::data_log ( 'uoms.log', "Add UoM | Code: $code | ID: $uomid | Name: $name | Active: $active" );
						}
					}
				}
			}
			self::connection_log ( 'getUOMs Call', "End: Getting UOMs" );
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'UoMs Checking Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			// $fishbowlapi->closeConnection(); exit; die();
		}
		sleep ( 2 );
		
		$fishbowlapi->closeConnection ();
	}
	public static function parts_sync() {
		self::truncate_log ( 'connection.log' );
		self::truncate_log ( 'mysql_error.log' );
		self::truncate_log ( 'uoms.log' );
		self::truncate_log ( 'customer.log' );
		self::truncate_log ( 'vendors.log' );
		self::truncate_log ( 'vendorparts.log' );
		self::truncate_log ( 'standardcost.log' );
		self::truncate_log ( 'inventory_quantity.log' );
		self::truncate_log ( 'onorder.log' );
		self::truncate_log ( 'qtyshipped.log' );
		self::truncate_log ( 'products.log' );
		self::truncate_log ( 'parts.log' );
		
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		self::connection_log ( 'FishbowlAPI Login', "Code: $code" );
		
		self::connection_log ( 'Part Sync', "Code: $code" );
		
		// Units of Measure
		if ($fishbowlapi->statusCode == 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'UoMs Checking Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			
			$fishbowlapi->getUOMs ();
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'getUOMs Call', "Code: {$fishbowlapi->statusCode} | Message: {$fishbowlapi->statusMsg}" );
			}
			// $uoms = $fishbowlapi->result['FbiMsgsRs']['UOMRs']['UOM'];
			$json = json_encode ( $fishbowlapi->result ['FbiMsgsRs'] ['UOMRs'] ['UOM'] );
			$uoms = json_decode ( $json, true );
			if (is_array ( $uoms )) {
				// $update = "UPDATE ".tbpre."partuoms SET active = 'false'"
				$update = PartUoMs::where ( 'active', 'true' )->update ( [ 
						'active' => 'false' 
				] );
				
				foreach ( $uoms as $key => $value ) {
					$uomid = $value->UOMID;
					$name = $value->Name;
					$code = $value->Code;
					$active = $value->Active;
					$uomids ["$code"] = "$uomid";
					if ($active == "true") {
						// $add = "INSERT INTO ".tbpre."partuoms (id, name, code, active) VALUES (\"$uomid\", \"$name\", \"$code\", '$active') ON DUPLICATE KEY UPDATE name = \"$name\", code = \"$code\", active = '$active'"
						$query = PartUoMs::find ( $uomid );
						if ($query->count () > 0) {
							$update = PartUoMs::find ( $uomid );
							$update->name = $name;
							$update->code = $code;
							$update->active = $active;
							$update->date_updated = date ( "Y-m-d H:i:s" );
							$update->save ();
						} else {
							$insert = new PartUoMs ();
							$insert->id = $uomid;
							$insert->name = $name;
							$insert->code = $code;
							$insert->active = $active;
							$insert->date_added = date ( "Y-m-d H:i:s" );
							$insert->date_updated = date ( "Y-m-d H:i:s" );
							$insert->save ();
						}
					}
					$log_data = "Code: $code | ID: $uomid | Name: $name | Active: $active";
					self::data_log ( 'uoms.log', $log_data );
				}
			}
			self::connection_log ( 'getUOMs Call', "End: Getting UOMs" );
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'UoMs Checking Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			// $fishbowlapi->closeConnection(); exit; die();
		}
		sleep ( 2 );
		
		// Standard Costs
		if ($fishbowlapi->statusCode == 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Standard Costs Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			$fishbowlapi->exportRq ( 'ExportPartStandardCost' );
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'ExportPartStandardCost Call', "Message: " . $fishbowlapi->statusMsg );
			}
			$costs = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			
			$partcosts = self::standardCost ( $costs );
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Standard Costs Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			// $fishbowlapi->closeConnection(); exit; die();
		}
		sleep ( 2 );
		$fishbowlapi->closeConnection ();
		// Connect Again
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		// Parts
		if ($fishbowlapi->statusCode == 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Parts Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			
			// Parts
			$fishbowlapi->exportRq ( 'ExportPart' );
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'ExportPart Call', "Export: " . $fishbowlapi->statusMsg );
			}
			$parts = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			$partNumbers = self::process_parts ( $parts, $partcosts, $uomids );
			self::connection_log ( 'self::parts', "Processed Parts" );
			$parts = $partNumbers ['partNumbers'];
			
			// Materials
			// Begin Processing Parts into Inventory Material List.
			if (is_array ( $parts )) {
				foreach ( $parts as $key => $value ) {
					$partNum = $key;
					$cost = $value ['cost'];
					$description = htmlspecialchars ( $value ['description'] );
					$uom = $value ['uom'];
					
					$fishbowlapi->getInvQty ( $partNum );
					$quantities = $fishbowlapi->result;
					// Because of Inventory in more than one location in the location group treat InvQty as an array with multiple values that need to be summed for QtyAvailable and QtyOnHand.
					$availableSale = 0;
					$quantityOnHand = 0;
					$quantityCommitted = 0;
					if (is_array ( $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] )) {
						
						if (array_key_exists ( '0', $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] )) {
							
							foreach ( $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] as $key => $value ) {
								$availSale = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->QtyAvailable;
								$fbpartid = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->Part->PartID;
								$availableSale = $availableSale + $availSale;
								$qtyOnHand = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->QtyOnHand;
								$quantityOnHand = $quantityOnHand + $qtyOnHand;
								$quantityCommitted = $quantityCommitted + $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->QtyCommitted;
							}
						} else {
							$availableSale = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] ['QtyAvailable'];
							$fbpartid = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty']->Part->PartID;
							$fb_active = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty']->Part->ActiveFlag;
							$quantityOnHand = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] ['QtyOnHand'];
							$quantityCommitted = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] ['QtyCommitted'];
						}
					}
					$log_data = "Part: $partNum | Available Sale: $availableSale | Quantity On Hand: $quantityOnHand";
					self::data_log ( 'inventory_quantity.log', $log_data );
					// Description, UoM and Cost set in GAMBA
					
					// $query = "INSERT INTO ".tbpre."parts (number, description, suom, fbuom, cost, fbcost, inventory, fishbowl) VALUES ('$partNum', \"$description\", '$uom', '$uom', '$cost', '$cost', 'true', 'true') ON DUPLICATE KEY UPDATE fbuom = '$uom', fbcost = '$cost'";
					
					$query = Parts::find ( $partNum );
					if ($query->count () > 0) {
						$update = Parts::find ( $partNum );
						$update->fbuom = $uom;
						$update->fbcost = $cost;
						$update->updated = date ( "Y-m-d H:i:s" );
						$update->save ();
					} else {
						$insert = new Parts ();
						$insert->number = $partNum;
						$insert->description = $description;
						$insert->suom = $uom;
						$insert->fbuom = $uom;
						$insert->cost = $cost;
						$insert->fbcost = $cost;
						$insert->inventory = 'true';
						$insert->fishbowl = 'true';
						$insert->created = date ( "Y-m-d H:i:s" );
						$insert->updated = date ( "Y-m-d H:i:s" );
						$insert->save ();
					}
					// $query = "INSERT INTO ".tbpre."inventory (number, fb_partid, fb_vendorid, fb_active, availablesale, quantityonhand, updated) VALUES ('$partNum', \"$fbpartid\", '', '$fb_active', '$availableSale', '$quantityOnHand', '".date("Y-m-d H:i:s")."') ON DUPLICATE KEY UPDATE quantityonhand = '$quantityOnHand', availablesale = '$availableSale', updated = '".date("Y-m-d H:i:s")."'";
					
					$query = Inventory::find ( $partNum );
					if ($query->count () > 0) {
						$update = Inventory::find ( $partNum );
						$update->quantityonhand = $quantityOnHand;
						$update->availablesale = $availableSale;
						$update->updated = date ( "Y-m-d H:i:s" );
						$update->save ();
					} else {
						$insert = new Inventory ();
						$insert->number = $partNum;
						$insert->fb_partid = $fbpartid;
						$insert->fb_vendorid = "";
						$insert->fb_active = $fb_active;
						$insert->availablesale = $availableSale;
						$insert->quantityonhand = $quantityOnHand;
						$insert->updated = date ( "Y-m-d H:i:s" );
						$insert->save ();
					}
					
					sleep ( 1 );
					$materials ['queries'] ["$partNum"] = "Part: $partNum - $description (" . $query . ")";
				}
				self::connection_log ( 'get_inventory_quantities', "Parts Returned to Process" );
				self::connection_log ( 'self::parts', "End: Processing Parts" );
			} else {
				self::connection_log ( 'get_inventory_quantities', "No Parts Returned to Process" );
			}
			self::connection_log ( 'get_parts', "End: Getting Parts" );
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Parts Status Failed', "Code: {$fishbowlapi->statusCode} | Message: {$fishbowlapi->statusMsg}" );
		}
		sleep ( 2 );
		
		$fishbowlapi->closeConnection ();
	}
	public static function inventory_sync() {
		self::truncate_log ( 'connection.log' );
		self::truncate_log ( 'mysql_error.log' );
		self::truncate_log ( 'uoms.log' );
		self::truncate_log ( 'customer.log' );
		self::truncate_log ( 'vendors.log' );
		self::truncate_log ( 'vendorparts.log' );
		self::truncate_log ( 'standardcost.log' );
		self::truncate_log ( 'inventory_quantity.log' );
		self::truncate_log ( 'onorder.log' );
		self::truncate_log ( 'qtyshipped.log' );
		self::truncate_log ( 'products.log' );
		self::truncate_log ( 'parts.log' );
		
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		self::connection_log ( 'FishbowlAPI Login', "Code: $code" );
		
		self::connection_log ( 'Inventory Sync', "Code: $code" );
		
		// Get On Order From Purchase Orders
		if ($fishbowlapi->statusCode == 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Purchase Orders Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			
			$fishbowlapi->exportRq ( 'ExportPurchaseOrder' );
			$onorder_parts = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			
			$statusArray = array (
					20 => "Issued",
					40 => "Partial" 
			);
			// $clearOnOrder = "UPDATE ".tbpre."inventory SET onorder = 0"
			$clearOnOrder = Inventory::where ( 'onorder', '>', 0 )->update ( [ 
					'onorder' => '0' 
			] );
			self::connection_log ( 'Purchase Orders Status', "Set Inventory On Order to 0" );
			foreach ( $onorder_parts as $key => $value ) {
				$string = $value;
				$temp = fopen ( "php://memory", "rw" );
				fwrite ( $temp, $string );
				fseek ( $temp, 0 );
				$array = fgetcsv ( $temp );
				$flag = $array [0];
				if ($flag == "PO") {
					$poNum = $array [1];
					$poStatus = $array [2];
					$poVendor = $array [3];
				}
				if ($flag == "Item" && $array [1] == 10) {
					$itemNum = $array [2];
					$partQuantity = $array [4];
					$fulfilledQuantity = $array [5];
					$pickedQuantity = $array [6];
					$uom = $array [7];
					if ($poStatus == 20 || $poStatus == 40) {
						$preTotal = $poArray ['Calculation'] ["$itemNum"] ['value'];
						$poArray ['Calculation'] ["$itemNum"] ['value'] = $poArray ['Calculation'] ["$itemNum"] ['value'] + ($partQuantity - $fulfilledQuantity);
						$postTotal = $poArray ['Calculation'] ["$itemNum"] ['value'];
						if ($postTotal > 0) {
							$poArray ['Calculation'] ["$itemNum"] ['uom'] = $uom;
						}
					}
				}
				sleep ( 1 );
			}
			foreach ( $poArray ['Calculation'] as $key => $value ) {
				$date = date ( "Y-m-d H:i:s" );
				$sql = "UPDATE inventory SET onorder = '" . $value ['value'] . "' WHERE number = '$key'";
				
				$update = Inventory::find ( $key );
				$update->onorder = $value ['value'];
				$update->save ();
				
				// self::data_log('onorder.log', $sql);
			}
			self::connection_log ( 'get_onorder', "End: Getting On Orders" );
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Purchase Orders Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			// $fishbowlapi->closeConnection(); exit; die();
		}
		sleep ( 2 );
		
		$fishbowlapi->closeConnection ();
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		
		// Quantity Shipped
		if ($fishbowlapi->statusCode == 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Quantity Shipped Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			$year = gambaTerm::year_by_status ( 'C' );
			$fishbowlapi->exportRq ( 'ExportSalesOrder' );
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'ExportSalesOrder Call', "Message: " . $fishbowlapi->statusMsg );
			}
			// "UPDATE ".tbpre."inventory SET quantityshipped = 0"
			$update = Inventory::where ( 'quantityshipped', '>', 0 )->update ( [ 
					'quantityshipped' => '0' 
			] );
			self::connection_log ( 'Sales Orders Status', "Set Inventory Quantity Shipped to 0" );
			
			$statusArray = array (
					10 => "Estimate",
					20 => "Issued",
					25 => "In Progress",
					60 => "Fulfilled",
					70 => "Closed Short",
					80 => "Void" 
			);
			// 2014.02.07 16:23:03 :: <FbiXml><Ticket><UserID>13</UserID><Key>UI60Kx+kpKhYg1JJx/GHxQ==</Key></Ticket><FbiMsgsRs statusCode="1000"><GetSOListRs statusCode="4100" statusMessage="There was an error loading SO 15-GGB Art:Star. GDS Exception. 335544761. too many open handles to database"/></FbiMsgsRs></FbiXml>
			
			$salesOrders = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			foreach ( $salesOrders as $key => $value ) {
				$string = $value;
				$temp = fopen ( "php://memory", "rw" );
				fwrite ( $temp, $string );
				fseek ( $temp, 0 );
				$array = fgetcsv ( $temp );
				$flag = $array [0];
				if ($flag == "SO") {
					$soNum = $array [1];
					$soStatus = $array [2];
					$FulfillmentDate = date ( "Y", strtotime ( $array [30] ) );
					if ($soStatus == 60) {
						$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Status'] = "$soStatus - " . $statusArray [$soStatus] . " - " . $FulfillmentDate;
					}
				}
				if ($flag == "Item") {
					if ($soStatus == 60) {
						$SOItemTypeID = $array [1];
						$ProductNumber = $array [2];
						$ProductQuantity = $array [3];
						$UOM = $array [4];
						$ProductPrice = $array [5];
						$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['SOItemTypeID'] = $SOItemTypeID;
						$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['ProductQuantity'] = $ProductQuantity;
						$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['UOM'] = $UOM;
						$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['ProductPrice'] = $ProductPrice;
					}
				}
			}
			sleep ( 2 );
			foreach ( $soArray [60] [$year] as $key => $value ) {
				foreach ( $value ['Products'] as $prodNum => $prodValues ) {
					$qtyShipped ["$prodNum"] = $qtyShipped ["$prodNum"] + $prodValues ['ProductQuantity'];
				}
			}
			sleep ( 2 );
			$date = date ( "Y-m-d" );
			foreach ( $qtyShipped as $key => $value ) {
				$query = "UPDATE inventory SET quantityshipped = '$value' WHERE number = '$key'";
				$update = Inventory::find ( $key );
				$update->quantityshipped = $value;
				$update->save ();
				self::data_log ( 'qtyshipped.log', $query );
			}
			self::connection_log ( 'ExportSalesOrder Call', "End: Getting Quantity Shipped" );
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Quantity Shipped Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			// $fishbowlapi->closeConnection(); exit; die();
		}
		
		$fishbowlapi->closeConnection ();
	}
	public static function fishbowl_sync() {
		self::truncate_log ( 'connection.log' );
		self::truncate_log ( 'mysql_error.log' );
		self::truncate_log ( 'uoms.log' );
		self::truncate_log ( 'customer.log' );
		self::truncate_log ( 'vendors.log' );
		self::truncate_log ( 'vendorparts.log' );
		self::truncate_log ( 'standardcost.log' );
		self::truncate_log ( 'inventory_quantity.log' );
		self::truncate_log ( 'onorder.log' );
		self::truncate_log ( 'qtyshipped.log' );
		self::truncate_log ( 'products.log' );
		self::truncate_log ( 'parts.log' );
		
		$sync_schedule = self::fishbowl_schedule ();
		$dayofweek = date ( "w" );
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		self::connection_log ( 'FishbowlAPI Login', "Code: $code" );
		
		self::connection_log ( 'Sync All', "Code: $code" );
		// Units of Measure
		if ($sync_schedule ['uom'] [$dayofweek] == 1) {
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'UoMs Checking Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				
				$fishbowlapi->getUOMs ();
				if ($fishbowlapi->statusCode == 1000) {
					self::connection_log ( 'getUOMs Call', "Code: {$fishbowlapi->statusCode} | Message: {$fishbowlapi->statusMsg}" );
				}
				// $uoms = $fishbowlapi->result['FbiMsgsRs']['UOMRs']['UOM'];
				$json = json_encode ( $fishbowlapi->result ['FbiMsgsRs'] ['UOMRs'] ['UOM'] );
				$uoms = json_decode ( $json, true );
				if (is_array ( $uoms )) {
					// $update = "UPDATE ".tbpre."partuoms SET active = 'false'"
					$update = PartUoMs::where ( 'active', 'true' )->update ( [ 
							'active' => 'false' 
					] );
					
					foreach ( $uoms as $key => $value ) {
						$uomid = $value->UOMID;
						$name = $value->Name;
						$code = $value->Code;
						$active = $value->Active;
						$uomids ["$code"] = "$uomid";
						if ($active == "true") {
							// $add = "INSERT INTO ".tbpre."partuoms (id, name, code, active) VALUES (\"$uomid\", \"$name\", \"$code\", '$active') ON DUPLICATE KEY UPDATE name = \"$name\", code = \"$code\", active = '$active'"
							$query = PartUoMs::find ( $uomid );
							if ($query->count () > 0) {
								$update = PartUoMs::find ( $uomid );
								$update->name = $name;
								$update->code = $code;
								$update->active = $active;
								$update->date_updated = date ( "Y-m-d H:i:s" );
								$update->save ();
							} else {
								$insert = new PartUoMs ();
								$insert->id = $uomid;
								$insert->name = $name;
								$insert->code = $code;
								$insert->active = $active;
								$insert->date_added = date ( "Y-m-d H:i:s" );
								$insert->date_updated = date ( "Y-m-d H:i:s" );
								$insert->save ();
							}
						}
						$log_data = "Code: $code | ID: $uomid | Name: $name | Active: $active";
						self::data_log ( 'uoms.log', $log_data );
					}
				}
				self::connection_log ( 'getUOMs Call', "End: Getting UOMs" );
			} else {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'UoMs Checking Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				// $fishbowlapi->closeConnection(); exit; die();
			}
			sleep ( 2 );
		} else {
			self::connection_log ( 'getUOMs Call', "Disabled" );
		}
		
		// Standard Costs
		if ($sync_schedule ['parts'] [$dayofweek] == 1) {
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Standard Costs Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				$fishbowlapi->exportRq ( 'ExportPartStandardCost' );
				if ($fishbowlapi->statusCode == 1000) {
					self::connection_log ( 'ExportPartStandardCost Call', "Message: " . $fishbowlapi->statusMsg );
				}
				$costs = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
				
				$partcosts = self::standardCost ( $costs );
			} else {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Standard Costs Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				// $fishbowlapi->closeConnection(); exit; die();
			}
			sleep ( 2 );
		} else {
			self::connection_log ( 'Standard Costs', "Disabled" );
		}
		
		// Connect Again
		$fishbowlapi->closeConnection ();
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		
		// Parts
		if ($sync_schedule ['parts'] [$dayofweek] == 1) {
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Parts Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				
				// Parts
				$fishbowlapi->exportRq ( 'ExportPart' );
				if ($fishbowlapi->statusCode == 1000) {
					self::connection_log ( 'ExportPart Call', "Export: " . $fishbowlapi->statusMsg );
				}
				$parts = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
				$partNumbers = self::process_parts ( $parts, $partcosts, $uomids );
				self::connection_log ( 'self::parts', "Processed Parts" );
				$parts = $partNumbers ['partNumbers'];
				
				// Materials
				// Begin Processing Parts into Inventory Material List.
				if (is_array ( $parts )) {
					foreach ( $parts as $key => $value ) {
						$partNum = trim ( $key );
						$cost = $value ['cost'];
						$description = htmlspecialchars ( $value ['description'] );
						$uom = $value ['uom'];
						
						$fishbowlapi->getInvQty ( $partNum );
						$quantities = $fishbowlapi->result;
						// Because of Inventory in more than one location in the location group treat InvQty as an array with multiple values that need to be summed for QtyAvailable and QtyOnHand.
						$availableSale = 0;
						$quantityOnHand = 0;
						$quantityCommitted = 0;
						if (is_array ( $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] )) {
							
							if (array_key_exists ( '0', $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] )) {
								
								foreach ( $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] as $key => $value ) {
									$availSale = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->QtyAvailable;
									$fbpartid = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->Part->PartID;
									$availableSale = $availableSale + $availSale;
									$qtyOnHand = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->QtyOnHand;
									$quantityOnHand = $quantityOnHand + $qtyOnHand;
									$quantityCommitted = $quantityCommitted + $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->QtyCommitted;
								}
							} else {
								$availableSale = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] ['QtyAvailable'];
								$fbpartid = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty']->Part->PartID;
								$fb_active = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty']->Part->ActiveFlag;
								$quantityOnHand = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] ['QtyOnHand'];
								$quantityCommitted = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] ['QtyCommitted'];
							}
						}
						$log_data = "Part: $partNum | Available Sale: $availableSale | Quantity On Hand: $quantityOnHand";
						self::data_log ( 'inventory_quantity.log', $log_data );
						// Description, UoM and Cost set in GAMBA
						
						// $query = "INSERT INTO ".tbpre."parts (number, description, suom, fbuom, cost, fbcost, inventory, fishbowl) VALUES ('$partNum', \"$description\", '$uom', '$uom', '$cost', '$cost', 'true', 'true') ON DUPLICATE KEY UPDATE fbuom = '$uom', fbcost = '$cost'";
						
						$query = Parts::find ( $partNum );
						if ($query->count () > 0) {
							$update = Parts::find ( $partNum );
							$update->fbuom = $uom;
							$update->fbcost = $cost;
							$update->save ();
						} else {
							$insert = new Parts ();
							$insert->number = $partNum;
							$insert->description = $description;
							$insert->suom = $uom;
							$insert->fbuom = $uom;
							$insert->cost = $cost;
							$insert->fbcost = $cost;
							$insert->inventory = 'true';
							$insert->fishbowl = 'true';
							$insert->save ();
						}
						
						// $query = "INSERT INTO ".tbpre."inventory (number, fb_partid, fb_vendorid, fb_active, availablesale, quantityonhand, updated) VALUES ('$partNum', \"$fbpartid\", '', '$fb_active', '$availableSale', '$quantityOnHand', '".date("Y-m-d H:i:s")."') ON DUPLICATE KEY UPDATE quantityonhand = '$quantityOnHand', availablesale = '$availableSale', updated = '".date("Y-m-d H:i:s")."'";
						$query = Inventory::find ( $partNum );
						if ($query->count () > 0) {
							$update = Inventory::find ( $partNum );
							$update->quantityonhand = $quantityOnHand;
							$update->availablesale = $availableSale;
							$update->updated = date ( "Y-m-d H:i:s" );
							$update->save ();
						} else {
							$insert = new Inventory ();
							$insert->number = $partNum;
							$insert->fb_partid = $fbpartid;
							$insert->fb_vendorid = "";
							$insert->fb_active = $fb_active;
							$insert->availablesale = $availableSale;
							$insert->quantityonhand = $quantityOnHand;
							$insert->updated = date ( "Y-m-d H:i:s" );
							$insert->save ();
						}
						
						sleep ( 1 );
						$materials ['queries'] ["$partNum"] = "Part: $partNum - $description (" . $query . ")";
					}
					self::connection_log ( 'get_inventory_quantities', "Parts Returned to Process" );
					self::connection_log ( 'self::parts', "End: Processing Parts" );
				} else {
					self::connection_log ( 'get_inventory_quantities', "No Parts Returned to Process" );
				}
				self::connection_log ( 'get_parts', "End: Getting Parts" );
			} else {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Parts Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				// $fishbowlapi->closeConnection(); exit; die();
			}
			sleep ( 2 );
		} else {
			self::connection_log ( 'Parts', "Disabled" );
		}
		
		// Connect Again
		$fishbowlapi->closeConnection ();
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		
		// Get On Order From Purchase Orders
		if ($sync_schedule ['inventory'] [$dayofweek] == 1) {
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Purchase Orders Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				
				$fishbowlapi->exportRq ( 'ExportPurchaseOrder' );
				$onorder_parts = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
				
				$statusArray = array (
						20 => "Issued",
						40 => "Partial" 
				);
				// $clearOnOrder = "UPDATE ".tbpre."inventory SET onorder = 0"
				$clearOnOrder = Inventory::update ( [ 
						'onorder' => '0' 
				] );
				
				foreach ( $onorder_parts as $key => $value ) {
					$string = $value;
					$temp = fopen ( "php://memory", "rw" );
					fwrite ( $temp, $string );
					fseek ( $temp, 0 );
					$array = fgetcsv ( $temp );
					$flag = $array [0];
					if ($flag == "PO") {
						$poNum = $array [1];
						$poStatus = $array [2];
						$poVendor = $array [3];
						$log_data = "Part: $poNum | Status: $poStatus | Vendor: $poVendor";
						self::data_log ( 'onorder.log', $log_data );
					}
					if ($flag == "Item" && $array [1] == 10) {
						$itemNum = $array [2];
						$partQuantity = $array [4];
						$fulfilledQuantity = $array [5];
						$pickedQuantity = $array [6];
						$uom = $array [7];
						if ($poStatus == 20 || $poStatus == 40) {
							$preTotal = $poArray ['Calculation'] ["$itemNum"] ['value'];
							$poArray ['Calculation'] ["$itemNum"] ['value'] = $poArray ['Calculation'] ["$itemNum"] ['value'] + ($partQuantity - $fulfilledQuantity);
							$postTotal = $poArray ['Calculation'] ["$itemNum"] ['value'];
							if ($postTotal > 0) {
								$poArray ['Calculation'] ["$itemNum"] ['uom'] = $uom;
							}
						}
					}
					sleep ( 1 );
				}
				foreach ( $poArray ['Calculation'] as $key => $value ) {
					$date = date ( "Y-m-d H:i:s" );
					// $update = "UPDATE ".tbpre."inventory SET onorder = '".$value['value']."' WHERE number = '$key'"
					
					$update = Inventory::find ( $key );
					$update->onorder = $value ['value'];
					$update->save ();
				}
				self::connection_log ( 'get_onorder', "End: Getting On Orders" );
			} else {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Purchase Orders Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				// $fishbowlapi->closeConnection(); exit; die();
			}
			sleep ( 2 );
		} else {
			self::connection_log ( 'Inventory', "Disabled" );
		}
		
		// Connect Again
		$fishbowlapi->closeConnection ();
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		
		// Quantity Shipped
		if ($sync_schedule ['qtyshipped'] [$dayofweek] == 1) {
			$term = gambaTerm::year_by_status ( 'C' );
			// $num_rows = "SELECT COUNT(*) AS sototal FROM ".tbpre."salesorders WHERE term = '$term' AND fishbowl = 'true'"
			$num_rows = SalesOrders::select ( 'id' )->where ( 'term', $term )->where ( 'fishbowl', 'true' )->get ();
			if ($num_rows->count () > 0) {
				if ($fishbowlapi->statusCode == 1000) {
					$code = $fberror->checkCode ( $fishbowlapi->statusCode );
					self::connection_log ( 'Quantity Shipped Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
					$year = gambaTerm::year_by_status ( 'C' );
					$fishbowlapi->exportRq ( 'ExportSalesOrder' );
					if ($fishbowlapi->statusCode == 1000) {
						self::connection_log ( 'ExportSalesOrder Call', "Message: " . $fishbowlapi->statusMsg );
					}
					// "UPDATE ".tbpre."inventory SET quantityshipped = 0"
					$update = Inventory::update ( [ 
							'quantityshipped' => '0' 
					] );
					
					$statusArray = array (
							10 => "Estimate",
							20 => "Issued",
							25 => "In Progress",
							60 => "Fulfilled",
							70 => "Closed Short",
							80 => "Void" 
					);
					$salesOrders = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
					foreach ( $salesOrders as $key => $value ) {
						$string = $value;
						$temp = fopen ( "php://memory", "rw" );
						fwrite ( $temp, $string );
						fseek ( $temp, 0 );
						$array = fgetcsv ( $temp );
						$flag = $array [0];
						if ($flag == "SO") {
							$soNum = $array [1];
							$soStatus = $array [2];
							$FulfillmentDate = date ( "Y", strtotime ( $array [30] ) );
							if ($soStatus == 60) {
								$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Status'] = "$soStatus - " . $statusArray [$soStatus] . " - " . $FulfillmentDate;
							}
						}
						if ($flag == "Item") {
							if ($soStatus == 60) {
								$SOItemTypeID = $array [1];
								$ProductNumber = $array [2];
								$ProductQuantity = $array [3];
								$UOM = $array [4];
								$ProductPrice = $array [5];
								$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['SOItemTypeID'] = $SOItemTypeID;
								$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['ProductQuantity'] = $ProductQuantity;
								$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['UOM'] = $UOM;
								$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['ProductPrice'] = $ProductPrice;
							}
						}
					}
					sleep ( 2 );
					foreach ( $soArray [60] [$year] as $key => $value ) {
						foreach ( $value ['Products'] as $prodNum => $prodValues ) {
							$qtyShipped ["$prodNum"] = $qtyShipped ["$prodNum"] + $prodValues ['ProductQuantity'];
						}
					}
					sleep ( 2 );
					$date = date ( "Y-m-d" );
					foreach ( $qtyShipped as $key => $value ) {
						// $query = "UPDATE ".tbpre."inventory SET quantityshipped = '$value' WHERE number = '$key'";
						$update = Inventory::find ( $key );
						$update->quantityshipped = $value;
						$update->save ();
					}
					self::connection_log ( 'ExportSalesOrder Call', "End: Getting Quantity Shipped" );
				} else {
					$code = $fberror->checkCode ( $fishbowlapi->statusCode );
					self::connection_log ( 'Quantity Shipped Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
					// $fishbowlapi->closeConnection(); exit; die();
				}
			} else {
				self::connection_log ( 'Qty Shipped', "No Sales Orders for $term." );
				// "UPDATE ".tbpre."inventory SET quantityshipped = 0"
				$update = Inventory::update ( [ 
						'quantityshipped' => '0' 
				] );
			}
		} else {
			self::connection_log ( 'Qty Shipped', "Disabled" );
		}
		
		// Connect Again
		$fishbowlapi->closeConnection ();
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		
		// Customers and Addresses (Camp Locations)
		if ($sync_schedule ['customers'] [$dayofweek] == 1) {
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Customers and Addresses Checking Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				$fishbowlapi->getCustomer ( 'List' );
				$customers = $fishbowlapi->result ['FbiMsgsRs'] ['CustomerListRs'] ['Customer'];
				$fishbowlapi->exportRq ( 'ExportCustomers' );
				$customer_addresses = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'];
				self::customers_list_add ( $customers, $customer_addresses );
				self::connection_log ( 'ExportCustomers Call', "End: Getting Customers" );
			} else {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Customers and Addresses Checking Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				// $fishbowlapi->closeConnection(); exit; die();
			}
			sleep ( 2 );
		} else {
			self::connection_log ( 'Customers', "Disabled" );
		}
		
		// Connect Again
		$fishbowlapi->closeConnection ();
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		
		// Vendors
		if ($sync_schedule ['vendors'] [$dayofweek] == 1) {
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Vendors Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				
				$fishbowlapi->exportRq ( 'ExportVendors' );
				if ($fishbowlapi->statusCode == 1000) {
					if (! empty ( $fishbowlapi->statusMsg )) {
						self::connection_log ( 'ExportVendors', "Export: " . $fishbowlapi->statusMsg );
					}
				}
				$vendor_addresses = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
				$fishbowlapi->getVendor ( 'List' );
				$vendors = $fishbowlapi->result ['FbiMsgsRs'] ['VendorListRs'] ['Vendor'];
				if (is_array ( $vendors )) {
					self::vendorslistadd ( $vendors, $vendor_addresses );
				}
				self::connection_log ( 'Get Vendors', "End: Getting Vendors" );
			} else {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Vendors Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				// $fishbowlapi->closeConnection(); exit; die();
			}
			sleep ( 2 );
		} else {
			self::connection_log ( 'Vendors', "Disabled" );
		}
		
		// Connect Again
		$fishbowlapi->closeConnection ();
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		
		// Products
		if ($sync_schedule ['products'] [$dayofweek] == 1) {
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Products Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				
				$fishbowlapi->exportRq ( 'ExportProduct' );
				if ($fishbowlapi->statusCode == 1000) {
					self::connection_log ( 'ExportProduct Call', "Message: " . $fishbowlapi->statusMsg );
				}
				$products = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
				
				$products = self::process_products ( $products );
			} else {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Products Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				// $fishbowlapi->closeConnection(); exit; die();
			}
			sleep ( 2 );
		} else {
			self::connection_log ( 'Products', "Disabled" );
		}
		// Vendor Part Numbers
		if ($sync_schedule ['vendors'] [$dayofweek] == 1) {
			if ($fishbowlapi->statusCode == 1000) {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Vendor Part Numbers Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				$fishbowlapi->exportRq ( 'ExportPartProductAndVendorPricing' );
				if ($fishbowlapi->statusCode == 1000) {
					self::connection_log ( 'ExportPartProductAndVendorPricing Call', "Message: " . $fishbowlapi->statusMsg );
				}
				$vendor_parts = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
				foreach ( $vendor_parts as $key => $value ) {
					$string = $value;
					$temp = fopen ( "php://memory", "rw" );
					fwrite ( $temp, $string );
					fseek ( $temp, 0 );
					$array = fgetcsv ( $temp );
					$part_num = $array [0];
					$part_desc = $array [1];
					$partTypeID = $array [5];
					$vendor = $array [34];
					$defaultVendorFlag = $array [35];
					$vendorPartNumber = $array [36];
					if ($partTypeID == 10 && $vendorPartNumber != "") {
						// $update = "INSERT INTO ".tbpre."vendorparts (partNumber, vendor, vendorPartNumber, defaultVendorFlag) VALUES ('$part_num', \"$vendor\", \"$vendorPartNumber\", '$defaultVendorFlag') ON DUPLICATE KEY UPDATE vendor = \"$vendor\", vendorPartNumber = \"$vendorPartNumber\", defaultVendorFlag = '$defaultVendorFlag'"
						$query = VendorParts::where ( 'number', $part_num );
						if ($query->count () > 0) {
							$update = VendorParts::where ( 'number', $part_num )->update ( [ 
									'vendor' => $vendor,
									'vendorPartNumber' => $vendorPartNumber,
									'defaultVendorFlag' => $defaultVendorFlag 
							] );
						} else {
							$insert = new VendorParts ();
							$insert->partNumber = $part_num;
							$insert->vendor = $vendor;
							$insert->vendorPartNumber = $vendorPartNumber;
							$insert->defaultVendorFlag = $defaultVendorFlag;
							$insert->save ();
						}
						
						$log_data = "Part: $part_num | Vendor: $vendor | Vendor Part: $vendorPartNumber";
						self::data_log ( 'vendorparts.log', $log_data );
					}
				}
			} else {
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );
				self::connection_log ( 'Vendor Part Numbers Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
				// $fishbowlapi->closeConnection(); exit; die();
			}
			sleep ( 2 );
		} else {
			self::connection_log ( 'Vendor Parts', "Disabled" );
		}
		$fishbowlapi->closeConnection ();
	}
	public static function customers_sync() {
		self::truncate_log ( 'connection.log' );
		self::truncate_log ( 'mysql_error.log' );
		// self::truncate_log('customer.log');
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		self::connection_log ( 'FishbowlAPI Login', "Code: $code" );
		
		if ($fishbowlapi->statusCode == 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Customers and Addresses Checking Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			$fishbowlapi->getCustomer ( 'List' );
			$customers = $fishbowlapi->result ['FbiMsgsRs'] ['CustomerListRs'] ['Customer'];
			$fishbowlapi->exportRq ( 'ExportCustomers' );
			$customer_addresses = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'];
			self::customers_list_add ( $customers, $customer_addresses );
			self::connection_log ( 'ExportCustomers Call', "End: Getting Customers" );
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Customers and Addresses Checking Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			// $fishbowlapi->closeConnection(); exit; die();
		}
		sleep ( 2 );
		
		$fishbowlapi->closeConnection ();
	}
	public static function rest_sync() {
		self::truncate_log ( 'connection.log' );
		self::truncate_log ( 'mysql_error.log' );
		self::truncate_log ( 'uoms.log' );
		self::truncate_log ( 'customer.log' );
		self::truncate_log ( 'vendors.log' );
		self::truncate_log ( 'vendorparts.log' );
		self::truncate_log ( 'standardcost.log' );
		self::truncate_log ( 'inventory_quantity.log' );
		self::truncate_log ( 'onorder.log' );
		self::truncate_log ( 'qtyshipped.log' );
		self::truncate_log ( 'products.log' );
		self::truncate_log ( 'parts.log' );
		
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		$code = $fberror->checkCode ( $fishbowlapi->statusCode );
		self::connection_log ( 'FishbowlAPI Login', "Code: $code" );
		
		self::connection_log ( 'The Rest Sync', "Code: $code" );
		// Customers and Addresses (Camp Locations)
		if ($fishbowlapi->statusCode == 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Customers and Addresses Checking Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			$fishbowlapi->getCustomer ( 'List' );
			$customers = $fishbowlapi->result ['FbiMsgsRs'] ['CustomerListRs'] ['Customer'];
			$fishbowlapi->exportRq ( 'ExportCustomers' );
			$customer_addresses = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'];
			self::customers_list_add ( $customers, $customer_addresses );
			self::connection_log ( 'ExportCustomers Call', "End: Getting Customers" );
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Customers and Addresses Checking Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			// $fishbowlapi->closeConnection(); exit; die();
		}
		sleep ( 2 );
		
		// Vendors
		if ($fishbowlapi->statusCode == 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Vendors Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			
			$fishbowlapi->exportRq ( 'ExportVendors' );
			if ($fishbowlapi->statusCode == 1000) {
				if (! empty ( $fishbowlapi->statusMsg )) {
					self::connection_log ( 'ExportVendors', "Export: " . $fishbowlapi->statusMsg );
				}
			}
			$vendor_addresses = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			$fishbowlapi->getVendor ( 'List' );
			$vendors = $fishbowlapi->result ['FbiMsgsRs'] ['VendorListRs'] ['Vendor'];
			if (is_array ( $vendors )) {
				self::vendorslistadd ( $vendors, $vendor_addresses );
			}
			self::connection_log ( 'Get Vendors', "End: Getting Vendors" );
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Vendors Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			// $fishbowlapi->closeConnection(); exit; die();
		}
		sleep ( 2 );
		
		// Products
		if ($fishbowlapi->statusCode == 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Products Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			
			$fishbowlapi->exportRq ( 'ExportProduct' );
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'ExportProduct Call', "Message: " . $fishbowlapi->statusMsg );
			}
			$products = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			
			$products = self::process_products ( $products );
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Products Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			// $fishbowlapi->closeConnection(); exit; die();
		}
		sleep ( 2 );
		
		// Vendor Part Numbers
		if ($fishbowlapi->statusCode == 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Vendor Part Numbers Status', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			$fishbowlapi->exportRq ( 'ExportPartProductAndVendorPricing' );
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'ExportPartProductAndVendorPricing Call', "Message: " . $fishbowlapi->statusMsg );
			}
			$vendor_parts = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			foreach ( $vendor_parts as $key => $value ) {
				$string = $value;
				$temp = fopen ( "php://memory", "rw" );
				fwrite ( $temp, $string );
				fseek ( $temp, 0 );
				$array = fgetcsv ( $temp );
				$part_num = $array [0];
				$part_desc = $array [1];
				$partTypeID = $array [5];
				$vendor = $array [34];
				$defaultVendorFlag = $array [35];
				$vendorPartNumber = $array [36];
				if ($partTypeID == 10 && $vendorPartNumber != "") {
					// $update = "INSERT INTO ".tbpre."vendorparts (partNumber, vendor, vendorPartNumber, defaultVendorFlag) VALUES ('$part_num', \"$vendor\", \"$vendorPartNumber\", '$defaultVendorFlag') ON DUPLICATE KEY UPDATE vendor = \"$vendor\", vendorPartNumber = \"$vendorPartNumber\", defaultVendorFlag = '$defaultVendorFlag'"
					$query = VendorParts::find ( $part_num );
					if ($query->count () > 0) {
						$update = VendorParts::find ( $part_num );
						$update->vendor = $vendor;
						$update->vendorPartNumber = $vendorPartNumber;
						$update->defaultVendorFlag = $defaultVendorFlag;
						$update->save ();
					} else {
						$insert = new VendorParts ();
						$insert->partNumber = $part_num;
						$insert->vendor = $vendor;
						$insert->vendorPartNumber = $vendorPartNumber;
						$insert->defaultVendorFlag = $defaultVendorFlag;
						$insert->save ();
					}
					
					$log_data = "Part: $part_num | Vendor: $vendor | Vendor Part: $vendorPartNumber";
					self::data_log ( 'vendorparts.log', $log_data );
				}
			}
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'Vendor Part Numbers Status Failed', "Code: " . $code . " | Message: " . $fishbowlapi->statusMsg );
			// $fishbowlapi->closeConnection(); exit; die();
		}
		sleep ( 2 );
		
		$fishbowlapi->closeConnection ();
	}
	public static function get_vendorparts() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		self::truncate_log ( 'vendorparts.log' );
		if ($fishbowlapi->statusCode != 1000) {
			self::connection_log ( 'get_vendorparts', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
		} else {
			self::connection_log ( 'get_vendorparts', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
			$fishbowlapi->exportRq ( 'ExportPartProductAndVendorPricing' );
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'ExportPartProductAndVendorPricing', "Export: " . $fishbowlapi->statusMsg );
			}
			$vendor_parts = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			foreach ( $vendor_parts as $key => $value ) {
				$string = $value;
				$temp = fopen ( "php://memory", "rw" );
				fwrite ( $temp, $string );
				fseek ( $temp, 0 );
				$array = fgetcsv ( $temp );
				$part_num = $array [0];
				$part_desc = $array [1];
				$partTypeID = $array [5];
				$vendor = $array [34];
				$defaultVendorFlag = $array [35];
				$vendorPartNumber = $array [36];
				if ($partTypeID == 10 && $vendorPartNumber != "") {
					// $update = "INSERT INTO ".tbpre."vendorparts (partNumber, vendor, vendorPartNumber, defaultVendorFlag) VALUES ('$part_num', \"$vendor\", \"$vendorPartNumber\", '$defaultVendorFlag') ON DUPLICATE KEY UPDATE vendor = \"$vendor\", vendorPartNumber = \"$vendorPartNumber\", defaultVendorFlag = '$defaultVendorFlag'"
					$query = VendorParts::find ( $part_num );
					if ($query->count () > 0) {
						$update = VendorParts::find ( $part_num );
						$update->vendor = $vendor;
						$update->vendorPartNumber = $vendorPartNumber;
						$update->defaultVendorFlag = $defaultVendorFlag;
						$update->save ();
					} else {
						$insert = new VendorParts ();
						$insert->partNumber = $part_num;
						$insert->vendor = $vendor;
						$insert->vendorPartNumber = $vendorPartNumber;
						$insert->defaultVendorFlag = $defaultVendorFlag;
						$insert->save ();
					}
					
					$log_data = "Part: $part_num | Vendor: $vendor | Vendor Part: $vendorPartNumber";
					self::data_log ( 'vendorparts.log', $log_data );
				}
			}
		}
		$fishbowlapi->closeConnection ();
	}
	public static function get_products() {
		// Products
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		self::truncate_log ( 'products.log' );
		if ($fishbowlapi->statusCode != 1000) {
			self::connection_log ( 'get_products', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
		} else {
			self::connection_log ( 'get_products', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
			
			$fishbowlapi->exportRq ( 'ExportProduct' );
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'ExportProduct', "Export: " . $fishbowlapi->statusMsg );
			}
			$products = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			
			$products = self::process_products ( $products );
		}
		$fishbowlapi->closeConnection ();
	}
	
	/**
	 * Process Products from Fishbowl
	 * 
	 * @param unknown $products
	 * @return string
	 */
	public function process_products($products) {
		self::connection_log ( 'process_products', "Process: Products" );
		if (is_array ( $products )) {
			$date = date ( "Y-m-d H:i:s" );
			foreach ( $products as $key => $value ) {
				if (! function_exists ( 'str_getcsv' )) {
					$product = self::csv2array ( $value );
				} else {
					$product = str_getcsv ( $value );
				}
				$part_number = $product [0];
				$strlen = strlen ( $part_number );
				if ($key != 0 && $strlen <= 6) {
					$product_number = trim ( $product [1] );
					$product_description = htmlspecialchars ( $product [2] );
					$product_price = $product [5];
					if ($product_price == "") {
						$product_price = "$0.00";
					}
					$product_uom = $product [4];
					// $query = "INSERT INTO ".tbpre."products (Num, Description, Price, UOM, PartID, updated) VALUES ('$product_number', \"$product_description\", '$product_price', \"$product_uom\", '$part_number', '$date') ON DUPLICATE KEY UPDATE Description = \"$product_description\", Price = \"$product_price\", UOM = \"$product_uom\", PartID = '$part_number', updated = '$date'";
					
					$query = Products::find ( $product_number );
					if ($query->count () > 0) {
						$update = Products::find ( $product_number );
						$update->Description = $product_description;
						$update->Price = $product_price;
						$update->UOM = $product_uom;
						$update->PartID = $part_number;
						$update->updated = $date;
						$update->save ();
					} else {
						$insert = new Products ();
						$insert->Num = $product_number;
						$insert->Description = $product_description;
						$insert->Price = $product_price;
						$insert->UOM = $product_uom;
						$insert->PartID = $part_number;
						$insert->updated = $date;
						$insert->save ();
					}
					
					$log_data = "Product: $product_number | Description: $product_description";
					self::data_log ( 'products.log', $log_data );
				}
			}
			$array ['update'] = "true";
		} else {
			$array ['update'] = "false";
		}
		return $array;
	}
	public static function get_uoms() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		self::truncate_log ( 'uoms.log' );
		if ($fishbowlapi->statusCode != 1000) {
			self::connection_log ( 'get_uoms', "Connect: " . $code . " - " . $fishbowlapi->statusMsg );
		} else {
			self::connection_log ( 'get_uoms', "Connect: " . $code . " - " . $fishbowlapi->statusMsg );
			
			$fishbowlapi->getUOMs ();
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'getUOMs', "Get: " . $fishbowlapi->statusMsg );
			}
			// $uoms = $fishbowlapi->result['FbiMsgsRs']['UOMRs']['UOM'];
			$json = json_encode ( $fishbowlapi->result ['FbiMsgsRs'] ['UOMRs'] ['UOM'] );
			$uoms = json_decode ( $json, true );
			if (is_array ( $uoms )) {
				// $update = "UPDATE ".tbpre."partuoms SET active = 'false'"
				$update = PartUoMs::where ( 'active', 'true' )->update ( [ 
						'active' => 'false' 
				] );
				
				foreach ( $uoms as $key => $value ) {
					$uomid = $value->UOMID;
					$name = $value->Name;
					$code = $value->Code;
					$active = $value->Active;
					$uomids ["$code"] = "$uomid";
					if ($active == "true") {
						// $add = "INSERT INTO ".tbpre."partuoms (id, name, code, active) VALUES (\"$uomid\", \"$name\", \"$code\", '$active') ON DUPLICATE KEY UPDATE name = \"$name\", code = \"$code\", active = '$active'"
						$query = PartUoMs::find ( $uomid );
						if ($query->count () > 0) {
							$update = PartUoMs::find ( $uomid );
							$update->name = $name;
							$update->code = $code;
							$update->active = $active;
							$update->date_updated = date ( "Y-m-d H:i:s" );
							$update->save ();
						} else {
							$insert = new PartUoMs ();
							$insert->id = $uomid;
							$insert->name = $name;
							$insert->code = $code;
							$insert->active = $active;
							$insert->date_added = date ( "Y-m-d H:i:s" );
							$insert->date_updated = date ( "Y-m-d H:i:s" );
							$insert->save ();
						}
					}
					$log_data = "Code: $code | ID: $uomid | Name: $name | Active: $active";
					self::data_log ( 'uoms.log', $log_data );
				}
				$array = $uomids;
			}
			self::connection_log ( 'get_uoms', "End: Getting UOMs" );
		}
		$fishbowlapi->closeConnection ();
		return $array;
	}
	
	// Log Files
	public static function connection_log($function, $msg) {
		$log_path = config ( 'gamba.log_path' );
		$filename = $log_path . 'connection.log';
		$date = date ( "Y-m-d H:i:s" );
		$data = "$function | $msg | Date: $date\r";
		file_put_contents ( $filename, $data, FILE_APPEND | LOCK_EX );
	}
	
	/**
	 *
	 * @param unknown $log_file
	 * @param unknown $log_data
	 * @param number $clean
	 *        	- set to 1 to not add a date at end
	 */
	public static function data_log($log_file, $log_data, $clean = 0) {
		$log_path = config ( 'gamba.log_path' );
		$filename = $log_path . $log_file;
		$date = date ( "Y-m-d H:i:s" );
		$data = $log_data;
		if ($clean == 0) {
			$data .= " | Date: $date\r";
		}
		file_put_contents ( $filename, $data, FILE_APPEND | LOCK_EX );
	}
	public static function truncate_log($log_file, $clean = 0) {
		$log_path = config ( 'gamba.log_path' );
		$filename = $log_path . $log_file;
		$handle = fopen ( $filename, 'w+' );
		$date = date ( "Y-m-d H:i:s" );
		if ($clean == 0) {
			$msg = "File Truncated on $date\r";
		} else {
			$msg = "";
		}
		fwrite ( $handle, $msg );
		fclose ( $handle );
	}
	public static function mysql_error_log($error) {
		$date = date ( "Y-m-d H:i:s" );
		if ($data != "") {
			$data = "Error Msg: $error | Date: $date";
			$log_path = config ( 'gamba.log_path' );
			file_put_contents ( $log_path . 'mysql_error.log', $data, FILE_APPEND | LOCK_EX );
		}
	}
	public static function get_customers_and_addresses() {
		// API Call: getCustomer('List')
		// API Call: exportRq('ExportCustomers')
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		self::truncate_log ( 'customer.log' );
		if ($fishbowlapi->statusCode != 1000) {
			self::connection_log ( 'get_customers_and_addresses', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
		} else {
			self::connection_log ( 'get_customers_and_addresses', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
			$fishbowlapi->getCustomer ( 'List' );
			$customers = $fishbowlapi->result ['FbiMsgsRs'] ['CustomerListRs'] ['Customer'];
			$fishbowlapi->exportRq ( 'ExportCustomers' );
			$customer_addresses = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'];
			self::customers_list_add ( $customers, $customer_addresses );
			self::connection_log ( 'get_customers_and_addresses', "End: Getting Customers" );
		}
		$fishbowlapi->closeConnection ();
	}
	
	/**
	 * Process and put into customer and customer addresses tables
	 * 
	 * @param unknown $customers
	 * @param unknown $customer_addresses
	 */
	public static function customers_list_add($customers, $customer_addresses) {
		self::connection_log ( 'customers_list_add', "Process: Customer List Add" );
		foreach ( $customer_addresses ['Rows'] ['Row'] as $key => $value ) {
			self::connection_log ( 'customers_list_add', "Address CSV: {$value}" );
			if (! function_exists ( 'str_getcsv' )) {
				$address = self::csv2array ( $value );
			} else {
				$address = str_getcsv ( $value );
			}
			if ($address [4] == "true") {
				$id = $address [0];
				self::connection_log ( 'customers_list_add', "Address Name: {$address[1]}" );
				$customerAddress [$id] ['AddressName'] = $address [1];
				$customerAddress [$id] ['AddressContact'] = $address [2];
				$customerAddress [$id] ['AddressType'] = $address [3];
				$customerAddress [$id] ['IsDefault'] = $address [4];
				$customerAddress [$id] ['Address'] = $address [5];
				$customerAddress [$id] ['City'] = $address [6];
				$customerAddress [$id] ['State'] = $address [7];
				$customerAddress [$id] ['Zip'] = $address [8];
				$customerAddress [$id] ['Country'] = $address [9];
			}
		}
		// $delete = "DELETE FROM ".tbpre."customers"
		$delete = Customers::truncate ();
		self::connection_log ( 'customers_list_add', "Delete Customers Table" );
		// $delete = "DELETE FROM ".tbpre."customeraddresses"
		$delete = CustomerAddresses::truncate ();
		self::connection_log ( 'customers_list_add', "Delete Customer Addresses Table" );
		foreach ( $customers as $key => $value ) {
			$CustomerID = $value->CustomerID;
			$AccountID = $value->AccountID;
			$Status = $value->Status;
			$DefPaymentTerms = $value->DefPaymentTerms;
			$DefShipTerms = $value->DefShipTerms;
			$TaxRate = $value->TaxRate; // OMIT
			$Name = $value->Name;
			$Number = $value->Number;
			$DateCreated = $value->DateCreated;
			$DateLastModified = $value->DateLastModified;
			$LastChangedUser = $value->LastChangedUser;
			$CreditLimit = $value->CreditLimit;
			$TaxExemptNumber = $value->TaxExemptNumber; // OMIT
			$Note = $value->Note;
			$ActiveFlag = $value->ActiveFlag;
			$AccountingID = $value->AccountingID;
			$DefaultSalesman = $value->DefaultSalesman; // OMIT
			$JobDepth = $value->JobDepth;
			$add_sql = "INSERT INTO " . tbpre . "customers (CustomerID, AccountID, Status, DefPaymentTerms, DefShipTerms, TaxRate, Name, Number, DateCreated, DateLastModified, LastChangedUser, CreditLimit, TaxExemptNumber, Note, ActiveFlag, AccountingID, DefaultSalesman, JobDepth) VALUES (\"$CustomerID\", \"$AccountID\", \"$Status\", \"$DefPaymentTerms\", \"$DefShipTerms\", \"$TaxRate\", \"$Name\", \"$Number\", \"$DateCreated\", \"$DateLastModified\", \"$LastChangedUser\", \"$CreditLimit\", \"$TaxExemptNumber\", \"$Note\", \"$ActiveFlag\", \"$AccountingID\", \"$DefaultSalesman\", \"$JobDepth\")";
			$insert = new Customers ();
			$insert->CustomerID = $CustomerID;
			$insert->AccountID = $AccountID;
			$insert->Status = $Status;
			$insert->DefPaymentTerms = $DefPaymentTerms;
			$insert->DefShipTerms = $DefShipTerms;
			$insert->TaxRate = $TaxRate;
			$insert->Name = $Name;
			$insert->Number = $Number;
			$insert->DateCreated = $DateCreated;
			$insert->DateLastModified = $DateLastModified;
			$insert->LastChangedUser = $LastChangedUser;
			$insert->CreditLimit = $CreditLimit;
			$insert->TaxExemptNumber = $TaxExemptNumber;
			$insert->Note = $Note;
			$insert->ActiveFlag = $ActiveFlag;
			$insert->AccountingID = $AccountingID;
			$insert->DefaultSalesman = $DefaultSalesman;
			$insert->JobDepth = $JobDepth;
			$insert->date_added = date ( "Y-m-d H:i:s" );
			$insert->save ();
			self::connection_log ( 'customers_list_add', $add_sql );
			//
			
			$attention = $customerAddress ["$Name"] ['AddressContact'];
			$street = $customerAddress ["$Name"] ['Address'];
			$city = $customerAddress ["$Name"] ['City'];
			$zip = $customerAddress ["$Name"] ['Zip'];
			$state = $customerAddress ["$Name"] ['State'];
			$country = $customerAddress ["$Name"] ['Country'];
			$log_data = "Name: $Name | Street: $street";
			self::data_log ( 'customer.log', $log_data );
			$add_sql = "INSERT INTO " . tbpre . "customeraddresses (id, name, attn, street, city, zip, state, country) VALUES (\"$CustomerID\", \"$Name\", \"$attention\", \"$street\", \"$city\", \"$zip\", \"$state\", \"$country\")";
			$insert = new CustomerAddresses ();
			$insert->id = $CustomerID;
			$insert->name = $Name;
			$insert->attn = $attention;
			$insert->street = $street;
			$insert->city = $city;
			$insert->zip = $zip;
			$insert->state = $state;
			$insert->country = $country;
			$insert->date_added = date ( "Y-m-d H:i:s" );
			$insert->save ();
			self::connection_log ( 'customers_list_add', $add_sql );
		}
	}
	public static function get_vendors($output = NULL) {
		// API Call: <VendorListRq></VendorListRq>
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		self::truncate_log ( 'vendors.log' );
		if ($fishbowlapi->statusCode != 1000) {
			self::connection_log ( 'get_vendors', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
		} else {
			self::connection_log ( 'get_vendors', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
			
			$fishbowlapi->exportRq ( 'ExportVendors' );
			if ($fishbowlapi->statusCode == 1000) {
				if (! empty ( $fishbowlapi->statusMsg )) {
					self::connection_log ( 'ExportVendors', "Export: " . $fishbowlapi->statusMsg );
				}
			}
			$vendor_addresses = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			$fishbowlapi->getVendor ( 'List' );
			$vendors = $fishbowlapi->result ['FbiMsgsRs'] ['VendorListRs'] ['Vendor'];
			if (is_array ( $vendors )) {
				self::vendorslistadd ( $vendors, $vendor_addresses );
			}
			self::connection_log ( 'get_vendors', "End: Getting Vendors" );
		}
		$fishbowlapi->closeConnection ();
	}
	
	/**
	 * Process and put data in vendor and vendor address tables
	 * 
	 * @param unknown $vendors
	 * @param unknown $vendor_addresses
	 */
	public static function vendorslistadd($vendors, $vendor_addresses) {
		self::connection_log ( 'vendorslistadd', "Process: Vendor List Add" );
		foreach ( $vendor_addresses as $key => $value ) {
			if (! function_exists ( 'str_getcsv' )) {
				$address = self::csv2array ( $value );
			} else {
				$address = str_getcsv ( $value );
			}
			if ($address [4] == "true") {
				$id = $address [0];
				$vendorAddress [$id] ['AddressName'] = $address [1];
				$vendorAddress [$id] ['AddressContact'] = $address [2];
				$vendorAddress [$id] ['AddressType'] = $address [3];
				$vendorAddress [$id] ['IsDefault'] = $address [4];
				$vendorAddress [$id] ['Address'] = $address [5];
				$vendorAddress [$id] ['City'] = $address [6];
				$vendorAddress [$id] ['State'] = $address [7];
				$vendorAddress [$id] ['Zip'] = $address [8];
				$vendorAddress [$id] ['Country'] = $address [9];
			}
		}
		foreach ( $vendors as $key => $value ) {
			$VendorID = $value->VendorID;
			$AccountID = $value->AccountID;
			$Status = $value->Status;
			$DefPaymentTerms = $value->DefPaymentTerms;
			$DefShipTerms = $value->DefShipTerms;
			$Name = $value->Name;
			$Number = $value->Number;
			$DateCreated = $value->DateCreated;
			$DateModified = $value->DateModified;
			$LastChangedUser = $value->LastChangedUser;
			$CreditLimit = $value->CreditLimit;
			$Note = $value->Note;
			$ActiveFlag = $value->ActiveFlag;
			$AccountingID = $value->AccountingID;
			$AccountingHash = $value->AccountingHash;
			
			// $query = "INSERT INTO ".tbpre."vendors (VendorID, AccountID, Status, DefPaymentTerms, DefShipTerms, Name, Number, DateCreated, DateModified, LastChangedUser, CreditLimit, Note, ActiveFlag, AccountingID, AccountingHash)
			// VALUES (\"$VendorID\", \"$AccountID\", \"$Status\", \"$DefPaymentTerms\", \"$DefShipTerms\", \"$Name\", \"$Number\", \"$DateCreated\", \"$DateModified\", \"$LastChangedUser\", \"$CreditLimit\", \"$Note\", \"$ActiveFlag\", \"$AccountingID\", \"$AccountingHash\")
			// ON DUPLICATE KEY UPDATE
			// Status = \"$Status\", DefPaymentTerms = \"$DefPaymentTerms\", DefShipTerms = \"$DefShipTerms\", Name = \"$Name\", Number = \"$Number\", DateCreated = \"$DateCreated\", DateModified = \"$DateModified\", LastChangedUser = \"$LastChangedUser\", CreditLimit = \"$CreditLimit\", Note = \"$Note\", ActiveFlag = \"$ActiveFlag\", AccountingID = \"$AccountingID\", AccountingHash = \"$AccountingHash\"";
			
			$query = Vendors::where ( 'VendorID', $VendorID );
			if ($query->count () > 0) {
				$update = Vendors::find ( $VendorID );
				$update->Status = $Status;
				$update->DefPaymentTerms = $DefPaymentTerms;
				$update->DefShipTerms = $DefShipTerms;
				$update->Name = $Name;
				$update->Number = $Number;
				$update->DateCreated = $DateCreated;
				$update->DateModified = $DateModified;
				$update->LastChangedUser = $LastChangedUser;
				$update->CreditLimit = $CreditLimit;
				$update->Note = $Note;
				$update->ActiveFlag = $ActiveFlag;
				$update->AccountingID = $AccountingID;
				$update->AccountingHash = $AccountingHash;
				$update->save ();
			} else {
				$insert = new Vendors ();
				$insert->VendorID = $VendorID;
				$insert->Status = $Status;
				$insert->DefPaymentTerms = $DefPaymentTerms;
				$insert->DefShipTerms = $DefShipTerms;
				$insert->Name = $Name;
				$insert->Number = $Number;
				$insert->DateCreated = $DateCreated;
				$insert->DateModified = $DateModified;
				$insert->LastChangedUser = $LastChangedUser;
				$insert->CreditLimit = $CreditLimit;
				$insert->Note = $Note;
				$insert->ActiveFlag = $ActiveFlag;
				$insert->AccountingID = $AccountingID;
				$insert->AccountingHash = $AccountingHash;
				$insert->save ();
			}
			
			$attention = $vendorAddress ["$Name"] ['AddressContact'];
			$street = $vendorAddress ["$Name"] ['Address'];
			$city = $vendorAddress ["$Name"] ['City'];
			$zip = $vendorAddress ["$Name"] ['Zip'];
			$state = $vendorAddress ["$Name"] ['State'];
			$country = $vendorAddress ["$Name"] ['Country'];
			
			// $query = "INSERT INTO ".tbpre."vendoraddresses (name, attn, street, city, zip, state, country) VALUES (\"$Name\", \"$attention\", \"$street\", \"$city\", \"$zip\", \"$state\", \"$country\") ON DUPLICATE KEY UPDATE attn = \"$attention\", street = \"$street\", city = \"$city\", zip = \"$zip\", state = \"$state\", country = \"$country\"";
			
			$query = VendorAddresses::where ( 'name', $Name );
			if ($query->count () > 0) {
				$update = VendorAddresses::find ( $Name );
				$update->attn = $attention;
				$update->street = $street;
				$update->city = $city;
				$update->zip = $zip;
				$update->state = $state;
				$update->country = $country;
				$update->save ();
			} else {
				$insert = new VendorAddresses ();
				$insert->name = $Name;
				$insert->attn = $attention;
				$insert->street = $street;
				$insert->city = $city;
				$insert->zip = $zip;
				$insert->state = $state;
				$insert->country = $country;
				$insert->save ();
			}
			
			$log_data = "Vendor: $Name | Street: $street";
			self::data_log ( 'vendors.log', $log_data );
		}
	}
	public static function get_onorder() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		if ($fishbowlapi->statusCode != 1000) {
			self::connection_log ( 'get_onorder', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
		} else {
			self::connection_log ( 'get_onorder', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
			
			self::truncate_log ( 'onorder.log' );
			$fishbowlapi->exportRq ( 'ExportPurchaseOrder' );
			$parts = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			
			$statusArray = array (
					20 => "Issued",
					40 => "Partial" 
			);
			// $clearOnOrder = "UPDATE ".tbpre."inventory SET onorder = 0"
			$clearOnOrder = Inventory::update ( [ 
					'onorder' => '0' 
			] );
			
			foreach ( $parts as $key => $value ) {
				$string = $value;
				$temp = fopen ( "php://memory", "rw" );
				fwrite ( $temp, $string );
				fseek ( $temp, 0 );
				$array = fgetcsv ( $temp );
				$flag = $array [0];
				if ($flag == "PO") {
					$poNum = $array [1];
					$poStatus = $array [2];
					$poVendor = $array [3];
					$log_data = "Part: $poNum | Status: $poStatus | Vendor: $poVendor";
					self::data_log ( 'onorder.log', $log_data );
				}
				if ($flag == "Item" && $array [1] == 10) {
					$itemNum = $array [2];
					$partQuantity = $array [4];
					$fulfilledQuantity = $array [5];
					$pickedQuantity = $array [6];
					$uom = $array [7];
					if ($poStatus == 20 || $poStatus == 40) {
						$preTotal = $poArray ['Calculation'] ["$itemNum"] ['value'];
						$poArray ['Calculation'] ["$itemNum"] ['value'] = $poArray ['Calculation'] ["$itemNum"] ['value'] + ($partQuantity - $fulfilledQuantity);
						$postTotal = $poArray ['Calculation'] ["$itemNum"] ['value'];
						if ($postTotal > 0) {
							$poArray ['Calculation'] ["$itemNum"] ['uom'] = $uom;
						}
					}
				}
			}
			foreach ( $poArray ['Calculation'] as $key => $value ) {
				$date = date ( "Y-m-d H:i:s" );
				// $update = "UPDATE ".tbpre."inventory SET onorder = '".$value['value']."' WHERE number = '$key'"
				
				$update = Inventory::find ( $key );
				$update->onorder = $value ['value'];
				$update->save ();
			}
			self::connection_log ( 'get_onorder', "End: Getting On Orders" );
		}
		
		$fishbowlapi->closeConnection ();
	}
	public static function get_qtyshipped() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		if ($fishbowlapi->statusCode != 1000) {
			self::connection_log ( 'get_qtyshipped', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
		} else {
			self::connection_log ( 'get_qtyshipped', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
			self::truncate_log ( 'qtyshipped.log' );
			$year = gambaTerm::year_by_status ( 'C' );
			$fishbowlapi->exportRq ( 'ExportSalesOrder' );
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'ExportSalesOrder', "Export: " . $fishbowlapi->statusMsg );
			}
			// "UPDATE ".tbpre."inventory SET quantityshipped = 0"
			$update = Inventory::update ( [ 
					'quantityshipped' => '0' 
			] );
			
			$statusArray = array (
					10 => "Estimate",
					20 => "Issued",
					25 => "In Progress",
					60 => "Fulfilled",
					70 => "Closed Short",
					80 => "Void" 
			);
			$salesOrders = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			foreach ( $salesOrders as $key => $value ) {
				$string = $value;
				$temp = fopen ( "php://memory", "rw" );
				fwrite ( $temp, $string );
				fseek ( $temp, 0 );
				$array = fgetcsv ( $temp );
				$flag = $array [0];
				if ($flag == "SO") {
					$soNum = $array [1];
					$soStatus = $array [2];
					$FulfillmentDate = date ( "Y", strtotime ( $array [30] ) );
					if ($soStatus == 60) {
						$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Status'] = "$soStatus - " . $statusArray [$soStatus] . " - " . $FulfillmentDate;
					}
				}
				if ($flag == "Item") {
					if ($soStatus == 60) {
						$SOItemTypeID = $array [1];
						$ProductNumber = $array [2];
						$ProductQuantity = $array [3];
						$UOM = $array [4];
						$ProductPrice = $array [5];
						$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['SOItemTypeID'] = $SOItemTypeID;
						$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['ProductQuantity'] = $ProductQuantity;
						$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['UOM'] = $UOM;
						$soArray ["$soStatus"] ["$FulfillmentDate"] ["$soNum"] ['Products'] ["$ProductNumber"] ['ProductPrice'] = $ProductPrice;
					}
				}
			}
			foreach ( $soArray [60] [$year] as $key => $value ) {
				foreach ( $value ['Products'] as $prodNum => $prodValues ) {
					$qtyShipped ["$prodNum"] = $qtyShipped ["$prodNum"] + $prodValues ['ProductQuantity'];
				}
			}
			$date = date ( "Y-m-d" );
			foreach ( $qtyShipped as $key => $value ) {
				// $query = "UPDATE ".tbpre."inventory SET quantityshipped = '$value' WHERE number = '$key'";
				$update = Inventory::find ( $key );
				$update->quantityshipped = $value;
				$update->save ();
			}
			self::connection_log ( 'get_qtyshipped', "End: Getting Quantity Shipped" );
		}
		$fishbowlapi->closeConnection ();
	}
	public static function get_inventory_quantities() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		if ($fishbowlapi->statusCode != 1000) {
			self::connection_log ( 'get_inventory_quantities', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
		} else {
			self::connection_log ( 'get_inventory_quantities', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
			self::truncate_log ( 'get_inventory_quantities.log' );
			
			$fishbowlapi->exportRq ( 'ExportPart' );
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'ExportPart', "Export: " . $fishbowlapi->statusMsg );
			}
			$parts = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			$parts = self::process_inv_parts ( $parts );
			if (is_array ( $parts )) {
				foreach ( $parts as $key => $value ) {
					$partNum = $key;
					if ($value ['active'] == "true") {
						$fishbowlapi->getInvQty ( $partNum );
						$quantities = $fishbowlapi->result;
						$availableSale = 0;
						$quantityOnHand = 0;
						$quantityCommitted = 0;
						if (is_array ( $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] )) {
							// InvQty exists
							if (array_key_exists ( '0', $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] )) {
								// Multiple Locations
								foreach ( $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] as $key => $value ) {
									$availableSale += $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->QtyAvailable;
									$quantityOnHand += $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->QtyOnHand;
								}
							} else {
								// Single Location
								$availableSale = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] ['QtyAvailable'];
								$quantityOnHand = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] ['QtyOnHand'];
							}
							$log_data = "Part: $partNum | Available Sale: $availableSale | Quantity On Hand: $quantityOnHand";
							self::data_log ( 'get_inventory_quantities.log', $log_data );
						}
						if ($availableSale > 0) {
							$i ++;
						}
						// $query = "UPDATE ".tbpre."inventory SET availablesale = '$availableSale', quantityonhand = '$quantityOnHand', updated = '".date("Y-m-d H:i:s")."' WHERE number = '$partNum'";
						$update = Inventory::find ( $partNum );
						$update->availablesale = $availableSale;
						$update->quantityonhand = $quantityOnHand;
						$update->updated = date ( "Y-m-d H:i:s" );
						$update->save ();
					}
				}
				self::connection_log ( 'get_inventory_quantities', "Parts Retrieved from Inventory Quantities" );
			} else {
				self::connection_log ( 'get_inventory_quantities', "No Parts from Inventory Quantities" );
			}
			self::connection_log ( 'get_inventory_quantities', "End: Getting Inventory Quantities" );
		}
		
		$fishbowlapi->closeConnection ();
	}
	
	/**
	 * Get Part Standard Costs
	 * 
	 * @return array
	 */
	public static function get_standardcost() {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		if ($fishbowlapi->statusCode != 1000) {
			self::connection_log ( 'get_standardcost', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
		} else {
			self::connection_log ( 'get_standardcost', "Connect: " . $fishbowlapi->statusCode . " - " . $fishbowlapi->statusMsg );
			self::truncate_log ( 'standardcost.log' );
			$fishbowlapi->exportRq ( 'ExportPartStandardCost' );
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'ExportPartStandardCost', "Export: " . $fishbowlapi->statusMsg );
			}
			$costs = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			
			$partCosts = self::standardCost ( $costs );
			return $partCosts;
		}
		$fishbowlapi->closeConnection ();
	}
	
	/**
	 * Format the standard costs array and update parts table
	 * 
	 * @param unknown $costs
	 * @return unknown
	 */
	private static function standardCost($costs) {
		self::connection_log ( 'standardCost', "Process: Standard Cost Process" );
		foreach ( $costs as $key => $value ) {
			if ($key != 0) {
				if (! function_exists ( 'str_getcsv' )) {
					$part = self::csv2array ( $value );
				} else {
					$part = str_getcsv ( $value );
				}
				$number = $part [0];
				$partCosts [$number] = $cost = $part [2];
				// Update Parts table with standard cost
				// "UPDATE ".tbpre."parts SET cost = '$cost', fbcost = '$cost' WHERE number = '$number'"
				$update = Parts::find ( $number );
				$update->cost = $cost;
				$update->fbcost = $cost;
				$update->save ();
				$log_data = "ID: $number | Cost: " . $part [2];
				self::data_log ( 'standardcost.log', $log_data );
			}
		}
		// No need to return array since we are now updating the parts table
		// return $partCosts;
	}
	public static function deprecated_get_parts($partcosts, $uomids) {
		$fb_server = config ( 'fishbowl.FB_SERVER' );
		$fb_port = config ( 'fishbowl.FB_PORT' );
		$fb_login = config ( 'fishbowl.FB_LOGIN' );
		$fb_pass = config ( 'fishbowl.FB_PASS' );
		$fishbowlapi = new FishbowlAPI ( $fb_server, $fb_port );
		$fishbowlapi->Login ( $fb_login, $fb_pass );
		$fberror = new FBErrorCodes ();
		if ($fishbowlapi->statusCode != 1000) {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'get_parts', "Connect: " . $code . " - " . $fishbowlapi->statusMsg );
		} else {
			$code = $fberror->checkCode ( $fishbowlapi->statusCode );
			self::connection_log ( 'get_parts', "Connect: " . $code . " - " . $fishbowlapi->statusMsg );
			
			self::truncate_log ( 'inventory_quantity.log' );
			// Parts
			$fishbowlapi->exportRq ( 'ExportPart' );
			if ($fishbowlapi->statusCode == 1000) {
				self::connection_log ( 'ExportPart', "Export: " . $fishbowlapi->statusMsg );
			}
			$parts = $fishbowlapi->result ['FbiMsgsRs'] ['ExportRs'] ['Rows'] ['Row'];
			$partNumbers = self::process_parts ( $parts, $partCosts, $uomids );
			self::connection_log ( 'self::parts', "Processed Parts" );
			$parts = $partNumbers ['partNumbers'];
			
			// Materials
			// Begin Processing Parts into Inventory Material List.
			if (is_array ( $parts )) {
				foreach ( $parts as $key => $value ) {
					$partNum = trim ( $key );
					$cost = $value ['cost'];
					$description = htmlspecialchars ( $value ['description'] );
					$uom = $value ['uom'];
					
					$fishbowlapi->getInvQty ( $partNum );
					$quantities = $fishbowlapi->result;
					// Because of Inventory in more than one location in the location group treat InvQty as an array with multiple values that need to be summed for QtyAvailable and QtyOnHand.
					$availableSale = 0;
					$quantityOnHand = 0;
					$quantityCommitted = 0;
					if (is_array ( $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] )) {
						
						if (array_key_exists ( '0', $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] )) {
							
							foreach ( $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] as $key => $value ) {
								$availSale = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->QtyAvailable;
								$fbpartid = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->Part->PartID;
								$availableSale = $availableSale + $availSale;
								$qtyOnHand = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->QtyOnHand;
								$quantityOnHand = $quantityOnHand + $qtyOnHand;
								$quantityCommitted = $quantityCommitted + $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] [$key]->QtyCommitted;
							}
						} else {
							$availableSale = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] ['QtyAvailable'];
							$fbpartid = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty']->Part->PartID;
							$fb_active = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty']->Part->ActiveFlag;
							$quantityOnHand = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] ['QtyOnHand'];
							$quantityCommitted = $quantities ['FbiMsgsRs'] ['InvQtyRs'] ['InvQty'] ['QtyCommitted'];
						}
					}
					$log_data = "Part: $partNum | Available Sale: $availableSale | Quantity On Hand: $quantityOnHand";
					self::data_log ( 'inventory_quantity.log', $log_data );
					// Description, UoM and Cost set in GAMBA
					// $result = "SELECT 1 FROM ".tbpre."parts WHERE number = '$partNum'"
					$query = Parts::find ( $partNum );
					if ($query->count () == 0 && $description != "") {
						// $query = "INSERT INTO ".tbpre."parts (number, description, suom, fbuom, cost, fbcost, inventory, fishbowl) VALUES ('$partNum', \"$description\", '$uom', '$uom', '$cost', '$cost', 'true', 'true')";
						$insert = new Parts ();
						$insert->number = $partNum;
						$insert->description = $description;
						$insert->suom = $uom;
						$insert->fbuom = $uom;
						$insert->cost = $cost;
						$insert->fbcost = $cost;
						$insert->inventory = 'true';
						$insert->fishbowl = 'true';
						$insert->save ();
						
						// $query = "INSERT INTO ".tbpre."inventory (number, fb_partid, fb_vendorid, fb_active, availablesale, quantityonhand, updated) VALUES ('$partNum', \"$fbpartid\", '', '$fb_active', '$availableSale', '$quantityOnHand', '".date("Y-m-d H:i:s")."')";
						$insert = new Inventory ();
						$insert->number = $partNum;
						$insert->fb_partid = $fbpartid;
						$insert->fb_vendorid = '';
						$insert->fb_active = $fb_active;
						$insert->availablesale = $availableSale;
						$insert->quantityonhand = $quantityOnHand;
						$insert->updated = date ( "Y-m-d H:i:s" );
						$insert->save ();
					} else {
						// $query = "UPDATE ".tbpre."inventory SET quantityonhand = '$quantityOnHand', availablesale = '$availableSale', updated = '".date("Y-m-d H:i:s")."' WHERE number = '$partNum'";
						$update = Inventory::find ( $partNum );
						$update->quantityonhand = $quantityOnHand;
						$update->availablesale = $availableSale;
						$update->updated = date ( "Y-m-d H:i:s" );
						$update->save ();
					}
					$materials ['queries'] ["$partNum"] = "Part: $partNum - $description (" . $query . ")";
				}
				self::connection_log ( 'get_inventory_quantities', "Parts Returned to Process" );
				self::connection_log ( 'self::parts', "End: Processing Parts" );
			} else {
				self::connection_log ( 'get_inventory_quantities', "No Parts Returned to Process" );
			}
			self::connection_log ( 'get_parts', "End: Getting Parts" );
		}
		
		$fishbowlapi->closeConnection ();
	}
	
	/**
	 * Process Part Data from Fishbowl
	 * Return Data to then get Quantity on Hand and Available Sale
	 * 
	 * @param unknown $parts
	 * @return unknown
	 */
	private static function process_parts($parts) {
		self::connection_log ( 'process_parts', "Process: Parts" );
		if (is_array ( $parts )) {
			foreach ( $parts as $key => $value ) {
				if (! function_exists ( 'str_getcsv' )) {
					$part = self::csv2array ( $value );
				} else {
					$part = str_getcsv ( $value );
				}
				if ($part [5] == "Inventory") {
					$part_num = trim ( $part [0] ); // PartNumber
					$description = htmlspecialchars ( $part [1] ); // PartDescription
					$uomCode = $part [3]; // UOM
					$active = $part [6]; // Active
					$cost = $partCosts ["$part_num"];
					$date = date ( "Y-m-d H:i:s" );
					$array ['partNumbers'] ["$part_num"] ['cost'] = $cost;
					$array ['partNumbers'] ["$part_num"] ['description'] = $description;
					$array ['partNumbers'] ["$part_num"] ['uom'] = $uomCode;
					// $query = "INSERT INTO ".tbpre."parts (number, description, approved, inventory, fishbowl, suom, fbuom, created, updated) VALUES ('$part_num', \"$description\", 0, 'true', 'true', '$uomCode', '$uomCode', '$date', '$date') ON DUPLICATE KEY UPDATE fbuom = '$uomCode', updated = '$date'";
					// $log_data = "$query\r";
					$query = Parts::find ( $part_num );
					if ($query->count () > 0) {
						$update = Parts::find ( $part_num );
						$update->fbuom = $uomCode;
						$update->updated = $date;
						$update->save ();
					} else {
						$insert = new Parts ();
						$insert->number = $part_num;
						$insert->description = $description;
						$insert->approved = '0';
						$insert->inventory = 'true';
						$insert->fishbowl = 'true';
						$insert->suom = $uomCode;
						$insert->fbuom = $uomCode;
						$insert->created = $date;
						$insert->updated = $date;
						$insert->save ();
					}
					
					// $query = "INSERT INTO ".tbpre."inventory (number, fb_active, updated) VALUES ('$part_num', '$active', '$date') ON DUPLICATE KEY UPDATE fb_active = '$active', updated = '$date'";
					// $log_data .= "$query\r";
					$query = Inventory::find ( $part_num );
					if ($query->count () > 0) {
						$update = Inventory::find ( $part_num );
						$update->fb_active = $active;
						$update->updated = $date;
						$update->save ();
					} else {
						$insert = new Inventory ();
						$insert->number = $part_num;
						$insert->fb_active = $active;
						$insert->updated = $date;
						$insert->save ();
					}
					
					// $log_data = "Part: $part_num $description";
					self::data_log ( 'parts.log', $log_data );
				}
			}
		}
		return $array;
	}
	private static function process_inv_parts($array) {
		self::connection_log ( 'process_parts', "Process: Inventory Parts" );
		if (is_array ( $array )) {
			foreach ( $array as $key => $value ) {
				if (! function_exists ( 'str_getcsv' )) {
					$part = self::csv2array ( $value );
				} else {
					$part = str_getcsv ( $value );
				}
				if ($part [5] == "Inventory") {
					$number = $part [0]; // PartNumber
					$return ["$number"] ['description'] = $description = htmlspecialchars ( $part [1] ); // PartDescription
					$return ["$number"] ['uom_code'] = $uomCode = $part [3]; // UOM
					$return ["$number"] ['active'] = $part [6]; // Active
				}
			}
		}
		return $return;
	}
	private static function csv2array($string) {
		$temp = fopen ( "php://memory", "rw" );
		fwrite ( $temp, $string );
		fseek ( $temp, 0 );
		$array = fgetcsv ( $temp );
		foreach ( $array as $value ) {
			$val = stripcslashes ( $value );
			$arr [] = $val;
		}
		return $arr;
	}
	public static function fishbowl_schedule() {
		// $sql = "SELECT value FROM ".tbpre."config WHERE field = 'fbsync_schedule'";
		$row = Config::select ( 'value' )->where ( 'field', 'fbsync_schedule' )->first ();
		$array = json_decode ( $row->value, true );
		return $array;
	}
	public static function data_update_fishbowl_sync_schedule($array) {
		
		// echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
		$json_array = json_encode ( $array ['schedule'] );
		// "UPDATE ".tbpre."config SET value = '$json_array' WHERE field = 'fbsync_schedule'"
		$update = Config::find ( 'fbsync_schedule' );
		// $toSQL = $update->toSql();
		// dd($toSQL);
		$update->value = $json_array;
		$update->save ();
	}
	
	// Moved to resources/views/app/settings/fishbowlsync.blade.php
	public static function view_fishbowl_control_sync($array) {
		$url = url ( '/' );
		$content_array ['side_nav'] = gambaNavigation::settings_nav ();
		$schedule = self::fishbowl_schedule ();
		$content_array ['page_title'] = "Execute Fishbowl Sync";
		$content_array ['content'] .= '<ul class="pagination">';
		// Sync All
		$content_array ['content'] .= '<li';
		if ($array ['view'] == "all") {
			$content_array ['content'] .= ' class="disabled"';
			$sync = " All";
			$sync_action = "sync_all";
		}
		$content_array ['content'] .= '><a href="' . $url . '/settings/fishbowl?view=all">All</a></li>';
		// Sync Parts
		$content_array ['content'] .= '<li';
		if ($array ['view'] == "parts") {
			$content_array ['content'] .= ' class="disabled"';
			$sync = " Parts";
			$sync_action = "sync_parts";
		}
		$content_array ['content'] .= '><a href="' . $url . '/settings/fishbowl?view=parts">Parts and UoMs</a></li>';
		// Sync UoMs
		$content_array ['content'] .= '<li';
		if ($array ['view'] == "uoms") {
			$content_array ['content'] .= ' class="disabled"';
			$sync = " UoMs";
			$sync_action = "sync_uoms";
		}
		$content_array ['content'] .= '><a href="' . $url . '/settings/fishbowl?view=uoms">Just UoMs</a></li>';
		// Sync Inventory
		$content_array ['content'] .= '<li';
		if ($array ['view'] == "inventory") {
			$content_array ['content'] .= ' class="disabled"';
			$sync = " Inventory";
			$sync_action = "sync_inventory";
		}
		$content_array ['content'] .= '><a href="' . $url . '/settings/fishbowl?view=inventory">Inventory</a></li>';
		// Sync The Rest
		$content_array ['content'] .= '<li';
		if ($array ['view'] == "rest") {
			$content_array ['content'] .= ' class="disabled"';
			$sync = " the Rest";
			$sync_action = "sync_rest";
		}
		$content_array ['content'] .= '><a href="' . $url . '/settings/fishbowl?view=rest">The Rest</a></li>';
		// Sync Customers
		$content_array ['content'] .= '<li';
		if ($array ['view'] == "customers") {
			$content_array ['content'] .= ' class="disabled"';
			$sync = " Customers";
			$sync_action = "sync_customers";
		}
		$content_array ['content'] .= '><a href="' . $url . '/settings/fishbowl?view=customers">Customers</a></li>';
		// Schedule
		$content_array ['content'] .= '<li><a data-reveal-id="schedule" href="#">Sync Schedule</a></li>';
		$content_array ['content'] .= "</ul>\n";
		$content_array ['content'] .= "<p>Date Time: " . date ( "F j, Y g:i a T" ) . "</p>";
		$date = date ( "YmdHis" );
		if ($array ['alert'] == 1) {
			$content_array ['content'] .= <<<EOT
						<div data-alert class="alert-box success radius">
							Syncing with Fishbowl. You can observe the log file below.
							<a href="#" class="close">&times;</a>
						</div>
EOT;
		}
		if ($array ['view'] != "") {
			$content_array ['content'] .= '<p><a href="' . $url . '/settings/' . $sync_action . '" class="button small radius">Sync' . $sync . ' with Fishbowl</a></p>';
		}
		$content_array ['content'] .= <<<EOT
		
			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/logs/connection.log?{$date}', function(data) {
							var logfile = data;
							$("#logfile").html("<pre>" + logfile + "</pre>");
							setTimeout(functionToLoadFile, 5000);
						});
					};
					setTimeout(functionToLoadFile, 10);
				});
			</script>
			<div id="logfile" class="panel radius"></div>
EOT;
		if ($array ['view'] == "uoms") {
			
			$content_array ['content'] .= <<<EOT
			
			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/logs/uoms.log?{$date}', function(data) {
							var logfile = data;
							$("#uomlogfile").html("<pre>" + logfile + "</pre>");
							setTimeout(functionToLoadFile, 5000);
						});
					};
					setTimeout(functionToLoadFile, 10);
				});
			</script>
			<div id="uomlogfile" class="panel radius"></div>
EOT;
		}
		$content_array ['content'] .= <<<EOT
			<!-- Add Available Sale Data -->
			<div id="schedule" class="reveal-modal" data-reveal aria-labelledby="myModalLabel" aria-hidden="true" role="dialog">
				<h2 id="modalTitle">Fishbowl Sync Schedule</h2>
				<form method="post" action="{$url}/settings/fishbowl_schedule" enctype="multipart/form-data" name="add_theme" class="form-horizontal">
EOT;
		$content_array ['content'] .= csrf_field ();
		$content_array ['content'] .= <<<EOT
					<p>Select the day of the week that you would like Gamba to synchronize data for each type.</p>
					<div class="row">
								<label class="small-12 medium-4 large-4 columns">Units of Measure </label>
								<div class="small-12 medium-8 large-8 columns">
EOT;
		$content_array ['content'] .= ' <input type="checkbox" name="schedule[uom][1]" value="1" ';
		if ($schedule ['uom'] [1] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Mon ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[uom][2]" value="1" ';
		if ($schedule ['uom'] [2] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Tue ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[uom][3]" value="1" ';
		if ($schedule ['uom'] [3] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Wed ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[uom][4]" value="1" ';
		if ($schedule ['uom'] [4] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Thu ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[uom][5]" value="1" ';
		if ($schedule ['uom'] [5] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Fri ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[uom][6]" value="1" ';
		if ($schedule ['uom'] [6] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sat ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[uom][0]" value="1" ';
		if ($schedule ['uom'] [0] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sun ';
		$content_array ['content'] .= <<<EOT
								</div>
							</div>
							
							<div class="row">
								<label class="small-12 medium-4 large-4 columns">Customers </label>
								<div class="small-12 medium-8 large-8 columns">
EOT;
		$content_array ['content'] .= ' <input type="checkbox" name="schedule[customers][1]" value="1" ';
		if ($schedule ['customers'] [1] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Mon ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[customers][2]" value="1" ';
		if ($schedule ['customers'] [2] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Tue ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[customers][3]" value="1" ';
		if ($schedule ['customers'] [3] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Wed ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[customers][4]" value="1" ';
		if ($schedule ['customers'] [4] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Thu ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[customers][5]" value="1" ';
		if ($schedule ['customers'] [5] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Fri ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[customers][6]" value="1" ';
		if ($schedule ['customers'] [6] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sat ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[customers][0]" value="1" ';
		if ($schedule ['customers'] [0] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sun ';
		$content_array ['content'] .= <<<EOT
								</div>
							</div>
							
							<div class="row">
								<label class="small-12 medium-4 large-4 columns">Vendors </label>
								<div class="small-12 medium-8 large-8 columns">
EOT;
		$content_array ['content'] .= ' <input type="checkbox" name="schedule[vendors][1]" value="1" ';
		if ($schedule ['vendors'] [1] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Mon ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[vendors][2]" value="1" ';
		if ($schedule ['vendors'] [2] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Tue ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[vendors][3]" value="1" ';
		if ($schedule ['vendors'] [3] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Wed ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[vendors][4]" value="1" ';
		if ($schedule ['vendors'] [4] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Thu ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[vendors][5]" value="1" ';
		if ($schedule ['vendors'] [5] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Fri ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[vendors][6]" value="1" ';
		if ($schedule ['vendors'] [6] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sat ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[vendors][0]" value="1" ';
		if ($schedule ['vendors'] [0] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sun ';
		$content_array ['content'] .= <<<EOT
								</div>
							</div>
							
							<div class="row">
								<label class="small-12 medium-4 large-4 columns">Products </label>
								<div class="small-12 medium-8 large-8 columns">
EOT;
		$content_array ['content'] .= ' <input type="checkbox" name="schedule[products][1]" value="1" ';
		if ($schedule ['products'] [1] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Mon ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[products][2]" value="1" ';
		if ($schedule ['products'] [2] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Tue ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[products][3]" value="1" ';
		if ($schedule ['products'] [3] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Wed ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[products][4]" value="1" ';
		if ($schedule ['products'] [4] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Thu ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[products][5]" value="1" ';
		if ($schedule ['products'] [5] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Fri ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[products][6]" value="1" ';
		if ($schedule ['products'] [6] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sat ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[products][0]" value="1" ';
		if ($schedule ['products'] [0] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sun ';
		$content_array ['content'] .= <<<EOT
								</div>
							</div>
							
							<div class="row">
								<label class="small-12 medium-4 large-4 columns">Parts </label>
								<div class="small-12 medium-8 large-8 columns">
EOT;
		$content_array ['content'] .= ' <input type="checkbox" name="schedule[parts][1]" value="1" ';
		if ($schedule ['parts'] [1] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Mon ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[parts][2]" value="1" ';
		if ($schedule ['parts'] [2] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Tue ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[parts][3]" value="1" ';
		if ($schedule ['parts'] [3] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Wed ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[parts][4]" value="1" ';
		if ($schedule ['parts'] [4] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Thu ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[parts][5]" value="1" ';
		if ($schedule ['parts'] [5] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Fri ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[parts][6]" value="1" ';
		if ($schedule ['parts'] [6] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sat ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[parts][0]" value="1" ';
		if ($schedule ['parts'] [0] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sun ';
		$content_array ['content'] .= <<<EOT
								</div>
							</div>
							
							<div class="row">
								<label class="small-12 medium-4 large-4 columns">Inventory </label>
								<div class="small-12 medium-8 large-8 columns">
EOT;
		$content_array ['content'] .= ' <input type="checkbox" name="schedule[inventory][1]" value="1" ';
		if ($schedule ['inventory'] [1] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Mon ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[inventory][2]" value="1" ';
		if ($schedule ['inventory'] [2] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Tue ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[inventory][3]" value="1" ';
		if ($schedule ['inventory'] [3] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Wed ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[inventory][4]" value="1" ';
		if ($schedule ['inventory'] [4] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Thu ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[inventory][5]" value="1" ';
		if ($schedule ['inventory'] [5] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Fri ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[inventory][6]" value="1" ';
		if ($schedule ['inventory'] [6] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sat ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[inventory][0]" value="1" ';
		if ($schedule ['inventory'] [0] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sun ';
		$content_array ['content'] .= <<<EOT
								</div>
							</div>
							
							<div class="row">
								<label class="small-12 medium-4 large-4 columns">Qty Shipped </label>
								<div class="small-12 medium-8 large-8 columns">
EOT;
		$content_array ['content'] .= ' <input type="checkbox" name="schedule[qtyshipped][1]" value="1" ';
		if ($schedule ['qtyshipped'] [1] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Mon ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[qtyshipped][2]" value="1" ';
		if ($schedule ['qtyshipped'] [2] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Tue ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[qtyshipped][3]" value="1" ';
		if ($schedule ['qtyshipped'] [3] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Wed ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[qtyshipped][4]" value="1" ';
		if ($schedule ['qtyshipped'] [4] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Thu ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[qtyshipped][5]" value="1" ';
		if ($schedule ['qtyshipped'] [5] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Fri ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[qtyshipped][6]" value="1" ';
		if ($schedule ['qtyshipped'] [6] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sat ';
		$content_array ['content'] .= '<input type="checkbox" name="schedule[qtyshipped][0]" value="1" ';
		if ($schedule ['qtyshipped'] [0] == 1) {
			$content_array ['content'] .= "checked ";
		}
		$content_array ['content'] .= '/> Sun ';
		$content_array ['content'] .= <<<EOT
								</div>
							</div>
							
						<p>
							<button type="button" class="button small radius" aria-label="Close">Cancel</button>
							<button type="submit" class="button small radius success">Update Schedule</button>
					<?p>
					<input type="hidden" name="action" value="fishbowl_schedule" />
				</form>
			<a class="close-reveal-modal" aria-label="Close">&#215;</a>
		</div><!-- /.modal -->
EOT;
		$content_array ['foundation_js'] .= <<<EOT
EOT;
		return $content_array;
		// Debug::preformatted_arrays($schedule, 'fb_schedule', 'Fishbowl Schedule');
	}
}
