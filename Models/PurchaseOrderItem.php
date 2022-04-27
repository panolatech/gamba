<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class PurchaseOrderItem extends Model {
		
		protected $table = 'purchaseorderitem';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'poid', 'number', 'qty', 'notes', 'term'];
		
		public $timestamps = false;
		
	}
