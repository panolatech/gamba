<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class StaffAssumptions extends Model {
		
		protected $table = 'staffassumptions';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'camp', 'term', 'field', 'theme_id', 'staff_id', 'rotation', 'assumption_values'];
		
		public $timestamps = false;
		
	}
