<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Groups extends Model {
		
		protected $table = 'groups';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'name', 'group_values'];
		
		public $timestamps = false;
		
	}
