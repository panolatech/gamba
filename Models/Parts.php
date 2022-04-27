<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class Parts extends Model {

		protected $table = 'parts';

		protected $primaryKey = 'number';

		public $incrementing = 'false';

		protected $fillable = ['number', 'description', 'suom', 'cost', 'pq', 'url', 'purl', 'approved', 'inventory', 'cwnotes', 'adminnotes', 'fishbowl', 'vendor', 'created', 'updated', 'old_id', 'fbuom', 'fbcost', 'conversion', 'xmlstring', 'part_options', 'concept', 'change_log'];

		public $timestamps = false;

	}
