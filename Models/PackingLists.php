<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class PackingLists extends Model {
		
		protected $table = 'packinglists';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'list', 'camp', 'alt', 'list_values', 'hide'];
		
		public $timestamps = false;
		
	}
