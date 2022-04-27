<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class Supplies extends Model {

		protected $table = 'supplies';

		protected $primaryKey = 'id';

		protected $fillable = ['id', 'packing_id', 'supplylist_id', 'theme_id', 'grade_id', 'activity_id', 'camp_id', 'term', 'part', 'itemtype', 'notes', 'request_quantities', 'location_quantities', 'packing_quantities', 'packing_recalc_quantities', 'packing_total', 'packing_subtracted', 'total_amount', 'nonstandard', 'lowest', 'cost', 'exclude', 'costing_summary', 'concept', 'part_class'];

		public $timestamps = false;

	}
