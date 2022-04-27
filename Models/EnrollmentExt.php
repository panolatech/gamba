<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class EnrollmentExt extends Model {
		
		protected $table = 'enrollmentext';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'location_id', 'camp', 'term', 'location_values'];
		
		public $timestamps = false;
		
	}
