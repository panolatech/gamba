<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class SalesOrders extends Model {
		
		protected $table = 'salesorders';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'customer', 'fulfillment_date', 'fishbowl', 'list', 'term', 'camp', 'theme', 'grade', 'location', 'dli', 'date_created', 'xmlstring'];
		
		public $timestamps = false;
		
	}
