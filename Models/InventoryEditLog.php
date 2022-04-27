<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class InventoryEditLog extends Model {
		
		protected $table = 'inventory_edit_log';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'user', 'material_id', 'part_num', 'part_desc', 'uom', 'cost', 'editdate', 'action'];
		
		public $timestamps = false;
		
	}
