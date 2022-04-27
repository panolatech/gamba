<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class VendorAddresses extends Model {
		
		protected $table = 'vendoraddresses';
		
		protected $primaryKey = 'name';
		
		protected $fillable = ['name', 'attn', 'street', 'city', 'zip', 'state', 'country'];
		
		public $timestamps = false;
		
	}
