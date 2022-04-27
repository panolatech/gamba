<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class CustomerAddresses extends Model {
		
		protected $table = 'customeraddresses';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'name', 'attn', 'street', 'city', 'zip', 'state', 'country', 'date_added'];
		
		public $timestamps = false;
		
	}
