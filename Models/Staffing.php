<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Staffing extends Model {
		
		protected $table = 'staffing';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'location', 'camp', 'grade_id', 'theme_id', 'staff_id', 'num_staff', 'term'];
		
		public $timestamps = false;
		
	}
