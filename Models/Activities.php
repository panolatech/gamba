<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Activities extends Model {
		
		protected $table = 'activities';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'activity_name', 'description', 'grade_id', 'theme_id', 'term', 'theme_type', 'camp'];
		
		public $timestamps = false;
		
	}
