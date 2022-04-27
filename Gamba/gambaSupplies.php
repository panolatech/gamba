<?php
namespace App\Gamba;

use Illuminate\Support\Facades\Session;

use App\Models\Config;
use App\Models\MaterialClassifications;
use App\Models\Parts;
use App\Models\RequestLog;
use App\Models\Supplies;
use App\Models\SupplyLists;

use App\Gamba\gambaActivities;
use App\Gamba\gambaCalc;
use App\Gamba\gambaCampCategories;
use App\Gamba\gambaCosts;
use App\Gamba\gambaGrades;
use App\Gamba\gambaLocations;
use App\Gamba\gambaLogs;
use App\Gamba\gambaPacking;
use App\Gamba\gambaParts;
use App\Gamba\gambaQuantityTypes;
use App\Gamba\gambaTerm;
use App\Gamba\gambaThemes;
use App\Gamba\gambaUOMs;
use App\Gamba\gambaUsers;

use App\Jobs\CalcAllPackingLists;
use App\Jobs\CalcPackingTotals;

class gambaSupplies {

    public static function supplies_per_list($id) {
        $num_rows = Supplies::select('id')->where('supplylist_id', $id)->get();
        $array['num_rows'] = $num_rows->count();
        $num_rows = Supplies::select('supplies.id')->leftjoin('parts', 'parts.number', '=', 'supplies.part')->where('supplies.supplylist_id', $id)->where('parts.fishbowl', 'true')->get();
        $array['num_fb_rows'] = $num_rows->count();
        return $array;
    }

    /**
     * Theme Budget - Used in Supplies and Material Cost Analysis
     * Set in Admin > Settings > Configuration
     * @return unknown
     */
    public static function theme_budget() {
        $row = Config::select('value')->where('field', 'theme_budget_display')->first();


        return $row['value'];
    }

    public static function cw_notes($part, $term, $theme = "", $grade = "", $list = "") {
        $query = Supplies::select('id', 'supplylist_id', 'activity_id', 'notes')->where('part', $part)->where('term', $term);
        if($theme != "") {
            $query = $query->where('theme_id', $theme);
        }
        if($grade != "") {
            $query = $query->where('grade_id', $grade);
        }
        if($list != "") {
            $query = $query->where('packing_id', $list);
        }
        $result = $query->get();
        if($result->count() > 0) {
            foreach($result as $key => $row) {
                $id = $row['id'];
                if($row['notes'] != "") {
                    $array[$id]['supplylist_id'] = $row['supplylist_id'];
                    $array[$id]['activity_id'] = $activity_id = $row['activity_id'];
                    $array[$id]['activity_info'] = gambaActivities::activity_info($activity_id);
                    $array[$id]['notes'] = $row['notes'];
                }
            }
        }
        return $array;
    }

    public static function number_supplies_by_activity($activity_id) {
        $number_rows = Supplies::where('activity_id', $activity_id)->count();
        return $number_rows;
    }

    public static function cw_notes_bypart_filtered($part, $term) {
        $query = Supplies::select('supplies.id', 'supplies.notes', 'supplies.activity_id')->leftjoin('packinglists', 'packinglists.id', '=', 'supplies.packing_id')->where('supplies.notes', '!=', '')->where('supplies.part', $part)->where('supplies.term', $term)->where('packinglists.hide', '1')->get();

        $array = array();
        if($query->count() > 0) {
            foreach($query as $key => $row) {
                $array[$row['id']]['activity_info'] = gambaActivities::activity_info($row['activity_id']);
                $array[$row['id']]['notes'] = $row['notes'];
            }
        }
        return $array;
    }

    public static function delete_material_request($array) {
        $user_name = Session::get('name');
        $list_id = $array['id'];
        $term = $array['term'];
        $supply_id = $array['supply_id'];
        $camp = $array['camp'];
        $number = $array['part'];
        $activity_id = $array['activity_id'];
        $part_info = gambaParts::part_info($number);
        $delete = Supplies::find($supply_id)->delete();
        // DELETE ANY SALES ORDERS
        // CAMP G A&S: CHECK PART NUMBERS AND CHECK IF ANY ARE STANDARD AND NON-STANDARD
        // CHECK HIGHEST AMOUNT
        $return['delete'] = 1;
        $return['number'] = $part_info['number'];
        $return['description'] = $part_info['description'];
        $return['suom'] = $part_info['suom'];
        $return = base64_encode(json_encode($return));
        $cw_name = Session::get('name');
        if($cw_name == "") {
            $cw_name = "Unknown User";
        }
        self::request_log($cw_name, $list_id, "Delete from List");
        return $return;
    }

    public static function move_materials($array) {
        // 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
        $supplylist_id = $array['id'];
        $camp = $array['camp'];
        $new_camp = $array['new_camp'];
        $term = $array['term'];
        $activity_id = $array['activity_id'];
        $packing = gambaPacking::packing_lists();
        $packing_camps = $packing['camps'][$new_camp];
        reset($packing_camps['lists']);
        list($key, $values) = each($packing_camps['lists']);
        $packing_id = $key;
        if($new_camp != "") {
            $update = SupplyLists::find($supplylist_id);
            $update->camp_type = $new_camp;
            $update->save();
            $update = Supplies::where('supplylist_id', $supplylist_id)->update([
                'camp_id' => $new_camp,
                'packing_id' => $packing_id
            ]);
            if($array['recalc'] == 1) {
                $job = (new CalcAllPackingLists($term, $camp))->onQueue('calculate');
                dispatch($job);
                $job = (new CalcAllPackingLists($term, $new_camp))->onQueue('calculate');
                dispatch($job);
            }
        }
        gambaPacking::delete_all_orphans($term, $packing_id);

    }

    public static function move_materials_change($camp, $id, $term, $activity_id, $button_disabled, $locked, $packing_list_ids) {
        $url = url('/');
        $camps = gambaCampCategories::camps_list();
        $user_id = Session::get('uid');
        if($locked == "true") {
            $lock_selector = <<<EOT
				<a href="{$url}/supplies/admin_unlock?camp={$camp}&id={$id}&term={$term}&activity_id={$activity_id}&packing_list_ids={$packing_list_ids}" class="button small success radius" id="unlock_list">Unlock Material List</a>
EOT;
        } else {
            $lock_selector = <<<EOT
				<a href="{$url}/supplies/admin_lock?camp={$camp}&id={$id}&term={$term}&activity_id={$activity_id}" class="button small alert radius">Lock Material List</a>
EOT;
            // Site Admin, Logesh Kumaar, Chad Whitney
            if($user_id == 1 || $user_id == 373 || $user_id == 371) {
                $lock_selector .= <<<EOT
				<a href="{$url}/supplies/admin_lock_debug?camp={$camp}&id={$id}&term={$term}&activity_id={$activity_id}&debug=1" class="button small alert radius">Lock List (Debug)</a>
EOT;
            }
        }
        $content .= <<<EOT
			<script>
				 $(document).ready(function(){
				 	$("#unlock_list").on("click", function unlock_progress(event){
				 		$.LoadingOverlay("show");
				 	});
				 });
			</script>
			<p><a href="#" data-reveal-id="move_materials_change" class="button small success radius{$button_disabled}">Move Material List</a> &nbsp;&nbsp;&nbsp; {$lock_selector}</p>
			<a href="{$url}/supplies/downloadcsv?camp={$camp}&id={$id}&term={$term}&activity_id={$activity_id}&file_name=cwmateriallist" class="button small success radius">Download CSV File</a></p>
			<div id="move_materials_change" class="reveal-modal" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
				<form name="form-data-inputs" action="{$url}/supplies/move_materials" method="post" class="form-horizontal">
EOT;
        $content .= csrf_field();
        $content .= <<<EOT

							<h2 id="modalTitle">Material List Move</h2>

						<p>Moving a material list should only be done before an admin locked has been performed and packing calculations performed. Select a camp category and move the list.</p>

					<div class="row">
						<div class="small-12 medium-4 large-4 columns">
							<label class="">
								Camp Category
							</label>
						</div>
						<div class="small-12 medium-8 large-8 columns">
							<select name="new_camp">
EOT;
        foreach($camps as $key => $value) {
            $content .= <<<EOT
							<option value="{$key}">{$value['name']}</option>
EOT;
        }
        $content .= <<<EOT
							</select>
						</div>
					</div>

					<!--<div class="row">
						<div class="small-12 medium-4 large-4 columns">
							<label class="">Recalculate</label>
						</div>
						<div class="small-12 medium-8 large-8 columns">
							<input type="checkbox" name="recalc" value="1" />
						</div>
					</div>-->

					<p><button type="button" class="button small radius" data-dismiss="modal">Cancel</button>
						<button type="submit" class="button small success radius">Move Material List</button></p>

					<input type="hidden" name="action" value="move_materials" />
					<input type="hidden" name="id" value="{$id}" />
					<input type="hidden" name="term" value="{$term}" />
					<input type="hidden" name="camp" value="{$camp}" />
					<input type="hidden" name="activity_id" value="{$activity_id}" />
				</form>
				<a class="close-reveal-modal" aria-label="Close">&#215;</a>
			</div>
EOT;
        return $content;
    }

    public static function delete_list($array) {
        $list_id = $array['id'];
        $activity_id = $array['activity_id'];
        $term = $array['$term'];
        $activity_info = gambaActivities::activity_info($activity_id);
        // DELETE ANY SALES ORDERS. DO BEFORE DELETING SUPPLY REQUESTS
        // CAMP G A&S: CHECK PART NUMBERS AND CHECK IF ANY ARE STANDARD AND NON-STANDARD
        // CHECK HIGHEST AMOUNT
        $delete = SupplyLists::find($list_id)->delete();
        $delete = Supplies::where('supplylist_id', $list_id)->delete();
        $return['delete'] = 1;
        $return['name'] = $activity_info['name'];
        $return['grade_name'] = $activity_info['grade_name'];
        $return['theme_name'] = $activity_info['theme_name'];
        $return['camp_name'] = $activity_info['camp_name'];
        $return = base64_encode(json_encode($return));
        return $return;
    }

    public static function createlist($array) {
        $activity_id = $array['activity_id'];
        $camp = $array['camp'];
        $term = $array['term'];
        $user_id = Session::get('uid');
        $user_name = Session::get('name');
        $created = date("Y-m-d H:i:s");
        $user_id = 1;
        $result['id'] = $id = SupplyLists::insertGetId(['activity_id' => $activity_id, 'term' => $term, 'camp_type' => $camp, 'user_id' => $user_id, 'created' => $created]);
        $cw_name = $user_name;
        if($cw_name == "") {
            $cw_name = "Unknown User";
        }
        self::request_log($cw_name, $id, "Create List");
        return $result;
    }

    public static function admin_lock($array) {
        $calc_array['camp'] = $camp = $array['camp'];
        $calc_array['term'] = $term = $array['term'];
        $calc_array['debug'] = $array['debug'];
        $calc_array['activity_id'] = $activity_id = $array['activity_id'];
        $calc_array['id'] = $supplylist_id = $array['id'];
        // 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();

        $supplyparts = self::supplyparts($supplylist_id);
        $activity_info = gambaActivities::activity_info($activity_id);
        $qts = gambaQuantityTypes::quantity_types_by_camp($camp, $term);
        // 			echo "<pre>"; print_r($supplyparts); echo "</pre>"; exit; die();

        foreach($supplyparts['supplies'] as $supply_id => $values) {
            if($values['exclude'] == 0) {
                $calc_array['update'][$supply_id]['part'] = $values['part'];
                $calc_array['update'][$supply_id]['description'] = $values['description'];
                $calc_array['update'][$supply_id]['exclude'] = $values['exclude'];
                if(is_array($qts['static'])) {
                    foreach($qts['static'] as $key => $value) {
                        $calc_array['update'][$supply_id]['request_quantities']['static'][$key] = $values['request_quantities']['static'][$key];
                    }
                }
                if(is_array($qts['dropdown'])) {
                    $calc_array['update'][$supply_id]['request_quantities']['quantity_val'] = $values['request_quantities']['quantity_val'];
                    $calc_array['update'][$supply_id]['request_quantities']['quantity_type_id'] = $values['request_quantities']['quantity_type_id'];
                }
                if(is_array($values['location_quantities'])) {
                    $calc_array['update'][$supply_id]['location_quantities'] = $values['location_quantities'];
                }
                $calc_array['update'][$supply_id]['itemtype'] = $values['itemtype'];
                if($day_col == "true") {
                    $calc_array['update'][$supply_id]['request_quantities']['day'] = $values['request_quantities']['day'];
                }
                $calc_array['update'][$supply_id]['notes'] = $values['notes'];
            }
        }
        // 			echo "<pre>"; print_r($calc_array); echo "</pre>"; exit; die();
        // 			$calc_array['debug'] = 1;
        gambaLogs::truncate_log('enroll_calc.log');
        gambaLogs::truncate_log('camp_calc.log');
        gambaLogs::truncate_log('supplies_calc.log');
        $update = SupplyLists::find($array['id']);
            $update->locked = 'true';
            $update->save();
        $calc = gambaCalc::calculate_from_requests($calc_array);
        return $calc;
    }

    public static function admin_unlock($array) {
        $update = SupplyLists::find($array['id']);
        $update->locked = 'false';
        $update->save();

        $packing_list_ids = explode(',', $array['packing_list_ids']);
        // 			echo '<pre>'; print_r($packing_list_ids); echo "</pre>"; exit; die();
        foreach($packing_list_ids as $key => $packing_id) {
            //gambaPacking::packing_totals_calc_all($array['term'], $packing_id);
            $job = (new CalcPackingTotals($array['term'], $packing_id, 1))->onQueue('calculate');
            dispatch($job);
        }
    }

