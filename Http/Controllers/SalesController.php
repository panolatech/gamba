<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Camps;
use App\Models\SalesOrders;

use App\Gamba\gambaFishbowl;
use App\Gamba\gambaGrades;
use App\Gamba\gambaLocations;
use App\Gamba\gambaQuantityTypes;
use App\Gamba\gambaSales;
use App\Gamba\gambaTerm;
use App\Gamba\gambaThemes;

class SalesController extends Controller
{
	
    public function index(Request $array) {
    	$content = gambaSales::view_salesorders($array, $array['r']);
    	return view('app.sales.home', ['content' => $content]);
    }
	
    public function showPackingLists(Request $array) {
    	$content = gambaSales::view_packinglists($array, $array['r']);
       	return view('app.sales.home', ['content' => $content]);
    }
	
    public function showCampLocations(Request $array) {
    	$content = gambaSales::view_camplocations($array, $array['r']);
       	return view('app.sales.home', ['content' => $content]);
    }
	
    public function showCampLocationParts(Request $array) {
    	$content = gambaSales::view_camplocationparts($array, $array['r']);
       	return view('app.sales.home', ['content' => $content]);
    }
	
    public function showSalesOrder(Request $array) {
    	$content = gambaSales::view_salesorder($array, $array['r']);
       	return view('app.sales.home', ['content' => $content]);
    }
	
    public function deleteSalesOrder(Request $array) {
    	$url = url('/');
    	$result = gambaSales::salesorderdelete($array);
       	return redirect("{$url}/sales#so_{$array['soid']}");
    }
	
    public function insertSalesOrder(Request $array) {
    	$url = url('/');
    	if($array['customer'] != "") {
	    	$result = gambaSales::salesordercreate($array);
	       	return redirect("{$url}/sales/salesorder?soid={$result}&dli={$array['dli']}&list={$array['list']}&theme={$array['theme']}&term={$array['term']}&camp={$array['camp']}&location={$array['location']}&grade={$array['grade']}&created=1&packby={$array['packby']}");
    	} else {
	       	return redirect("{$url}/sales/salesorder?dli={$array['dli']}&list={$array['list']}&theme={$array['theme']}&term={$array['term']}&camp={$array['camp']}&location={$array['location']}&grade={$array['grade']}&error=customer&packby={$array['packby']}");
    	}
    }
	
    public function updateSalesOrder(Request $array) {
    	$url = url('/');
    	$result = gambaSales::salesorderupdate($array);
    	
    	if($fulfillment_date != "") { $fulfillment_date = date("Y-m-d", strtotime($fulfillment_date)); }
    	
		if($array['submit'] == "Push Sales Order to Fishbowl") {
			$fishbowl = 'true';
			$result = gambaFishbowl::push_sales_order($array);
			$fishbowl = $result['fishbowl'];
			if($result['statuscode'] != 1000) {
				$result = base64_encode(json_encode($result));
				return redirect("{$url}/sales/salesorder?soid={$array['soid']}&dli={$array['dli']}&list={$array['list']}&theme={$array['theme']}&term={$array['term']}&camp={$array['camp']}&location={$array['location']}&grade={$array['grade']}&packby={$array['packby']}&r={$result}");
				exit;
			}
		}
		
		if($customer_id != "") {
			$update = SalesOrders::find($array['soid']);
				$update->customer = $array['customer_id'];
				$update->fishbowl = $fishbowl;
				$update->fulfillment_date = $fulfillment_date;
				$update->save();
		}

    	if($array['customer_id'] != "") {
    		return redirect("{$url}/sales/salesorder?customer={$array['customer_id']}&action=salesorder&soid={$array['soid']}&dli={$array['dli']}&list={$array['list']}&theme={$array['theme']}&term={$array['term']}&camp={$array['camp']}&location={$array['location']}&grade={$array['grade']}&r=$result&packby={$array['packby']}");
    		exit;
    	} else {
    		return redirect("{$url}/sales/salesorder?action=salesorder&soid={$array['soid']}&dli={$array['dli']}&list={$array['list']}&theme={$array['theme']}&term={$array['term']}&camp={$array['camp']}&location={$array['location']}&grade={$array['grade']}&error=customer&packby={$array['packby']}");
    		exit;
    	}
       	
    }
	
    public function markPushed(Request $array) {
    	$url = url('/');
    	$result = gambaSales::mark_pushed($array);
       	return redirect("{$url}/sales#so_{$array['soid']}");
    }
    
