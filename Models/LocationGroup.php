<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class LocationGroup extends Model {
		
		protected $table = 'locationgroup';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'LocationGroup'];
		
		public $timestamps = false;
		
	}
