<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Vendors extends Model {
		
		protected $table = 'vendors';
		
		protected $primaryKey = 'VendorID';
		
		protected $fillable = ['VendorID', 'AccountID', 'Status', 'DefPaymentTerms', 'DefShipTerms', 'Name', 'Number', 'DateCreated', 'DateModified', 'LastChangedUser', 'CreditLimit', 'Note', 'ActiveFlag', 'AccountingID', 'AccountingHash'];
		
		public $timestamps = false;
		
	}
