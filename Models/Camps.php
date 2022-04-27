<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Camps extends Model {
		
		protected $table = 'camps';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'abbr', 'name', 'alt_name', 'camp_values', 'data_inputs', 'dummy'];
		
		public $timestamps = false;
		
	}