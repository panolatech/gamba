<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model as Eloquent;

	class Products extends Eloquent {
		
		protected $table = 'products';
		
		protected $primaryKey = 'Num';
		
		protected $fillable = ['Num', 'Description', 'Price', 'UOM', 'PartID', 'updated'];
		
		public $timestamps = false;
		
	}
