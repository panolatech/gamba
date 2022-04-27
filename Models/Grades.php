<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Grades extends Model {
		
		protected $table = 'grades';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'level', 'camp_type', 'enrollment', 'altname', 'grade_options'];
		
		public $timestamps = false;
		
	}