    public static function add_supply_requests($array) {
        $user_name = Session::get('name');
        // 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
        $part_prefixes = gambaParts::part_prefixes();
        $term = $array['term'];
        $supplylist_id = $array['id'];
        $camp = $array['camp'];
        $camps = gambaCampCategories::camps_list();
        $activity_id = $array['activity_id'];
        $activity_info = gambaActivities::activity_info($activity_id);
        $grade_id = $activity_info['grade_id'];
        $theme_id = $activity_info['theme_id'];
        $camps = gambaCampCategories::camps_list("");
        $standard = $camps[$camp]['camp_values']['standard'];
        $packing_lists = gambaPacking::packing_lists();
        $camp_packing_lists = $packing_lists['camps'][$camp];
        reset($camp_packing_lists['lists']);
        list($key, $values) = each($camp_packing_lists['lists']);
        $packing_id = $key;

        foreach($array['add'] as $add_row => $add_values) {
            if($add_values['material_type'] == "add") {
                $debug_array[$add_row]['part'] = $add_values['part'];
                list($number, $description) = explode("|", $add_values['part']);
                $number = trim($number);
                $array['add'][$add_row]['part'] = $number;
                $check_part_number_existence = gambaParts::check_part_number_existence($number);
                $exists = $check_part_number_existence['part_exists'];
            }
            if($add_values['material_type'] == "create") {
                if($add_values['part'] != "") {
                    $debug_array[$add_row]['part'] = $add_values['part'];
                    $exists = "true";
                    $part_array['number'] = $number = gambaParts::gambaPartNumber();
                    $part_array['description'] = $description = $add_values['part'];
                    $part_array['suom'] = $suom = $add_values['suom'];
                    $part_array['cwnotes'] = $cwnotes = $add_values['notes'];
                    $part_array['url'] = $url = $add_values['url'];
                    $part_array['concept'] = $add_values['concept'];
                    $part_array['cost'] = $add_values['cost'];
                } else {
                    $exists = "false";
                }
            }
            $debug_array[$add_row]['number'] = $number;
            $debug_array[$add_row]['exists'] = $exists;
            if($exists == "true" && $number != "") {
                $itemtype = $add_values['itemtype'];
                $notes = htmlspecialchars($add_values['notes']);
                if(is_array($add_values['request_quantities'])) {
                    $request_quantities = json_encode($add_values['request_quantities']);
                }
                $location_quantities_array = "";
                if(is_array($add_values['location_quantities'])) {
                    foreach($add_values['location_quantities'] as $location_id => $quantity) {
                        if($quantity['value'] != "") {
                            // $quantity should not be an array
                            $location_quantities_array[$location_id] = $quantity;
                        }
                    }
                    $location_quantities = json_encode($location_quantities_array);
                }
                $id = Supplies::insertGetId([
                    'packing_id' => $packing_id,
                    'supplylist_id' => $supplylist_id,
                    'theme_id' => $theme_id,
                    'grade_id' => $grade_id,
                    'activity_id' => $activity_id,
                    'camp_id' => $camp,
                    'term' => $term,
                    'part' => $number,
                    'itemtype' => $itemtype,
                    'notes' => $notes,
                    'request_quantities' => $request_quantities,
                    'location_quantities' => $location_quantities
                ]);
                //$debug_array[$add_row]['sql'] = \DB::last_query();
                $add_array['update'][$id]['part'] = $number;
                $add_array['update'][$id]['description'] = $description;
                $add_array['update'][$id]['request_quantities'] = $add_values['request_quantities'];
                $add_array['update'][$id]['location_quantities'] = $add_values['location_quantities'];
                $add_array['update'][$id]['itemtype'] = $add_values['itemtype'];
                $add_array['update'][$id]['notes'] = $add_values['notes'];
                if($add_values['material_type'] == "create") {
                    gambaParts::cw_part_add($part_array);
                }
            } else {
                $return['add_fail'][] = $add_values['part'];
            }
        }
        $add_array['term'] = $term;
        $add_array['camp'] = $camp;
        $add_array['id'] = $supplylist_id;
        $add_array['activity_id'] = $activity_id;


        $cw_name = $user_name;
        if($cw_name == "") {
            $cw_name = "Unknown User";
        }
        gambaCosts::material_list_calculate($camp, $term, $supplylist_id);
        self::request_log($cw_name, $array['id'], "Add to List");
        // 			gambaLogs::truncate_log('enroll_calc.log');
        // 			gambaLogs::truncate_log('camp_calc.log');
        // 			gambaLogs::truncate_log('supplies_calc.log');
        // Commented out for Admin Lock
        // $calc = gambaCalc::calculate_from_requests($add_array);
        return $return;
    }

    public static function use_part_update($part) {
        $term = gambaTerm::year_by_status('C');
        $query = Supplies::select(
            'supplies.id',
            'supplies.supplylist_id',
            'supplies.activity_id',
            'supplies.camp_id',
            'supplies.term',
            'supplies.part',
            'supplies.itemtype',
            'supplies.request_quantities');
        $query = $query->leftjoin('supplylists', 'supplylists.id', '=', 'supplies.supplylist_id');
        $query = $query->where('supplylists.locked', 'true');
        $query = $query->where('supplies.part', $part);
        $query = $query->where('supplies.term', $term);
        $query = $query->where('supplies.camp_id', '1');
        $query = $query->orderBy('supplies.term')->orderBy('supplies.camp_id');
        $query = $query->get();
        if($query->count() > 0) {
            foreach($query as $key => $row) {
                $supply_id = $row['id'];
                $array['activity_id'] = $row['activity_id'];
                $array['camp'] = $row['camp_id'];
                $array['term'] = $row['term'];
                $array['update'][$supply_id]['part'] = $row['part'];
                // Need to add the static
                $array['update'][$supply_id]['itemtype'] = $row['itemtype'];
                $request_quantities = json_decode($row->request_quantities, true);
                // Drop Down Quantity Type and Value
                $array['update'][$supply_id]['request_quantities']['quantity_val'] = $request_quantities['quantity_val'];
                $array['update'][$supply_id]['request_quantities']['quantity_type_id'] = $request_quantities['quantity_type_id'];
                gambaCalc::calculate_from_requests($array);
            }
        }
        // 			echo "<pre>"; print_r($array); echo "</pre>";
        // 			exit; die();
    }

    /**
     * Supplies Update
     * @param unknown $array
     */
    public static function supplies_update($array) {;
    $user_name = Session::get('name');
    $url = url('/');
// 	echo "<pre>"; print_r($array['update']); echo "</pre>";
// 	exit; die();
    gambaLogs::truncate_log('camp_calc.log');
    gambaLogs::truncate_log('costs.log');
    $supplylist_id = $array['id'];
    $camp = $array['camp'];
    $term = $array['term'];
    $camps = gambaCampCategories::camps_list();
    foreach($array['update'] as $update_row => $update_values) {
        if($update_values['delete'] == "true") {
            $supplies = Supplies::find($update_row);
            $supplies->delete();
        } else {
            $itemtype = $update_values['itemtype'];
            $exclude = $update_values['exclude'];
            $notes = htmlspecialchars($update_values['notes']);
            if(is_array($update_values['request_quantities'])) {
                $request_quantities = json_encode($update_values['request_quantities']);
            }
            if(is_array($update_values['location_quantities'])) {
                foreach($update_values['location_quantities'] as $location_id => $quantity) {
                    if($quantity != "") {
                        // $quantity should not be an array
                        $location_quantities_array[$location_id] = $quantity;
                    }
                }
                $location_quantities = json_encode($location_quantities_array);
            }
            $update = Supplies::find($update_row);
            $update->itemtype = $itemtype;
            $update->notes = $notes;
            $update->request_quantities = $request_quantities;
            $update->location_quantities = $location_quantities;
            $update->exclude = $exclude;
            $update->part_class = $update_values['part_class'];
            $update->save();
            $calc_array['supply_id'] = $update_row;
            $calc_array['part'] = $update_values['part'];
        }
    }
    $cw_name = $user_name;
    if($cw_name == "") {
        $cw_name = "Unknown User";
    }
    gambaCosts::material_list_calculate($camp, $term, $supplylist_id);
    self::request_log($cw_name, $supplylist_id, "Update List");
    // Commented out for Admin Lock
    // gambaCalc::calculate_from_requests($array);
    if($array['debug'] == 1) {
        echo "<p><a href='{$url}/supplies/supplylistview?id=".$supplylist_id."&term=".$array['term']."&camp=".$camp."&activity_id=".$array['activity_id']."&packtotalcalc=1&r=$return' class='button small success radius'>Return to Material List</a></p>";
    }
    return $return;
    }

    public static function material_classifications() {
        $part_classes = MaterialClassifications::select('id', 'name', 'label')->orderBy('id')->get();
        foreach($part_classes as $key => $values) {
            $array[$values['id']]['name'] = $values['name'];
            $array[$values['id']]['label'] = $values['label'];
        }
        return $array;
    }
    /**
     * request_log - Was requestLog in GAMBA 1.0
     * @param unknown $user
     * @param unknown $acsid
     * @param unknown $action
     */
    public static function request_log($user, $acsid, $action) {
        $insert = new RequestLog;
        $insert->user = $user;
        $insert->acsid = $acsid;
        $insert->editdate = date("Y-m-d H:i:s");
        $insert->action = $action;
        $insert->save();
    }

    private static function last_request_user($acsid) {
        $row = RequestLog::select('user', 'editdate', 'action')->where('acsid', $acsid)->orderBy('editdate', 'DESC')->first();
        $array['user'] = $row['user'];
        $array['editdate'] = date("F j, Y h:i a", strtotime($row['editdate']));
        $array['action'] = $row['action'];
        return $array;
    }

    public static function view_change_log($id) {
        $url = url('/');
        $query = RequestLog::select('user', 'editdate', 'action')->where('acsid', $id)->orderBy('editdate', 'DESC')->limit(10)->get();
        if($query->count() > 0) {
            $content .= <<<EOT
			<div id="changelog" class="reveal-modal" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
				<h2 id="modalTitle">Material Requests Change Log</h2>

				<table class="table table-striped table-bordered table-hover table-condensed table-small">
					<thead>
						<tr>
							<th>User</th>
							<th>Action</th>
							<th>Date</th>
						</tr>
					</thead>
					<tbody>
EOT;
            foreach($query as $key => $row) {
                $user = $row['user'];
                $editdate = date("F j, Y h:i a", strtotime($row['editdate']));
                $action = $row['action'];
                $content .= <<<EOT
						<tr>
							<td>{$user}</td>
							<td>{$action}</td>
							<td>{$editdate}</td>
						</tr>
EOT;
            }
            $content .= <<<EOT
					</tbody>
				</table>
				<a class="close-reveal-modal" aria-label="Close">&#215;</a>
			</div>
EOT;
        }
        return $content;
    }

    public static function update_totals($array) {
        foreach($array as $supply_id => $values) {
            $total = $values['total'];
            $packing_quantities = json_encode($values['packing']);
            $update = Supplies::find($supply_id);
            $update->packing_quantities = $packing_quantities;
            $update->total_amount = $total;
            $update->packing_total = $total;
            $update->save();
            $sql = "UPDATE gmb_supplies SET packing_quantities = '$packing_quantities', total_amount = '$total', packing_total = '$total' WHERE id = $supply_id";
            gambaLogs::data_log("Supplies Update Totals SQL: $sql", 'camp_calc.log');
        }
    }

    public static function supplyactivities($term, $orderby = NULL, $camp_select = NULL) {
        $activies = gambaActivities::activity_list_by_term($term);
        $query = SupplyLists::select('supplylists.id', 'supplylists.activity_id', 'supplylists.term', 'supplylists.camp_type', 'supplylists.cg_staff', 'supplylists.user_id', 'supplylists.created', 'themes.name', 'themes.theme_options', 'grades.level', 'supplylists.budget', 'supplylists.locked', 'themes.id AS theme_id');
        // 			$query = $query->select(\DB::raw('gmb_themes.id AS theme_id'));
        $query = $query->leftjoin('activities', 'activities.id', '=', 'supplylists.activity_id');
        $query = $query->leftjoin('themes', 'themes.id', '=', 'activities.theme_id');
        $query = $query->leftjoin('grades', 'grades.id', '=', 'activities.grade_id');
        $query = $query->where('supplylists.term', $term);
        if($camp_select != "") {
            $query = $query->where('supplylists.camp_type', $camp_select);
        }
        if($orderby == "camp_id") {
            $query = $query->orderBy('supplylists.camp_type');
        }
        $query = $query->orderBy('grades.level');
        $query = $query->orderBy('themes.name');
        $query = $query->orderBy('activities.activity_name');

        if($camp_select != "") {
            $sql = $query->toSql();
            gambaLogs::data_log("Supply Activities SQL: $sql", 'costs.log');
        }
        $query = $query->get();
        gambaLogs::data_log("Get Supply List and Activities ", "costs.log", "false", "false", "true");
        if($query->count() > 0) {
            foreach($query as $key => $row) {
                $camp = $row['camp_type'];
                $supply_id = $row['id'];
                $theme_id = $row['theme_id'];
                $theme_name = $row['name'];
                $activity_id = $row['activity_id'];
                gambaLogs::data_log("Supply List ID: {$row['id']} | Camp: $camp | Theme: $theme_name ($theme_id) | Activity ID: $activity_id", "costs-{$camp}.log");
                gambaLogs::data_log(" . ", "costs.log", "false", "false", "true");
                $array['camps'][$camp]['activities'][] = $activity_id;
                $array['bycamp'][$camp][$supply_id]['activity_id'] = $activity_id;
                $array['bycamp'][$camp][$supply_id]['last_user'] = $last_user = self::last_request_user($supply_id);
                $number_items = self::supplies_per_list($supply_id);
                $array['bycamp'][$camp][$supply_id]['number_items'] = $num_items = $number_items['num_rows'];
                $array['bycamp'][$camp][$supply_id]['number_fbitems'] = $number_fbitems = $number_items['num_fb_rows'];
                $array['bycamp'][$camp][$supply_id]['activity_name'] = $activity_name = $activies[$activity_id]['name'];
                $array['bycamp'][$camp][$supply_id]['activity_info'] = $activity_info = $activies[$activity_id];
                $array['bycamp'][$camp][$supply_id]['cg_staff'] = $cg_staff = $row['cg_staff'];
                $array['bycamp'][$camp][$supply_id]['user_id'] = $user_id = $user_id = $row['user_id'];
                $array['bycamp'][$camp][$supply_id]['user_info'] = $user_info = gambaUsers::user_info($user_id);
                $array['bycamp'][$camp][$supply_id]['created'] = $created = $row['created'];
                $array['bycamp'][$camp][$supply_id]['theme_name'] = $theme_name;
                $array['bycamp'][$camp][$supply_id]['theme_id'] = $theme_id;
                $array['bycamp'][$camp][$supply_id]['theme_options'] = $theme_options = json_decode($row->theme_options, true);
                $array['bycamp'][$camp][$supply_id]['grade_level'] = $grade_level = $row['level'];
                $array['bycamp'][$camp][$supply_id]['budget'] = $budget = $row['budget'];
                $array['bycamp'][$camp][$supply_id]['locked'] = $locked = $row['locked'];
                $array['bycamptheme'][$camp][$theme_id]['theme_name'] = $theme_name;
                $array['bycamptheme'][$camp][$theme_id]['theme_options'] = $theme_options;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['activity_id'] = $activity_id;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['last_user'] = $last_user;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['number_items'] = $num_items;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['number_fbitems'] = $number_fbitems;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['activity_name'] = $activity_name;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['activity_info'] = $activity_info;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['cg_staff'] = $cg_staff;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['user_id'] = $user_id;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['user_info'] = $user_info;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['created'] = $created;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['theme_name'] = $theme_name;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['theme_id'] = $theme_id;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['theme_options'] = $theme_options;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['grade_level'] = $grade_level;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['budget'] = $budget;
                $array['bycamptheme'][$camp][$theme_id]['supplies'][$supply_id]['locked'] = $locked;
            }
        }
        // 			echo "<pre>"; print_r($array); echo "</pre>";
        return $array;
    }

