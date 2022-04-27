<?php
    namespace App\Gamba;

    use Illuminate\Support\Facades\Session;

    use App\Models\Config;
    use App\Models\CurrentParts;
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
    use App\Models\SupplyParts;
    use App\Models\VendorParts;
    use App\Models\Vendors;
    use App\Models\VendorAddresses;

    use App\Gamba\FishbowlAPI;
    use App\Gamba\FBErrorCodes;
    use App\Gamba\gambaTerm;
    use App\Gamba\gambaResupplyOrders;
    use App\Gamba\gambaCustomers;
    use App\Gamba\gambaFishbowl;
    use App\Gamba\gambaInventory;
    use App\Gamba\gambaNavigation;
    use App\Gamba\gambaProducts;
    use App\Gamba\gambaSupplies;
    use App\Gamba\gambaUsers;
    use App\Gamba\gambaLogs;

    class gambaFBSync {


    	public static function fishbowl_sync($output = NULL) {
	        $sync_schedule = gambaFishbowl::fishbowl_schedule();
	        $dayofweek = date("w");
	        $parts = CurrentParts::get();
	        // Units of Measure - Good
	        $result = self::uoms($output);
	        if($output == "flush") { echo "<p>UoMs: $result</p>"; ob_flush(); flush(); }
	        // Parts, Standard Costs, Qty, On Order, Quantity Shipped - Good
	        $result = "";
	        $result = self::parts($parts, $output);
	        if($output == "flush") { echo "<p>Parts: $result</p>"; ob_flush(); flush(); }
	        // 			// Customers and Addresses (Camp Locations)
	        $result = "";
	        $result = self::customers($output);
	        if($output == "flush") { echo "<p>Customers: $result</p>"; ob_flush(); flush(); }
	        // Vendors - Good
	        $result = "";
	        //             $result = self::vendors();
	        $result = gambaFishbowl::get_vendors($output);
	        if($output == "flush") { echo "<p>Vendors: Original Method</p>"; ob_flush(); flush(); }
	        // Products - Good
	        $result = "";
	        $result = self::products($parts, $output);
	        if($output == "flush") { echo "<p>Products: $result</p>"; ob_flush(); flush(); }
	        // Vendor Part Numbers - Good
	        $result = "";
	        $result = self::vendor_parts($parts, $output);
	        if($output == "flush") { echo "<p>Vendor Parts: $result</p>"; ob_flush(); flush(); }
	    }

	    public static function hourly_sync($output = NULL) {
	        gambaFishbowl::truncate_log('connection.log');
	        // $sync_schedule = gambaFishbowl::fishbowl_schedule();
	        // $dayofweek = date("w");
	        $parts = CurrentParts::get();
	        // if($sync_schedule['parts'][$dayofweek] == 1 || $array['sync_now'] == 1) {
	        $result = self::parts($parts, $output);
	        // }
	        return $result;
	    }

	    // Manual Syncs
	    public static function sync_all($output = NULL) {
	    	$action_id = gambaLogs::action_start_log('fishbowl_sync_all', "Sync All With Fishbowl");
	        gambaFishbowl::truncate_log('connection.log');
	        gambaFishbowl::connection_log('Manual Fishbowl Sync Started', "All");
	        self::fishbowl_sync($output);
	        gambaLogs::action_end_log($action_id);
	    }
	    public static function sync_parts($output = NULL) {
	    	$action_id = gambaLogs::action_start_log('fishbowl_sync_parts', "Sync Parts With Fishbowl");
	        gambaFishbowl::truncate_log('connection.log');
	        gambaFishbowl::connection_log('Manual Fishbowl Sync Started', "Parts and Inventory");
	        $parts = CurrentParts::get();
	        $result = self::parts($parts, $output);
	        gambaLogs::action_end_log($action_id);
	    }
	    public static function sync_uoms($output = NULL) {
	    	$action_id = gambaLogs::action_start_log('fishbowl_sync_uoms', "Sync UoMs With Fishbowl");
	        gambaFishbowl::truncate_log('connection.log');
	        gambaFishbowl::connection_log('Manual Fishbowl Sync Started', "Units of Measure");
	        self::uoms($output);
	        gambaLogs::action_end_log($action_id);
	    }
	    public static function sync_vendors($output = NULL) {
	    	$action_id = gambaLogs::action_start_log('fishbowl_sync_vendors', "Sync Vendors With Fishbowl");
	        gambaFishbowl::truncate_log('connection.log');
	        gambaFishbowl::connection_log('Manual Fishbowl Sync Started', "Vendors");
	        gambaFishbowl::get_vendors($output);
	        gambaLogs::action_end_log($action_id);
	    }
	    public static function sync_customers($output = NULL) {
	    	$action_id = gambaLogs::action_start_log('fishbowl_sync_customers', "Sync Customers With Fishbowl");
	        gambaFishbowl::truncate_log('connection.log');
	        gambaFishbowl::connection_log('Manual Fishbowl Sync Started', "Customers");
	        self::customers($output);
	        gambaLogs::action_end_log($action_id);
	    }
	    public static function sync_products($output = NULL) {
	    	$action_id = gambaLogs::action_start_log('fishbowl_sync_products', "Sync Products With Fishbowl");
	        gambaFishbowl::truncate_log('connection.log');
	        gambaFishbowl::connection_log('Manual Fishbowl Sync Started', "Products");
	        $parts = CurrentParts::get();
	        self::products($parts, $output);
	        gambaLogs::action_end_log($action_id);
	    }
	    public static function sync_vendor_parts($output = NULL) {
	    	$action_id = gambaLogs::action_start_log('fishbowl_sync_vendor_parts', "Sync Vendor Parts With Fishbowl");
	        gambaFishbowl::truncate_log('connection.log');
	        gambaFishbowl::connection_log('Manual Fishbowl Sync Started', "Vendor Parts");
	        $parts = CurrentParts::get();
	        $result = self::vendor_parts($parts, $output);
	        gambaLogs::action_end_log($action_id);
	    }

	    public static function customers($output = NULL) {
	        $fb_server = config('fishbowl.FB_SERVER');
	        $fb_port = config('fishbowl.FB_PORT');
	        $fb_login = config('fishbowl.FB_LOGIN');
	        $fb_pass = config('fishbowl.FB_PASS');
	        $fishbowlapi = new FishbowlAPI($fb_server, $fb_port);
	        $fishbowlapi->Login($fb_login, $fb_pass);
	        $fberror = new FBErrorCodes();
	        $code = $fberror->checkCode($fishbowlapi->statusCode);
	        $started = date("H:i:s");
	        $i = 0;
	        if ($fishbowlapi->statusCode == 1000) {
	            if($output == "flush") { echo "<h3>Customers</h3>"; ob_flush(); flush(); }
	            $fishbowlapi->executeNamedQueryRq('gambaCustomers');
	            // 		        echo "<pre>"; print_r($fishbowlapi); echo "</pre>"; ob_flush(); flush();
	            $customers = $fishbowlapi->result['FbiMsgsRs']['ExecuteQueryRs']['Rows']['Row'];
	            // 		        echo "<pre>"; print_r($customers); echo "</pre>"; ob_flush(); flush();
	            $delete = Customers::truncate();
	            $delete = CustomerAddresses::truncate();
	            foreach($customers as $key => $value) {
	                if($key > 0) {
	                    $i++;
	                    $customer_array = str_getcsv($value);
	                    // 		                if($output == "flush") { echo "<pre>"; print_r($customer_array); echo "</pre>"; ob_flush(); flush(); }
	                    $CustomerID = $customer_array[0]; //CustomerID
	                    // 		                echo "$CustomerID, "; ob_flush(); flush();
	                    $AccountID = $customer_array[1]; // AccountID
	                    $Status = $customer_array[2]; // Status
	                    $Name = $customer_array[3]; // Name
	                    $DefPaymentTerms = $customer_array[4]; // DefPaymentTerms
	                    $DefShipTerms = $customer_array[5]; // DefShipTerms
	                    $TaxRate = ""; // TaxRate // OMIT
	                    $Number = $customer_array[6]; // Number
	                    $DateCreated = $customer_array[7]; // DateCreated
	                    $DateLastModified = $customer_array[8]; // DateLastModified
	                    $LastChangedUser = $customer_array[9]; // LastChangedUser
	                    $CreditLimit = $customer_array[10]; // CreditLimit
	                    $TaxExemptNumber = ""; // TaxExemptNumber // OMIT
	                    $Note = $customer_array[11]; // Note
	                    $ActiveFlag = $customer_array[12]; // ActiveFlag
	                    $AccountingID = $customer_array[13]; // AccountingID
	                    $DefaultSalesman = ""; // DefaultSalesman // OMIT
	                    $JobDepth = $customer_array[14]; // JobDepth

	                    $attention = $customer_array[15]; // AddressContact
	                    $street = $customer_array[16]; // Address
	                    $city = $customer_array[17]; // City
	                    $zip = $customer_array[18]; // Zip
	                    $state = $customer_array[19]; // State
	                    $country = $customer_array[20]; // Country

	                    if($output == "flush") {
	                        echo "<p>$Name | ID: $CustomerID | Address: $street</p>";
	                        ob_flush(); flush();
	                    }

	                    $insert = new Customers;
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
	                    $insert->date_added = date("Y-m-d H:i:s");
	                    $insert->save();

	                    $insert = new CustomerAddresses;
	                    $insert->id = $CustomerID;
	                    $insert->name = $Name;
	                    $insert->attn = $attention;
	                    $insert->street = $street;
	                    $insert->city = $city;
	                    $insert->zip = $zip;
	                    $insert->state = $state;
	                    $insert->country = $country;
	                    $insert->date_added = date("Y-m-d H:i:s");
	                    $insert->save();
	                }
	            }
	            $ended = date("H:i:s");
	            $msg = "{$i} Rows Synced. ($started - $ended)";
	        } else {
	            $msg = "$started | Could not connect to Fishbowl.";
	        }
	        $fishbowlapi->closeConnection();
	        if($output == "flush") { echo "<p>$msg</p>"; ob_flush(); flush(); }
	        gambaFishbowl::connection_log("Customer Sync Completed", "Results:" . $msg);
	        return $msg;
	    }

	    public static function products($parts, $output = NULL) {
	        $fb_server = config('fishbowl.FB_SERVER');
	        $fb_port = config('fishbowl.FB_PORT');
	        $fb_login = config('fishbowl.FB_LOGIN');
	        $fb_pass = config('fishbowl.FB_PASS');
	        $fishbowlapi = new FishbowlAPI($fb_server, $fb_port);
	        $fishbowlapi->Login($fb_login, $fb_pass);
	        $fberror = new FBErrorCodes();
	        $code = $fberror->checkCode($fishbowlapi->statusCode);
	        $started = date("H:i:s");
	        $i = 0;
	        if ($fishbowlapi->statusCode == 1000) {
	            if($output == "flush") { echo "<h3>Products</h3>"; ob_flush(); flush(); }
	            foreach($parts as $values) {
	                $sql = "SELECT `PRODUCT`.`NUM`, `PRODUCT`.`DESCRIPTION`, CONCAT(`PRODUCT`.`PRICE`), `UOM`.`CODE`, `PART`.`NUM`
	FROM `PRODUCT`
	LEFT JOIN `UOM` ON `UOM`.`ID` = `PRODUCT`.`UOMID`
	LEFT JOIN `PART` ON `PART`.`ID` = `PRODUCT`.`PARTID`
	WHERE `PART`.`NUM` = '{$values->part}'";
	                $fishbowlapi->executeSqlQueryRq($sql);
	                $product_info = $fishbowlapi->result['FbiMsgsRs']['ExecuteQueryRs']['Rows']['Row'];
	                foreach($product_info as $key => $value) {
	                    if($key > 0) {
	                        $i++;
	                        $product_array = str_getcsv($value);
	                        // 		                    if($output == "flush") { echo "<pre>"; print_r($product_array); echo "</pre>"; ob_flush(); flush(); }
	                        if($output == "flush") {
	                            echo "<p>{$product_array[0]} | Description: $product_array[1] | Price: {$product_array[2]} | UoM: {$product_array[3]}</p>";
	                            ob_flush(); flush();
	                        }
	                        // 		                    echo "{$product_array[0]}, ";
	                        ob_flush(); flush();
	                        $query = Products::where('PartID', $product_array[4]);
	                        if($query->count() > 0) {
	                            $update = Products::find($product_array[0]);
	                            $update->Description = $product_array[1];
	                            $update->Price = $product_array[2];
	                            $update->UOM = $product_array[3];
	                            $update->PartID = $product_array[4];
	                            $update->updated = date("Y-m-d H:i:s");
	                            $update->save();
	                        } else {
	                            $insert = new Products;
	                            $insert->Num = $product_array[0];
	                            $insert->Description = $product_array[1];
	                            $insert->Price = $product_array[2];
	                            $insert->UOM = $product_array[3];
	                            $insert->PartID = $product_array[4];
	                            $insert->updated = date("Y-m-d H:i:s");
	                            $insert->save();
	                        }
	                    }
	                }
	            }
	            // 		        echo "</p>"; ob_flush(); flush();
	            $ended = date("H:i:s");
	            $msg = "{$i} Rows Synced. ($started - $ended)";
	        } else {
	            $msg = "$started | Could not connect to Fishbowl.";
	        }
	        $fishbowlapi->closeConnection();
	        if($output == "flush") { echo "<p>$msg</p>"; ob_flush(); flush(); }
	        gambaFishbowl::connection_log("Products Sync Completed", "Results:" . $msg);
	        return $msg;
	    }

	    public static function vendor_parts($parts, $output = NULL) {
	        $fb_server = config('fishbowl.FB_SERVER');
	        $fb_port = config('fishbowl.FB_PORT');
	        $fb_login = config('fishbowl.FB_LOGIN');
	        $fb_pass = config('fishbowl.FB_PASS');
	        $fishbowlapi = new FishbowlAPI($fb_server, $fb_port);
	        $fishbowlapi->Login($fb_login, $fb_pass);
	        $fberror = new FBErrorCodes();
	        $code = $fberror->checkCode($fishbowlapi->statusCode);
	        $started = date("H:i:s");
	        $i = 0;
	        if ($fishbowlapi->statusCode == 1000) {
	            if($output == "flush") { echo "<h3>Vendor Parts</h3>"; ob_flush(); flush(); }
	            foreach($parts as $values) {
	                $sql = "SELECT `PART`.`NUM`, `VENDOR`.`NAME`, `VENDORPARTS`.`VENDORPARTNUMBER`, `VENDORPARTS`.`DEFAULTFLAG`, `PART`.`DESCRIPTION`
	FROM `VENDORPARTS`
	LEFT JOIN `PART` ON `PART`.`ID` = `VENDORPARTS`.`PARTID`
	LEFT JOIN `VENDOR` ON `VENDOR`.`ID` = `VENDORPARTS`.`VENDORID`
	WHERE `PART`.`NUM` = '{$values->part}' AND `VENDORPARTS`.`DEFAULTFLAG` = '1'";
	                // 		            echo "<p>$sql</p>";
	                $fishbowlapi->executeSqlQueryRq($sql);
	                $part_info = $fishbowlapi->result['FbiMsgsRs']['ExecuteQueryRs']['Rows']['Row'];
	                // 		            echo "<pre>"; print_r($part_info); echo "</pre>"; ob_flush(); flush();
	                foreach($part_info as $key => $value) {
	                    if($key > 0) {
	                        $i++;
	                        $part_array = str_getcsv($value);
	                        // 		                    if($output == "flush") { echo "<pre>"; print_r($part_array); echo "</pre>"; ob_flush(); flush(); }
	                        // 		                    echo "{$part_array[0]}, ";
	                        if($output == "flush") {
	                            echo "<p>{$part_array[0]} | Description: $part_array[4] | Vendor: {$part_array[1]} | Vendor Part #: {$part_array[2]}</p>";
	                            ob_flush(); flush();
	                        }
	                        $query = VendorParts::where('partNumber', $part_array[0]);
	                        if($query->count() > 0) {
	                            $update = VendorParts::where('partNumber', $part_num)->update([
	                                'vendor' => $part_array[1],
	                                'vendorPartNumber' => $part_array[2],
	                                'defaultVendorFlag' => $part_array[3]
	                            ]);
	                        } else {
	                            $insert = new VendorParts;
	                            $insert->partNumber = $part_array[0];
	                            $insert->vendor = $part_array[1];
	                            $insert->vendorPartNumber = $part_array[2];
	                            $insert->defaultVendorFlag = $part_array[3];
	                            $insert->save();
	                        }
	                    }
	                }
	            }
	            // 		        echo "</p>"; ob_flush(); flush();
	            $ended = date("H:i:s");
	            $msg = "{$i} Rows Synced. ($started - $ended)";
	        } else {
	            $msg = "$started | Could not connect to Fishbowl.";
	        }
	        $fishbowlapi->closeConnection();
	        if($output == "flush") { echo "<p>$msg</p>"; ob_flush(); flush(); }
	        gambaFishbowl::connection_log("Vendor Parts Sync Completed", "Results:" . $msg);
	        return $msg;
	    }

	    public static function vendors() {
	        $fb_server = config('fishbowl.FB_SERVER');
	        $fb_port = config('fishbowl.FB_PORT');
	        $fb_login = config('fishbowl.FB_LOGIN');
	        $fb_pass = config('fishbowl.FB_PASS');
	        $fishbowlapi = new FishbowlAPI($fb_server, $fb_port);
	        $fishbowlapi->Login($fb_login, $fb_pass);
	        $fberror = new FBErrorCodes();
	        $code = $fberror->checkCode($fishbowlapi->statusCode);
	        $started = date("H:i:s");
	        $i = 0;
	        $statuscode = $fishbowlapi->statusCode;
	        if ($fishbowlapi->statusCode == 1000) {
	            if($output == "flush") {  "<h3>Vendors</h3>"; ob_flush(); flush(); }
	            $fishbowlapi->executeNamedQueryRq('gambaVendors');
	            //$querystatuscode = $fishbowlapi->statusCode;
	            //                 echo "<pre>"; print_r($fishbowlapi); echo "</pre>"; ob_flush(); flush();
	            $vendors = $fishbowlapi->result['FbiMsgsRs']['ExecuteQueryRs']['Rows']['Row'];
	            //echo "<pre>"; print_r($vendors); echo "</pre>"; ob_flush(); flush();
	            foreach($vendors as $key => $value) {
	                if($key > 0) {
	                    $i++;
	                    // 		                echo "$key, ";
	                    $vendor_array = str_getcsv($value);
	                    if($output == "flush") { echo "<pre>"; print_r($vendor_array); echo "</pre>"; ob_flush(); flush(); }
	                    $VendorID = $vendor_array[0];
	                    // 		                echo "$VendorID, ";  ob_flush(); flush();
	                    $AccountID = $vendor_array[1];
	                    $Status = $vendor_array[2];
	                    $DefPaymentTerms = $vendor_array[3];
	                    $DefShipTerms = $vendor_array[4];
	                    $Name = $vendor_array[5];
	                    $AccountNumber = $vendor_array[6];
	                    $DateCreated = $vendor_array[7];
	                    $DateModified = $vendor_array[8];
	                    $LastChangedUser = $vendor_array[9];
	                    $CreditLimit = $vendor_array[10];
	                    $Note = $vendor_array[11];
	                    $ActiveFlag = $vendor_array[12];
	                    $AccountingID = $vendor_array[13];
	                    $AccountingHash = $vendor_array[14];

	                    $AddressName = $vendor_array[15];
	                    $AddressContact = $vendor_array[16];
	                    $AddressType = $vendor_array[17];
	                    $Address = $vendor_array[18];
	                    $City = $vendor_array[19];
	                    $State = $vendor_array[20];
	                    $Zip = $vendor_array[21];
	                    $Country = $vendor_array[22];

	                    $query = Vendors::where('VendorID', $VendorID);
	                    if($query->count() > 0) {
	                        $update = Vendors::where('VendorID', $VendorID);
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
	                        $update->save();

	                        $update = VendorAddresses::where('name', $AddressName);
	                        $update->attn = $AddressContact;
	                        $update->street = $Address;
	                        $update->city = $City;
	                        $update->zip = $Zip;
	                        $update->state = $State;
	                        $update->country = $Country;
	                        $update->save();
	                    } else {
	                        $insert = new Vendors;
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
	                        $insert->save();

	                        $insert = new VendorAddresses;
	                        $insert->name = $AddressName;
	                        $insert->attn = $AddressContact;
	                        $insert->street = $Address;
	                        $insert->city = $City;
	                        $insert->zip = $Zip;
	                        $insert->state = $State;
	                        $insert->country = $Country;
	                        $insert->save();
	                    }
	                }
	            }
	            // 		        echo "</p>"; ob_flush(); flush();
	            $ended = date("H:i:s");
	            $msg = "{$i} Rows Synced. ($started - $ended) | Login FB: $statuscode | Query: $querystatuscode";
	        } else {
	            $msg = "$started | Could not connect to Fishbowl.";
	        }
	        // 		    echo "<pre>"; print_r($fishbowlapi); echo "</pre>"; ob_flush(); flush();
	        $fishbowlapi->closeConnection();
	        if($output == "flush") { echo "<p>$msg</p>"; ob_flush(); flush(); }
	        gambaFishbowl::connection_log("Vendors Sync Completed", "Results:" . $msg);
	        return $msg;
	    }


	    public static function parts($parts, $output = NULL) {
	        $fb_server = config('fishbowl.FB_SERVER');
	        $fb_port = config('fishbowl.FB_PORT');
	        $fb_login = config('fishbowl.FB_LOGIN');
	        $fb_pass = config('fishbowl.FB_PASS');
	        $fishbowlapi = new FishbowlAPI($fb_server, $fb_port);
	        $fishbowlapi->Login($fb_login, $fb_pass);
	        $fberror = new FBErrorCodes();
	        $code = $fberror->checkCode($fishbowlapi->statusCode);
	        $started = date("H:i:s");
	        $i = 0;
	        if ($fishbowlapi->statusCode == 1000) {
	            $clearOnOrder = Inventory::where('number', '!=', "")->update(['onorder' => '0', 'quantityshipped' => '0', 'availablesale' => '0', 'quantityshort' => '0']);
	            if($output == "flush") { echo "<h3>Parts</h3>"; ob_flush(); flush(); }
	            foreach($parts as $values) {
	                $sql = "SELECT PART.ID, PART.NUM AS PART, PART.DESCRIPTION, UOM.CODE AS UOMCODE, CONCAT(PART.stdCost),
	    COALESCE((SELECT SUM(CONCAT(QTYONHAND.QTY))
	                FROM QTYONHAND
	               WHERE QTYONHAND.PARTID = PART.ID
	                 AND QTYONHAND.LOCATIONGROUPID IN (1,3,4,5)), 0) AS QTY,
	   COALESCE((SELECT  SUM( CONCAT(QTYNOTAVAILABLE.QTY))
	                FROM QTYNOTAVAILABLE
	               WHERE QTYNOTAVAILABLE.PARTID = PART.ID
	                 AND QTYNOTAVAILABLE.LOCATIONGROUPID IN (1,3,4,5)), 0) AS  UNAVAILABLE,
	    COALESCE((SELECT SUM( CONCAT(QTYCOMMITTED.QTY))
	                FROM QTYCOMMITTED
	               WHERE QTYCOMMITTED.PARTID = PART.ID
	                 AND QTYCOMMITTED.LOCATIONGROUPID IN (1,3,4,5)), 0) AS QTYCOMMITTED,
	    COALESCE((SELECT SUM( CONCAT(QTYALLOCATED.QTY))
	                FROM QTYALLOCATED
	               WHERE QTYALLOCATED.PARTID = PART.ID
	                 AND QTYALLOCATED.LOCATIONGROUPID IN (1,3,4,5)), 0) AS ALLOCATED,
	    COALESCE((SELECT SUM( CONCAT(QTYONORDER.QTY))
	                FROM QTYONORDER
	               WHERE QTYONORDER.PARTID = PART.ID
	                 AND QTYONORDER.LOCATIONGROUPID IN (1,3,4,5)), 0) AS ONORDER,
	    PART.ACTIVEFLAG

	FROM part
	    LEFT JOIN uom ON part.uomid = uom.id
	    JOIN company ON company.id = 1

	WHERE part.num  = '{$values->part}'";
	                $fishbowlapi->executeSqlQueryRq($sql);
	                $part_info = $fishbowlapi->result['FbiMsgsRs']['ExecuteQueryRs']['Rows']['Row'];

	                foreach($part_info as $key => $value) {
	                    if($key > 0) {
	                        $i++;
	                        $qty_onhand = 0; $qty_unavailable = 0; $qty_committed = 0;
	                        $qty_allocated = 0; $qty_onorder = 0; $availablesale = 0;
	                        $part_array = str_getcsv($value);
	                        //     		                if($output == "flush") { echo "<pre>"; print_r($part_array); echo "</pre>"; ob_flush(); flush(); }
	                        $part_id = $part_array[0];
	                        $part = $part_array[1];
	                        $description = $part_array[2];
	                        $uomcode = $part_array[3];
	                        $standardcost = $part_array[4];
	                        $qty_onhand = $part_array[5];
	                        $qty_unavailable = $part_array[6];
	                        $qty_committed = $part_array[7];
	                        $qty_allocated = $part_array[8];
	                        $qty_onorder = $part_array[9];
	                        $active_flag = $part_array[10];
	                        // Calculate
	                        $quantityshipped = $qty_allocated;
	                        // Available for Sale = On Hand - Allocated - Not Available + Drop Ship
	                        $availablesale = ($qty_onhand - $qty_unavailable - $qty_allocated);
	                        //     		                echo "{$part} ($availablesale) ($quantityshipped) ";
	                        $supply_part = SupplyParts::select('converted_total')->where('part', $part)->first();
	                        $total_amount = $supply_part['converted_total'];
	                        $quantityshort = $total_amount - ($qty_onhand + $qty_onorder + $quantityshipped);
	                        if($output == "flush") {
	                            echo "<p>$part | Description: $description | On Hand: $qty_onhand | On Order: $qty_onorder | Available Sale: $availablesale</p>";
	                            ob_flush(); flush();
	                        }
	                        if($quantityshort < 0) { $quantityshort = 0; }
	                        $update = Parts::find($part);
	                        $update->fbuom = $uomcode;
	                        //$update->fbcost = $standardcost; // Disable per Kate Lammers 8/23/17
	                        $update->save();
	                        $query = Inventory::where('number', $part);
	                        if($query->count() > 0) {
	                            $update = Inventory::find($part);
	                            $update->quantityonhand = $qty_onhand;
	                            $update->onorder = $qty_onorder;
	                            $update->availablesale = $availablesale;
	                            $update->quantityshipped = $quantityshipped;
	                            $update->updated = date("Y-m-d H:i:s");
	                            $update->save();
	                            // 		                        echo "Update";
	                        } else {
	                            $insert = new Inventory;
	                            $insert->number = $part;
	                            $insert->fb_partid = $part_id;
	                            $insert->fb_vendorid = "";
	                            $insert->fb_active = $active_flag;
	                            $insert->onorder = $qty_onorder;
	                            $insert->availablesale = $availablesale;
	                            $insert->quantityshipped = $quantityshipped;
	                            $insert->quantityonhand = $qty_onhand;
	                            $insert->updated = date("Y-m-d H:i:s");
	                            $insert->save();
	                            // 		                        echo "Insert";
	                        }
	                        //     		                echo ", "; ob_flush(); flush();
	                    }
	                }
	            }
	            $ended = date("H:i:s");
	            $msg = "{$i} Rows Synced. ($started - $ended)";
	            if($output == "flush") { echo "<p>$msg</p>"; ob_flush(); flush(); }
	            gambaFishbowl::connection_log("Parts and Inventory Sync Completed", "Results:" . $msg);
	//     		gambaInventory::quantity_short(); // Talk to Chad and Logesh before implementing
	        } else {
	            $msg = "$started | Could not connect to Fishbowl.";
	            if($output == "flush") { echo "<p>$msg</p>"; ob_flush(); flush(); }
	            gambaFishbowl::connection_log("Parts and Inventory Sync Completed", "Results:" . $msg);
	        }
	        $fishbowlapi->closeConnection();
	        return $msg;
	    }

	    public static function uoms($output = NULL) {
	        $fb_server = config('fishbowl.FB_SERVER');
	        $fb_port = config('fishbowl.FB_PORT');
	        $fb_login = config('fishbowl.FB_LOGIN');
	        $fb_pass = config('fishbowl.FB_PASS');
	        $fishbowlapi = new FishbowlAPI($fb_server, $fb_port);
	        $fishbowlapi->Login($fb_login, $fb_pass);
	        $fberror = new FBErrorCodes();
	        $code = $fberror->checkCode($fishbowlapi->statusCode);
	        $started = date("H:i:s");
	        if ($fishbowlapi->statusCode == 1000) {
	            $sql = "SELECT * FROM uom";
	            $fishbowlapi->executeSqlQueryRq($sql);
	            $uoms = $fishbowlapi->result['FbiMsgsRs']['ExecuteQueryRs']['Rows']['Row'];
	            // echo "<pre>"; print_r($uoms[0]); echo "</pre>";
	            if($output == "flush") { echo "<h3>UOMS</h3>"; ob_flush(); flush(); }
	            $i = 0;
	            foreach($uoms as $key => $value) {
	                if($key > 0) {
	                    $uom_array = str_getcsv($value);
	                    //                         if($output == "flush") { echo "<pre>"; print_r($uom_array); echo "</pre>"; ob_flush(); flush(); }
	                    $id = $uom_array[0];
	                    $activeFlag = $uom_array[1];
	                    $code = $uom_array[2];
	                    //                         echo "$code, "; ob_flush(); flush();
	                    $defaultRecord = $uom_array[3];
	                    $description = $uom_array[4];
	                    $integral = $uom_array[5];
	                    $name = $uom_array[6];
	                    $readOnly = $uom_array[7];
	                    $uomType = $uom_array[8];
	                    if($output == "flush") {
	                        echo "<p>$name | Code: $code</p>";
	                        ob_flush(); flush();
	                    }
	                    if($activeFlag == "true") {
	                        $query = PartUoMs::where('id', $id);
	                        if($query->count() > 0) {
	                            $update = PartUoMs::find($id);
	                            $update->name = $name;
	                            $update->code = $code;
	                            $update->active = $activeFlag;
	                            $update->date_updated = date("Y-m-d H:i:s");
	                            $update->save();
	                        } else {
	                            $insert = new PartUoMs;
	                            $insert->id = $id;
	                            $insert->name = $name;
	                            $insert->code = $code;
	                            $insert->active = $activeFlag;
	                            $insert->date_added = date("Y-m-d H:i:s");
	                            $insert->date_updated = date("Y-m-d H:i:s");
	                            $insert->save();
	                        }

	                    }
	                    $i++;
	                }
	            }
	            //     		    echo "</p>"; ob_flush(); flush();
	            $ended = date("H:i:s");
	            $msg = "{$i} Rows Synced. ($started - $ended)";
	            //exit; die();
	        } else {
	            $msg = "$started | Could not connect to Fishbowl.";
	        }
	        $fishbowlapi->closeConnection();
	        if($output == "flush") { echo "<p>$msg</p>"; ob_flush(); flush(); }
	        gambaFishbowl::connection_log("Units of Measure Sync Completed", "Results:" . $msg);
	        return $msg;
	    }

	}
