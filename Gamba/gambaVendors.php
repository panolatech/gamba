<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;
	
	use App\Models\VendorParts;
	use App\Models\Vendors;
	
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaNavigation;
	use App\Gamba\gambaUsers;
	
	class gambaVendors {
		
		public static function vendor_list() {
			$vendors = Vendors::select('VendorID', 'AccountID', 'Status', 'DefPaymentTerms', 'DefShipTerms', 'Name', 'Number', 'DateCreated', 'DateModified', 'LastChangedUser', 'ActiveFlag');
			$vendors = $vendors->where('ActiveFlag', '=', 'true');
			$vendors = $vendors->orderBy('Name');
			$vendors = $vendors->get();
			if($vendors->count() > 0) {
				foreach($vendors as $key => $row) {
					$id = $row['VendorID'];
					$array['vendors'][$id]['accountid'] = $row['AccountID'];
					$array['vendors'][$id]['status'] = $row['Status'];
					$array['vendors'][$id]['paymentterms'] = $row['DefPaymentTerms'];
					$array['vendors'][$id]['shipterms'] = $row['DefShipTerms'];
					$array['vendors'][$id]['name'] = $row['Name'];
					$array['vendors'][$id]['number'] = $row['Number'];
					$array['vendors'][$id]['datecreated'] = $row['DateCreated'];
					$array['vendors'][$id]['datemodified'] = $row['DateModified'];
					$array['vendors'][$id]['lastchangeduser'] = $row['LastChangedUser'];
					$array['vendors'][$id]['activeflag'] = $row['ActiveFlag'];
				}
			}
// 			echo "<pre>"; print_r($array); echo "</pre>";
			return $array;
		}
		
		public static function ids_from_vendors() {
			$query = Vendors::select('VendorID', 'Name')->where('ActiveFlag', 'true')->orderBy('Name')->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$name = $row['Name'];
					$array['vendors']["$name"] = $row['VendorID'];
				}
			}
// 			echo "<pre>"; print_r($array); echo "</pre>";
			return $array;
		}
		
		public static function vendor_parts_term($term) {
			$vendors = self::vendor_list();
			foreach($vendors['vendors'] as $vendor_id => $vendor_values) {
				if($vendor_values['activeflag'] == "true") {
					$array['vendors'][$vendor_id]['name'] = $vendor_values['name'];
// 					$array['vendors'][$vendor_id]['supply_requests'] = $num_rows;
					$array['vendors'][$vendor_id]['sql'] = $sql;
				}
			}
			return $array;
		}
		
		public static function vendor_parts() {
			$query = VendorParts::select('vendorparts.id', 'vendorparts.partid', 'parts.partNum', 'parts.partDesc', 'vendorparts.vendorid', 'vendors.Name', 'vendorparts.vendorpartnumber', 'vendorparts.defaultflag', 'vendorparts.datelastmodified')->leftjoin('vendors', 'vendors.VendorID', '=', 'vendorparts.vendorid')->leftjoin('parts', 'parts.partid', '=', 'vendorparts.partid')->orderBy('vendorparts.defaultflag', 'DESC')->orderBy('parts.partNum')->get();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$vendorPartsArray[$key]['id'] = $row['id'];
					$vendorPartsArray[$key]['partid'] = $row['partid'];
					$vendorPartsArray[$key]['partNum'] = $row['partNum'];
					$vendorPartsArray[$key]['partDesc'] = $row['partDesc'];
					$vendorPartsArray[$key]['vendorid'] = $row['vendorid'];
					$vendorPartsArray[$key]['Name'] = $row['Name'];
					$vendorPartsArray[$key]['vendorpartnumber'] = $row['vendorpartnumber'];
					$vendorPartsArray[$key]['defaultflag'] = $row['defaultflag'];
					$vendorPartsArray[$key]['datelastmodified'] = $row['datelastmodified'];
				}
				return $vendorPartsArray;
			}
		}
		
		public static function view_fishbowl_vendors() {
			$url = url('/');
			$content_array['main_nav'] = gambaNavigation::navigation_static();
			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$content_array['page_title'] = "Fishbowl Vendors";
			$vendors = self::vendor_list();
			$content_array['content'] .= gambaDirections::getDirections('vendors_view');
			
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
					<th>ID</th>
					<th>Name</th>
					<th>Date Created</th>
					<th>Last Updated</th>
					<th>User</th>
				</tr>
			</thead>
			<tbody>
EOT;
			foreach($vendors['vendors'] as $key => $values) {
				$datecreated = date("Y-m-d h:i a", strtotime($values['datecreated']));
				$datemodified = date("Y-m-d h:i a", strtotime($values['datemodified']));
				$content_array['content'] .= <<<EOT
				<tr>
					<td>{$key}</td>
					<td>{$values['name']}</td>
					<td>{$datecreated}</td>
					<td>{$datemodified}</td>
					<td>{$values['lastchangeduser']}</td>
				</tr>
EOT;
			}
			$content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
			return $content_array;
		}
		
	}