    public static function campactivities($term, $camp_array) {
        if($term == "") {
            $term = gambaTerm::year_by_status('C');
        }
        $supplyactivities = self::supplyactivities($term);
        $camps = gambaCampCategories::camps_list();
        if(!is_array($camp_array)) {
            foreach($camps as $key => $value) {
                $array['theme_activities'][$key]['camp_name'] = $value['name'];
                $array['theme_activities'][$key]['camp_abbr'] = $value['abbr'];
                $array['theme_activities'][$key]['camp_alt_name'] = $value['alt_name'];
                $array['theme_activities'][$key]['supply_lists'] = $supplyactivities['bycamptheme'][$key];
            }
        } else {
            foreach($camp_array as $key => $camp) {
                $array['theme_activities'][$camp]['camp_name'] = $camps[$camp]['name'];
                $array['theme_activities'][$camp]['camp_abbr'] = $camps[$camp]['abbr'];
                $array['theme_activities'][$camp]['camp_alt_name'] = $camps[$camp]['alt_name'];
                $array['theme_activities'][$camp]['supply_lists'] = $supplyactivities['bycamptheme'][$camp];
            }
        }
        return $array;
    }

    /**
     * Available Activities to be used in the Create Material List
     * @param unknown $term
     * @return string
     */
    public static function available_activities($term) {
        $activities = gambaActivities::themes_activities_by_term($term);
        $supplyactivities = self::supplyactivities($term);
        $camps = gambaCampCategories::camps_list();
        $grades = gambaGrades::grade_list();
        $themes = gambaThemes::themes_camps_all($term);
        foreach($activities as $camp => $themes) {
            foreach($themes as $theme_id => $theme_values) {
                foreach($theme_values['activities'] as $key => $values) {
                    if(!in_array($key, $supplyactivities['camps'][$camp]['activities'])) {
                        $grade_id = $values['grade_id'];
                        $array['theme_activities'][$camp]['camp_name'] = $theme_values['camp_name'];
                        $array['theme_activities'][$camp]['camp_abbr'] = $camps[$camp]['abbr'];
                        $array['theme_activities'][$camp]['camp_alt_name'] = $camps[$camp]['alt_name'];
                        $array['theme_activities'][$camp]['supply_lists'][$key]['activity_id'] = $key;
                        $array['theme_activities'][$camp]['supply_lists'][$key]['number_items'] = 0;
                        $array['theme_activities'][$camp]['supply_lists'][$key]['activity_name'] = $values['activity_name'];
                        $array['theme_activities'][$camp]['supply_lists'][$key]['activity_info'] = $activies[$camp][$key];
                        $array['theme_activities'][$camp]['supply_lists'][$key]['user_id'] = "";
                        $array['theme_activities'][$camp]['supply_lists'][$key]['user_info'] = "";
                        $array['theme_activities'][$camp]['supply_lists'][$key]['created'] = date("Y-m-d H:i:s");
                        $array['theme_activities'][$camp]['supply_lists'][$key]['theme_name'] = $theme_values['name'];
                        $array['theme_activities'][$camp]['supply_lists'][$key]['grade_id'] = $grade_id;
                        $array['theme_activities'][$camp]['supply_lists'][$key]['grade_level'] = $grades[$theme_values['theme_camp']]['grades'][$grade_id]['level'];
                        // 							}
                    }
                }
            }
        }
        $array['supplyactivities'] = $supplyactivities;
        $array['activities'] = $activities;
        return $array;
    }

    public static function supplylist_by_part($part) {
        $supplies = Supplies::select('id', 'activity_id');
        $supplies = $supplies->where('part', '=', "$part")->get();
        if($supplies->count() > 0) {
            foreach($supplies as $key => $row) {
                $id = $row['id'];
                $activity_id = $row['activity_id'];
                $array[$id] = gambaActivities::activity_info($activity_id);
            }
        }
        return $array;
    }

    public static function material_list_costs($supplylist_id, $camp) {
        $camps = gambaCampCategories::camps_list();
        $avg_camper = $camps[$camp]['camp_values']['avg_camper'];
        $supply_parts = self::supplyparts($supplylist_id);
        foreach($supply_parts['supplies'] as $id => $values) {
            $total += $values['cost'] * $avg_camper;
        }
        // 			if($cost_per_camper_total > 0) {
        // 				$total = $cost_per_camper_total;
        // 			} else {
        // 				$total = 0;
        // 			}
        $update = SupplyLists::find($supplylist_id);
        $update->budget = $total;
        $update->save();
        return $total;
    }

    public static function supplyparts($id, $excluded = NULL) {
        $query = Supplies::select(
            'supplies.id',
            'supplies.packing_id',
            'supplies.supplylist_id',
            'supplies.theme_id',
            'supplies.grade_id',
            'supplies.activity_id',
            'supplylists.camp_type',
            'supplies.term',
            'supplies.part',
            'parts.description',
            'parts.suom',
            'parts.concept',
            'supplies.itemtype',
            'supplies.notes',
            'supplies.request_quantities',
            'supplies.location_quantities',
            'supplies.total_amount',
            'supplies.nonstandard',
            'supplies.lowest',
            'parts.cost',
            'parts.conversion',
            'parts.fbuom',
            'parts.fbcost',
            'supplylists.budget',
            'supplies.exclude',
            'supplies.part_class',
            'parts.approved',
            'parts.adminnotes',
            'supplylists.locked',
            'supplies.cost AS cost_per_camper',
            'supplies.costing_summary AS part_costing_summary',
            'supplylists.costing_summary');
        $query = $query->leftjoin('parts', 'parts.number', '=', 'supplies.part');
        $query = $query->leftjoin('supplylists', 'supplylists.id', '=', 'supplies.supplylist_id');
        $query = $query->where('supplies.supplylist_id', $id);
        $query = $query->orderBy('supplies.exclude');
        $query = $query->orderBy('parts.approved');
        $query = $query->orderBy('parts.description');
        $array['sql'] = $query->toSql();
        $query = $query->get();
        if($query->count() > 0) {
            foreach($query as $key => $row) {
                if(($excluded == "true" && $row['exclude'] == 0) || $excluded == "" || $excluded == "false") {
                    $id = $row['id'];
                    $array['supplies'][$id]['packing_id'] = $packing_id = $row['packing_id'];
                    $array['supplies'][$id]['term'] = $term = $row['term'];
                    $array['supplies'][$id]['theme_id'] = $theme_id = $row['theme_id'];
                    $array['supplies'][$id]['grade_id'] = $theme_id = $row['grade_id'];
                    $array['supplies'][$id]['activity_id'] = $activity_id = $row['activity_id'];
                    $array['supplies'][$id]['camp_id'] = $camp_id = $row['camp_type'];
                    $array['supplies'][$id]['part'] = $row['part'];
                    $array['supplies'][$id]['description'] = gambaParts::utf8_filter($row['description']);
                    $array['supplies'][$id]['suom'] = $row['suom'];
                    $array['supplies'][$id]['concept'] = $row['concept'];
                    $array['supplies'][$id]['part_class'] = $row['part_class'];
                    $array['supplies'][$id]['cost'] = $row['cost'];
                    $array['supplies'][$id]['itemtype'] = $row['itemtype'];
                    $array['supplies'][$id]['notes'] = $row['notes'];
                    $array['supplies'][$id]['request_quantities'] = json_decode($row->request_quantities, true);
                    $array['supplies'][$id]['location_quantities'] = json_decode($row->location_quantities, true);
                    $array['supplies'][$id]['total_amount'] = $row['total_amount'];
                    $array['supplies'][$id]['nonstandard'] = $row['nonstandard'];
                    $array['supplies'][$id]['lowest'] = $row['lowest'];
                    $array['supplies'][$id]['conversion'] = $row['conversion'];
                    $array['supplies'][$id]['fbuom'] = $row['fbuom'];
                    $array['supplies'][$id]['fbcost'] = $row['fbcost'];
                    $array['supplies'][$id]['costing_summary'] = json_decode($row->part_costing_summary, true);
                    $array['supplies'][$id]['cost_per_camper'] = json_decode($row->cost_per_camper, true);
                    $array['supplies'][$id]['exclude'] = $row['exclude'];
                    $array['supplies'][$id]['approved'] = $row['approved'];
                    $array['supplies'][$id]['adminnotes'] = $row['adminnotes'];
                    $array['budget'] = $row['budget'];
                    $array['locked'] = $row['locked'];
                    $array['costing_summary'] = json_decode($row->costing_summary, true);
                }
            }
        }
        return $array;
    }

