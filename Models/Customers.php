<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Customers extends Model {
		
		protected $table = 'customers';
		
		protected $primaryKey = 'CustomerID';
		
		protected $fillable = ['CustomerID', 'AccountID', 'Status', 'DefPaymentTerms', 'DefShipTerms', 'TaxRate', 'Name', 'Number', 'DateCreated', 'DateLastModified', 'LastChangedUser', 'CreditLimit', 'TaxExemptNumber', 'Note', 'ActiveFlag', 'AccountingID', 'DefaultSalesman', 'JobDepth', 'date_added'];
		
		public $timestamps = false;
		
	}
