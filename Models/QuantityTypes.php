<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class QuantityTypes extends Model {
		
		protected $table = 'quantitytypes';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'type', 'value', 'camp_type', 'ordering', 'qt_options', 'cost_options'];
		
		public $timestamps = false;
		
	}
