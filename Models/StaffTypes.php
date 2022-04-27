<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class StaffTypes extends Model {
		
		protected $table = 'stafftypes';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'name', 'camp_type', 'grade', 'ordering'];
		
		public $timestamps = false;
		
	}
