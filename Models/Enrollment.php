<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Enrollment extends Model {
		
		protected $table = 'enrollment';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'sheet_id', 'term', 'grade_id', 'camp', 'theme_id', 'location_id', 'extra_class', 'dli', 'location_values', 'theme_values'];
		
		public $timestamps = false;
		
	}
