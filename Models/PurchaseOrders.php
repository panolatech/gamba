<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class PurchaseOrders extends Model {
		
		protected $table = 'purchaseorders';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'number', 'vendorid', 'notes', 'fulfillmentdate', 'datecreated', 'locationgroup', 'term', 'xmlstring', 'fishbowl_response'];
		
		public $timestamps = false;
		
	}
