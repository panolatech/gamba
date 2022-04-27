<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class VendorParts extends Model {
		
		protected $table = 'vendorparts';
		
		protected $primaryKey = 'partNumber';
		
		protected $fillable = ['partNumber', 'vendor', 'vendorPartNumber', 'defaultVendorFlag'];
		
		public $timestamps = false;
		
	}
