<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\AllParts;
	use App\Models\Config;
	use App\Models\Inventory;
	use App\Models\Parts;
	use App\Models\Supplies;
	use App\Models\VendorParts;
	use App\Models\ViewPartCosts;
	use App\Models\ViewPartsList;

	use App\Gamba\gambaDebug;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaFishbowl;
	use App\Gamba\gambaInventory;
	use App\Gamba\gambaSupplies;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaVendors;
	use App\Gamba\gambaUOMs;
	use App\Gamba\gambaUsers;

	use App\Jobs\CalcQuantityShort;

	class gambaParts {

		/**
		 * List of Parts
		 *
		 * @param unknown $order
		 * @param unknown $part_num
		 * @param unknown $alpha
		 * @param unknown $view
		 * @return number
		 */
		public static function parts_list($order, $part_num, $alpha, $view, $limit = NULL) {
			$current_term = gambaTerm::year_by_status('C');
			$vendors = gambaVendors::vendor_list();
			$uoms = gambaUOMs::uom_list();
	// 		$num_sql = "SELECT 1 FROM " . tbpre . "parts WHERE description != ''";
			// Old - $sql = "SELECT number, description, suom, pq, url, purl, approved, inventory, cwnotes, adminnotes, fishbowl, cost, vendor, created, IFNULL(updated, created) part_updated, fbuom, fbcost, conversion, xmlstring, part_options, updated FROM " . tbpre . "parts WHERE description != ''";
// 			$parts = Parts::select('number', 'description', 'suom', 'cost', 'pq', 'url', 'purl', 'approved', 'inventory', 'cwnotes', 'adminnotes', 'fishbowl', 'vendor', 'updated', 'created', 'old_id', 'fbuom', 'fbcost', 'conversion', 'xmlstring', 'part_options');
	// 		$parts = Parts::raw("SELECT number, description, suom, pq, url, purl, approved, inventory, cwnotes, adminnotes, fishbowl, cost, vendor, created, IFNULL(updated, created) part_updated, fbuom, fbcost, conversion, xmlstring, part_options, updated");
// 			$parts = $parts->where('description', '!=', "");
			$parts = AllParts::where('description', '!=', "");
			if ($alpha == "") {
				$alpha = "a";
			}
			if ($view == "approved" || $view == "inventory") {
				// Old - $sql .= " AND approved = 0 AND inventory = 'true'";
				$parts = $parts->where('approved', '=', "0")->where('inventory', '=', "true");
			} 		// Approved
			elseif ($view == 3) {
				// Old - $sql .= " AND inventory = 'true'";
				$parts = $parts->where('inventory', '=', "true");
			} 		// All Active
			elseif ($view == "retired") {
				// Old - $sql .= " AND (inventory = 'false' OR inventory IS NULL OR inventory = \"\")";
				$parts = $parts->whereRaw("(inventory = 'false' OR inventory IS NULL OR inventory = \"\")");
			} 		// Retired
			elseif ($view == "all") {
				// Old - $sql .= " AND inventory = 'true'";
				$parts = $parts->where('inventory', '=', "true");
			} 		// All Parts
			elseif ($view == "awaiting") {
				// Old - $sql .= " AND approved = 1 AND inventory = 'true'";
				$parts = $parts->where('approved', '=', "1")->where('inventory', '=', "true");
			} 		// Awaiting Approval
			elseif ($view == "vendors") {
				// Old - $sql .= " AND vendor > 0 AND inventory = 'true'";
				$parts = $parts->where('vendor', '>', "0")->where('inventory', '=', "true");
			} 		// Vendors
			elseif ($view == "gamba") {
				// Old - $sql .= " AND number LIKE 'GMB%'";
				$parts = $parts->where('number', 'LIKE', "GMB%");
			} 		// GAMBA Parts
			elseif ($view == "lastupdated") {
				// Old - $sql .= " AND updated IS NOT NULL";
				$parts = $parts->where('updated', 'IS NOT', NULL);
			} else {
				// Old - $sql .= " AND approved = 1 AND inventory = 'true'";
				$parts = $parts->where('inventory', '=', "true");
			} // Awaiting Approval

			if ($part_num != "") {
				// Old - $sql .= " AND number LIKE '$part_num%'";
				$parts = $parts->where('number', 'LIKE', "$part_num%");
			}
			if ($alpha != "" && $view != "awaiting" && $view != "inventory" && $view != "lastupdated") {
				// Old - $sql .= " AND description LIKE '$alpha%'";
				if($alpha == "nonalpha") {
					$parts = $parts->whereRaw("description REGEXP '^[[:digit:]]'");
				} else {
					$parts = $parts->where('description', 'LIKE', "$alpha%");
				}
			}
			if ($view == "lastupdated") {
				// Old - $sql .= " ORDER BY updated DESC";
				$parts = $parts->orderBy('updated', 'DESC');
				$limit = 50;
			} else {
				// Old - $sql .= " ORDER BY description";
				$parts = $parts->orderBy('description');
			}
			if ($limit != NULL) {
				// Old - $sql .= " LIMIT 0, $limit";
				$parts = $parts->limit($limit);
			}
// 			$array['sql'] = $parts->toSql();
			$parts = $parts->get();
// 			echo "<pre>"; print_r($parts); echo "</pre>";
// 			$array['Number Parts'] = $num_parts = $parts->count();
// 			if($num_parts > 0) {
				foreach($parts as $value) {
// 					echo "<pre>"; print_r($value); echo "</pre>";
					$number = $value['number'];
					$array['parts'][$number]['number'] = $number;
	// 				$num_parts_term = "SELECT id FROM " . tbpre . "supplies WHERE part = '$part_number' AND term = '$current_term'"
					if (preg_match ( '/GMB/i', $number )) {
						$array['parts'][$number]['gamba_part'] = $gamba_part = 1;
					}
					$array['parts'][$number]['description'] = $value['description'];
					$array['parts'][$number]['suom'] = $value['suom'];
					$array['parts'][$number]['fbuom'] = $value['fbuom'];
					if ($value['fbcost'] == NULL || $value['fbcost'] == "") {
						$fbcost = "0.00";
					} else {
					    $fbcost = number_format($value['fbcost'], 3);
					}
					$array['parts'][$number]['fbcost'] = $fbcost;
					$array['parts'][$number]['conversion'] = $value ['conversion'];
					$array['parts'][$number]['pq'] = $value ['pq'];
					$array['parts'][$number]['url'] = $value ['url'];
					$array['parts'][$number]['purl'] = $value ['purl'];
					$array['parts'][$number]['approved'] = $value ['approved'];
					$array['parts'][$number]['inventory'] = $inventory = $value ['inventory'];
					$array['parts'][$number]['cwnotes'] = $value ['cwnotes'];
					$array['parts'][$number]['adminnotes'] = $value ['adminnotes'];
					$array['parts'][$number]['fishbowl'] = $fishbowl = $value ['fishbowl'];
					$array['parts'][$number]['invquantities'] = gambaInventory::part_inventory($number);
					$vendor_parts = self::vendor_parts ( $number, $inventory, $fishbowl );
					$array['parts'][$number]['fpvendor'] = $vendor_parts ['vendor'];
					$array['parts'][$number]['fpvendorpartnumber'] = $vendor_parts ['vendorPartNumber'];
					if ($value['cost'] == NULL || $value['cost'] == "") {
						$cost = "0.00";
					} else {
					    $cost = number_format($value['cost'], 3);
					}
					$array['parts'][$number]['cost'] = $cost;
					$array['parts'][$number]['vendor_id'] = $vendor_id = $value['vendor'];
					$array['parts'][$number]['vendor'] = $vendors['vendors'][$vendor_id]['name'];
					$array['parts'][$number]['created'] = $value['created'];
					$updated = $value['updated'];
					if($updated == "") { $part_updated = $value['created']; } else { $part_updated = $updated; }
					$array['parts'][$number]['updated'] = $part_updated;
					$array['parts'][$number]['created_formatted'] = date("n/j/Y", strtotime($value ['created']));
					$array['parts'][$number]['updated_formatted'] = date("n/j/Y", strtotime($part_updated));
// 					$array['parts'][$number]['xmlstring'] = $value['xmlstring'];
					if ($view == "awaiting") {
						$array['parts'][$number]['supplies'] = gambaSupplies::supplylist_by_part($number);
					}
// 					echo "<pre>"; print_r($array['parts'][$number]); echo "</pre>";
					$num_parts_term = Supplies::select('id')->where('part', '=', $number)->where('term', '=', $current_term)->count();
					$array['parts'][$number]['supply_parts'] = $num_parts_term;
// 				}
			}
// 			echo "<pre>"; print_r($array); echo "</pre>";
			return $array;
		}

		public static function part_search($view, $page = "", $offset = 0, $alpha = "") {
		    $parts = ViewPartsList::where('part_num', '!=', "");
		    if ($view == "approved" || $view == "inventory") {
		        $parts = $parts->where('approved', "0")->where('inventory', "true")->where('fishbowl', "true");
		    } elseif ($view == 3) {
		        $parts = $parts->where('inventory', "true");
		    } elseif ($view == "retired") {
		        if($offset == "") { $offset = 0; }
		        $parts = $parts->whereRaw("(inventory = 'false' OR inventory IS NULL OR inventory = \"\")")->limit(50)->offset($offset);
		    } elseif ($view == "all") {
		        $parts = $parts->where('inventory', "true");
		    } elseif ($view == "awaiting") {
		        if($offset == "") { $offset = 0; }
		        $parts = $parts->where('approved', 1)->where('inventory', "true")->where('conceptitem', 0);
		        $parts = $parts->where(function($where) {
		          $where->where('fishbowl', "false")->orWhere('fishbowl', 0);
		        }); // Ignore error
		        $array['list_count'] = $parts->count();
		        $parts = $parts->limit(50)->offset($offset);
		    } elseif ($view == "concept") {
		        if($offset == "") { $offset = 0; }
		        $parts = $parts->where('conceptitem', "1");
		        $array['list_count'] = $parts->count();
		        $parts = $parts->limit(50)->offset($offset);
		    } elseif ($view == "vendors") {
		        $parts = $parts->where('vendor', '>', "0")->where('inventory', "true");
		    } elseif ($view == "gamba") {
		        if($offset == "") { $offset = 0; }
		        $parts = $parts->where('part_num', 'LIKE', "GMB%")->where('fishbowl', "false");
		        $array['list_count'] = $parts->count();
		        $parts = $parts->limit(50)->offset($offset);
		    } elseif ($view == "lastupdated") {
		        if($offset == "") { $offset = 0; }
		        $parts = $parts->where('updated', '!=', NULL);
		        $array['list_count'] = $parts->count();
		        $parts = $parts->limit(50)->offset($offset);
		        $parts = $parts->orderBy('updated', DESC);
		    } else {
		        if($offset == "") { $offset = 0; }
		        $parts = $parts->where('approved', "1")->where('inventory', "true")->where('conceptitem', "0");
		        $array['list_count'] = $parts->count();
		        $parts = $parts->limit(50)->offset($offset); // Awaiting Approval
		    }
		    if($alpha != "" && $alpha == "0-9") {
		        $parts = $parts->whereRaw("part_description REGEXP '^[[:digit:]]'");
		    }
		    if($alpha != "" && $alpha != "0-9") {
		        $parts = $parts->where('part_description', 'LIKE', "$alpha%");
		    }
		    $array['sql'] = $parts->toSql();
		    $parts = $parts->get();
		    if($parts->count() > 0) {
		        $array['count'] = $parts->count();
		        $array['result'] = "{$array['count']} Parts Returned.";
    		    foreach($parts as $value) {
    		        $number = $value['part_num'];
    		        $array['parts'][$number]['number'] = $number;
    		        $array['parts'][$number]['description'] = $value['part_description'];
    		        $array['parts'][$number]['url'] = $value ['url'];
    		        $array['parts'][$number]['suom'] = $value['gmb_uom'];
    		        if ($value['gmb_cost'] == NULL || $value['gmb_cost'] == "") {
    		            $cost = "0.00";
    		        } else {
    		            $cost = $value['gmb_cost'];
    		        }
    		        $array['parts'][$number]['cost'] = $cost;
    		        $array['parts'][$number]['inventory'] = $inventory = $value ['inventory'];
    		        $array['parts'][$number]['approved'] = $value ['approved'];
    		        $array['parts'][$number]['fbuom'] = $value['fbuom'];
    		        $array['parts'][$number]['fishbowl'] = $fishbowl = $value ['fishbowl'];
    		        if ($value['fbcost'] == NULL || $value['fbcost'] == "") {
    		            $fbcost = "0.00";
    		        } else {
    		            $fbcost = $value['fbcost'];
    		        }
    		        $array['parts'][$number]['fbcost'] = $fbcost;
    		        $array['parts'][$number]['vendor_id'] = $vendor_id = $value['vendorid'];
    		        $updated = $value['updated'];
    		        if($updated == "") {
    		            $part_updated = $value['created'];
    		        } else {
    		            $part_updated = $updated;
    		        }
    		        $array['parts'][$number]['updated'] = $part_updated;
    		        $array['parts'][$number]['created_formatted'] = date("n/j/Y", strtotime($value ['created']));
    		        $array['parts'][$number]['updated_formatted'] = date("n/j/Y", strtotime($part_updated));
    		        $array['parts'][$number]['conceptitem'] = $value['conceptitem'];
    		        $array['parts'][$number]['vendor'] = $value['vendorname'];
    		        $array['parts'][$number]['fpvendorpartnumber'] = $value['vendorpart'];
    		        $array['parts'][$number]['supply_parts'] = $value['number_requests'];
    		        $array['parts'][$number]['pq'] = $value ['purchase_quantity'];
    		    }
		    } else {
		        $array['count'] = 0;
		        $array['result'] = "No Results.";
		    }
		    return $array;
		}

		public static function parts_retired_requests() {
			$current_term = gambaTerm::year_by_status ( 'C' );
	// 		$array ['sql'] = $sql = "SELECT number, description, suom, pq, url, purl, approved, inventory, cwnotes, adminnotes, fishbowl, cost, vendor, created, IFNULL(updated, created) part_updated, fbuom, fbcost, conversion, xmlstring, part_options FROM " . tbpre . "parts WHERE description != '' AND (inventory = 'false' OR inventory IS NULL OR inventory = \"\")";
			$parts = AllParts::select('number', 'description', 'suom', 'cost', 'pq', 'url', 'purl', 'approved', 'inventory', 'cwnotes', 'adminnotes', 'fishbowl', 'vendor', 'updated', 'created', 'old_id', 'fbuom', 'fbcost', 'conversion', 'xmlstring', 'part_options');
			$parts = $parts->where('description', '!=', "");
			$parts = $parts->whereRaw("(inventory = 'false' OR inventory IS NULL OR inventory = \"\")");
			$array ['sql'] = $parts->toSql();
			$parts = $parts->get();
			// Old - $result = mysql_query ( $sql ) or die ( "$sql: " . mysql_error () );
			if($parts->count() > 0) {
			// Old - if (mysql_num_rows ( $result ) > 0) {
				foreach($parts as $key => $row) {
				// Old - while ( $row = mysql_fetch_array ( $result ) ) {
					$number = $row ['number'];
					// Old - $num_parts_term = mysql_num_rows ( mysql_query ( "SELECT 1 FROM " . tbpre . "supplies WHERE part = '$number' AND term = $current_term" ) );
					$num_parts_term= Supplies::select('id')->where('part', '=', $number)->where('term', '=', "$current_term")->count();
					if ($num_parts_term > 0) {
						$array ['parts'] ["$number"] ['supply_parts'] = $num_parts_term;
						if (preg_match ( '/GMB/i', $number )) {
							$array ['parts'] ["$number"] ['gamba_part'] = $gamba_part = 1;
						}
						$array ['parts'] ["$number"] ['description'] = $row ['description'];
						$array ['parts'] ["$number"] ['suom'] = $row ['suom'];
						$array ['parts'] ["$number"] ['fbuom'] = $row ['fbuom'];
						if ($row ['fbcost'] == NULL || $row ['fbcost'] == "") {
							$fbcost = "0.00";
						} else {
						    $fbcost = number_format($row ['fbcost'], 3);
						}
						$array ['parts'] ["$number"] ['fbcost'] = $fbcost;
						$array ['parts'] ["$number"] ['conversion'] = $row ['conversion'];
						$array ['parts'] ["$number"] ['pq'] = $row ['pq'];
						$array ['parts'] ["$number"] ['url'] = $row ['url'];
						$array ['parts'] ["$number"] ['purl'] = $row ['purl'];
						$array ['parts'] ["$number"] ['approved'] = $row ['approved'];
						$array ['parts'] ["$number"] ['inventory'] = $inventory = $row ['inventory'];
						$array ['parts'] ["$number"] ['cwnotes'] = $row ['cwnotes'];
						$array ['parts'] ["$number"] ['adminnotes'] = $row ['adminnotes'];
						$array ['parts'] ["$number"] ['fishbowl'] = $fishbowl = $row ['fishbowl'];
						$array ['parts'] ["$number"] ['invquantities'] = gambaInventory::part_inventory ( $number );
						$vendor_parts = self::vendor_parts ( $number, $inventory, $fishbowl );
						$array ['parts'] ["$number"] ['fpvendor'] = $vendor_parts ['vendor'];
						$array ['parts'] ["$number"] ['fpvendorpartnumber'] = $vendor_parts ['vendorPartNumber'];
						if ($row ['cost'] == NULL || $row ['cost'] == "") {
							$cost = "0.00";
						} else {
						    $cost = number_format($row ['cost'], 3);
						}
						$array ['parts'] ["$number"] ['cost'] = $cost;
						$array ['parts'] ["$number"] ['vendor_id'] = $vendor_id = $row ['vendor'];
						$array ['parts'] ["$number"] ['vendor'] = $vendors ['vendors'] [$vendor_id] ['name'];
						$array ['parts'] ["$number"] ['created'] = $row ['created']; $updated = $row['updated'];
						if($updated == "") { $part_updated = $row['created']; } else { $part_updated = $updated; }
						$array ['parts'] ["$number"] ['updated'] = $part_updated;
						$array ['parts'] ["$number"] ['created_formatted'] = date("n/j/Y", strtotime($row ['created']));
						$array ['parts'] ["$number"] ['updated_formatted'] = date("n/j/Y", strtotime($row ['part_updated']));
						$array ['parts'] ["$number"] ['xmlstring'] = $row ['xmlstring'];
						if ($view == "awaiting") {
							$array ['parts'] ["$number"] ['supplies'] = gambaSupplies::supplylist_by_part ( $number );
						}
					}
				}
			}
			return $array;
		}

		public static function view_fishbowl_part_search($array) {
			$url = url('/');
			$part = $array ['part'];
			$content_array['page_title'] = "Fishbowl Part Search";
			$content_array['content'] .= <<<EOT
				<div class="row">
					<div class="small-12 medium-12 large-12 columns">
						<form method="get" name="seach" action="{$url}/settings/fbpart_search" class="form-horizontal">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT

							<div class="row">
								<div class="small-12 medium-2 large-2 columns">
									<label for="part" class="">Part Number</label>
								</div>
								<div class="small-12 medium-2 large-2 columns">
									<input type="text" name="part" class="form-control" required />
								</div>
								<div class="small-12 medium-2 large-2 columns">
									<input type="submit" name="submit" value="Search" class="button small success" />
								</div>
							</div>
						</form>
					</div>
				</div>
EOT;
			self::fbpart_search_get ( $part );
			return $content_array;
		}

		private static function fbpart_search_get($part) {
			if ($part != "") {
				$fishbowlapi = new FishbowlAPI ( FB_SERVER, FB_PORT );
				$fishbowlapi->Login ( FB_LOGIN, FB_PASS );
				$fberror = new FBErrorCodes ();
				$code = $fberror->checkCode ( $fishbowlapi->statusCode );

				$fishbowlapi->getPart ( $part );
				$result = $fishbowlapi->result;
				$fishbowlapi->closeConnection ();
				$content .= "<pre>";
	// 			$content .= gambaDebug::array2ul($result);
				$content .= implode("','", $result);
				$content .= "</pre>";
				return $content;
			}
		}

		public static function parts_autocomplete() {
			//$sql = "SELECT p.number, p.description, p.suom, p.cost, i.quantityonhand FROM " . tbpre . "parts p LEFT JOIN " . tbpre . "inventory i ON i.number = p.number WHERE inventory = 'true' ORDER BY p.description";
			$parts = AllParts::select('parts.number', 'parts.description', 'parts.suom', 'parts.cost', 'inventory.quantityonhand');
			$parts = $parts->leftjoin('inventory', 'inventory.number', '=', 'parts.number');
			$parts = $parts->where('parts.inventory', '=', 'true');
			$parts = $parts->where('parts.concept', '0');
			$parts = $parts->orderBy('parts.description');
			$parts = $parts->get();
			// Old - $result = mysql_query ( $sql ) or die ( "Error ($sql): " . mysql_error () );
			if($parts->count() > 0) {
			// Old - if (mysql_num_rows ( $result ) > 0) {
				$content = '<script type="text/javascript">' . "\n";
				$content .= '$(".partlist").each(function(){$(this).autocomplete({' . "\n";
				// echo '$(".partlist").autocomplete({'."\n";
				$content .= 'source: [' . "\n";
				foreach($parts as $key => $row) {
				// Old - while ( $row = mysql_fetch_array ( $result ) ) {
					$number = $row ['number'];
					$cost = $row ['cost'];
					$quantityonhand = $row ['quantityonhand'];
					$array ["$number"] ['description'] = $description = str_replace ( "&quot;", "''", $row ['description'] );
					$array ["$number"] ['suom'] = $suom = $row ['suom'];
					$content .= "\"$number | $description (";
					if ($cost != "") {
						$content .= "$$cost ";
					}
					$content .= "$suom)";
					$content .= " QoH: $quantityonhand";
					$content .= "\", ";
				}
				$content .= ']' . "\n";
				$content .= "});" . "\n";
				$content .= "});" . "\n";
				$content .= "</script>" . "\n";
			}
// 			echo $content;
			return $content;
		}

		/**
		 * List of Vendors by Parts
		 *
		 * @param unknown $number
		 * @param unknown $inventory
		 * @param unknown $fishbowl
		 * @return unknown
		 */
		public static function vendor_parts($number, $inventory, $fishbowl) {
			if ($inventory == "true" && $fishbowl == "true") {
	// 			$row = mysql_fetch_array ( mysql_query ( "SELECT vendor, vendorPartNumber FROM " . tbpre . "vendorparts WHERE partNumber = '$number' AND defaultVendorFlag = 'true'" ) );
				$vendorparts= VendorParts::select('vendor', 'vendorPartNumber');
				$vendorparts = $vendorparts->where('partNumber', '=', "$number");
				$row = $vendorparts->first();
			}
			$array ['vendor'] = $row ['vendor'];
			$array ['vendorPartNumber'] = $row ['vendorPartNumber'];
			return $array;
		}

		/**
		 * Part Number Prefixes
		 *
		 * @return multitype:string
		 */
		public static function part_prefixes() {
			$array = array (
					"C" => "Consumable",
					"NC" => "Non-Consumable",
					"GMB" => "GAMBA Only",
					"FG" => "Food Grade",
					"D" => "Durable",
					"S" => "Seasonal",
					"SM" => "Stock Material"
			);
			return $array;
		}

		/**
		 * Array of Part List Views
		 *
		 * @return multitype:string
		 */
		public static function part_views() {
			$array = array (
					"all" => "All",
					"approved" => "Approved",
					"awaiting" => "Awaiting Approval",
					"gamba" => "GAMBA Parts",
					"retired" => "Retired",
					"lastupdated" => "Last Updated",
					"vendors" => "Vendors",
					"parts_retired_requests" => "Parts Retired with Current Requests"
			);
			return $array;
		}

		/**
		 * List of Available Part Numbers
		 *
		 * @return string
		 */
		public static function available_part_numbers() {
			$prefixes = self::part_prefixes ();
			foreach ( $prefixes as $key => $values ) {
				for($i = 1; $i <= 9999; $i ++) {
					$number = $key . str_pad ( $i, 4, "0", STR_PAD_LEFT );
					// Old - $sql = "SELECT number FROM " . tbpre . "parts WHERE number = '$number'";
					$parts = AllParts::select('number')->where('number', '=', "$number");
					if ($key == "S") {
						// Old - $sql .= " AND number NOT LIKE 'SM%'";
						$parts = $parts->where('number', 'NOT LIKE', 'SM%');
					}
					// Old - $sql .= " ORDER BY number";
					$row = $parts->orderBy('number')->first();
					// Old - $row = mysql_fetch_array ( mysql_query ( $sql ) );

					if ($row ['number'] != $number) {
						$numbers .= "$number, ";
						$return ['array'] ["$number"] = $sql;
						break;
					}
				}
			}
			// echo "<pre>"; print_r($return); echo "</pre>"; exit; die();
			$return ['numbers'] = rtrim ( $numbers, ", " );
			return $return;
		}

		/**
		 * Dropdown List of Part Views
		 *
		 * @param unknown $view
		 * @param unknown $alpha
		 */
		public static function view_navigation($view, $alpha) {
			$url = url('/');
			$user_id = Session::get('uid');
			$views = self::part_views ();
			if (is_array ( $views )) {
				$content = '<dl class="sub-nav">';
				foreach ( $views as $key => $value ) {
						$content .= '<dd';
						if ($view == $key) {
							$content .= ' class="active"';
						}
						$content .= '><a href="'.$url.'/parts?view=' . $key . '&alpha=' . $alpha . '">' . $value . '</a></dd>';
				}
				// parts_log
				if ($user_id == 1) {
					$content .= '<dd><a href="'.$url.'/parts/parts_log">Parts Log</a></dd>';
					$content .= '<dd><a href="'.$url.'/parts/products_log">Products Log</a></dd>';
				}
				$content .= '</dl>';
			}
			return $content;
		}

	public static function utf8_filter($text) {
		// http://www.utf8-chartable.de/unicode-utf8-table.pl?utf8=string-literal
		// http://www.utf8-chartable.de/unicode-utf8-table.pl
		// http://www.utf8-chartable.de/unicode-utf8-table.pl?start=8448&number=128&names=2&utf8=string-literal
		$search = array(
			"\xe2\x80\x9c", // Curly Opening Double Quote
			"\xe2\x80\x9d", // Curly Closing Double Quote
			"\xe2\x80\x98", // Curly Opening Single Quote
			"\xe2\x80\x99",	// Curly Closing Single Quote

			"\xc2\xbc",	// VULGAR FRACTION ONE QUARTER
			"\xc2\xbd",	// VULGAR FRACTION ONE HALF
			"\xc2\xbe",	// VULGAR FRACTION THREE QUARTERS

			"\xe2\x85\x90", // VULGAR FRACTION ONE SEVENTH
			"\xe2\x85\x91", // VULGAR FRACTION ONE NINTH
			"\xe2\x85\x92", // VULGAR FRACTION ONE TENTH
			"\xe2\x85\x93", // VULGAR FRACTION ONE THIRD
			"\xe2\x85\x94", // VULGAR FRACTION TWO THIRDS
			"\xe2\x85\x95", // VULGAR FRACTION ONE FIFTH
			"\xe2\x85\x96", // VULGAR FRACTION TWO FIFTHS
			"\xe2\x85\x97", // VULGAR FRACTION THREE FIFTHS
			"\xe2\x85\x98", // VULGAR FRACTION FOUR FIFTHS
			"\xe2\x85\x99", // VULGAR FRACTION ONE SIXTH
			"\xe2\x85\x9a", // VULGAR FRACTION FIVE SIXTHS
			"\xe2\x85\x9b", // VULGAR FRACTION ONE EIGHTH
			"\xe2\x85\x9c", // VULGAR FRACTION THREE EIGHTHS
			"\xe2\x85\x9d", // VULGAR FRACTION FIVE EIGHTHS
			"\xe2\x85\x9e", // VULGAR FRACTION SEVEN EIGHTHS
		);
		$replace = array(
			"''", // Curly Opening Double Quote
			"''", // Curly Closing Double Quote
			"'",  // Curly Opening Single Quote
			"'",  // Curly Closing Single Quote

			"1/4",	// VULGAR FRACTION ONE QUARTER
			"1/2",	// VULGAR FRACTION ONE HALF
			"3/4",	// VULGAR FRACTION THREE QUARTERS

			"1/7", // VULGAR FRACTION ONE SEVENTH
			"1/9", // VULGAR FRACTION ONE NINTH
			"1/10", // VULGAR FRACTION ONE TENTH
			"1/3", // VULGAR FRACTION ONE THIRD
			"2/3", // VULGAR FRACTION TWO THIRDS
			"1/5", // VULGAR FRACTION ONE FIFTH
			"2/5", // VULGAR FRACTION TWO FIFTHS
			"3/5", // VULGAR FRACTION THREE FIFTHS
			"4/5", // VULGAR FRACTION FOUR FIFTHS
			"1/6", // VULGAR FRACTION ONE SIXTH
			"5/6", // VULGAR FRACTION FIVE SIXTHS
			"1/8", // VULGAR FRACTION ONE EIGHTH
			"3/8", // VULGAR FRACTION THREE EIGHTHS
			"5/8", // VULGAR FRACTION FIVE EIGHTHS
			"7/8", // VULGAR FRACTION SEVEN EIGHTHS
		);
		$text = str_replace($search, $replace, $text);
		return $text;
	}

		/**
		 * Part Information by Number
		 *
		 * @param unknown $number
		 * @return unknown
		 */
		public static function part_info($number) {
			// Old - $sql = "SELECT description, suom, fbuom, conversion, vendor, adminnotes FROM " . tbpre . "parts WHERE number = '$number'";
			// Old - $row = mysql_fetch_array ( mysql_query ( $sql ) );
			$parts = Parts::select('description', 'suom', 'fbuom', 'conversion', 'vendor', 'adminnotes');
			$parts = $parts->where('number', '=', "$number");
			$row = $parts->first();
			$array ['number'] = $number;
			$array ['description'] = self::utf8_filter($row['description']);
			$array ['suom'] = $row ['suom'];
			$array ['fbuom'] = $row ['fbuom'];
			$array ['conversion'] = $row ['conversion'];
			$array ['vendor'] = $row ['vendor'];
			$array ['adminnotes'] = $row ['adminnotes'];
			return $array;
		}

		/**
		 * Alphabetical Array by View
		 *
		 * @param unknown $view
		 * @return string
		 */
		public static function alphalist($view) {
			$alpha_array = array (
					'a',
					'b',
					'c',
					'd',
					'e',
					'f',
					'g',
					'h',
					'i',
					'j',
					'k',
					'l',
					'm',
					'n',
					'o',
					'p',
					'q',
					'r',
					's',
					't',
					'u',
					'v',
					'w',
					'x',
					'y',
					'z'
			);

			foreach ( $alpha_array as $value ) {
				// Old - $sql = "SELECT 1 FROM " . tbpre . "parts WHERE description LIKE '$value%'";
				$parts = AllParts::select('number');
				$parts = $parts->where('description', 'LIKE', "$value%");
				if ($view == "approved" || $view === "fbcosts") {
					// Old - $sql .= " AND approved = 0 AND inventory = 'true'";
					$parts = $parts->where('approved', '=', "0")->where('inventory', '=', "true");
				} 			// Approved
				elseif ($view == 3) {
					// Old - $sql .= " AND inventory = 'true'";
					$parts = $parts->where('inventory', '=', "true");
				} 			// All Active
				elseif ($view == 'retired') {
					$sql .= " AND (inventory = 'false' OR inventory = '')";
					$parts = $parts->whereRaw("(inventory = 'false' OR inventory = '')");
				} 			// Retired
				elseif ($view == 'all') {
					// Old - $sql .= " ";
				} 			// All Parts
				elseif ($view == "awaiting") {
					// Old - $sql .= " AND approved = 1 AND inventory = 'true'";
					$parts = $parts->where('approved', '=', "1")->where('inventory', '=', "true");
				} 			// Awaiting Approval
				elseif ($view == "vendors") {
					// Old - $sql .= " AND vendor > 0 AND inventory = 'true'";
					$parts = $parts->where('vendor', '>', "0")->where('inventory', '=', "true");
				} 			// Vendors
				elseif ($view == "gamba") {
					// Old - $sql .= " AND number LIKE 'GMB%'";
					$parts = $parts->where('number', 'LIKE', "GMB%");
				} 			// GAMBA Parts
				elseif ($view == "fbcosts") {
				    $parts = $part->where('fishbowl', 'true');
				}
				else {
					// Old - $sql .= " AND approved = 1 AND inventory = 'true'";
					$parts = $parts->where('approved', '=', "1")->where('inventory', '=', "true");
				} // Awaiting Approval
				// Old - $num_rows = mysql_num_rows ( mysql_query ( $sql ) );
				// Old - $array [$value] ['sql'] = $sql;
				$array [$value] ['sql'] = $parts->toSql();
				// Old - $array [$value] ['num'] = $num_rows;
				$array [$value] ['num'] = $parts->count();
				if ($value != "z") {
					$array [$value] ['separator'] = "|";
				} else {
					$array [$value] ['separator'] = "";
				}
			}

			return $array;
		}

		/**
		 * Navigation Part View Alphabetically
		 *
		 * @param unknown $view
		 */
		public static function alphalistnav($path, $view, $page) {
			$url = url('/');
			if ($view != "awaiting" && $view != "lastupdated" && $view != "parts_retired_requests") {
				$alpha = self::alphalist ( $view );
				$content = <<<EOT
				<div class="pagination-centered">
				<ul class="pagination parts-nav">
EOT;
				foreach ( $alpha as $key => $value ) {
				    $content .= ""; $current_page = "";
					if ($page == $key) {
					    $current_page = ' class="current"';
					}
					$content .= "<li{$current_page}><a href=\"{$url}{$path}/{$key}\">";
					$content .= strtoupper ( $key );
					$content .= "</a></li>";
				}
				$content .= '<li><a href="'.$url . $path.'/0-9">0-9</a></li>';
				$content .= "</ul>\n</div>";
			}
			return $content;
		}

		public static function partslistnav($view, $page, $count) {
		    $url = url('/');
		    if($count > 50) {
		        $content = <<<EOT
			<div class="pagination-centered">
				<ul class="pagination parts-nav">
EOT;
		        $pages = ceil($count / 50);
                for($i = 1; $i <= $pages; $i++) {
                    $content .= "";
                    if ($value ['num'] > 0) {
                        $content .= '<li><a href="'.$url.'/parts/' . $view . '/' . $key . '">';
                    } else {
                        $content .= '<li class="current"><a>';
                    }
                    $content .= $i;
                    $content .= "</a></li>";
                }
		        $content .= <<<EOT
		        </ul>
            </div>
EOT;
		    }
		    return $content;
		}

		public static function partalphalistnav($view) {
			$url = url('/');
			if ($view != "awaiting" || $view != "lastupdated") {
				$alpha = self::alphalist ( $view );
				$content = '<ul class="pagination">';
				foreach ( $alpha as $key => $value ) {
					$content .= "";
					// if($value['num'] > 0) {
					$content .= '<li><a href="'.$url.'/supplies/parts?alpha=' . $key . '">';
					// } else {
					// $content .= '<li class="disabled"><a>';
					// }
					$content .= strtoupper ( $key );
					$content .= "</a></li>";
				}
				$content .= '<li><a href="'.$url.'/supplies/parts?view='.$view.'&alpha=nonalpha">0-9</a></li>';
				$content .= "</ul>";
			}
			return $content;
		}

		/**
		 * Checks if part exists in database
		 *
		 * @param unknown $number
		 * @return number
		 */
		public static function check_part_number_existence($number) {
			// Old - "SELECT number, description FROM " . tbpre . "parts WHERE number = '$number'";
			$parts = AllParts::select('number', 'description');
			$parts = $parts->where('number', '=', "$number");
			$row = $parts->first();
			if ($row['description'] != "") {
				$array ['part_exists'] = "true";
				$array ['part_exists_desc'] = $row ['description'];
			} else {
				$array ['part_exists'] = "false";
			}
			return $array;
		}

		/**
		 * Gets the next number from the config table for GMB parts
		 *
		 * @return string
		 */
		public static function gambaPartNumber() {
			// Old - $row = mysql_fetch_array ( mysql_query ( "SELECT value FROM " . tbpre . "config WHERE field = 'gambapartid'" ) );

			$config = Config::select('id','value');
			$config = $config->where('field', 'gambapartid');
			$row = $config->first();
			$row_id = $row['id'];
			$part_id = $row ['value'] + 1;

	// 		$update = mysql_query ( "UPDATE " . tbpre . "config SET value = $part_id WHERE field = 'gambapartid'" );

			$update = Config::where('id', $row_id)->update(['value' => $part_id]);


			$part_num = "GMB" . str_pad ( $part_id, 4, '0', STR_PAD_LEFT );
			return $part_num;
		}

		/**
		 * Add Part to GAMBA and Fishbowl
		 *
		 * @param unknown $array
		 */
		public static function part_add($array) {
	// 		echo "<pre>"; print_r($array); echo "</pre>"; //exit; die();
			$return ['add'] ['number'] = $number = trim ( $array ['number'] );
			if (preg_match ( '/^GMB/', $number )) {
				$gamba_part = 1;
			}
			$check_part_number_existence = self::check_part_number_existence ( $number );
			$return ['add'] ['part_exists'] = $part_exists = $check_part_number_existence ['part_exists'];
			$return ['add'] ['part_exists_desc'] = $check_part_number_existence ['part_exists_desc'];
			$result['alpha'] = strtolower ( substr ( $array ['description'], 0, 1 ) );
			$return ['add'] ['description'] = $description = $array ['description'] = htmlspecialchars ( self::utf8_filter($array ['description']) );
			$return ['add'] ['suom'] = $suom = $array ['suom'];
			$return ['add'] ['fbuom'] = $fbuom = $array ['fbuom'];
			if($suom == $fbuom) { $array ['conversion'] = ""; }
			$return ['add'] ['conversion'] = $conversion = $array ['conversion'];
			$return ['add'] ['cost'] = $cost = $array ['cost'];
			$return ['add'] ['fbcost'] = $fbcost = $array ['fbcost'];
			$return ['add'] ['url'] = $url = htmlspecialchars ( $array ['url'] );
			$return ['add'] ['vendor'] = $vendor = $array ['vendor'];
			$return ['add'] ['fishbowl'] = $fishbowl = $array ['fishbowl'];
			$return ['add'] ['adminnotes'] = $adminnotes = htmlspecialchars ( $array ['adminnotes'] );
			$return ['add'] ['part_options'] = $part_options = json_encode ( $array ['part_options'] );
			$created = date ( "Y-m-d H:i:s" );
			if ($part_exists == "true") {
				$return ['trigger_modal'] = "#add_part";
			}

			// Old - $return ['sql'] = $sql = "INSERT INTO " . tbpre . "parts (number, description, suom, fishbowl, cost, fbcost, vendor, fbuom, conversion, adminnotes, created, inventory, url, part_options) VALUES ('$number', \"$description\", '$suom', '$fishbowl', '$cost', '$fbcost', '$vendor', '$fbuom', '$conversion', \"$adminnotes\", '$created', 'true', '$url', '$part_options')";
	// 		echo "<p>$part_exists</p>";
	// 		echo $sql; exit; die();
			if ($part_exists == "false") {
				// Old - mysql_query ( $sql ) or die ( "Error ($sql): " . mysql_error () );
				$insert = new Parts;
					$insert->number = $number;
					$insert->description = $description;
					$insert->suom = $suom;
					$insert->cost = $cost;
					$insert->url = $url;
					$insert->inventory = 'true';
					$insert->adminnotes = $adminnotes;
					$insert->fishbowl = $fishbowl;
					$insert->vendor = $vendor;
					$insert->created = $created;
					$insert->fbuom = $fbuom;
					$insert->fbcost = $fbcost;
					$insert->conversion = $conversion;
					$insert->part_options = $part_options;
					$insert->old_id = $number;
					$insert->save();
				//$return ['sql'] = \DB::last_query();
				if ($fishbowl == "true") {
					$return ['push_part'] = gambaFishbowl::push_part ( $array );
				}
			} else {
				$result['return'] = base64_encode ( json_encode ( $return ) );
				return $result;
				exit;
			}
			if ($fishbowl == "true" && $gamba_part != 1) {
				// Old - $query = "INSERT INTO ".tbpre."inventory (number, fb_active, updated) VALUES ('$number',  '$active', '$date') ON DUPLICATE KEY UPDATE fb_active = '$active', updated = '$date'";
				// Old - mysql_query($query);
				$inventory = Inventory::firstOrCreate(array(
					'number' => $number,
					'fb_active' => $active,
					'updated' => $date,
				));
			}

			// echo "<pre>"; print_r($return); echo "</pre>"; exit; die();
			//exec(php_path . " " . Site_path . "execute_php quantity_short > /dev/null &");
			//$job = (new CalcQuantityShort())->onQueue('calculate');
			//dispatch($job);
			return $result;
		}

		public static function cw_part_add($array) {
			if($array['number'] != "" && $array['description'] != "") {
				$return ['add'] ['number'] = $number = trim ( $array ['number'] );
				$return ['add'] ['description'] = $description = htmlspecialchars ( self::utf8_filter($array ['description']) );
				$return ['add'] ['suom'] = $suom = $array ['suom'];
				$return ['add'] ['url'] = $url = htmlspecialchars ( $array ['url'] );
				$return ['add'] ['cwnotes'] = $cwnotes = $array ['cwnotes'];
				$return ['add'] ['concept'] = $concept = $array ['concept'];
				$return ['add'] ['cost'] = $cost = $array ['cost'];
				if($concept == "") {
				    $concept = 0;
				}
				if($cost == "") { $cost = "0.00"; }
				$created = date("Y-m-d H:i:s");
				// Old - $return ['sql'] = $sql = "INSERT INTO " . tbpre . "parts (number, description, suom, url, cwnotes, created, inventory, approved, fishbowl) VALUES ('$number', \"$description\", '$suom', '$url', \"$cwnotes\", '$created', 'true', '1', 'false')";
				// Old - mysql_query ( $sql ) or die ( "Error ($sql): " . mysql_error () );
				$insert = new Parts;
					$insert->number = $number;
					$insert->description = $description;
					$insert->cost = $cost;
					$insert->suom = $suom;
					$insert->url = $url;
					$insert->inventory  = "true";
					$insert->cwnotes = $cwnotes;
					$insert->created = $created;
					$insert->inventory = 'true';
					$insert->approved = 1;
					$insert->fishbowl = "false";
					$insert->concept = $concept;
					$insert->old_id = $number;
					$insert->save();
				//$return ['sql'] = \DB::last_query();
				return $return;
			}
		}

		/**
		 * Update Part to GAMBA and Fishbowl
		 *
		 * @param unknown $array
		 */
		public static function part_update($array) {
			$return ['update'] ['approval_status'] = $approval_status = $array ['approval_status'];
			$return ['number'] = $number = trim ( $array ['number'] );
			$return ['number_edit'] = $number_edit = $array ['number_edit'];
			if (preg_match ( '/GMB/i', $number )) {
				$return ['gamba_part'] = $gamba_part = 1;
			} else {
				$return ['gamba_part'] = $gamba_part = "false";
			}
			$return ['update'] ['approved'] = $approved = $array ['approved'];

			if (($approval_status == 1 && $number_edit != $number) || ($approved == 0 && $number_edit != $number)) {
				$check_part_number_existence = self::check_part_number_existence ( $number_edit );
				$return ['update'] ['part_exists'] = $part_exists = $check_part_number_existence ['part_exists'];
				$return ['update'] ['part_exists_desc'] = $check_part_number_existence ['part_exists_desc'];
			} else {
				$return ['update'] ['part_exists'] = $part_exists = "false";
			}
			$return ['update'] ['description'] = $description = htmlspecialchars ( self::utf8_filter($array ['description']) );
			$return ['update'] ['suom'] = $suom = $array ['suom'];
			$return ['update'] ['fbuom'] = $fbuom = $array ['fbuom'];
			if($suom == $fbuom) { $array ['conversion'] = ""; }
			$return ['update'] ['url'] = $url = htmlspecialchars ( $array ['url'] );
			$return ['update'] ['conversion'] = $conversion = $array ['conversion'];
			$return ['update'] ['cost'] = $cost = $array ['cost'];
			$return ['update'] ['fbcost'] = $fbcost = $array ['fbcost'];
			$return ['update'] ['vendor'] = $vendor = $array ['vendor'];
			$return ['update'] ['fishbowl'] = $fishbowl = $array ['fishbowl'];
			$return ['update'] ['inventory'] = $inventory = $array ['inventory'];
			$return ['update'] ['adminnotes'] = $adminnotes = htmlspecialchars  ( $array ['adminnotes'] );
			$return ['update'] ['cwnotes'] = $cwnotes = htmlspecialchars ( $array ['cwnotes'] );
			$return ['update'] ['part_options'] = $part_options = json_encode ( $array ['part_options'] );
			$updated = date ( "Y-m-d H:i:s" );

			if ($fishbowl == "true" && $gamba_part == 'false') {
				// Old - $query = "INSERT INTO ".tbpre."inventory (number, fb_active, updated) VALUES ('$number',  '$active', '$date') ON DUPLICATE KEY UPDATE fb_active = '$active', updated = '$updated'";
				// Old - mysql_query($query);
				$inventory= Inventory::firstOrNew([
					'number' => $number,
					'fb_active' => $active,
					'updated' => $updated,
				]);
				$return ['push_part'] = gambaFishbowl::push_part ( $array );
				$return ['push_part'] ['msg'] = "Part Pushed to Fishbowl";
			} else {
				$return ['push_part'] ['msg'] = "Part Not Pushed to Fishbowl";
			}

			$part = Parts::find($number);
				$part->approved = $approved;
				$part->description = $description;
				$part->suom = $array ['suom'];
				$part->fishbowl = $array ['fishbowl'];
				$part->inventory = $array ['inventory'];
				$part->cost = $cost;
				$part->fbcost = $fbcost;
				$part->vendor = $vendor;
				$part->fbuom = $fbuom;
				$part->conversion = $conversion;
				$part->adminnotes = $adminnotes;
				$part->cwnotes = $cwnotes;
				$part->fishbowl_response = "Fishbowl Push: {$fishbowl} | {$return['push_part']['connect_fishbowl']} | {$return['push_part']['part_push_status']} | Gamba Part: {$gamba_part} | Date: " . date("Y-m-d H:i:s");
				$part->updated = $updated;
				$part->url = $url;
				$part->part_options = $part_options;
				$part->save();

				//$return['update']['sql'] = \DB::last_query();
				// 		echo "<pre>"; print_r($return['update']['sql']); echo "</pre>"; exit; die();

			if ($part_exists == "false" && $gamba_part == 1 && $number_edit != $number) {
				$part = Parts::find($number);
					$part->number = $number_edit;
					$part->save();
			}

			if ($number != $number_edit && $part_exists == "false") {
	// 			$sql = "UPDATE " . tbpre . "supplies SET part = '$number_edit' WHERE part = '$number'";
	// 			mysql_query ( $sql ) or die ( "Error ($sql): " . mysql_error () );
				$row = Supplies::select('id')->where('part', '=', $number)->get();
				foreach($row as $key => $value) {
					if($value['id'] != "") {
						$supplies = Supplies::find($value['id']);
	    					$supplies->part = $number_edit;
	    					$supplies->save();
					}
				}
				$result['number'] = $number_edit;
			} else {
				$result['number'] = $number;
			}
			// echo "<pre>"; print_r($return); echo "</pre>"; exit; die();
			$result['return'] = base64_encode ( json_encode ( $return ) );

			$use_part_update = gambaSupplies::use_part_update ( $number );
			//exec(php_path . " " . Site_path . "execute_php quantity_short > /dev/null &");
			//$job = (new CalcQuantityShort())->onQueue('calculate');
			//dispatch($job);
			$result['alpha'] = strtolower ( substr ( trim ( $description ), 0, 1 ) );
			$result['gamba_part'] = $gamba_part;

			return $result;
		}

		/**
		 * Part Delete
		 * @param unknown $array
		 */
		public static function part_delete($array) {
			// echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
			$part_num = $array ['number'];
			$part_info = self::part_info ( $part_num );
			// Old - $return ['sql'] = $sql = "DELETE FROM " . tbpre . "parts WHERE number = '$part_num'";
			// Old - mysql_query ( $sql );
			$part = Parts::find($part_num);
			$part->delete();
			$return ['sql'] = $part->toSql();
			$return ['part_deleted'] = 1;
			$return ['part_num'] = $part_num;
			$return ['description'] = $part_info ['description'];
			$return = base64_encode ( json_encode ( $return ) );
			return $return;
		}

		public static function supply_requests($number) {
			$term = gambaTerm::year_by_status ( 'C' );
			/* Old - $result = mysql_query ( "SELECT s.id, s.packing_id, s.supplylist_id, pl.list, s.theme_id, t.name, s.grade_id, g.level, s.activity_id, a.activity_name, s.camp_id, c.alt_name, s.term, s." . $total_amount_field . " AS total_amount, s.lowest, s.itemtype, s.exclude
					FROM " . tbpre . "supplies s
					LEFT JOIN " . tbpre . "activities a ON a.id = s.activity_id
					LEFT JOIN " . tbpre . "grades g ON g.id = s.grade_id
					LEFT JOIN " . tbpre . "themes t ON t.id = s.theme_id
					LEFT JOIN " . tbpre . "camps c ON c.id = s.camp_id
					LEFT JOIN " . tbpre . "parts p ON p.number = s.part
					LEFT JOIN " . tbpre . "packinglists pl ON pl.id = s.packing_id
					WHERE s.part = '$number' ORDER BY s.term DESC, pl.list, t.name, g.level, a.activity_name" ); */
			// Old - if (mysql_num_rows ( $result ) > 0) {
			// Old - 	while ( $row = mysql_fetch_array ( $result ) ) {
			$supplies= Supplies::select('supplies.id', 'supplies.packing_id', 'supplies.supplylist_id', 'packinglists.list', 'supplies.theme_id', 'themes.name', 'supplies.grade_id', 'grades.level', 'supplies.activity_id', 'activities.activity_name', 'supplies.camp_id', 'camps.alt_name', 'supplies.term', 'supplies.packing_total', 'supplies.total_amount', 'supplies.lowest', 'supplies.itemtype', 'supplies.exclude', 'supplies.part_class');
			$supplies = $supplies->leftjoin('activities', 'activities.id', '=', 'supplies.activity_id');
			$supplies = $supplies->leftjoin('grades', 'grades.id', '=', 'supplies.grade_id');
			$supplies = $supplies->leftjoin('themes', 'themes.id', '=', 'supplies.theme_id');
			$supplies = $supplies->leftjoin('camps', 'camps.id', '=', 'supplies.camp_id');
			$supplies = $supplies->leftjoin('parts', 'parts.number', '=', 'supplies.part');
			$supplies = $supplies->leftjoin('packinglists', 'packinglists.id', '=', 'supplies.packing_id');
			$supplies = $supplies->where('supplies.part', '=', "$number");
			$supplies = $supplies->orderBy('supplies.term', 'DESC');
			$supplies = $supplies->orderBy('packinglists.list');
			$supplies = $supplies->orderBy('themes.name');
			$supplies = $supplies->orderBy('grades.level');
			$supplies = $supplies->orderBy('activities.activity_name');
			$supplies = $supplies->get();
			$basic_calc_status = config('gamba.basic_calc_status');
			if($supplies->count() > 0) {
				foreach($supplies as $key => $row) {
					$id = $row ['id'];
					$term = $row ['term'];
					$array [$term] [$id] ['packing_id'] = $row ['packing_id'];
					$array [$term] [$id] ['list'] = $row ['list'];
					$array [$term] [$id] ['supplylist_id'] = $row ['supplylist_id'];
					$array [$term] [$id] ['theme_id'] = $row ['theme_id'];
					$array [$term] [$id] ['theme_name'] = $row ['name'];
					$array [$term] [$id] ['grade_id'] = $row ['grade_id'];
					$array [$term] [$id] ['grade_level'] = $row ['level'];
					$array [$term] [$id] ['activity_id'] = $row ['activity_id'];
					$array [$term] [$id] ['activity_name'] = $row ['activity_name'];
					$array [$term] [$id] ['camp_id'] = $row ['camp_id'];
					$array [$term] [$id] ['camp_name'] = $row ['alt_name'];
					$array [$term] [$id] ['term'] = $term;
					if ($basic_calc_status == 1) {
						$total_amount = $row ['packing_total'];
					} else {
						$total_amount = $row ['total_amount'];
					}
					$array [$term] [$id] ['total_amount'] = $total_amount;
					$array [$term] [$id] ['lowest'] = $row ['lowest'];
					$array [$term] [$id] ['itemtype'] = $row ['itemtype'];
					$array [$term] [$id] ['exclude'] = $row ['exclude'];
					$array [$term] [$id] ['part_class'] = $row ['part_class'];
				}
			}
			return $array;
		}

		public static function view_partslogfile($array) {
			$url = url('/');
			$date = date ( "YmdHis" );
			$content_array['page_title'] = "Fishbowl Parts List";
			$content_array['content'] = <<<EOT

			<p><a href="{$url}/parts?view=approved&alpha=a" class="button small success">Return to Parts List</a>
			<a href="{$url}/parts/get_parts" class="button small success">Get Parts from Fishbowl</a></p>

			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/logs/export_parts.log?{$date}', function(data) {
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
		}

		public static function view_productslogfile($array) {
			$url = url('/');
			$content_array['page_title'] = "Fishbowl Products List";
			$content_array['content'] = <<<EOT

			<p><a href="{$url}/parts?view=approved&alpha=a" class="button small success">Return to Parts List</a>
			<a href="{$url}/parts/get_products" class="button small success">Get Products from Fishbowl</a></p>

			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/logs/export_products.log?{$date}', function(data) {
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
		}


	}



