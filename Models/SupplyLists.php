<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class SupplyLists extends Model {
		
		protected $table = 'supplylists';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'activity_id', 'term', 'camp_type', 'cg_staff', 'user_id', 'user_name', 'created', 'data_inputs', 'budget', 'locked'];
		
		public $timestamps = false;
		
	}
