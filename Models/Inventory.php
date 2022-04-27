<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class Inventory extends Model {

		protected $table = 'inventory';

		protected $primaryKey = 'number';

		public $incrementing = 'false';

		protected $fillable = ['number', 'fb_partid', 'fb_vendorid', 'fb_active', 'availablesale', 'quantityonhand', 'onorder', 'onorderuom', 'quantityshipped', 'quantityshippeduom', 'quantityshort', 'updated', 'quantityshort_updated'];

		public $timestamps = false;

	}