    public static function supplypartsbycamptheme($term, $packing_id) {
        $query = Supplies::select('supplies.camp_id', 'supplies.grade_id', 'supplies.theme_id', 'supplies.part', 'supplies.packing_quantities')->leftjoin('supplylists', 'supplylists.id', '=', 'supplies.supplylist_id')->where('supplies.term', $term)->where('supplies.packing_id', $packing_id)->where('supplies.exclude', '0')->where('supplylists.locked', 'true')->get();
        //$array['sql'] = \DB::last_query();
        if($query->count() > 0) {
            foreach($query as $key => $row) {
                $supply_id = $row['id'];
                $camp = $row['camp_id'];
                $grade = $row['grade_id'];
                $theme = $row['theme_id'];
                $part = $row['part'];
                $part_info = gambaParts::part_info($part);
                $array['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['theme'][$theme]['part'][$part]['description'] = $part_info['description'];
                $array['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['theme'][$theme]['part'][$part]['conversion'] = $part_info['conversion'];
                $array['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['theme'][$theme]['part'][$part]['supply'][$supply_id]['packing_quantities'] = json_decode($row->packing_quantities, true);
            }
        }
        return $array;
    }

    /**
     * Supply Parts by Packing ID, Grade and Theme
     * @param unknown $term
     * @param unknown $packing_id
     * @param unknown $grade
     * @param unknown $theme
     * @return string
     */
    public static function supplypartsbypackinidgradetheme($term, $packing_id, $grade, $theme) {
        gambaLogs::data_log("Supply Parts by Packing Grade Theme SQL: $sql", 'camp_calc.log');
        $query = Supplies::select('supplies.id', 'supplies.camp_id', 'supplies.part', 'supplies.packing_quantities', 'supplies.exclude');
        $query = $query->leftjoin('supplylists', 'supplylists.id', '=', 'supplies.supplylist_id');
        $query = $query->where('supplies.term', $term);
        $query = $query->where('supplies.packing_id', $packing_id);
        $query = $query->where('supplies.grade_id', $grade);
        $query = $query->where('supplies.theme_id', $theme);
        $query = $query->where('supplylists.locked', 'true');
        $query = $query->get();
        //$array['sql'] = \DB::last_query();
        if($query->count() > 0) {
            foreach($query as $key => $row) {
                $supply_id = $row['id'];
                $camp = $row['camp_id'];
                $part = $row['part'];
                $part_info = gambaParts::part_info($part);
                $array['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['theme'][$theme]['part'][$part]['description'] = $part_info['description'];
                $array['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['theme'][$theme]['part'][$part]['conversion'] = $part_info['conversion'];
                $array['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['theme'][$theme]['part'][$part]['supply'][$supply_id]['packing_quantities'] = json_decode($row->packing_quantities, true);
                $array['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['theme'][$theme]['part'][$part]['supply'][$supply_id]['exclude'] = $row['exclude'];;
            }
        }
        return $array;
    }

    /**
     * Supply Parts by Packing ID, Grade and Theme
     * @param unknown $term
     * @param unknown $packing_id
     * @param unknown $grade
     * @param unknown $theme
     * @return string
     */
    public static function supplypartsbypackinidgrade($term, $packing_id, $grade) {
        $array['sql'] = $sql = "SELECT s.id, s.camp_id, s.part, s.packing_quantities, s.exclude FROM gmb_supplies s LEFT JOIN gmb_supplylists sl ON sl.id = s.supplylist_id WHERE s.term = '$term' AND s.packing_id = '$packing_id' AND s.grade_id = '$grade' AND sl.locked = 'true'";
        gambaLogs::data_log("Supply Parts by Packing Grade Theme SQL: $sql", 'camp_calc.log');
        $query = Supplies::select('supplies.id', 'supplies.camp_id', 'supplies.part', 'supplies.packing_quantities', 'supplies.exclude');
        $query = $query->leftjoin('supplylists', 'supplylists.id', '=','supplies.supplylist_id');
        $query = $query->where('supplies.term', $term);
        $query = $query->where('supplies.packing_id', $packing_id);
        $query = $query->where('supplies.grade_id', $grade);
        $query = $query->where('supplylists.locked', 'true');
        $query = $query->get();
        //$result = mysql_query($sql);
        if($query->count() > 0) {
            foreach($query as $key => $row) {
                $supply_id = $row['id'];
                $camp = $row['camp_id'];
                $part = $row['part'];
                $part_info = gambaParts::part_info($part);
                $array['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['part'][$part]['description'] = $part_info['description'];
                $array['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['part'][$part]['conversion'] = $part_info['conversion'];
                $array['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['part'][$part]['supply'][$supply_id]['packing_quantities'] = json_decode($row->packing_quantities, true);
                $array['camp'][$camp]['packing'][$packing_id]['grade'][$grade]['part'][$part]['supply'][$supply_id]['exclude'] = $row['exclude'];
            }
        }
        return $array;
    }


    public static function cwsupplylists_term_dropdown($term, $action) {
        $url = url('/');
        $terms = gambaTerm::terms();
        if($term == "") { $term = gambaTerm::year_by_status('C'); }
        $content .= <<<EOT
			<dl class="sub-nav">
				<dt>Terms:</dt>
EOT;
        foreach($terms as $year => $values) {
            if($term == $year) { $mark_active = ' class="active"'; } else { $mark_active = ""; }
            $content .= <<<EOT
		  		<dd{$mark_active}><a href="{$url}/supplies?action=supplyrequests&term={$year}">{$year}</a></dd>
EOT;
        }
        $content .= <<<EOT
			</dl>
EOT;
        return $content;
    }

    public static function themesandactivities($term) {
        $camps = gambaCampCategories::camps_list();
        $themes = gambaThemes::themes_camps_all($term);
        $activities = gambaActivities::activity_list_by_term($term);
        foreach($themes as $camp_id => $values) {
            foreach($values as $theme_id => $theme_values) {
                $array[$camp_id]['camp'] = $camps[$camp_id]['name'];
                $array[$camp_id]['themes'][$theme_id] = $theme_values;
                $activities = gambaActivities::activities_by_theme($theme_id, $camp_id);
                foreach($activities['activities'] as $id => $act_values) {
                    $array[$camp_id]['themes'][$theme_id]['activities'][$id] = $act_values;
                    $supplies_camp_activity = self::supplies_camp_activity($camp_id, $id, $term);
                    $array[$camp_id]['themes'][$theme_id]['activities'][$id]['requests'] = $supplies_camp_activity['requests'];
                    $array[$camp_id]['themes'][$theme_id]['activities'][$id]['supplylist_id'] = $supplies_camp_activity['supplylist_id'];
                }
            }
        }
        return $array;
    }

    private static function supplies_camp_activity($camp_type, $activity_id, $term) {
        $row = SupplyLists::select('id')->where('activity_id', $activity_id)->where('term', $term)->where('camp_type', $camp_type)->first();
        $array['supplylist_id'] = $id = $row['id'];
        $num_rows = Supplies::select('id')->where('supplylist_id', $id)->get();
        $array['requests'] = $num_rows->count();
        return $array;
    }

    public static function listcopyterm($array) {
        $url = url('/');
        $current_term = gambaTerm::year_by_status('C');
        $next_term = gambaTerm::year_by_status('N');
        $content_array['page_title'] = "Copy Material Requests - Select Term";
        $content_array['content'] = <<<EOT
			<div class="directions">
				<p><strong>Directions:</strong> Please select either the current term or the next term and select next.</p>
			</div>
				<form method="post" name="select" action="{$url}/supplies/supplylistcopy?action=supplylistcopy&id={$array['id']}&term={$array['term']}&activity_id={$array['activity_id']}&camp={$array['camp']}">
					<p>Select Term:
						<input type="radio" name="select_term" value="{$current_term}" checked /> {$current_term} (Current)
						<input type="radio" name="select_term" value="{$next_term}" /> {$next_term} (Next)
					</p>
					<p><input type="submit" name="submit" value="Next" class="btn btn-primary" /></p>
				</form>
EOT;
        echo <<<EOT
			<h1>{$content_array['page_title']}</h1>
			{$content_array['content']}
EOT;
    }

    // Moved to SuppliesController.php and /resources/views/app/supplies/cwlistcopy.blade.php
    public static function listcopy($array) {
        $url = url('/');
        $term = $array['term'];
        $camp = $array['camp'];
        $camps = gambaCampCategories::camps_list();
        $themesandactivities = self::themesandactivities($term);
        $content_array['page_title'] .= "Copy Material Requests into the $term Term";
        $content_array['content'] .= gambaDirections::getDirections('listcopy');
        $activity_info = gambaActivities::activity_info($array['activity_id']);
        $content_array['content'] .= "<h3><strong>Term:</strong> ".$activity_info['term']." &nbsp; <strong>Activity:</strong> ".$activity_info['name']." &nbsp; ";
        if($activity_info['grade_name']) { $content_array['content'] .= "<strong>Grade:</strong> ".$activity_info['grade_name']; }
        $content_array['content'] .= "<br /><strong>Theme:</strong> ".$activity_info['theme_name']."&nbsp; <strong>Category:</strong> ".$camps[$camp]['name']."</h3>";

        $content_array['content'] .= <<<EOT

		<table class="table table-striped table-bordered table-hover table-condensed table-small table-fixed-header" id="supplylist">
			<thead>
				<tr>
					<th>Grade Level</th>
					<th>Theme</th>
					<th>Activity</th>
					<th class="center">Items</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
EOT;
        foreach($themesandactivities as $camp_id => $values) {
            $content_array['content'] .= <<<EOT
				<tr class="row-theme">
					<td colspan="5" class="theme-name"><a name="camp_$key"></a><strong>{$values['camp']}</strong></td>
				</tr>
EOT;

            foreach($values['themes'] as $theme_id => $theme_values) {
                foreach($theme_values['activities'] as $activity_id => $act_values) {
                    if($act_values['supplylist_id'] != "") {
                        $button_name = "Insert into List";
                        $button_action = "listinsert";
                    } else {
                        $button_name = "Create and Insert";
                        $button_action = "listcreateinsert";
                    }
                    $content_array['content'] .= <<<EOT

				<tr>
					<td>{$act_values['grade_name']}</td>
					<td>{$theme_values['name']}</td>
					<td>{$act_values['activity_name']}</td>
					<td class="center">{$act_values['requests']}</td>
					<td><a href="{$url}/supplies/{$button_action}?id={$array['id']}&supplylist_id={$act_values['supplylist_id']}&term=$term&activity_id=$activity_id&camp=$camp_id" class="button small success radius">$button_name</a></td>
				</tr>
EOT;
                }
            }
        }
        $content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
        return $content_array;
        // 			gambaDebug::preformatted_arrays($themesandactivities, 'themesandactivities', 'Themes and Activities');
        // 			gambaDebug::preformatted_arrays($themes, 'themes', 'Themes');
        // 			gambaDebug::preformatted_arrays($activities, 'activities', 'Activities');
    }

    public static function listinsert($array) {
        $id = $array['id'];
        $activity_id = $array['activity_id'];
        $camp = $array['camp'];
        $term = $array['term'];
        $supplylist_id = $array['supplylist_id'];
        $activity_info = gambaActivities::activity_info($activity_id);
        $theme_id = $activity_info['theme_id'];
        $grade_id = $activity_info['grade_id'];
        $query = Supplies::select(
            'part',
            'itemtype',
            'request_quantities',
            'location_quantities');
        $query = $query->where('supplylist_id', $id);
        $query = $query->get();
        if($query->count() > 0) {
            foreach($query as $key => $row) {
                $part = $row['part'];
                $itemtype = $row['itemtype'];
                $request_quantities = $row['request_quantities'];
                $location_quantities = $row['location_quantities'];
                $insert = new Supplies;
                $insert->supplylist_id = $supplylist_id;
                $insert->theme_id = $theme_id;
                $insert->grade_id = $grade_id;
                $insert->activity_id = $activity_id;
                $insert->camp_id = $camp;
                $insert->term = $term;
                $insert->part = $part;
                $insert->itemtype = $itemtype;
                $insert->packing_total = 0;
                $insert->packing_subtracted = 0;
                $insert->request_quantities = $request_quantities;
                $insert->location_quantities = $location_quantities;
                $insert->save();
            }
        }

    }

    public static function listcreateinsert($array) {
        $id = $array['id'];
        $activity_id = $array['activity_id'];
        $camp = $array['camp'];
        $term = $array['term'];
        $activity_info = gambaActivities::activity_info($activity_id);
        $theme_id = $activity_info['theme_id'];
        $grade_id = $activity_info['grade_id'];
        $user_id = Session::get('uid');
        // 			$user_id = 1;
        $created = date("Y-m-d H:i:s");
        $supplylist_id = SupplyLists::insertGetId([
            'activity_id' => $activity_id,
            'term' => $term,
            'camp_type' => $camp,
            'user_id' => $user_id,
            'created' => $created
        ]);
        $cw_name = Session::get('name');
        if($cw_name == "") {
            $cw_name = "Unknown User";
        }
        self::request_log($cw_name, $id, "Create List");
        $query = Supplies::select('part', 'itemtype', 'request_quantities', 'location_quantities')->where('supplylist_id', $id)->get();
        if($query->count() > 0) {
            foreach($query as $key => $row) {
                $part = $row['part'];
                $itemtype = $row['itemtype'];
                $request_quantities = $row['request_quantities'];
                $location_quantities = $row['location_quantities'];
                $insert = new Supplies;
                $insert->supplylist_id = $supplylist_id;
                $insert->theme_id = $theme_id;
                $insert->grade_id = $grade_id;
                $insert->activity_id = $activity_id;
                $insert->camp_id = $camp;
                $insert->term = $term;
                $insert->part = $part;
                $insert->itemtype = $itemtype;
                $insert->packing_total = 0;
                $insert->packing_subtracted = 0;
                $insert->request_quantities = $request_quantities;
                $insert->location_quantities = $location_quantities;
                $insert->save();
            }
        }
    }

    public static function update_data_inputs($array) {
        // 			echo "<pre>"; print_r($array); echo "</pre>"; exit; die();
        $id = $array['id'];
        $term = $array['term'];
        $camp = $array['camp'];
        $activity_id = $array['activity_id'];
        $data_inputs = json_encode($array['data_inputs']);
        $update = SupplyLists::find($id);
        $update->data_inputs = $data_inputs;
        $update->save();
    }

    public static function supplylist_data_inputs($id) {
        $row = SupplyLists::find($id);
        $data_inputs = json_decode($row->data_inputs, true);
        return $data_inputs;
    }

    public static function data_inputs($camp, $id, $term, $activity_id) {
        $url = url('/');
        $supply_data_inputs = self::supplylist_data_inputs($id);
        $camps = gambaCampCategories::camps_list();
        //gambaDebug::preformatted_arrays($camps[$camp], 'camps', 'Camp Info');
        $location_input = $camps[$camp]['location_input'];
        if($location_input == "true") {
            $locations = gambaLocations::locations_by_camp();
        }
        //gambaDebug::preformatted_arrays($locations, 'data_locations', 'Data Input Locations');
        foreach($camps[$camp]['data_inputs'] as $key => $value) {
            if($value['enabled'] == "true") {
                $array[$key] = $value['name'];
            }
        }
        if(is_array($array)) {
            $content .= <<<EOT
			<table class="table table-striped table-bordered table-hover table-condensed table-small table-responsive" id="themes" style="width:300px;">
				<thead>
					<tr>
						<th>Data Input Type</th>
EOT;
            if($location_input == "true") {
                foreach($locations['locations'][$camp] as $key => $values) {
                    if($values['terms'][$term]['active'] == "Yes") {
                        $content .= <<<EOT
						<th class="center">{$values['abbr']}</th>
EOT;
                    }
                }
            } else {
                $content .= <<<EOT
						<th class="center">Amount</th>
EOT;
            }
            $content .= <<<EOT
					</tr>
				</thead>
				<tbody>
EOT;
            foreach($array as $key => $value) {
                $content .= <<<EOT
					<tr>
						<td>{$value}</td>
EOT;
                if($location_input == "true") {
                    foreach($locations['locations'][$camp] as $location_id => $location_values) {
                        if($supply_data_inputs['data'][$key]['locations'][$location_id]['amount']) { $amount = $supply_data_inputs['data'][$key]['locations'][$location_id]['amount']; } else { $amount = 0; }
                        if($location_values['terms'][$term]['active'] == "Yes") {
                            $content .= <<<EOT
						<td class="center">{$amount}</td>
EOT;
                        }
                    }
                } else {
                    if($supply_data_inputs[$key]['amount']) { $amount = $supply_data_inputs[$key]['amount']; } else { $amount = 0; }
                    $content .= <<<EOT
						<td class="center">{$amount}</td>
EOT;
                }
                $content .= <<<EOT
					</tr>
EOT;
            }
            $content .= <<<EOT
				</tbody>
			</table>
			<p><a data-reveal-id="edit_data_inputs" href="#" class="button small success radius">Edit Data Inputs</a></p>
EOT;
            // 				gambaDebug::preformatted_arrays($supply_data_inputs, 'supply_data_inputs', 'Supply Data Inputs');
            $content .= <<<EOT

		<div id="edit_data_inputs" class="reveal-modal" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
 			<form name="form-data-inputs" action="{$url}/supplies/update_data_inputs" method="post" class="form-horizontal">
EOT;
            $content .= csrf_field();
            $content .= <<<EOT
							<h2 class="modalTitle">Edit Data Inputs</h2>

						<p class="directions">Once you have put in your data and clicked <span class="button small success radius">Update Data Inputs</span> you will need to click <span class="button small success radius">Edit All</span> to recalculate this material request list.</p>
EOT;
            if($location_input == "true") {
                $content .= <<<EOT
						<table class="table table-striped table-bordered table-hover table-condensed table-small table-responsive" id="themes" style="width:100%;">
				<thead>
					<tr>
						<th>Data Input Type</th>
EOT;
                if($location_input == "true") {
                    foreach($locations['locations'][$camp] as $key => $values) {
                        if($values['terms'][$term]['active'] == "Yes") {
                            $content .= <<<EOT
						<th class="center">{$values['abbr']}</th>
EOT;
                        }
                    }
                }
                $content .= <<<EOT
					</tr>
				</thead>
				<tbody>
EOT;
                foreach($array as $key => $value) {
                    $content .= <<<EOT
					<tr>
						<td>{$value}</td>
EOT;
                    foreach($locations['locations'][$camp] as $location_id => $location_values) {
                        if($location_values['terms'][$term]['active'] == "Yes") {
                            if($supply_data_inputs['data'][$key]['locations'][$location_id]['amount']) { $amount = $supply_data_inputs['data'][$key]['locations'][$location_id]['amount']; } else { $amount = 0; }
                            $content .= <<<EOT
						<td class="center"><input type="text" name="data_inputs[data][{$key}][locations][{$location_id}][amount]" value="{$amount}" class="form-control" />
							<input type="hidden" name="data_inputs[data][{$key}][locations][{$location_id}][name]" value="{$value}" />
							<input type="hidden" name="data_inputs[data][{$key}][name]" value="{$value}" />
							<input type="hidden" name="data_inputs[data][{$key}][locations][{$location_id}][location]" value="{$location_values['abbr']}" /></td>
EOT;
                        }
                    }
                    $content .= <<<EOT
					</tr>
EOT;
                }
                $content .= <<<EOT
				</tbody>
			</table>
			<input type="hidden" name="data_inputs[location_input]" value="true" />
EOT;
            } else {
                foreach($array as $key => $value) {
                    if($supply_data_inputs[$key]['amount']) { $amount = $supply_data_inputs[$key]['amount']; } else { $amount = 0; }
                    $content .= <<<EOT
					<div class="row">
						<div class="small-12 medium-4 large-4 columns">
							<label class="">{$value}</label>
						</div>
						<div class="small-12 medium-8 large-8 columns">
							<input type="text" name="data_inputs[{$key}][amount]" value="{$amount}" class="form-control" />
							<input type="hidden" name="data_inputs[{$key}][name]" value="{$value}" />
						</div>
					</div>
EOT;
                }
            }
            $content .= <<<EOT

							<button type="button" class="button small radius" aria-label="Close">Cancel</button>
							<button type="submit" class="button small success radius">Update Data Inputs</button>

					<input type="hidden" name="action" value="update_data_inputs" />
					<input type="hidden" name="id" value="{$id}" />
					<input type="hidden" name="term" value="{$term}" />
					<input type="hidden" name="camp" value="{$camp}" />
					<input type="hidden" name="activity_id" value="{$activity_id}" />
				</form>

		</div>
EOT;
        }
        return $content;
    }

    /**
     * Curriculum Writer Material Requests
     * @param unknown $term
     * @param unknown $action
     * @param unknown $return
     */
    public static function supplylists($term, $action, $return) {
        $url = url('/');
        if($term == "") { $term = gambaTerm::year_by_status('C'); }
        $current_term = gambaTerm::year_by_status('C');
        $campactivities = self::campactivities($term);
        $terms = gambaTerm::terms();
        $theme_budget = self::theme_budget();

        $content_array['page_title'].= "Curriculum Writer Material Lists";

        if($return['delete'] == 1) {
            $content_array['content'] .= <<<EOT
				<div data-alert class="alert-box success radius">
					<strong>
EOT;
            $content_array['content'] .= $return['name'];
            if($return['theme_name'] != "") { $content_array['content'] .= " &gt; " . $return['theme_name']; }
            if($return['grade_name'] != "") { $content_array['content'] .= " &gt; " . $return['grade_name']; }
            if($return['camp_name'] != "") { $content_array['content'] .= " &gt; " . $return['camp_name']; }
            $content_array['content'] .= <<<EOT
					</strong> successfully deleted.
					<a href="#" class="close">&times;</a>
				</div>
EOT;
        }
        $content_array['content'] .= self::cwsupplylists_term_dropdown($term, $action);
        $content_array['content'] .= gambaDirections::getDirections('supplylists');
        if($action == "supplyrequests") {
            $content_array['content'] .= "<div style='margin-top: 10px;'><a href='{$url}/supplies/createsupplylist?term=$term' class='button small success radius'>Create Material List</a></div>";
        }
        $content_array['content'] .= <<<EOT
		<script type="text/javascript">
			$(function(){
			    $(".table-sort").tablesorter({
					widgets: [ 'stickyHeaders' ],
					widgetOptions: { stickyHeaders_offset : 50, },
				});
			 });
		</script>
		<table class="table table-striped table-bordered table-hover table-condensed table-small table-sort" id="supplylist">
			<thead>
				<tr>
					<th>ID</th>
					<th>Grade Level</th>
					<th>Theme</th>
					<th>Activity</th>
					<th class="center">Items</th>
					<th class="center">Actual Cost</th>
EOT;
        if($theme_budget == 1) { $content_array['content'] .= "<th>Activity Budget</th>"; }
        if($action == "supplyrequests") { $content_array['content'] .= "<th>Last User</th>"; }
        $content_array['content'] .= <<<EOT
					<th class="center">Status</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
EOT;
        foreach($campactivities['theme_activities'] as $key => $value) {
            if($theme_budget == 1) { $colspan = 9; } else { $colspan = 8; }
            $content_array['content'] .= <<<EOT
				<tr class="row-theme">
					<td colspan="$colspan" class="theme-name"><a name="camp_$key"></a><strong>{$value['camp_name']}</strong></td>
				</tr>
EOT;
            foreach($value['supply_lists'] as $theme_id => $theme_values) {
                if(is_array($theme_values['supplies'])) {
                    if($theme_budget == 1 && $theme_values['theme_options']['theme_budget_per_camper'] != "") {
                        if($theme_values['theme_options']['theme_budget_per_camper'] != "") { $theme_budget = "$".$theme_values['theme_options']['theme_budget_per_camper']; } else { $theme_budget = ""; }
                        $content_array['content'] .= <<<EOT
				<tr class="row-theme">
					<td colspan="2" class="theme-name"><strong>{$theme_values['theme_name']}</strong></td>
					<td colspan="2" class="theme-name right"><strong>Theme Budget:</strong></td>
					<td class="theme-name right">$theme_budget</td>
					<td colspan="2"></td>
				</tr>
EOT;
                    }
                    foreach($theme_values['supplies'] as $list_id => $list_values) {
                        $activity_cost_per_camper = "$" . number_format($list_values['activity_info']['costing_summary']['activity_cost_per_camper'], 3);
                        $content_array['content'] .= <<<EOT
				<tr>
					<td>{$list_id}</td>
					<td title="$list_id">{$list_values['grade_level']}</td>
					<td>{$list_values['theme_name']}</td>
					<td>{$list_values['activity_name']}</td>
					<td class="center">{$list_values['number_items']}</td>
					<td class="center">{$activity_cost_per_camper}</td>
EOT;
                        if($theme_budget == 1) {
                            $content_array['content'] .= "<td class='right'>$".number_format($list_values['budget'], 3)."</td>";
                        }
                        if($action == "supplyrequests") { $content_array['content'] .= '<td>'.$list_values['last_user']['user'].'</td>'; }
                        $content_array['content'] .= "<td class='center'>";
                        if($list_values['locked'] == "true") {
                            $content_array['content'] .= "<img src=\"{$url}/img/1465944803_lock_basic_green.png\" title=\"Locked\" />";
                        } else {
                            $content_array['content'] .= "<img src=\"{$url}/img/1465944767_lock-unlock_basic_red.png\" title=\"Editable\" />";
                        }
                        $content_array['content'] .= "</td>";
                        $content_array['content'] .= "<td class='center'>";
                        if($action == "supplyrequests") {
                            $content_array['content'] .= <<<EOT
					<a href="{$url}/supplies/supplylistview?id=$list_id&term=$term&activity_id={$list_values['activity_id']}&camp=$key" class="button small success radius">View</a>
					<a href="{$url}/supplies/supplylistdelete?id=$list_id&term=$term&activity_id={$list_values['activity_id']}&camp=$key" onclick="return confirm('Are you sure you want to delete {$list_values['activity_name']}?');" class="button small radius">Delete</a>
					<a href="{$url}/supplies/supplylistcopy?id=$list_id&term=$current_term&activity_id={$list_values['activity_id']}&camp=$key" class="button small success radius">Copy</a>

EOT;
                        } else {
                            $content_array['content'] .= <<<EOT
					<a href="{$url}/supplies/createlist?activity_id=$list_id&camp=$key&term=$term" class="button small success radius">Create Material List</a>
EOT;
                        }
                        $content_array['content'] .= <<<EOT
					</td>
				</tr>
EOT;
                    }
                } else {
                    $content_array['content'] .= <<<EOT
				<tr>
					<td colspan="8">There are no supply lists created for this camp.</td>
				</tr>
EOT;
                }
            }
        }
        $content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
        return $content_array;
        // 			gambaDebug::preformatted_arrays($campactivities['theme_activities'], "campactivities", "Camp Activities");

    }

    /**
     * Create Material Lists
     * @param unknown $term
     * @param unknown $action
     * @param unknown $return
     */
    public static function createsupplylists($term, $action, $return) {
        $url = url('/');
        if($term == "") { $term = gambaTerm::year_by_status('C'); }
        $available_activities = self::available_activities($term);
        $directions = $action;

        $content_array['page_title'] .= "Create Material Lists: Available Activities for $term";
        $content_array['content'] .= gambaDirections::getDirections($directions);
        $content_array['content'] .= <<<EOT

		<script>
			$('#supplylist').fixedHeaderTable({
				footer: true
			});
		</script>
		<table class="table table-striped table-bordered table-hover table-condensed table-small table-fixed-header" id="supplylist">
			<thead>
				<tr>
					<th>Grade Level</th>
					<th>Theme</th>
					<th>Activity</th>
					<th class="center">Items</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
EOT;
        if(is_array($available_activities['theme_activities'])) {
            foreach($available_activities['theme_activities'] as $key => $value) {
                $content_array['content'] .= <<<EOT
				<tr class="row-theme">
					<td colspan="6" class="row-theme"><strong>{$value['camp_name']}</strong></td>
				</tr>
EOT;
                if(is_array($value[supply_lists])) {
                    foreach($value[supply_lists] as $list_id => $list_values) {
                        $content_array['content'] .= <<<EOT
				<tr>
					<td title="$list_id">{$list_values['grade_level']}</td>
					<td>{$list_values['theme_name']}</td>
					<td>{$list_values['activity_name']}</td>
					<td class="center">{$list_values['number_items']}</td>
					<td>
					<a href="{$url}/supplies/createlist?activity_id=$list_id&camp=$key&term=$term" class="button small success radius">Create Material List</a>
					</td>
				</tr>
EOT;
                    }
                } else {
                    $content_array['content'] .= <<<EOT
				<tr>
					<td colspan="5">There are no supply lists created for this camp.</td>
				</tr>
EOT;
                }
            }
        } else {
            $content_array['content'] .= <<<EOT
				<tr>
					<td colspan="5">There are no available camp category themes and activities for creating supply lists.</td>
				</tr>
EOT;
        }
        $content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
        return $content_array;
        //gambaDebug::preformatted_arrays($available_activities, "available_activities", "Available Activities");

    }

    /**
     * Amotorized Percentage - Used in Material Cost Analysis - Set in Admin > Settings > Configuration
     * @return unknown
     */
    public static function amt_cost_per() {
        $row = Config::select('value')->where('field', 'amt_cost_percentages')->first();
        $amt_cost_per = json_decode($row->value, true);
        return $amt_cost_per;
    }


    /**
     * Moved to SuppliesController@showSupplyList and app/supplies/cwlistview.blade.php
     * Individual Material Requests View
     * @param unknown $array
     * @param unknown $return


    public static function supplylistview($array, $return) {
        $url = url('/');
        $user_group = Session::get('group');
        $id = $array['id'];
        $term = $array['term'];
        $activity_id = $array['activity_id'];
        $camp = $array['camp'];
        $supplyparts = self::supplyparts($id);
        if($supplyparts['locked'] == "true") { $button_disabled = " disabled"; } else { $button_disabled = ""; }
        $list_budget = $supplyparts['budget'];
        $activity_info = gambaActivities::activity_info($activity_id, $id);
        $qts = gambaQuantityTypes::quantity_types_by_camp($camp, $term);
        $uoms = gambaUOMs::uom_list();
        $camps = gambaCampCategories::camps_list();
        $amt_cost_per = self::amt_cost_per();
        $theme_budget = self::theme_budget();
        $day_col = $camps[$camp]['camp_values']['day_col'];
        if($array['packtotalcalc'] == 1) {
            // May change if we go back to standards/non-standards
            // 				$camp_info = gambaCampCategories::camp();
            // 				$camp_theme_type = $camp_info['camp_values']['theme_type'];
            // 				$packing_lists = gambaPacking::packing_lists();
            // 				$lists = $packing_lists['camps'][$camp]['lists'];
            // 				if($camp_theme_type == "true") {
            // 					foreach($lists as $key => $pack_values) {
            // 						if($pack_values['theme_type'] == $activity_info['theme_type']) {
            // 							$packing_id = $key; exit;
            // 						}
            // 					}
            // 				} else {
            // 					$packing_id = $lists['packing_id'];
            // 				}
            // 				$i = 1;
            // 				foreach($lists as $key => $value) { $i++; }
            // 				$a = 1;
            // 				foreach($lists as $packing_id => $value) {
            // 					if($i == $a) { $qtshort = 1; }
            // 					$a++;
            // 				}

        }
        if(is_array($camps[$camp]['camp_values']['request_locations'])) {
            $locations = gambaLocations::locations_by_camp();
            foreach($camps[$camp]['camp_values']['request_locations'] as $key => $camp_id) {
                // echo $camp_id;
                foreach($locations['locations'][$camp_id] as $location_id => $location_values) {
                    if($location_values['terms'][$term]['active'] == "Yes") {
                        $location_array[$location_id]['camp'] = $camp_id;
                        $location_array[$location_id]['abbr'] = $location_values['abbr'];
                        $location_array[$location_id]['name'] = $location_values['name'];
                    }
                }
            }
        }
        $content_array['header_css'] = <<<EOT
			<link href="{$url}/css/ui-lightness/jquery-ui.custom.min.css" rel="stylesheet">

EOT;
        // Get Packing IDs from List of Supplies to Refresh Packing Lists
        // 			$packing_list_array = array();
        foreach($supplyparts['supplies'] as $key => $values) {
            $packing_list_array[] = $values['packing_id'];
        }
        $packing_list_array = array_unique($packing_list_array);
        $packing_list_ids = implode(',', $packing_list_array);
        // 			echo "<pre>"; print_r($packing_list_array); echo "</pre>$packing_list_ids";

        $content_array['page_title'] = "Term: $term &nbsp; Activity: ".$activity_info['name']." &nbsp; ";
        if($activity_info['grade_name']) { $content_array['page_title'] .= "Grade: ".$activity_info['grade_name']; }
        $content_array['page_title'] .= "<br />Theme: ".$activity_info['theme_name']."&nbsp; Category: ".$camps[$camp]['name'];

        $content_array['content'] .= <<<EOT
		<div class="row">
			<div class="small-12 medium-6 large-6 columns">
				<h3>Material Requests View</h3>
			</div>
			<div class="small-12 medium-6 large-6 columns">
				<p class="right"><br /><a href="{$url}/supplies?action=supplyrequests&term={$term}#camp_$camp" class="button small success radius">&lt;= Back to Material Requests</a></p>
			</div>
		</div>
EOT;
        $content_array['content'] .= <<<EOT

		<div class="row">
			<div class="small-12 medium-2 large-2 columns">
				<a href="#" data-reveal-id="changelog" class="button small success radius">View Change Log</a>
			</div>
			<div class="small-12 medium-10 large-10 columns">
EOT;
        $content_array['content'] .= gambaDirections::getDirections('supplylistview');
        $content_array['content'] .= <<<EOT
			</div>
		</div>
EOT;
        $content_array['content'] .= self::view_change_log($id);
        $content_array['content'] .= self::data_inputs($camp, $id, $term, $activity_id);
        // 			gambaDebug::preformatted_arrays($supplyparts, 'supplyparts', 'Supply Parts');
        if($user_group <= 1) {
            $content_array['content'] .= self::move_materials_change($camp, $id, $term, $activity_id, $button_disabled, $supplyparts['locked'], $packing_list_ids);
        }
        if($theme_budget == 1 && $activity_info['theme_options']['theme_budget_per_camper'] != "") {
            if($list_budget == "") { $list_budget = "0.00"; }
            $content_array['content'] .= "<h3><strong>Theme Budget:</strong> $".$activity_info['theme_options']['theme_budget_per_camper']." &nbsp; &nbsp; <strong>List Budget:</strong> $".$list_budget."</h3>";
        }
        if($camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
            $theme_budget = "$" . number_format($activity_info['budget']['theme_budget_per_camper_yr1'], 3);
        } else {
            $theme_budget = "$" . number_format($activity_info['budget']['theme_budget_per_camper'], 3);
        }
        $activity_cost_per_camper = "$" . number_format($activity_info['costing_summary']['activity_cost_per_camper'], 3);
        $activity_cost_per_class = "$" . number_format($activity_info['costing_summary']['activity_cost_per_class'], 3);
        // 			echo '<pre>'; print_r($activity_info); echo "</pre>";
        $content_array['content'] .= <<<EOT
		<div class="row">
			<div class="small-12 medium-2 large-2 columns">
				<strong>Budget: {$theme_budget}</strong>
			</div>
			<div class="small-12 medium-2 large-2 columns">
				<strong>Total Cost Per Camper: {$activity_cost_per_camper}</strong>
			</div>
			<div small-12 medium-8 large-8 columns>
				<strong>Total Cost Per Class: {$activity_cost_per_class}</strong>
			</div>
		</div>
EOT;

        if(is_array($supplyparts['supplies'])) {
            $content_array['content'] .= <<<EOT
		<script type="text/javascript">
			$(document).ready(function(){
				$("[data-toggle=popover]").popover();
			});
			// call the tablesorter plugin
			$(function(){
			    $(".table-sorter").tablesorter({
					widgets: [ 'stickyHeaders' ],
					widgetOptions: { stickyHeaders_offset : 50, },
				});
			 });
		</script>
		<table class="table table-striped table-bordered table-hover table-condensed table-small table-sorter">
			<thead>
				<tr>
					<th><a href="{$url}/supplies/supplylistedit?term=$term&camp=$camp&id=$id&activity_id=$activity_id " class="button small success radius$button_disabled">Edit All</a></th>
					<th>Part #</th>
					<th>Material</th>
					<th>UoM</th>
					<th>Cost Per<br />Item</th>
EOT;
            if($camps[$camp]['camp_values']['cost_analysis'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
                $year_one = "<br />1st Year";
            } else {
                $year_one = "";
            }
            $content_array['content'] .= <<<EOT

					<th>Cost Per<br />Camper{$year_one}</th>
EOT;
            if($camps[$camp]['camp_values']['cost_analysis'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
                $content_array['content'] .= <<<EOT

					<th>Cost Per<br />Camper<br />2nd Year</th>
EOT;
            }
            $content_array['content'] .= <<<EOT

					<th>Cost<br />Per Class</th>
EOT;
            $nc3display = ""; if($camp == 1) { $nc3display = "<br />/NCx3"; }
            $content_array['content'] .= <<<EOT

					<th class="center">C/NC{$nc3display}</th>
EOT;
            if(is_array($qts['static'])) {
                foreach($qts['static'] as $key => $value) {
                    if($value['qt_options']['terms'][$term] == "true") {
                        $content_array['content'] .= <<<EOT

					<th title="Quantity ID: {$key}" width="50" class="center">{$value['name']}</th>
EOT;
                    }
                }
            }
            // if(is_array($qts['dropdown'])) {
           //  foreach($qts['dropdown'] as $key => $value) {
           //  if($value['qt_options']['terms'][$term] == "true") {
           //  $content_array['content'] .= '<th title="Quantity ID: '.$key.'" width="50" class="center">'.$value['name'].'</th>';
           //  }
           //  }
           //  }
            $content_array['content'] .= <<<EOT

					<th>Quantity Type</th>
					<th>Quantity</th>
EOT;
            if(is_array($location_array)) {
                foreach($location_array as $location_id => $location_values) {
                    $content_array['content'] .= <<<EOT

					<th title="Location: {$location_id} - {$location_values['name']}" width="50" class="location-vertical-text center">{$location_values['abbr']}</th>
EOT;
                }
            }
            if($day_col == "true") {
                $content_array['content'] .= <<<EOT

					<th>Day</th>
EOT;
            }
            /// $content_array['content'] .= <<<EOT
           //  <th>Cost Per<br />Camper Year 1</th>
           //  EOT;
           //  if($camps[$camp]['camp_values']['cost_analysis'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
           //  $content_array['content'] .= <<<EOT
            // <th>Cost Per<br />Camper Year 2</th>
           //  EOT;
           //  }
            $content_array['content'] .= <<<EOT

					<th>Notes</th>
					<th>Packing Total</th>
					<th>UoM</th>
					<th>Purchaser<br />Notes</th>
				</tr>
			</thead>
			<tbody>
EOT;
            foreach($supplyparts['supplies'] as $supply_id => $values) {
                if($values['conversion'] > 0) { $conversion = " [F] "; } else { $conversion = ""; }
                if($values['cost'] != "") { $cost = "$".number_format($values['cost'], 3); } else { $cost = ""; }
                if($values['itemtype'] != "C") {
                    if($values['cost'] != "") {
                        $amt_cost = $values['cost'] * $amt_cost_per[$camp][$term];
                        $amt_cost = "$".number_format($amt_cost, 3);
                    } else {
                        $amt_cost = "";
                    }
                } else {
                    $amt_cost = "";
                }
                if($values['exclude'] == 1) { $row_exclude = 'row-exclude'; $part_exclude = '<br />[REQUEST EXCLUDED FROM PACKING]'; } else { $row_exclude = ""; $part_exclude = ""; }
                if($values['approved'] == 1) { $row_not_approved = 'row-not-approved'; $part_not_approved = '<br />[PART NOT APPROVED BY ADMIN]'; } else { $row_not_approved = ""; $part_not_approved = ""; }
                if($camps[$camp]['camp_values']['cost_analysis'] == "true") {
                    // Less than 1 cent
                    if($values['costing_summary']['part_cost_per_camper'] < 0.001 && $values['costing_summary']['part_cost_per_camper'] != "") {
                        $cost = '&lt; $0.001';
                    } elseif($values['costing_summary']['part_cost_per_camper'] >= 0.001) {
                        $cost = "$" . number_format($values['costing_summary']['part_cost_per_camper'], 3);
                    } else {
                        $cost = "";
                    }
                    $cost_calc = trim($values['costing_summary']['parts']['calc']);
                    if($values['cost'] != "") {
                        $item_cost = '$'.number_format($values['cost'], 3);
                    } else {
                        $item_cost = '$0.00';
                    }
                }
                $content_array['content'] .= <<<EOT
				<tr class="{$row_exclude}{$row_not_approved}">
					<td><a href="{$url}/supplies/delete_supply?id=$id&term=$term&camp=$camp&supply_id=$supply_id&part={$values['part']}&activity_id=$activity_id" class="button small alert radius$button_disabled" onclick="return confirm('Are you sure you want to delete {$values['description']}');">Delete</a></td>
					<td title="Part #">{$values['part']}</td>
					<td title="Material">{$values['description']} {$part_exclude} {$part_not_approved}</td>
					<td title="UoM">{$values['suom']}</td>
					<td title="Cost Per Item">{$item_cost}</td>
					<td class="center" title="Cost Per Camper: {$cost_calc}">{$cost}</td>
EOT;
                if($camps[$camp]['camp_values']['cost_analysis'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
                    // Less than 1 cent
                    if($values['costing_summary']['parts']['cost_per_camper_yr2'] < 0.01 && $values['costing_summary']['parts']['cost_per_camper_yr2'] != "") {
                        $cost2 = '&lt; $0.01';
                    } elseif($values['costing_summary']['parts']['cost_per_camper_yr2'] >= 0.01) {
                        $cost2 = "$" . number_format($values['costing_summary']['parts']['cost_per_camper_yr2'], 3);
                    } else {
                        $cost2 = "";
                    }
                    $cost_calc2 = trim($values['costing_summary']['parts']['calc_yr2']);
                    $content_array['content'] .= <<<EOT

					<td class="center" title="Cost Per Camper Year 2: {$cost_calc2}">{$cost2}</td>
EOT;
                }
                // Actual Cost per Class
                if($values['costing_summary']['parts']['actual_cost_per_class'] < .01) {
                    $actual_cost_per_class = '&lt; $0.01';
                } else {
                    $actual_cost_per_class = "$".number_format($values['costing_summary']['parts']['actual_cost_per_class'], 3);
                }
                //if($values['exclude'] == 1) { $actual_cost_per_class = ""; }
                $actual_cost_per_class_calc = trim($values['costing_summary']['parts']['actual_cost_per_class_calc']);
                $content_array['content'] .= <<<EOT

					<td class="center" title="Cost Per Class: {$actual_cost_per_class_calc}">{$actual_cost_per_class}</td>
					<td class="center" title="C/NC/NCx3">{$values['itemtype']}</td>
EOT;
                if(is_array($qts['static'])) {
                    foreach($qts['static'] as $key => $value) {
                        if($value['qt_options']['terms'][$term] == "true") {
                            // 								if($value['old_table_identifier'] == "") {
                            // 									$identifier = $key;
                            // 								} else {
                            // 									$identifier = $value['old_table_identifier'];
                            // 								}
                            $content_array['content'] .= <<<EOT

					<td title="Quantity ID: {$key} ({$identifier})" class="center">
EOT;
                            if($values['request_quantities']['static'][$key] != "") {
                                $content_array['content'] .= str_replace('.00', '',$values['request_quantities']['static'][$key]);
                            } else {
                                $content_array['content'] .= '0';
                            }
                            $content_array['content'] .= <<<EOT
</td>
EOT;
                        }
                    }
                }
                $quantity = str_replace('.00', '',$values['request_quantities']['quantity_val']);
                $content_array['content'] .= <<<EOT

					<td title="Quantity Type">{$qts['dropdown'][$values['request_quantities']['quantity_type_id']]['name']}</td>
					<td class="center" title="Quantity">$quantity</td>
EOT;
                // Location Assigned Amount (Override any calculted amount)
                if(is_array($location_array)) {
                    foreach($location_array as $location_id => $location_values) {
                        $content_array['content'] .= '<td title="Location: '.$location_id.' - '.$location_values['name'].'" class="center">';
                        if($values['location_quantities'][$location_id]['value'] != "") {
                            $content_array['content'] .= str_replace('.00', '', $values['location_quantities'][$location_id]['value']);
                            if($values['cost'] != "") { $cost_per_camper[$supply_id] = $cost_per_camper[$supply_id] + ($values['location_quantities'][$location_id]['value'] * $values['cost']); }
                        }
                        $content_array['content'] .= '</td>';
                    }
                }
                $cost_per_camper_total += $cost_per_camper = $values['cost'] * $avg_camper;
                if($day_col == "true") {
                    $content_array['content'] .= <<<EOT

					<td class="center" title="Day">{$values['request_quantities']['day']}</td>
EOT;
                }

            //    <td class="right"><?php echo $values['cost_per_camper']; //if($cost_per_camper != "") { echo "$".number_format($cost_per_camper, 2); } ?></td>
            //
            //    if($camps[$camp]['camp_values']['cost_analysis'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
            //
            //    <td class="right"><?php echo $values['cost_per_camper']; //if($cost_per_camper != "") { echo "$".number_format($cost_per_camper, 2); } ?></td>
            //
             //   }
                $content_array['content'] .= <<<EOT

					<td title="Notes">
EOT;
                if($values['notes']) {
                    $notes = substr($values['notes'], 0, 15);
                    $content_array['content'] .= <<<EOT
					<a data-container="body" data-toggle="popover" data-placement="left" data-content="{$values['notes']}" data-original-title title="Note for {$values['part']} {$values['description']}"> {$notes}...</a>
EOT;
                }
                $content_array['content'] .= <<<EOT
					</td>
					<td class="center" title="Packing Total">{$values['total_amount']}</td>
					<td title="FB UoM">{$values['fbuom']}</td>
					<td title="Purchaser Notes">{$values['adminnotes']}</td>
				</tr>
EOT;
            }
            $content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
        } else {
            $content_array['content'] .= <<<EOT
			<p>There are no supply lists created for this camp.</p>
EOT;
        }
        if($supplyparts['locked'] == "false" || $supplyparts['locked'] != "true" || $supplyparts['locked'] == "") {
            // $content_array['content'] .= <<<EOT
            // <script type="text/javascript" src="js/add_form_field.js.php?activity_id={$activity_id}&camp={$camp}&term={$term}"></script>
           //  EOT;
            $content_array['content'] .= <<<EOT
		<h3>Material Add</h3>
EOT;
            $content_array['content'] .= gambaDirections::getDirections("material_add_$camp");
            $content_array['content'] .= <<<EOT
		<form method="post" action="{$url}/supplies/supplies_add" name="add" class="form">
EOT;
            $content_array['content'] .= csrf_field();
            $content_array['content'] .= <<<EOT

		<table class="table table-bordered table-hover table-condensed table-small">
			<thead>
				<tr>
					<th style="width: 150px !important;">Material</th>
EOT;
            if(is_array($qts['static'])) {
                foreach($qts['static'] as $key => $value) {
                    if($value['qt_options']['terms'][$term] == "true") {
                        $content_array['content'] .= "<th>".$value['name']."</th>";
                    }
                }
            }
            if(is_array($qts['dropdown'])) {
                $content_array['content'] .= '<th class="table-header-theme-odd">Quantity</th>';
                $content_array['content'] .= '<th class="table-header-theme-odd">Quantity Type</th>';
            }
            if(is_array($location_array)) {
                foreach($location_array as $location_id => $location_values) {
                    $content_array['content'] .= '<th title="Location: '.$location_id.' - '.$location_values['name'].'" width="50" class="center">'.$location_values['abbr'].'</th>';
                }
            }
            $ncx3 = ""; if($camp == 1) { $ncx3 = "/NCx3"; }
            $content_array['content'] .= <<<EOT

EOT;
            if($day_col == "true") {
                $content_array['content'] .= <<<EOT
					<th>Day</th>
EOT;
            }
            $content_array['content'] .= <<<EOT
					<th>Notes</th>
				</tr>
			</thead>
			<tbody>
EOT;
            for($i=1; $i <= 10; $i++) {
                $content_array['content'] .= <<<EOT
				<tr>
					<td><input type="text" name="add[{$i}][part]" class="partlist form-control" style="min-width: 200px;" /></td>
EOT;
                if(is_array($qts['static'])) {
                    foreach($qts['static'] as $key => $value) {
                        if($value['qt_options']['terms'][$term] == "true") {
                            // 								if($value['old_table_identifier'] == "") {
                            // 									$identifier = $key;
                            // 								} else {
                            // 									$identifier = $value['old_table_identifier'];
                            // 								}
                            $content_array['content'] .= <<<EOT
					<td><input type="text" name="add[{$i}][request_quantities][static][{$key}]" class="form-control" size="40" /></td>
EOT;
                        }
                    }
                }
                if(is_array($qts['dropdown'])) {
                    $content_array['content'] .= <<<EOT
					<td class="table-header-theme-odd"><input type="text" name="add[{$i}][request_quantities][quantity_val]" class="form-control" style="min-width: 65px;" /></td>
					<td class="table-header-theme-odd">
						<select name="add[{$i}][request_quantities][quantity_type_id]" class="form-control" style="min-width: 180px;">
EOT;
                    foreach($qts['dropdown'] as $key => $value) {
                        $content_array['content'] .= "<option value=\"$key\">".$value['name']."</option>";
                    }
                    $content_array['content'] .= '</select></td>';
                }
                if(is_array($location_array)) {
                    foreach($location_array as $location_id => $location_values) {
                        $content_array['content'] .= '<td><input type="text" name="add['. $i .'][location_quantities]['.$location_id.'][value]" class="form-control" /></td>';
                    }
                }
                $content_array['content'] .= <<<EOT
					<td><select name="add[{$i}][itemtype]" class="form-control">
						<option value="C">C</option>
						<option value="NC">NC</option>
EOT;
                if($camp == 1) {
                    $content_array['content'] .= <<<EOT
						<option value="NCx3">NCx3</option>
EOT;
                }
                $content_array['content'] .= <<<EOT
					</select></td>
EOT;
                if($day_col == "true") {
                    $content_array['content'] .= <<<EOT
					<td><input type="text" name="add[{$i}][request_quantities][day]" class="form-control" style="min-width: 40px;" /></td>
EOT;
                }
                $content_array['content'] .= <<<EOT
					<td><textarea name="add[{$i}][notes]" class="form-control" style="min-width:200px;"></textarea></td>
				</tr>
				<input type="hidden" name="add[{$i}][material_type]" value="add" />
EOT;
            }
            $content_array['content'] .= <<<EOT
			</tbody>
		</table>
		<p><button type="submit" name="submit" class="button small success radius">Add Materials To Request List</button></p>
EOT;
            // if($user_group <= 1) {
            // $content_array['content'] .= <<<EOT
           //  <p><input type="checkbox" name="debug" value="1" /> Debug: Check off box to check calculation.</p>
           //  EOT;
            // }
            $content_array['content'] .= <<<EOT
		<h3>Create & Add New Parts</h3>
EOT;
            $content_array['content'] .= gambaDirections::getDirections("create_materials_$camp",'direction');
            $content_array['content'] .= <<<EOT
		<table class="table table-bordered table-hover table-condensed table-small">
			<thead>
				<tr>
					<!-- <th></th> -->
					<th style="width: 150px !important;">Material</th>
					<th>UoM</th>
					<th>URL</th>
EOT;
            if(is_array($qts['static'])) {
                foreach($qts['static'] as $key => $value) {
                    if($value['qt_options']['terms'][$term] == "true") {
                        $content_array['content'] .= "<th>".$value['name']."</th>";
                    }
                }
            }
            if(is_array($qts['dropdown'])) {
                $content_array['content'] .= <<<EOT
					<th class="table-header-theme-odd">Quantity</th>
					<th class="table-header-theme-odd">Quantity Type</th>
EOT;
            }
            if(is_array($location_array)) {
                foreach($location_array as $location_id => $location_values) {
                    $content_array['content'] .= '<th title="Location: '.$location_id.' - '.$location_values['name'].'" width="50" class="center">'.$location_values['abbr'].'</th>';
                }
            }
            $ncx3 = ""; if($camp == 1) { $ncx3 = "/NCx3"; }
            $content_array['content'] .= <<<EOT
					<th>C/NC{$ncx3}</th>
EOT;
            if($day_col == "true") {
                $content_array['content'] .= <<<EOT
					<th>Day</th>
EOT;
            }
            $content_array['content'] .= <<<EOT
					<th>Notes</th>

				</tr>
			</thead>
			<tbody>
EOT;
            for($i=11; $i <= 15; $i++) {
                $content_array['content'] .= <<<EOT
				<tr>
					<!-- <td><a href="#" onClick="addNMFormField(); return false;" class="button small success radius">Add</a></td> -->
					<td><input type="text" name="add[{$i}][part]" class="form-control" style="min-width: 200px;" /></td>
					<td><select name="add[{$i}][suom]" class="form-control" style="min-width: 100px;">
						<option value="">--------</option>
EOT;
                foreach($uoms['uoms'] as $id => $uom_values) {
                    $content_array['content'] .= '<option value="'.$uom_values['code'].'">'.$uom_values['name'].'</option>';
                }
                $content_array['content'] .= <<<EOT
					</select></td>
					<td><input type="text" name="add[{$i}][url]" class="form-control" style="min-width: 200px;" /></td>
EOT;
					if(is_array($qts['static'])) {
						foreach($qts['static'] as $key => $value) {
							if($value['qt_options']['terms'][$term] == "true") {
// 								if($value['old_table_identifier'] == "") {
// 									$identifier = $key;
// 								} else {
// 									$identifier = $value['old_table_identifier'];
// 								}
								$content_array['content'] .= '<td><input type="text" name="add['.$i.'][request_quantities][static]['.$key.']" class="form-control" size="40" /></td>';
							}
						}
					}

					if(is_array($qts['dropdown'])) {
						$content_array['content'] .= <<<EOT
					<td class="table-header-theme-odd">
						<input type="text" name="add[{$i}][request_quantities][quantity_val]" class="form-control" style="min-width: 65px;" />
					</td>
					<td class="table-header-theme-odd">
						<select name="add[{$i}][request_quantities][quantity_type_id]" class="form-control" style="min-width: 180px;">
EOT;
                    foreach($qts['dropdown'] as $key => $value) {
                        $content_array['content'] .= "<option value=\"$key\">".$value['name']."</option>";
                    }
                    $content_array['content'] .= '</select></td>';
                }
                if(is_array($location_array)) {
                    foreach($location_array as $location_id => $location_values) {
                        $content_array['content'] .= '<td><input type="text" name="add['.$i.'][location_quantities]['.$location_id.'][value]" class="form-control" /></td>';
                    }
                }
                $content_array['content'] .= <<<EOT
					<td><select name="add[{$i}][itemtype]" class="form-control">
						<option value="C">C</option>
						<option value="NC">NC</option>
EOT;
                if($camp == 1) {
                    $content_array['content'] .= <<<EOT
						<option value="NCx3">NCx3</option>
EOT;
                }
                $content_array['content'] .= <<<EOT
					</select></td>
EOT;
                if($day_col == "true") {
                    $content_array['content'] .= <<<EOT
					<td><input type="text" name="add[{$i}][request_quantities][day]" class="form-control" style="min-width: 40px;" /></td>
EOT;
                }
                $content_array['content'] .= <<<EOT
					<td><textarea name="add[{$i}][notes]" class="form-control" style="min-width:200px;"></textarea></td>
					<input type="hidden" name="add[{$i}][material_type]" value="create" />
				</tr>
EOT;
            }
            $content_array['content'] .= <<<EOT
			</tbody>
			<!-- <tbody id="divNMRow">
			</tbody> -->
		</table>
		<input type="hidden" id="rowid" value="12" />
		<input type="hidden" id="rowNMid" value="12" />
		<input type="hidden" name="action" value="supplies_add" />
		<input type="hidden" name="term" value="{$term}" />
		<input type="hidden" name="camp" value="{$camp}" />
		<input type="hidden" name="id" value="{$array['id']}" />
		<input type="hidden" name="activity_id" value="{$activity_id}" />
		<p><button type="submit" name="submit" class="button small success radius">Add Materials To Request List</button></p>
		</form>
EOT;
        }
        $content_array['content'] .= gambaParts::parts_autocomplete();
        // 			$content_array['content'] .= gambaDirections::getDirections("create_materials_$camp",'modal');
        // 			gambaDebug::preformatted_arrays($qts, "quantity_types", "Quantity Types");
        // 			gambaDebug::preformatted_arrays($uoms, "uoms", "Unit of Measures");
        // 			gambaDebug::preformatted_arrays($supplyparts, "supplyparts", "Supply Parts");
        // 			gambaDebug::preformatted_arrays($camps[$camp], "camps", "Camps");
        // 			$content_array['content'] .= "<pre>";
        // 			$content_array['content'] .= print_r($uoms, true);
        // 			$content_array['content'] .= "</pre>";
        return $content_array;
    }

   */
   /**
     * Moved to SuppliesController.php and cwlistviewedit.blade.php
     * @param unknown $array

    public static function supplylistedit($array) {
        $url = url('/');
        $user_id = Session::get('uid');
        $id = $array['id'];
        $term = $array['term'];
        $activity_id = $array['activity_id'];
        $camp = $array['camp'];
        $supplyparts = self::supplyparts($id);
        $activity_info = gambaActivities::activity_info($activity_id);
        $qts = gambaQuantityTypes::quantity_types_by_camp($camp, $term);
        $uoms = gambaUOMs::uom_list();
        $camps = gambaCampCategories::camps_list();
        $day_col = $camps[$camp]['camp_values']['day_col'];
        if(is_array($camps[$camp]['camp_values']['request_locations'])) {
            $locations = gambaLocations::locations_by_camp();
            foreach($camps[$camp]['camp_values']['request_locations'] as $key => $camp_id) {
                // echo $camp_id;
                foreach($locations['locations'][$camp_id] as $location_id => $location_values) {
                    if($location_values['terms'][$term]['active'] == "Yes") {
                        $location_array[$location_id]['camp'] = $camp_id;
                        $location_array[$location_id]['abbr'] = $location_values['abbr'];
                        $location_array[$location_id]['name'] = $location_values['name'];
                    }
                }
            }
        }
        $content_array['page_title'] = "Material Requests Edit";
        $content_array['content'] .= <<<EOT
		<h3><strong>Term:</strong> $term &nbsp; <strong>Activity:</strong> {$activity_info['name']} &nbsp; <strong>Grade:</strong> {$activity_info['grade_name']}<br />
			<strong>Theme:</strong> {$activity_info['theme_name']}&nbsp; <strong>Camp:</strong> {$camps[$camp]['name']}</h3>
EOT;
        $content_array['content'] .= gambaDirections::getDirections('supplylistview');
        if($array['data_inputs_updated'] == 1) {
            $content_array['content'] .= "<div class='alert alert-success'>You have successfully update the Data Inputs for this Material Request. Please check you quantity requests below and click on <span class='button small success radius'>Update Material Requests</span> at the bottom of the page.</div>";
        }
        $content_array['content'] .= <<<EOT
		<form method="post" action="{$url}/supplies/supplies_update" name="supply_edit" class="form" id="supplyEdit">
EOT;
        $content_array['content'] .= csrf_field();
        $content_array['content'] .= <<<EOT
		<table class="table table-bordered table-hover table-condensed table-small table-fixed-header">
			<thead>
				<tr>
					<th>Delete</th>
					<th>Part #</th>
					<th style="width: 150px !important;">Material Name</th>
					<th>Exclude</th>
					<th class="center">Standard<br />UoM</th>
EOT;
        if(is_array($qts['static'])) {
            foreach($qts['static'] as $key => $value) {
                $content_array['content'] .= "<th style=\"width: 150px !important;\">{$value['name']}</th>";
            }
        }
        if(is_array($qts['dropdown'])) {
            $content_array['content'] .= '<th class="table-header-theme-odd" style=\"width: 75px !important;\">Quantity</th>';
            $content_array['content'] .= '<th class="table-header-theme-odd" style=\"width: 150px !important;\">Quantity Type</th>';
        }
        if(is_array($location_array)) {
            foreach($location_array as $location_id => $location_values) {
                $content_array['content'] .= '<th title="Location: '.$location_id.' - '.$location_values['name'].'" width="50" class="center">'.$location_values['abbr'].'</th>';
            }
        }
        if($camp == 1) { $ncx3 = "/NCx3"; } else { $ncx3 = ""; }
        $content_array['content'] .= <<<EOT
					<th>C/NC{$ncx3}</th>
EOT;
        if($day_col == "true") {
            $content_array['content'] .= <<<EOT
					<th>Day</th>
EOT;
        }
        $content_array['content'] .= <<<EOT
					<th>Notes</th>
				</tr>
			</thead>
			<tbody>
EOT;
        foreach($supplyparts['supplies'] as $supply_id => $values) {
            if($values['exclude'] == 1) { $exclude = "checked "; } else { $exclude = ""; }
            $content_array['content'] .= <<<EOT
				<tr>
					<td class="center"><input type="checkbox" name="update[{$supply_id}][delete]" value="true" class="partDelete" /></td>
					<td>{$values['part']}<input type="hidden" name="update[{$supply_id}][part]" value="{$values['part']}" /></td>
					<td>{$values['description']}<input type="hidden" name="update[{$supply_id}][description]" value="{$values['description']}" /></td>
					<td class="center"><input type="checkbox" name="update[{$supply_id}][exclude]" value="1" {$exclude}/></td>
					<td class="center">{$values['suom']}</td>
EOT;
            if(is_array($qts['static'])) {
                foreach($qts['static'] as $key => $value) {
                    // 							if($value['old_table_identifier'] == "") {
                    // 								$identifier = $key;
                    // 							} else {
                    // 								$identifier = $value['old_table_identifier'];
                    // 							}
                    $content_array['content'] .= '<td><input type="text" name="update['. $supply_id .'][request_quantities][static]['.$key.']" value="'.$values['request_quantities']['static'][$key].'" class="form-control" size="40" /></td>';
                }
            }
            if(is_array($qts['dropdown'])) {
                $content_array['content'] .= '<td class="table-header-theme-odd"><input type="text" name="update['. $supply_id .'][request_quantities][quantity_val]" value="'.$values['request_quantities']['quantity_val'].'" class="form-control" style="min-width: 65px;" /></td>';
                $content_array['content'] .= '<td class="table-header-theme-odd"><select name="update['. $supply_id .'][request_quantities][quantity_type_id]" class="form-control" style="min-width: 180px;">';
                foreach($qts['dropdown'] as $key => $value) {
                    $content_array['content'] .= "<option value=\"$key\"";
                    if($values['request_quantities']['quantity_type_id'] == $key) { $content_array['content'] .= " selected"; }
                    $content_array['content'] .= ">".$value['name']."</option>";
                }
                $content_array['content'] .= '</select></td>';
            }
            if(is_array($location_array)) {
                foreach($location_array as $location_id => $location_values) {
                    $content_array['content'] .= '<td><input type="text" name="update['. $supply_id .'][location_quantities]['.$location_id.'][value]" value="'.$values['location_quantities'][$location_id]['value'].'" class="form-control" /></td>';
                }
            }
            if($values['itemtype'] == "C") { $cselected = " selected"; } else { $cselected = ""; }
            if($values['itemtype'] == "NC") { $ncselected = " selected"; } else { $ncselected = ""; }
            $content_array['content'] .= <<<EOT
					<td><select name="update[{$supply_id}][itemtype]" class="form-control">
						<option value="C"{$cselected}>C</option>
						<option value="NC"{$ncselected}>NC</option>
EOT;
            if($camp == 1) {
                if($values['itemtype'] == "NCx3") { $nc3select = " selected"; } else { $nc3select = ""; }
                $content_array['content'] .= <<<EOT
						<option value="NCx3"{$nc3select}>NCx3</option>
EOT;
            }
            $content_array['content'] .= <<<EOT
					</select></td>
EOT;
            if($day_col == "true") {
                $content_array['content'] .= <<<EOT
					<td><input type="text" name="update[{$supply_id}][request_quantities][day]" value="{$values['request_quantities']['day']}" class="form-control" style="min-width: 40px;" /></td>
EOT;
					}
					$content_array['content'] .= <<<EOT
					<td><textarea name="update[{$supply_id}][notes]" class="form-control" style="min-width:200px;">{$values['notes']}</textarea></td>
				</tr>
EOT;
        }
        $content_array['content'] .= <<<EOT
			</tbody>
		</table>
		<script type="text/javascript">
			$(document).ready(function() {
				$('#supplyEdit').submit(function(event) {
					if ($('.partDelete').prop('checked')) {
						if(!confirm("Are you sure you want to delete supplies.")) {
							event.preventDefault();
						}
					}
				});
			});
		</script>
EOT;
        // if($user_id == 1) {
        // $content_array['content'] .= <<<EOT
        // <p><input type="checkbox" name="debug" value="1" /> Debug: Check off box to check calculation.</p>
        // EOT;
        // }
        $content_array['content'] .= <<<EOT
		<input type="hidden" name="action" value="supplies_update" />
		<input type="hidden" name="term" value="{$term}" />
		<input type="hidden" name="camp" value="{$camp}" />
		<input type="hidden" name="id" value="{$_REQUEST['id']}" />
		<input type="hidden" name="activity_id" value="{$activity_id}" />
		<p><button type="submit" name="submit" class="button small success radius">Update Material Requests</button></p>
		</form>
EOT;
        //gambaDebug::preformatted_arrays($supplyparts, "supplyparts", "Supply Parts");
        return $content_array;
    }

*/
    public static function list_download($array) {

        $id = $array['id'];
        $term = $array['term'];
        $activity_id = $array['activity_id'];
        $camp = $array['camp'];
        $supplyparts = self::supplyparts($id);
        $list_budget = $supplyparts['budget'];
        $activity_info = gambaActivities::activity_info($activity_id, $id);
        $qts = gambaQuantityTypes::quantity_types_by_camp($camp, $term);
        $uoms = gambaUOMs::uom_list();
        $camps = gambaCampCategories::camps_list();
        $amt_cost_per = self::amt_cost_per();
        $theme_budget = self::theme_budget();
        $day_col = $camps[$camp]['camp_values']['day_col'];

        if(is_array($camps[$camp]['camp_values']['request_locations'])) {
            $locations = gambaLocations::locations_by_camp();
            foreach($camps[$camp]['camp_values']['request_locations'] as $key => $camp_id) {
                // echo $camp_id;
                foreach($locations['locations'][$camp_id] as $location_id => $location_values) {
                    if($location_values['terms'][$term]['active'] == "Yes") {
                        $location_array[$location_id]['camp'] = $camp_id;
                        $location_array[$location_id]['abbr'] = $location_values['abbr'];
                        $location_array[$location_id]['name'] = $location_values['name'];
                    }
                }
            }
        }
        if(is_array($supplyparts['supplies'])) {
            echo "\"Part\",\"Material\",\"UoM\",";
            if($camps[$camp]['camp_values']['cost_analysis'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") { $year_one = " 1st Year"; } else { $year_one = ""; }
            echo "\"Cost Per Item{$year_one}\",";
            if($camps[$camp]['camp_values']['cost_analysis'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
                echo "\"Cost Per Item 2nd Year\",";
            }
            echo "\"C/NC"; if($camp == 1) { echo '/NCx3'; } echo "\",";
            if(is_array($qts['static'])) {
                foreach($qts['static'] as $key => $value) {
                    if($value['qt_options']['terms'][$term] == "true") {
                        echo "\"{$value['name']}\",";
                    }
                }
            }
            if(is_array($qts['dropdown'])) {
                foreach($qts['dropdown'] as $key => $value) {
                    if($value['qt_options']['terms'][$term] == "true") {
                        echo "\"{$value['name']}\",";
                    }
                }
            }
            if(is_array($location_array)) {
                foreach($location_array as $location_id => $location_values) {
                    echo "\"{$location_values['abbr']}\",";
                }
            }
            if($day_col == "true") {
                echo "\"Day\",";
            }
            echo "\"Notes\",\"Packing Total\",\"UoM\",\"Purchaser Notes\"\r";

            foreach($supplyparts['supplies'] as $supply_id => $values) {
                if($values['conversion'] > 0) { $conversion = " [F] "; } else { $conversion = ""; }
                if($values['cost'] != "") { $cost = "$".number_format($values['cost'], 3); } else { $cost = ""; }
                if($values['itemtype'] != "C") {
                    if($values['cost'] != "") {
                        $amt_cost = $values['cost'] * $amt_cost_per[$camp][$term];
                        $amt_cost = "$".number_format($amt_cost, 3);
                    } else {
                        $amt_cost = "";
                    }
                } else {
                    $amt_cost = "";
                }
                if($values['exclude'] == 1) {
                    $row_exclude = 'row-exclude'; $part_exclude = ' [REQUEST EXCLUDED FROM PACKING]';
                } else {
                    $row_exclude = ""; $part_exclude = "";
                }
                if($values['approved'] == 1) {
                    $row_not_approved = 'row-not-approved'; $part_not_approved = ' [PART NOT APPROVED BY ADMIN]';
                } else {
                    $row_not_approved = ""; $part_not_approved = "";
                }
                $description = str_replace("&quot;", "''", $values['description']);
                echo "\"{$values['part']}\",\"{$description} {$part_exclude} {$part_not_approved}\",\"{$values['suom']}\",\"$cost\",";
                if($camps[$camp]['camp_values']['cost_analysis'] == "true" && $camps[$camp]['camp_values']['cost_analysis_summary'] == "gsq") {
                    echo "\"$amt_cost\",";
                }
                echo "\"{$values['itemtype']}\",";
                if(is_array($qts['static'])) {
                    foreach($qts['static'] as $key => $value) {
                        if($value['qt_options']['terms'][$term] == "true") {

                            if($values['request_quantities']['static'][$key] != "") {
                                echo "\"" . str_replace('.00', '',$values['request_quantities']['static'][$key]) . "\",";
                            } else {
                                echo "\"0\",";
                            }
                        }
                    }
                }
                if(is_array($qts['dropdown'])) {
                    foreach($qts['dropdown'] as $key => $value) {
                        if($value['qt_options']['terms'][$term] == "true") {

                            if($key == $values['request_quantities']['quantity_type_id']) {
                                echo "\"" . str_replace('.00', '',$values['request_quantities']['quantity_val']) . "\",";
                            } else {
                                echo "\"0\",";
                            }
                        }
                    }
                }
                // Location Assigned Amount (Override any calculted amount)
                if(is_array($location_array)) {
                    foreach($location_array as $location_id => $location_values) {
                        if($values['location_quantities'][$location_id]['value'] != "") {
                            echo "\"" . str_replace('.00', '', $values['location_quantities'][$location_id]['value']) . "\",";
                            if($values['cost'] != "") {
                                $cost_per_camper[$supply_id] = $cost_per_camper[$supply_id] + ($values['location_quantities'][$location_id]['value'] * $values['cost']);
                            }
                        }
                    }
                }
                $cost_per_camper_total += $cost_per_camper = $values['cost'] * $avg_camper;
                if($day_col == "true") {
                    echo "\"{$values['request_quantities']['day']}\",";
                }
                if($values['notes']) {
                    echo "\"" . addcslashes($values['notes']) . "\",";
                } else {
                    echo "\"\",";
                }
                echo "\"{$values['total_amount']}\",\"{$values['fbuom']}\",";
                echo "\"" . addcslashes($values['adminnotes']) . "\"\r";

            }


        } else {
            echo "There are no supply lists created for this camp.";
        }

    }
}
