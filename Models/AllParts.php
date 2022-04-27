<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model as Eloquent;

	class AllParts extends Eloquent {

		protected $table = 'parts';

		protected $fillable = ['number', 'description', 'suom', 'cost', 'pq', 'url', 'purl', 'approved', 'inventory', 'cwnotes', 'adminnotes', 'fishbowl', 'vendor', 'created', 'updated', 'old_id', 'fbuom', 'fbcost', 'conversion', 'xmlstring', 'part_options', 'content'];

		public $timestamps = false;

	}