    public function create_supplemental() {
    	$query = Camps::select('id', 'abbr', 'name', 'alt_name', 'camp_values')->orderBy('id')->get();
    	foreach($query as $values) {
    		$camp_values = json_decode($values['camp_values'], true);
    		if($camp_values['active'] == "true") {
	    		$content['camps'][$values['id']]['name'] = $values['name'];
	    		$content['camps'][$values['id']]['abbr'] = $values['abbr'];
	    		$content['camps'][$values['id']]['alt_name'] = $values['alt_name'];
    		}
    	}
    	$content['grades'] = gambaGrades::grade_list();
    	$content['quantity_types'] = gambaQuantityTypes::quantity_types_by_camp(1, 2017);
    	return view('app.sales.createsupplemental', ['array' => $content]);
    }
    
    public function get_locations(Request $array) {
    	$current_term = gambaTerm::year_by_status('C');
    	$locations = gambaLocations::locations_with_camps();
    	$i = 0;
    	//$array['id'] = 1;
    	foreach($locations['camps'][$array['id']]['locations'] as $id => $values) {
    		if($values['terms'][$current_term]['active'] == "Yes") {
	    		//$json_array[$i]['location_id'] = $id;
	    		//$json_array[$i]['name'] = $values['name'];
	    		//$json_array[$i]['abbr'] = $values['abbr'];
	    		$json_array['locations'][$id] = "{$values['abbr']} - {$values['name']}";
	    		$i++;
    		}
    	}
    	$json = json_encode($json_array);
    	echo $json;
    	//echo "[{\"location_id\": 1, \"name\": \"Alameda\"}, {\"location_id\": 2, \"name\": \"Berkeley\"}]";
    	
    }
    
    public function get_themes(Request $array) {
    	$current_term = gambaTerm::year_by_status('C');
    	$themes = gambaThemes::themes_by_camp($array['id'], $current_term);
    	foreach($themes as $theme_id => $values) {
    		$json_array['themes'][$theme_id] = $values['name'];
    	}
    	$json = json_encode($json_array);
    	echo $json;
    }

    public function get_grades(Request $array) {
    	$current_term = gambaTerm::year_by_status('C');
    	$grades = gambaGrades::grade_list();
    	foreach($grades[$array['id']]['grades'] as $grade_id => $values) {
    		if($values['enrollment'] == 1) {
    			$json_array['grades'][$grade_id] = $values['level'];
    		}
    	}
    	$json = json_encode($json_array);
    	echo $json;
    }

    public function get_quantitytypes(Request $array) {
    	$current_term = gambaTerm::year_by_status('C');
    	$quantity_types = gambaQuantityTypes::quantity_types_by_camp($array['id'], $current_term);
    	foreach($quantity_types['dropdown'] as $id => $values) {
    		if($values['qt_options']['kqd'] == 1 && $values['qt_options']['terms'][$current_term] == "true") {
    			$json_array['quantity_types'][$id] = $values['name'];
    		}
    	}
    	//$json_array['quantity_types']['all'] = "All";
    	$json = json_encode($json_array);
    	echo $json;
    }
    
