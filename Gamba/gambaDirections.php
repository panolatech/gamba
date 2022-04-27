<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Models\Directions;

	use App\Gamba\gambaUsers;

	class gambaDirections {
		/**
		 * Access the content of the 'directions' table
		 */
		private function directions() {
			$directions = Directions::select('field', 'directions', 'updated', 'option_values');
			$directions = $directions->orderBy('field');
			$directions = $directions->get();
			if($directions->count() > 0) {
				foreach($directions as $key => $row) {
					$field = $row['field'];
					$array["$field"]['directions'] = $row['directions'];
					$array["$field"]['updated'] = $row['updated'];
					$array["$field"]['option_values'] = json_decode($row->option_values, true);
				}
			}
			return $array;
		}

		public static function allDirections() {

		}

		/**
		 * Access or create the content of the 'directions' table
		 *
		 * $field needs to be unique.
		 * $display - both, direction, modal
		 */
		public static function getDirections($field, $display = "both") {
			$user_group = Session::get('group');
			$url = url('/');
			$result = self::missingDirection($field);
			$directions = Directions::select('field', 'directions', 'updated', 'option_values');
			$directions = $directions->where('field', '=', "$field");
			$directions = $directions->orderBy('field');
			$row = $directions->first();
			$option_values = json_decode($row->option_values, true);
			if($display == "both" || $display == "direction") {
				$content .= '<div class="panel radius directions">';
				if($user_group <= 1) { $content .= '<a data-reveal-id="directions-'.$field.'" href="#" class="close">edit</a>'; }
				$content .= '<strong>'.$option_values['type'].':</strong> '.$row['directions'];
				$content .= '</div>';
			}
			if($display == "both" || $display == "modal") {
				$select_direction = ""; if($option_values['type'] == "Directions") { $select_direction .= " selected"; }
				$select_note = ""; if($option_values['type'] == "Notes") { $select_note .= " selected"; }
				$gamba_dir = config('gamba.gamba_dir');
				$redirect_url = str_replace($gamba_dir, "", $_SERVER['REQUEST_URI']);
				$content .= <<<EOT
<div id="directions-{$field}" class="reveal-modal" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">

	<form name="edit_direction" class="form" action="{$url}/settings/update_direction">
EOT;
				$content .= csrf_field();
				$content .= <<<EOT
		<h2 class="modalTitle">Edit Directions ({$field})</h2>
		<div class="row">
			<label for="directions">Directions</label>
			<textarea class="form-control" name="directions" id="directions">{$row['directions']}</textarea>
		</div>

		<div class="row">
			<label for="type">Type</label>
			<select name="type" id="type">
				<option value="Directions"{$select_direction}>Directions</option>
				<option value="Notes"{$select_note}>Notes</option>
			</select>
		</div>

		<p><button type="submit" class="button small radius">Save changes</button></p>

		<input type="hidden" name="action" value="update_direction" />
		<input type="hidden" name="field" value="{$field}" />
		<input type="hidden" name="url" value="{$redirect_url}" />
	</form>

	<a class="close-reveal-modal" aria-label="Close">&#215;</a>
</div><!-- /.modal -->
EOT;
			}
			return $content;
		}


		public static function missingDirection($field) {
// 			$direction = Directions::select('field')->where('field', $field)->first();
// 			if($direction['field'] == "") {
// 				$directions = "Someone needs to add directions here.";
// 				$option_values['type'] = "Directions";

// 				$add = new Directions;
// 				$add->field = $field;
// 				$add->directions = $directions;
// 				$add->option_values = json_encode($option_values);
// 				$add->updated = date("Y-m-d H:i:s");
// 				$add->save();
// 			}
		}

		public static function direction_update($array) {
// 			echo "<pre>"; print_r($array); echo "</pre>"; exit(); die();
			$field = $array['field'];
			$directions = htmlspecialchars($array['directions']);
			$option_values['type'] = $array['type'];
			$option_values_json = json_encode($option_values);

			$update = Directions::where('field', $field)->update([
				'directions' => $directions,
				'option_values' => $option_values_json
			]);
		}
	}
