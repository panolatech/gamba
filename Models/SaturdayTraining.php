<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class SaturdayTraining extends Model {
		
		protected $table = 'saturdaytraining';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'term', 'field', 'activity_id', 'location_id', 'training_value'];
		
		public $timestamps = false;
		
	}
