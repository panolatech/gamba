<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;
	
	use App\Models\Customers;
	use App\Models\CustomerAddresses;
	
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaNavigation;
	use App\Gamba\gambaUsers;
	
	class gambaCustomers {
		
		public static function customer_list() {
			$customers = Customers::select('CustomerID', 'AccountID', 'Status', 'DefPaymentTerms', 'DefShipTerms', 'TaxRate', 'Name', 'Number', 'DateCreated', 'DateLastModified', 'LastChangedUser', 'CreditLimit', 'TaxExemptNumber', 'Note', 'ActiveFlag', 'AccountingID', 'DefaultSalesman', 'JobDepth');
			$customers = $customers->where('ActiveFlag', '=', 'true');
			$customers = $customers->orderBy('Name');
			$customers = $customers->get();
			if($customers->count() > 0) {
				foreach($customers as $id => $row) {
					$array['customers'][$id]['CustomerID'] = $row['CustomerID']; 
					$array['customers'][$id]['AccountID'] = $row['AccountID'];
					$array['customers'][$id]['Status'] = $row['Status'];
					$array['customers'][$id]['DefPaymentTerms'] = $row['DefPaymentTerms'];
					$array['customers'][$id]['DefShipTerms'] = $row['DefShipTerms'];
					$array['customers'][$id]['TaxRate'] = $row['TaxRate'];
					$array['customers'][$id]['Name'] = $customer_name = $row['Name'];
					// The Camp Location Name needs to be the same as the Customer Name to Work 6/18/13
					$array['customers'][$id]['Number'] = $row['Number'];
// 					$array['customers'][$id]['Address'] = self::addresses($customer_name);
					$array['customers'][$id]['DateCreated'] = $row['DateCreated'];
					$array['customers'][$id]['DateLastModified'] = $row['DateLastModified'];
					$array['customers'][$id]['LastChangedUser'] = $row['LastChangedUser'];
					$array['customers'][$id]['CreditLimit'] = $row['CreditLimit'];
					$array['customers'][$id]['TaxExemptNumber'] = $row['TaxExemptNumber'];
					$array['customers'][$id]['Note'] = $row['Note'];
					$array['customers'][$id]['ActiveFlag'] = $row['ActiveFlag'];
					$array['customers'][$id]['AccountingID'] = $row['AccountingID'];
					$array['customers'][$id]['DefaultSalesman'] = $row['DefaultSalesman'];
					$array['customers'][$id]['JobDepth'] = $row['JobDepth'];
				}
			}
			return $array;
		}
		
		public static function addresses($customer_name, $camp_abbr) {
			$query = CustomerAddresses::select('name', 'attn', 'street', 'city', 'zip', 'state', 'country');
			$where = "name = '$customer_name'"; if($camp_abbr != "") { $where .= " OR name = '$camp_abbr'"; }
			$query = $query->whereRaw($where);
			$array['sql'] = $query->toSql();
			$row = $query->first();
			if($row['name'] != "") {
				$array['name'] = $row['name'];
				$array['status'] = "true";
				$array['attn'] = $row['attn'];
				$array['street'] = $row['street'];
				$array['city'] = $row['city'];
				$array['zip'] = $row['zip'];
				$array['state'] = $row['state'];
				$array['country'] = $row['country'];
				$array['sql'] = $sql;
			} else {
				$array['status'] = "false";
				$array['msg'] = "There is no product in the database for this part $part_num";
			}
			return $array;
		}
		
		public static function view_fishbowl_customers() {
			$url = url('/');
			$customers = self::customer_list();
			
			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$content_array['page_title'] = "Fishbowl Customers";
			$content_array['content'] .= gambaDirections::getDirections('customers_view');
			
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
					<th>FB Customer ID</th>
					<th>Name</th>
					<th>Date Created</th>
					<th>Last Updated</th>
					<th>User</th>
					<th>Active Flag</th>
				</tr>
			</thead>
			<tbody>
EOT;
			foreach($customers['customers'] as $key => $values) {
				$datecreated = date("Y-m-d h:i a", strtotime($values['DateCreated']));
				$datelastmodified = date("Y-m-d h:i a", strtotime($values['DateLastModified']));
				$content_array['content'] .= <<<EOT
				<tr>
					<td>{$values['CustomerID']}</td>
					<td>{$values['Name']}</td>
					<td>{$datecreated}</td>
					<td>{$datelastmodified}</td>
					<td>{$values['LastChangedUser']}</td>
					<td>{$values['ActiveFlag']}</td>
				</tr>
EOT;
			}
			$content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
			return $content_array;
// 			echo "<pre>"; print_r($customers); echo "</pre>";
		}
	}
