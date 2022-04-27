<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class ReorderItems extends Model {
		
		protected $table = 'reorderitems';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'request_id', 'term', 'reorder_id', 'supply_id', 'camp', 'qty', 'created', 'updated', 'user', 'status', 'notes', 'warh_notes', 'ship_date', 'need_by', 'order_status', 'need_status', 'request_reason'];
		
		public $timestamps = false;
		
	}
