<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Directions extends Model {
		
		protected $table = 'directions';
		
		protected $primaryKey = 'field';
		
		protected $fillable = ['field', 'directions', 'updated', 'option_values'];
		
		public $timestamps = false;
		
	}
