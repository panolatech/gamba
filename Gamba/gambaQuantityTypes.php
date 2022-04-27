<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\QuantityTypes;
	use App\Models\Supplies;

	use App\Gamba\gambaCalc;
	use App\Gamba\gambaCampCategories;
	use App\Gamba\gambaDebug;
	use App\Gamba\gambaDirections;
	use App\Gamba\gambaTerm;
	use App\Gamba\gambaUsers;

	class gambaQuantityTypes {

		public static function camp_quantity_types() {
			$camps = gambaCampCategories::camps_list();
// 			echo "Test";
			foreach($camps as $camp => $camp_values) {
				$array[$camp]['camp'] = $camp_values['name'];
				$array[$camp]['camp_alt_name'] = $camp_values['alt_name'];
				$array[$camp]['data_inputs'] = $camp_values['data_inputs'];
				$array[$camp]['num_rows'] = mysql_num_rows(mysql_query("SELECT 1 FROM ".tbpre."quantitytypes WHERE camp_type = $camp"));
				$array[$camp]['camp_cost_options'] = $camp_values['cost_options'];
				$query = QuantityTypes::select('id', 'type', 'value', 'ordering', 'camp_type', 'qt_options', 'old_table_identifier', 'cost_options')->where('camp_type', $camp)->orderBy('ordering')->get();
				if($query->count() > 0) {
					foreach($query as $key => $row) {
						$camp = $row['camp_type'];
						$id = $row['id'];
						$qt_options = json_decode($row['qt_options'], true);
						$cost_options = json_decode($row['cost_options'], true);
						$array[$camp]['quantity_types'][$id]['name'] = $row['type'];
						$array[$camp]['quantity_types'][$id]['camp'] = $camp; // Added for shared quantity types
						$array[$camp]['quantity_types'][$id]['value'] = $row['value'];
						$array[$camp]['quantity_types'][$id]['ordering'] = $row['ordering'];
						$array[$camp]['quantity_types'][$id]['old_table_identifier'] = $row['old_table_identifier'];
						$array[$camp]['quantity_types'][$id]['qt_options'] = $qt_options;
						$array[$camp]['quantity_types'][$id]['cost_options'] = $cost_options; // Added for material cost analysis
						if(is_array($camp_values['camp_values']['quantity_types_shared'])) {
							foreach($camp_values['camp_values']['quantity_types_shared'] as $key => $camp_id) {
								$array[$camp_id]['camp'] = $camps[$camp_id]['name'];
								$array[$camp_id]['camp_alt_name'] = $camps[$camp_id]['alt_name'];
								$array[$camp_id]['shared'] = "true";
								$array[$camp_id]['shared_camp'] = $camp;
								$array[$camp_id]['edit'] = "false";
								$array[$camp_id]['add'] = "false";
								$array[$camp_id]['data_inputs'] = $camps[$camp_id]['data_inputs'];
// 								$array[$camp_id]['camp_cost_options'] = json_decode($camps[$camp_id]['cost_options'], true);
								$array[$camp_id]['num_rows'] = mysql_num_rows(mysql_query("SELECT 1 FROM ".tbpre."quantitytypes WHERE camp_type = $camp"));
								$array[$camp_id]['quantity_types'][$id]['name'] = $row['type'];
								$array[$camp_id]['quantity_types'][$id]['camp'] = $camp; // Added for shared quantity types
								$array[$camp_id]['quantity_types'][$id]['value'] = $row['value'];
								$array[$camp_id]['quantity_types'][$id]['ordering'] = $row['ordering'];
								$array[$camp_id]['quantity_types'][$id]['old_table_identifier'] = $row['old_table_identifier'];
								$array[$camp_id]['quantity_types'][$id]['qt_options'] = $qt_options;
								$array[$camp_id]['quantity_types'][$id]['cost_options'] = $cost_options; // Added for material cost analysis
							}
						}
					}
				}
			}
			return $array;
// 			echo "<pre>"; print_r($array); echo "</pre>";
		}

		public static function quantity_types_by_camp($camp, $term) {
			$qts = self::camp_quantity_types();
			if(is_array($qts[$camp]['quantity_types'])) {
				foreach($qts[$camp]['quantity_types'] as $key => $values) {
					if($values['qt_options']['dropdown'] == "true") {
						if($values['qt_options']['terms'][$term] == "true") {
							$array['dropdown'][$key] = $values;
						}
					} else {
						if($values['qt_options']['terms'][$term] == "true") {
							$array['static'][$key] = $values;
						}
					}
				}
				$array['all'] = $qts[$camp]['quantity_types'];
			}
			return $array;
		}

		private static function qt_terms($id, $terms) {
			if($terms == "") {
				$terms = gambaTerm::terms();
				foreach($terms as $key => $value) {
					$array[$key] = "true";
				}
				$json_array = json_encode($array);
				$update = QuantityTypes::find($id);
					$update->terms = $json_array;
					$update->save();
				return $array;
			} else {
				$terms = json_decode($terms, true);
				return $terms;
			}

		}

		public static function camps_nav($camp) {
			$url = url('/');
			if($camp == "") { $camp = 1; }
			$camps_array = self::camp_quantity_types();
			$camps = gambaCampCategories::camps_list();
// 			echo "<pre>"; print_r($camps_array); echo "</pre>";

			if(is_array($camps_array)) {
				$content .= '<button href="#" data-dropdown="camp_dropdown" aria-controls="drop1" aria-expanded="false" class="button dropdown small">Select Category ('. $camps[$camp]['name'] .') </button><br />';
				$content .= '<ul id="camp_dropdown" data-dropdown-content class="f-dropdown" aria-hidden="true">';
				foreach($camps as $key => $value) {
					if(is_int($key)) {
						$content .= '<li';
						if($camp == $key) { $content .= ' class="disabled"';  $return_camp = $camp; }
						$content .= "><a href=\"{$url}/settings/quantity_types?camp={$key}\">{$value['name']}</a></li>";
					}
				}
				$content .= '</ul>';
			}
			return $content;
		}

		public static function quantity_types_used($camp) {
			$term = gambaTerm::year_by_status('C');
			$query = Supplies::select('id', 'request_quantities')->where('camp_id', $camp)->where('term', '>=', $term)->get();
// 			$array['sql'] = Supplies::last_query();
			if($query->count() > 0) {
				foreach($query as $key => $row) {
					$id = $row['id'];
// 					$array['quantity_types']['ids'] = $id;
					$request_quantities = json_decode($row->request_quantities, true);
					if($request_quantities['quantity_val'] > 0) { $array['quantity_types'][$request_quantities['quantity_type_id']] += 1; }
					if(is_array($request_quantities['static'])) {
						foreach($request_quantities['static'] as $id => $value) {
							if($value > 0) {
								$array['quantity_types'][$id] += 1;
							}
						}
					}
				}
			}
			return $array;
		}

		public static function data_add_quantitytype($array) {
			$camp = $array['camp'];
			$last_row = $array['num_rows'] + 1;
			$name = htmlspecialchars($array['type']);
			$value = $array['value'];
			$old_table_identifier = htmlspecialchars($array['old_table_identifier']);
			if($array['qt_options']['kqd'] == "") { $array['qt_options']['kqd']  = 0; }
			$qt_options = json_encode($array['qt_options']);

			$return['row_added'] = QuantityTypes::insertGetId(['type' => $array['type'], 'value' => $array['value'], 'camp_type' => $camp, 'ordering' => $last_row, 'qt_options' => $qt_options, 'old_table_identifier' => $old_table_identifier]);
			$array['sql'] = \DB::last_query();

			$return['add'] = 1;
			$return['name'] = $name;
			return base64_encode(json_encode($return));
		}

		public static function data_update_quantitytype($array) {
			$id = $array['id'];
			$name = htmlspecialchars($array['type']);
			$value = $array['value'];
			$old_table_identifier = htmlspecialchars($array['old_table_identifier']);
			if($array['qt_options']['kqd'] == "") { $array['qt_options']['kqd']  = 0; }
			$qt_options = json_encode($array['qt_options']);

			$update = QuantityTypes::find($id);
				$update->type = $array['type'];
				$update->value = $array['value'];
				$update->qt_options = $qt_options;
				$update->old_table_identifier = $old_table_identifier;
				$update->save();
			$array['sql'] = \DB::last_query();

			$return['updated'] = 1;
			$return['row_updated'] = $id;
			$return['name'] = $name;
			return base64_encode(json_encode($return));
		}

		public static function data_ordering_quantitytypes($array) {
			$id = $array['id']; $camp = $array['camp']; $order = $array['order'];
			if($array['movement'] == "up") { $array['move'] = $move = $order - 1; }
			if($array['movement'] == "down") { $array['move'] = $move = $order + 1; }

			$displace = QuantityTypes::where('ordering', $move)->where('camp_type', $camp)->update([
				'ordering' => $move
			]);
			$array['displace_sql'] = \DB::last_query();

			$update = QuantityTypes::find($id);
				$update->ordering = $move;
				$update->save();
			$array['update_sql'] = \DB::last_query();

			$return['reorder'] = 1;
			return base64_encode(json_encode($return));
		}

		public static function view_quantity_types($camp, $return) {
			$url = url('/');
			if($camp == "") { $camp = 1; }
			$quantity_types_used = self::quantity_types_used($camp);
			$quantity_types = self::camp_quantity_types();
			$enrollment_data = gambaCalc::enrollment_data();
			$content_array['page_title'] = "Quantity Types: ".$quantity_types[$camp]['camp'];

			$content_array['content'] .= gambaDirections::getDirections('quantity_types_edit');
			$content_array['content'] .= self::camps_nav($camp);
			$terms = gambaTerm::terms();
			$current_term = gambaTerm::year_by_status('C');
			if($return['add_error'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box('Please check your entry and try again.', 'warning');
			}
			if($return['reorder'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box('The Quantity Type has been Moved.', 'success');
			}
			if($return['updated'] == 1) {
				$content_array['content'] .= gambaDebug::alert_box($return['name'].' successfully updated.', 'success');
			}
			if($return['add'] > 0) {
				$content_array['content'] .= gambaDebug::alert_box($return['name'].' successfully added.', 'success');
			}
			$num_rows = $quantity_types[$camp]['num_rows'];
			if($quantity_types[$camp]['add'] == "false") { $add_disabled = " disabled"; }
			if($quantity_types[$camp]['edit'] == "false") { $edit_disabled = " disabled"; }
			$content_array['content'] .= <<<EOT

		<table class="table table-striped table-bordered table-hover table-condensed table-small">
			<thead>
				<tr>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th colspan="3">Material List Calc Divider</th>
					<th></th>
					<th></th>
					<th colspan="2"></th>
				</tr>
				<tr>
					<th><a href="{$url}/settings/quantity_type_add?action=quantity_type_add&camp={$camp}" class="button small radius success{$add_disabled}">Add</a></th>
					<th>ID</th>
					<th>Quantity Type</th>
					<th>Dropdown/<br />Static</th>
					<th>Supplies<br />Current Term</th>
					<th><span data-tooltip aria-haspopup="true" class="has-tip" data-options="show_on:large" title="Hold your mouse over the C, NC or NCx3 to see the formula. The ones that are not bold and do not have a tool tip have not been inputted by the programmer. Formerly listed C, NC or Both.">C/NC/<br />NCx3</span></th>
					<th>KQD</th>
					<th>Data Multiplier</th>
					<th>DLI</th>
					<th title="KQD Person Multiplier">KQD</th>
					<th>Both</th>
					<th>(C)</th>
					<th>(NC)</th>
					<th>Term Enabled</th>
					<th colspan="2">Ordering</th>
				</tr>
			</thead>
			<tbody>
EOT;
			$i = 1;
			foreach($quantity_types[$camp]['quantity_types'] as $key => $values) {
				if($return['row_updated'] == $key || $return['row_added'] == $key) { $row_success = ' class="success"'; } else { $row_success = ""; }
				if($values['qt_options']['dropdown'] == "true") { $dropdown = "Dropdown"; } else { $dropdown = "Static"; }
				if($values['qt_options']['kqdccalculation'] != "") { $kqdccalculation = "KQD C: ". $enrollment_data[$camp][$values['qt_options']['kqdccalculation']]['name'] . "<br />"; } else { $kqdccalculation = ""; }
				if($values['qt_options']['kqdnccalculation'] != "") { $kqdnccalculation = "KQD NC: ". $enrollment_data[$camp][$values['qt_options']['kqdnccalculation']]['name'] . "<br />"; } else { $kqdnccalculation = ""; }
				if($values['qt_options']['ccalculation'] != "") { $ccalculation = "C: ". $enrollment_data[$camp][$values['qt_options']['ccalculation']]['name'] . "<br />"; } else { $ccalculation = ""; }
				if($values['qt_options']['nccalculation'] != "") { $nccalculation = "NC: ". $enrollment_data[$camp][$values['qt_options']['nccalculation']]['name'] . "<br />"; } else { $nccalculation = ""; }
				if($values['qt_options']['data_input'] != "") { $data_input = $quantity_types[$camp]['data_inputs'][$values['qt_options']['data_input']]['name'] . "<br />"; } else { $data_input = ""; }
				if($values['qt_options']['data_input_c'] != "") { $data_input_c = "(C) " . $quantity_types[$camp]['data_inputs'][$values['qt_options']['data_input_c']]['name'] . "<br />"; } else { $data_input_c = ""; }
				if($values['qt_options']['data_input_nc'] != "") { $data_input_nc = "(NC) " . $quantity_types[$camp]['data_inputs'][$values['qt_options']['data_input_nc']]['name'] . "<br />"; } else { $data_input_nc = ""; }
				if($values['qt_options']['kqd'] == 1) { $kqd = "Yes"; } else { $kqd = "No"; }
				$qt_terms = ""; foreach($values['qt_options']['terms'] as $term => $status) {  if($status == "true") { $qt_terms .= "$term, "; }}
				// Ordering
				if($i > 1) {
					$up = "<a href=\"{$url}settings/data_ordering_quantitytypes?action=data_ordering_quantitytypes&id={$key}&movement=up&camp={$camp}&order={$values['ordering']}\" class=\"button primary small\">&#x25BC;</a>";
				} else {
					$up = "";
				}
				if($i != $num_rows) {
					$down = "<a href=\"{$url}/settings/data_ordering_quantitytypes?action=data_ordering_quantitytypes&id={$key}&movement=down&camp={$camp}&order={$values['ordering']}\" class=\"button primary small\">&#x25BC;</a>";
				} else {
					$down = "";
				}

				if($values['qt_options']['dli'] == "dli") { $dli = "DLI"; }
				elseif($values['qt_options']['dli'] == "ndli") { $dli = "Non-DLI"; }
				elseif($values['qt_options']['dli'] == "false") { $dli = "All"; }
				else { $dli = ""; }
				if($quantity_types_used['quantity_types'][$key] > 0) { $qt_used = $quantity_types_used['quantity_types'][$key]; } else { $qt_used = 0; }
				$formulas = ""; $b = 0;
				foreach($values['qt_options']['formulas'] as $qt_type => $formula) {
					if($formula != "") {
						if($b >= 1) { $formulas .= ", "; }
						$formulas .= '<span data-tooltip aria-haspopup="true" class="has-tip" data-options="show_on:large" title="';
						$formulas .= "$qt_type | $formula";
						$formulas .= '">'. $qt_type . '</span>';
						$b++;
					}
				}
				if($formulas == "") {
					$formulas = $values['qt_options']['cnc'];
				}
				$content_array['content'] .= <<<EOT
				<tr{$row_success}>
					<td><a href="{$url}/settings/quantity_type_edit?action=quantity_type_edit&id={$key}&camp={$camp}" class="button small radius{$edit_disabled}">Edit</a></td>
					<td class="center">{$key}</td>
					<td>{$values['name']}</td>
					<td>{$dropdown}</td>
					<td>{$qt_used}</td>
					<td class="center">{$formulas}</td>
					<td class="center">{$kqd}</td>
					<td>
						{$kqdccalculation}
						{$kqdnccalculation}
						{$ccalculation}
						{$nccalculation}
						{$data_input}
						{$data_input_c}
						{$data_input_nc}
					</td>
					<td class="center">{$dli}</td>
					<td class="center">{$values['value']}</td>
					<td class="center">{$values['qt_options']['mlcalc']['mlcalc']}</td>
					<td class="center">{$values['qt_options']['mlcalc']['mlcalcc']}</td>
					<td class="center">{$values['qt_options']['mlcalc']['mlcalcnc']}</td>
					<td>{$qt_terms}</td>
					<td>{$up}</td>
					<td>{$down}</td>
				</tr>
EOT;
				$i++;
			}
			$content_array['content'] .= <<<EOT
			</tbody>
		</table>
EOT;
			return $content_array;
// 			echo "<pre>"; print_r($quantity_types[$camp]); echo "</pre>";
		}

		public static function form_data_all_quanitytypes($action, $return, $array) {
			$url = url('/');
			$camps = gambaCampCategories::camps_list();
			$camp = $array['camp'];
			$terms = gambaTerm::terms();
			$quantity_types_used = self::quantity_types_used($camp);
			$quantity_types = self::camp_quantity_types();
			$enrollment_data = gambaCalc::enrollment_data();

			if($action == "quantity_type_edit") {
				$values = QuantityTypes::where('id', $array['id'])->first();
				$values['qt_options'] = json_decode($values['qt_options'], true);
				$values['cost_options'] = json_decode($values['cost_options'], true);
				$content_array['page_title'] = $camps[$camp]['name'] . ": ". $values['type'];
				$form_action = "data_update_quantitytype";
				$form_button = "Save Changes";
				//$content_array['content'] .= "<pre>" . print_r($values, true) . "</pre>";
			}
			if($action == "quantity_type_add") {
				$content_array['page_title'] = "Add Quantity Type: " . $camps[$camp]['name'];
				$form_action = "data_add_quantitytype";
				$form_button = "Add Quantity Type";
			}
			$content_array['content'] .= <<<EOT
				<form method="post" action="{$url}/settings/{$form_action}" name="edit_quantitytype" class="form-horizontal form-small-label" id="form-qts">
EOT;
			$content_array['content'] .= csrf_field();
			$content_array['content'] .= <<<EOT

					<div class="row">
						<div class="small-12 medium-3 large-3 columns">
							<label for="type">Quantity Type Name</label>
						</div>
						<div class="small-12 medium-6 large-6 end columns">
							<input type="text" name="type" value="{$values['type']}" id="type" required />
						</div>
						<div class="small-12 medium-3 large-3 columns">
							(required)
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-2 large-2 columns">
							<label>Terms</label>
						</div>
						<div class="small-12 medium-10 large-10 columns switch small round">
							<span class="help-block">Indicate if you want to enable this quantity type for the following years.</span>
							<ul class="small-block-grid-2">
EOT;
			$i=0;
			foreach($terms as $term => $term_value) {
				if($i == 3) { $i = 0; }
				$term_true = ""; if($values['qt_options']['terms'][$term] == "true") { $term_true = " checked"; }
				$term_false = ""; if($values['qt_options']['terms'][$term] == "false") { $term_false = " checked"; }
				$content_array['content'] .= <<<EOT
								<li>
									<input type="radio" name="qt_options[terms][{$term}]" value="true"{$term_true} id="term_{$term}_true" />
									<label for="term_{$term}_true" class="radio-true">Yes</label>
									<input type="radio" name="qt_options[terms][{$term}]" value="false"{$term_false} id="term_{$term}_false" />
									<label for="term_{$term}_false" class="radio-false">No</label>
									&nbsp;{$term}
								</li>
EOT;
				$i++;
			}
			$content_array['content'] .= <<<EOT
							</ul>
						</div>
					</div>
EOT;
			$uid = Session::get('uid');
			if($uid != 1) {
				$formula_lock = " disabled";
			}
			$content_array['content'] .= <<<EOT
					<div class="row">
						<div class="small-12 medium-12 large-12 columns">
							<label>Formula Examples (Set by Programmer)</label>
						</div>
						<div class="small-1 medium-1 large-1 columns">
							<label>(C)</label>
						</div>
						<div class="small-11 medium-11 large-11 columns">
							<input type="text" name="qt_options[formulas][C]" value="{$values['qt_options']['formulas']['C']}" id="formula_c"{$formula_lock} />
						</div>
						<div class="small-1 medium-1 large-1 columns">
							<label>(NC)</label>
						</div>
						<div class="small-11 medium-11 large-11 columns">
							<input type="text" name="qt_options[formulas][NC]" value="{$values['qt_options']['formulas']['NC']}" id="formula_nc"{$formula_lock} />
						</div>
						<div class="small-1 medium-1 large-1 columns">
							<label>(NCx3)</label>
						</div>
						<div class="small-11 medium-11 large-11 columns">
							<input type="text" name="qt_options[formulas][NCx3]" value="{$values['qt_options']['formulas']['NCx3']}" id="formula_nc3"{$formula_lock} />
						</div>
					</div>
EOT;
			if($uid != 1) {
				$content_array['content'] .= <<<EOT
					<input type="hidden" name="qt_options[formulas][C]" value="{$values['qt_options']['formulas']['C']}" />
					<input type="hidden" name="qt_options[formulas][NC]" value="{$values['qt_options']['formulas']['NC']}" />
					<input type="hidden" name="qt_options[formulas][NCx3]" value="{$values['qt_options']['formulas']['NCx3']}" />
EOT;
			}
			$content_array['content'] .= <<<EOT
					<div class="row">
						<div class="small-12 medium-6 large-6 columns">
							<label for="mlcalc" class="">Material List Calc Divider</label>
							<span class="help-block">Used in Material Request Calculations. Not in Packing.</span>
						</div>
						<div class="small-12 medium-2 large-2 columns">
							<label>Both</label>
							<input type="text" name="qt_options[mlcalc][mlcalc]" value="{$values['qt_options']['mlcalc']['mlcalc']}" id="mlcalc" />
						</div>
						<div class="small-12 medium-2 large-2 columns">
							<label>(C)</label>
							<input type="text" name="qt_options[mlcalc][mlcalcc]" value="{$values['qt_options']['mlcalc']['mlcalcc']}" id="mlcalcc" />
						</div>
						<div class="small-12 medium-2 large-2 columns">
							<label>(NC)</label>
							<input type="text" name="qt_options[mlcalc][mlcalcnc]" value="{$values['qt_options']['mlcalc']['mlcalcnc']}" id="mlcalcnc" />
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-6 large-6 columns">
							<label for="calc_divider" class="">Packing Calculation Divider</label>
							<input type="text" name="qt_options[calc_divider]" value="{$values['qt_options']['calc_divider']}" id="calc_divider" />
							<span class="help-block">Used in Packing Calculations.</span>
						</div>

						<div class="small-12 medium-6 large-6 columns">
							<label for="value" class="">Person Multiplier (required)</label>
							<input type="text" name="value" value="{$values['value']}" id="value" required />
							<span class="help-block">Example: if it is per (2) two people the multiplier should be 0.5.</span>
						</div>
					</div>
EOT;
			if($values['qt_options']['cnc'] == "C") { $cnc_c = " selected"; }
			if($values['qt_options']['cnc'] == "NC") { $cnc_nc = " selected"; }
			if($values['qt_options']['cnc'] == "both") { $cnc_both = " selected"; }

			if($values['qt_options']['location_size_option'] == "less") { $location_size_option_less = " selected"; }
			if($values['qt_options']['location_size_option'] == "greater") { $location_size_option_greater = " selected"; }

			if($values['qt_options']['dropdown'] == "true") { $dropdown_true = " checked"; }
			if($values['qt_options']['dropdown'] == "false") { $dropdown_false = " checked"; }
			$content_array['content'] .= <<<EOT
					<div class="row">
						<div class="small-12 medium-4 large-4 columns">
							<label for="cnc" class="">Consumable/Non-Consumable</label>
						</div>

						<div class="small-12 medium-8 large-8 columns">
							<select name="qt_options[cnc]" id="cnc">
								<option value="C"{$cnc_c}>Consumable</option>
								<option value="NC"{$cnc_nc}>Non-Consumable</option>
								<option value="both"{$cnc_both}>Both</option>
							</select>
							<span class="help-block">You can select Consumable, Non-Consumable or Both. Will calculate quantity to order based on choice. If C or NC will override choice of curriculum writer.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-6 large-6 columns">
							<label>Material Lists</label>
							<span class="help-block">Select dropdown to include in a dropdown for material requests, or static to have it as a stand alone input.</span>
						</div>
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="qt_options[dropdown]" value="true"{$dropdown_true} id="dropdown_true" />
							<label for="dropdown_true">Dropdown</label>
							<span class="help-block">Dropdown</span>
						</div>
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="qt_options[dropdown]" value="false"{$dropdown_false} id="dropdown_false" />
							<label for="dropdown_false">Static</label>
							<span class="help-block">Static</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-12 large-12 end columns">
							<label for="location_size" class="">Location Size</label>
						</div>
						<div class="small-12 medium-4 large-4 columns">
							<label>Greater or Lesser Than</label>
							<select name="qt_options[location_size_option]" id="location_size">
								<option value="">--------------------------</option>
								<option value="less"{$location_size_option_less}>Less Than, Equal Too</option>
								<option value="greater"{$location_size_option_greater}>Greater Than</option>
							</select>
						</div>
						<div class="small-12 medium-2 large-2 columns">
							<label>Camp Size</label>
							<input type="text" name="qt_options[location_size]" value="{$values['qt_options']['location_size']}" />
						</div>
						<div class="small-12 medium-6 large-6 columns">
							<label>Select Enrollment Value to Multiply</label>
							<select name="qt_options[location_size_compare]">
								<option value="">------------------</option>
EOT;
			foreach($enrollment_data[$camp] as $type => $info) {
				$location_size_compare = ""; if($values['qt_options']['location_size_compare'] == $type) { $location_size_compare = " selected"; }
				$content_array['content'] .= <<<EOT
										<option value="{$type}"{$location_size_compare}>{$info['name']}</option>
EOT;
			}
			if($values['qt_options']['kqd'] == 1) { $kqd = " checked"; }
			$content_array['content'] .= <<<EOT
							</select>
						</div>
					</div>
EOT;
			if($values['qt_options']['dli'] == "dli") { $dli_dli = " checked"; }
			if($values['qt_options']['dli'] == "ndli") { $dli_ndli = " checked"; }
			if($values['qt_options']['dli'] == "false" || $values['qt_options']['dli'] == "") { $dli_false = " checked"; }
			$content_array['content'] .= <<<EOT

					<div class="row">
						<div class="small-12 medium-5 large-5 columns">
							<label>DLI Locations</label>
							<span class="help-block">Select yes if the quantity type is for a non-DLI or DLI location.</span>
						</div>
						<div class="small-12 medium-2 large-2 columns switch small round">
							<input type="radio" name="qt_options[dli]" value="false"{$dli_false} id="dli_false" />
							<label for="dli_false">All</label>
							<span class="help-block">All</span>
						</div>
						<div class="small-12 medium-2 large-2 columns switch small round">
							<input type="radio" name="qt_options[dli]" value="dli"{$dli_dli} id="dli_dli" />
							<label for="dli_dli">DLI</label>
							<span class="help-block">DLI</span>
						</div>
						<div class="small-12 medium-3 large-3 columns switch small round">
							<input type="radio" name="qt_options[dli]" value="ndli"{$dli_ndli} id="dli_ndli" />
							<label for="dli_ndli">NDLI</label>
							<span class="help-block">Non-DLI</span>
						</div>
					</div>


					<div class="row">
						<div class="small-12 medium-12 large-12 end columns">
							<label>Multipliers</label>
							<span class="help-block">Select yes if the quantity type is for a non-DLI or DLI location.</span>
						</div>

						<div class="small-12 medium-5 large-5 columns">
							<select name="qt_options[ccalculation]" id="ccalculation">
								<option value="">------------------</option>
EOT;
			foreach($enrollment_data[$camp] as $type => $info) {
				$ccalculation = ""; if($values['qt_options']['ccalculation'] == $type) { $ccalculation = " selected"; }
				$content_array['content'] .= <<<EOT
								<option value="{$type}"{$ccalculation}>{$info['name']}</option>
EOT;
			}
			$content_array['content'] .= <<<EOT
							</select>
						</div>
						<div class="small-12 medium-1 large-1 columns">
							<label for="ccalculation">(C)</label>
						</div>

						<div class="small-12 medium-5 large-5 columns">
							<select name="qt_options[nccalculation]" id="nccalculation">
								<option value="">------------------</option>
EOT;
			 foreach($enrollment_data[$camp] as $type => $info) {
				$nccalculation = ""; if($values['qt_options']['nccalculation'] == $type) { $nccalculation = " selected"; }
				$content_array['content'] .= <<<EOT
										<option value="{$type}"{$nccalculation}>{$info['name']}</option>
EOT;
			}
			if($values['qt_options']['crotations'] == 1) { $crotations = " checked"; }
			if($values['qt_options']['cthemeweeks'] == 1) { $cthemeweeks = " checked"; }
			$content_array['content'] .= <<<EOT
							</select>
							<span class="help-block">Multipliers should be set by developer.</span>
						</div>
						<div class="small-12 medium-1 large-1 columns">
							<label for="ccalculation">(NC)</label>
						</div>
					</div>
					<div class="row">
						<div class="small-12 medium-1 large-1 columns switch small round">
							<input type="checkbox" name="qt_options[kqd]" id="kqd" value="1"{$kqd} />
							<label for="kqd">KQD</label>
						</div>
						<div class="small-12 medium-11 large-11 columns">
						<label>KQD</label>
							<span class="help-block">Check box if quantity value is used in calculating Quantity Per Person.</span>
						</div>
 						<script type="text/javascript">
 							$(document).ready(function(){
 								if($('#kqd').prop('checked')){
							        $('.kqd_multiplier').show();
								} else {
 									$('.kqd_multiplier').hide();
								}
								$('#form-qts').change(function() {
									$('.kqd_multiplier').hide();
									if($('#kqd').prop('checked')){
								        $('.kqd_multiplier').show();
 								    }
 								});
 							});
						</script>
					</div>

					<div class="row kqd_multiplier">
						<div class="small-12 medium-4 large-4 columns">
							<select name="qt_options[kqdccalculation]" id="kqdccalculation">
								<option value="">------------------</option>
EOT;
			foreach($enrollment_data[$camp] as $type => $info) {
				$kqdccalculation = ""; if($values['qt_options']['kqdccalculation'] == $type) { $kqdccalculation = " selected"; }
				$content_array['content'] .= <<<EOT
								<option value="{$type}"{$kqdccalculation}>{$info['name']}</option>
EOT;
			}
			$content_array['content'] .= <<<EOT
							</select>
						</div>
						<div class="small-12 medium-2 large-2 columns">
							<label for="kqdccalculation">KQD (C)</label>
						</div>

						<div class="small-12 medium-4 large-4 columns">
							<select name="qt_options[kqdnccalculation]" id="kqdnccalculation">
								<option value="">------------------</option>
EOT;
			foreach($enrollment_data[$camp] as $type => $info) {
				$kqdnccalculation = ""; if($values['qt_options']['kqdnccalculation'] == $type) { $kqdnccalculation = " selected"; }
				$content_array['content'] .= <<<EOT
								<option value="{$type}"{$kqdnccalculation}>{$info['name']}</option>
EOT;
			}
			$content_array['content'] .= <<<EOT
							</select>
						</div>
						<div class="small-12 medium-2 large-2 columns">
							<label for="kqdnccalculation">KQD (NC)</label>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-1 large-1 columns switch small round">
							<input type="checkbox" name="qt_options[crotations]" id="crotations" value="1"{$crotations} />
							<label for="crotations">Consumable Rotation Multiplier</label>
						</div>
						<div class="small-12 medium-11 large-11 columns">
							<label for="crotations">Consumable Rotation Multiplier</label>
							<span class="help-block">Check box if you want to multiply by the number of rotations in enrollment. Must draw data from an enrollment table with rotations.</span>
						</div>
					</div>

					<div class="row">
						<div class="small-12 medium-1 large-1 columns switch small round">
							<input type="checkbox" name="qt_options[cthemeweeks]" id="cthemeweeks" value="1"{$cthemeweeks} />
							<label for="cthemeweeks">Consumable Theme Weeks Multiplier</label>
						</div>
						<div class="small-12 medium-11 large-11 columns">
							<label for="cthemeweeks">Consumable Theme Weeks Multiplier</label>
							<span class="help-block">Check box if you want to multiply by the number of theme weeks in enrollment. Must draw data from an enrollment table with theme weeks.</span>
						</div>
					</div>
EOT;
			if(!empty($quantity_types[$camp]['data_inputs'])) {
				$content_array['content'] .= <<<EOT
					<div class="row">
						<div class="small-12 medium-6 large-6 columns">
							<select name="qt_options[data_input]" id="data_input">
								<option value="">------------------</option>
EOT;
				foreach($quantity_types[$camp]['data_inputs'] as $id => $data_values) {
					if($data_values['enabled'] == "true") {
						$data_input = ""; if($values['qt_options']['data_input'] == $id) { $data_input = " selected"; }
						$content_array['content'] .= <<<EOT
								<option value="{$id}"{$data_input}>{$data_values['name']}</option>
EOT;
					}
				}
				$content_array['content'] .= <<<EOT
							</select>
						</div>
						<div class="small-12 medium-6 large-6 columns">
							<label for="data_input">Data Input Multiplier</label>
							<span class="help-block">Multipliers should be set by developer.</span>
						</div>
					</div>
EOT;
				$content_array['content'] .= <<<EOT
					<div class="row">
						<div class="small-12 medium-6 large-6 columns">
							<select name="qt_options[data_input_c]" id="data_input_c">
								<option value="">------------------</option>
EOT;
				foreach($quantity_types[$camp]['data_inputs'] as $id => $data_values) {
					if($data_values['enabled'] == "true") {
						$data_input_c = ""; if($values['qt_options']['data_input_c'] == $id) { $data_input_c = " selected"; }
						$content_array['content'] .= <<<EOT
								<option value="{$id}"{$data_input_c}>{$data_values['name']}</option>
EOT;
					}
				}

				$content_array['content'] .= <<<EOT
							</select>
						</div>
						<div class="small-12 medium-6 large-6 columns">
							<label for="data_input_c">Consumable Data Input Multiplier</label>
							<span class="help-block">Multiplier only used if the item is consumable.</span>
						</div>
					</div>
EOT;
				$content_array['content'] .= <<<EOT
					<div class="row">
						<div class="small-12 medium-6 large-6 columns">
							<select name="qt_options[data_input_nc]" id="data_input_nc">
								<option value="">------------------</option>
EOT;
				foreach($quantity_types[$camp]['data_inputs'] as $id => $data_values) {
					if($data_values['enabled'] == "true") {
						$data_input_nc = ""; if($values['qt_options']['data_input_nc'] == $id) { $data_input_nc = " selected"; }
						$content_array['content'] .= <<<EOT
								<option value="{$id}"{$data_input_nc}>{$data_values['name']}</option>
EOT;
					}
				}
				$content_array['content'] .= <<<EOT
							</select>
						</div>
						<div class="small-12 medium-6 large-6 columns">
							<label for="data_input_nc">Non-Consumable Data Input Multiplier</label>
							<span class="help-block">Multiplier only used if the item is non-consumable.</span>
						</div>
					</div>
EOT;
			}
			$content_array['content'] .= <<<EOT

					<p><button type="submit" class="button small">{$form_button}</button></p>
					<input type="hidden" name="action" value="{$form_action}" />
					<input type="hidden" name="camp" value="{$camp}" />
					<input type="hidden" name="id" value="{$array['id']}" />
				</form>
EOT;
			return $content_array;
// 			echo "<pre>"; print_r($values); echo "</pre>";
// 			gambaDebug::preformatted_arrays($quantity_types[$camp], "quantity_types$camp", "Quantity Types", $_REQUEST['debug_override']);
// 			gambaDebug::preformatted_arrays($enrollment_data[$camp], "enrollment_data$camp", "Enrollment Data");
// 			gambaDebug::preformatted_arrays($quantity_types_used, 'quantity_types_used', 'Quantity Types Used');
		}

	}
