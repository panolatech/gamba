<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class StaffTotals extends Model {
		
		protected $table = 'stafftotals';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'location_id', 'term', 'field', 'activity_id', 'staff_id', 'theme_id', 'total_value'];
		
		public $timestamps = false;
		
	}