    public function get_saleslist(Request $array) {
    	//$form_data = $array['form_data'];
    	$query['camp'] = $array['camp'];
    	$query['enrollment'] = $array['enrollment'];
    	$query['location'] = $array['location'];
    	$query['theme'] = $array['theme'];
    	$query['grade'] = $array['grade'];
    	$query['itemtype'] = $array['itemtype'];
    	$query['quantity_type'] = $array['quantity_type'];
    	$results = gambaSales::supplemental_orders($query);
    	$i = 1;
    	$date = date("F j, Y");
    	print <<<EOT
<div class="directions">
	<strong>Directions:</strong> Review the below Sales Order, select Customer and Fulfillment Date, then click on the Create Sales Order button at the bottom.
</div>
<input type="hidden" name="camp" value="{$array['camp']}" />
<input type="hidden" name="enrollment" value="{$array['enrollment']}" />
<input type="hidden" name="location" value="{$array['location']}" />
<input type="hidden" name="theme" value="{$array['theme']}" />
<input type="hidden" name="grade" value="{$array['grade']}" />
<input type="hidden" name="itemtype" value="{$array['itemtype']}" />
<input type="hidden" name="quantity_type" value="{$array['quantity_type']}" />
<input type="hidden" name="term" value="{$results['term']}" />
<p>Number of Items: {$results['total']} &nbsp;&nbsp;&nbsp; Timestamp: {$results['timestamp']}</p>
<table>
	<thead>
		<tr>
			<th>#</th>
			<th class="right">Number</th>
			<th>Description</th>
			<th class="center">Qty</th>
			<th class="center">UoM</th>
			<th class="center">FB UoM</th>
			<th class="right">Unit Price</th>
			<th class="center">Type</th>
			<th class="center">Status</th>
			<th>Date Scheduled</th>
		</tr>
	</thead>
	<tbody>
EOT;
//     	{$results['form_data']}
//     	{$results['sql']}
    	foreach($results['parts'] as $part => $values) {
    		print <<<EOT
		<tr>						
			<td>{$i}</td>
			<td>{$part}</td>
			<td>{$values['description']}
			<input type="hidden" name="products[{$part}][part_desc]" value="{$values['description']}" />
			<input type="hidden" name="products[{$part}][qty]" value="{$values['qty']}" />
			<input type="hidden" name="products[{$part}][uom]" value="{$values['fbuom']}" />
			<input type="hidden" name="products[{$part}][price]" value="{$values['price']}" />
			<input type="hidden" name="products[{$part}][date]" value="{$date}" />
			</td>
			<td class="center">{$values['qty']}</td>
			<td class="center">{$values['suom']}</td>
			<td class="center" title="">{$values['fbuom']}</td>
			<td>{$values['price']}</td>
			<td class="center">Sale</td>
			<td class="center">Entered</td>
			<td>{$date}</td>
		</tr>
EOT;
    		$i++;
    	}
//     	{$results['kqd_quantity_types']}
    	print <<<EOT
	</tbody>
</table>
		<script>
		 $(function() {
		    $( ".datepicker" ).datepicker();
		  });
		</script>
EOT;
    	// customer addresses
    	if(count($results['customers']) > 1) {
    		print <<<EOT
	<div class="row">
		<div class="large-2 medium-2 small-12 columns">
			<label class="right" for="customers">Customer:</label> 
		</div>
		<div class="large-4 medium-4 small-12 columns">
    		<select name="customer" id="customers">
EOT;
    		foreach($results['customers'] as $key => $customers) {
    			print <<<EOT
    			<option value="{$customers['id']}">{$customers['name']}</option>
EOT;
    		}
    		print <<<EOT
			</select>
    	</div>
		<div class="large-2 medium-2 small-12 columns">
    		<label class="right" for="customers">Fulfillment Date:</label> 
		</div>
		<div class="large-4 medium-4 small-12 columns">
    		<input type="text" name="fulfillment_date" class="datepicker" required />
    	</div>
    </div>
EOT;
    	} else {
    		print <<<EOT
	<div class="row">
		<div class="large-2 medium-2 small-12 columns">
			<label class="right" for="customers">Customer:</label> 
		</div>
		<div class="large-4 medium-4 small-12 columns">
    		<strong>Customer:</strong> {$results['customers'][0]['name']}
    		<input type="hidden" name="customer" value="{$results['customers'][0]['id']}" />
    	</div>
		<div class="large-2 medium-2 small-12 columns">
    		<label class="right" for="customers">Fulfillment Date:</label> 
		</div>
		<div class="large-4 medium-4 small-12 columns">
    		<input type="text" name="fulfillment_date" class="datepicker" required />
    	</div>
    </div>
EOT;
    	}
    	print <<<EOT
<p><input type="submit" name="submit" value="Create Sales Order" class="button small radius success" /></p>
EOT;
		//echo "<pre>"; print_r($results); echo "</pre>";
    }
    
    public static function create_supplemental_order(Request $array) {
//     	echo "<pre>"; print_r($array['products']); echo "</pre>"; exit; die();
    	$url = url('/');
    	$current_term = gambaTerm::year_by_status('C');
    	$json_products = json_encode($array['products']);
		$soid = SalesOrders::insertGetId([
			'customer' => $array['customer'], 
			'fishbowl' => 'false', 
			'list' => 0, 
			'term' => $current_term, 
			'camp' => $array['camp'], 
			'theme' => $array['theme'], 
			'grade' => $array['grade'], 
			'location' => $array['location'], 
			'dli' => 0, 
			'date_created' => date("Y-m-d H:i:s"), 
			'fulfillment_date' => date("Y-m-d", strtotime($array['fulfillment_date'])),
			'supplemental' => '1',
			'supplemental_parts' => $json_products
		]);
    	return redirect("{$url}/sales/salesorder?soid={$soid}&theme={$array['theme']}&term={$array['term']}&camp={$array['camp']}&location={$array['location']}&grade={$array['grade']}&created=1");
    }
}
